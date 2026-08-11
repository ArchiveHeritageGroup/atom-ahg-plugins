<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Supporting evidence for a Collections Procedure step.
 *
 * A procedure could record that it happened and never what it was based on. The
 * valuer's report, the signed approval, the courier's condition note at handover
 * - none of it had anywhere to live. `spectrum_loan_document` was declared for
 * loans years ago and no code was ever written against it.
 *
 * Evidence is keyed the way the checklist is keyed - (procedure_type, record_id,
 * step_key) - so it needs no per-procedure code and works for all 21 flows.
 *
 * ON STORAGE
 *
 * Only the generated filename is stored. The directory is recomputed from the
 * procedure and record every time it is needed. There is no path in the
 * database, so there is no stored path to traverse, and none of the three
 * incompatible `file_path` conventions already in this codebase (relative to the
 * upload dir, `/uploads/...` web path, absolute) can leak in. Taken from
 * ahgSAHRAPlugin, the only attachment implementation in the suite with a
 * complete access-controlled lifecycle.
 *
 * Files are NOT reachable over the web. They live under a directory the web
 * server does not map to a route, and are streamed by an action that re-checks
 * authorisation. Every other attachment in the suite is served as a guessable
 * /uploads/... URL with no access control at all.
 */
class SpectrumEvidenceService
{
    /** Directory name under the data dir. */
    public const STORAGE_ROOT = 'spectrum_evidence';

    public const TYPES = [
        'document' => 'Document',
        'report' => 'Report',
        'certificate' => 'Certificate',
        'photograph' => 'Photograph',
        'correspondence' => 'Correspondence',
        'invoice' => 'Invoice',
        'receipt' => 'Receipt',
        'other' => 'Other',
    ];

    /**
     * Everything attached to one procedure on one record, newest first.
     *
     * @return array<int, object>
     */
    public static function forRecord(string $procedureType, int $recordId): array
    {
        return DB::table('spectrum_evidence')
            ->where('procedure_type', $procedureType)
            ->where('record_id', $recordId)
            ->orderByDesc('created_at')
            ->get()
            ->all();
    }

    /**
     * Grouped by step key, for rendering beside the checklist.
     *
     * Evidence with no step key is collected under '' - it belongs to the
     * procedure rather than to a stage of it.
     *
     * @return array<string, array<int, object>>
     */
    public static function groupedByStep(string $procedureType, int $recordId): array
    {
        $out = [];

        foreach (self::forRecord($procedureType, $recordId) as $row) {
            $out[(string) ($row->step_key ?? '')][] = $row;
        }

        return $out;
    }

    public static function get(int $id): ?object
    {
        return DB::table('spectrum_evidence')->where('id', $id)->first();
    }

    public static function countFor(string $procedureType, int $recordId, ?string $stepKey = null): int
    {
        $q = DB::table('spectrum_evidence')
            ->where('procedure_type', $procedureType)
            ->where('record_id', $recordId);

        if (null !== $stepKey) {
            $q->where('step_key', $stepKey);
        }

        return (int) $q->count();
    }

    /**
     * Root of the evidence store. OUTSIDE the document root.
     *
     * This took two attempts and both failures are worth recording, because the
     * obvious answers are wrong:
     *
     *   sf_upload_dir  nginx maps it to /uploads/ - a file written there came
     *                  back HTTP 200 to an anonymous request. This is #258.
     *   sf_data_dir    also inside the AtoM directory, which nginx roots, so
     *                  /data/<file> returned the PDF itself, 69 bytes,
     *                  content-type application/pdf.
     *
     * Anything under the application directory is reachable unless a location
     * block says otherwise, and per-site nginx config is exactly what went
     * missing in #258. So the default sits beside the application rather than
     * inside it, where no vhost roots.
     *
     * Override with the `spectrum_evidence_path` setting if the deployment wants
     * it elsewhere - a mount, a share, an encrypted volume.
     */
    public static function storageRoot(): string
    {
        $configured = '';

        if (class_exists('AhgSettingsService')) {
            $configured = (string) AhgSettingsService::get('spectrum_evidence_path', '');
        }

        if ('' !== trim($configured)) {
            return rtrim(trim($configured), '/');
        }

        $appRoot = class_exists('sfConfig') ? (string) sfConfig::get('sf_root_dir') : '';

        if ('' === $appRoot) {
            return sys_get_temp_dir().'/'.self::STORAGE_ROOT;
        }

        // /var/lib, not a sibling of the application.
        //
        // php-fpm ships with ProtectSystem=full, which mounts /usr read-only for
        // the worker - so a store beside the app under /usr/share/nginx cannot be
        // written to, and mkdir fails with "Could not create the evidence
        // directory" no matter what the ownership says. /var is left writable by
        // that unit setting, so no systemd drop-in is needed.
        //
        // Named after the instance so two AtoMs on one host do not share a store.
        return '/var/lib/ahg-evidence/'.basename(rtrim($appRoot, '/'));
    }

    /**
     * Where a procedure's evidence lives. Derived, never stored.
     */
    public static function storageDir(string $procedureType, int $recordId): string
    {
        // Both components are constrained by the caller (a configured procedure
        // type and an integer id), but basename() them anyway - a directory
        // built from request data is not a place to rely on the caller.
        return self::storageRoot()
            .'/'.basename($procedureType)
            .'/'.(int) $recordId;
    }

    /**
     * Absolute path of one evidence file, rebuilt from its row.
     */
    public static function pathFor(object $evidence): string
    {
        return self::storageDir((string) $evidence->procedure_type, (int) $evidence->record_id)
            .'/'.basename((string) $evidence->stored_name);
    }

    /**
     * Store one uploaded file against a step.
     *
     * Sequence is AccessionIntakeService::addAttachment()'s, which is the
     * tightest of the four in the suite: validate before the move, move, then
     * validate the magic bytes of what actually landed and delete it if it lies.
     * A file that survives all of that but whose row fails to insert is removed
     * too, so a failed upload leaves nothing behind.
     *
     * @param array $file one entry from $_FILES
     *
     * @throws RuntimeException with a message safe to show the uploader
     */
    public static function store(
        string $procedureType,
        int $recordId,
        ?string $stepKey,
        array $file,
        array $meta = [],
        ?int $userId = null
    ): int {
        $validation = \AtomExtensions\Services\FileValidationService::validateUpload($file);

        if (empty($validation['valid'])) {
            throw new RuntimeException('Rejected: '.implode(' ', $validation['errors'] ?? ['invalid upload']));
        }

        $clean = \AtomExtensions\Services\FileValidationService::sanitizeFilename((string) $file['name']);
        $ext = strtolower((string) pathinfo($clean, PATHINFO_EXTENSION));

        // Random rather than uniqid(): uniqid is the clock and is guessable, and
        // these files are not meant to be reachable by guessing.
        $stored = bin2hex(random_bytes(16)).('' !== $ext ? '.'.$ext : '');

        $dir = self::storageDir($procedureType, $recordId);

        if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create the evidence directory.');
        }

        $target = $dir.'/'.$stored;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Could not store the uploaded file.');
        }

        @chmod($target, 0640);

        $mime = \AtomExtensions\Services\FileValidationService::validateMime($target, $file['type'] ?? null);

        if (empty($mime['valid'])) {
            @unlink($target);

            throw new RuntimeException('Rejected: the file contents do not match its name.');
        }

        try {
            return (int) DB::table('spectrum_evidence')->insertGetId([
                'procedure_type' => $procedureType,
                'record_id' => $recordId,
                'step_key' => ('' === (string) $stepKey) ? null : $stepKey,
                'evidence_type' => isset(self::TYPES[$meta['evidence_type'] ?? '']) ? $meta['evidence_type'] : 'document',
                'original_name' => mb_substr((string) $file['name'], 0, 255),
                'stored_name' => $stored,
                // detected_mime, not the browser's claim - validateMime returns
                // what finfo actually read from the bytes.
                'mime_type' => $mime['detected_mime'] ?? ($file['type'] ?? null),
                'size_bytes' => isset($file['size']) ? (int) $file['size'] : null,
                'caption' => isset($meta['caption']) ? mb_substr((string) $meta['caption'], 0, 255) : null,
                'note' => $meta['note'] ?? null,
                'uploaded_by' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // Do not leave an orphan on disk that nothing points at.
            @unlink($target);

            throw new RuntimeException('Could not record the evidence: '.$e->getMessage());
        }
    }

    /**
     * Remove one piece of evidence, file and row.
     *
     * The row goes first only if the file is gone or removable, so a failed
     * unlink does not leave a row pointing at a file nobody can delete.
     */
    public static function delete(int $id): bool
    {
        $row = self::get($id);

        if (!$row) {
            return false;
        }

        $path = self::pathFor($row);

        if (is_file($path) && !@unlink($path)) {
            return false;
        }

        return DB::table('spectrum_evidence')->where('id', $id)->delete() > 0;
    }
}
