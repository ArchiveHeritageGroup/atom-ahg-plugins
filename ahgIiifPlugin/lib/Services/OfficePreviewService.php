<?php

declare(strict_types=1);

namespace AhgIiif\Services;

use Illuminate\Database\Capsule\Manager as DB;

require_once __DIR__.'/OfficePreviewConverter.php';

/**
 * Queue, access rules and storage for Office document display copies (#231).
 *
 * The PDF lives at uploads/r/<repository-slug>/office-preview/<n>/office-<id>.pdf.
 * It has no digital_object row, so digitalobject/view answers 404 for its URL and
 * the only way to it is /media/convert/:id, which calls canView() first. The name
 * deliberately avoids the _141/_142 suffix that nginx serves on a faster path.
 */
class OfficePreviewService
{
    public const TABLE = 'iiif_office_preview';

    /** Marks the transcript rows this feature owns, so only those are ever removed. */
    public const TEXT_SCOPE = 'Text extracted from Office display copy using pdftotext';

    private const MASTER_USAGE_ID = 140;
    private const MAX_ATTEMPTS = 3;
    private const STALE_SECONDS = 3600;
    private const PREVIEW_PATH = '#^/uploads/r/[^/]+/office-preview/\d+/office-\d+\.pdf$#';

    public static function isOfficeFile(string $name): bool
    {
        return in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), OfficePreviewConverter::EXTENSIONS, true);
    }

    public static function isInstalled(): bool
    {
        try {
            return DB::schema()->hasTable(self::TABLE);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * May the current user see the display copy? Every doubt answers no.
     *
     * The copy is a reference derivative, so it follows readReference plus the
     * restrictions AtoM's ACL does not know about (classification, donor, embargo,
     * ICIP, ODRL). A record with redactions is shown to administrators only: the
     * redaction tooling works on PDF masters and has not been applied to this copy.
     *
     * @param \QubitDigitalObject $digitalObject
     * @param \myUser             $user
     */
    public static function canView($digitalObject, $user): bool
    {
        try {
            if (self::MASTER_USAGE_ID !== (int) $digitalObject->usageId || !empty($digitalObject->parentId)) {
                return false;
            }
            $resource = $digitalObject->object;
            if (!$resource instanceof \QubitInformationObject) {
                return false;
            }
            if (!\QubitAcl::check($resource, 'read') || !\QubitAcl::check($resource, 'readReference')) {
                return false;
            }
            $denyReason = null;
            if (!\QubitGrantedRight::checkPremis($resource->id, 'readReference', $denyReason)) {
                return false;
            }

            $objectId = (int) $resource->id;
            $userId = $user->isAuthenticated() ? (int) $user->getUserID() : null;
            $restricted = \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()
                ->getRestrictedObjectIds($userId);
            if (in_array($objectId, array_map('intval', $restricted), true)) {
                return false;
            }

            return $user->isAdministrator() || !self::hasRedactions($objectId);
        } catch (\Throwable $e) {
            error_log('OfficePreview access check failed: '.$e->getMessage());

            return false;
        }
    }

    /** Same sources as the privacy plugin's check. Its tables are optional. */
    public static function hasRedactions(int $objectId): bool
    {
        $schema = DB::schema();
        if ($schema->hasTable('privacy_visual_redaction')
            && DB::table('privacy_visual_redaction')
                ->where('object_id', $objectId)
                ->whereIn('status', ['approved', 'applied', 'pending'])
                ->exists()) {
            return true;
        }

        return $schema->hasTable('ahg_ner_entity')
            && DB::table('ahg_ner_entity')->where('object_id', $objectId)->where('status', 'redacted')->exists();
    }

    /** Absolute path of a current display copy, or null. */
    public function readyFile(object $master): ?string
    {
        $row = DB::table(self::TABLE)->where('digital_object_id', $master->id)->where('status', 'ready')->first();
        if (!$row || (string) $row->source_checksum !== (string) $master->checksum
            || !preg_match(self::PREVIEW_PATH, (string) $row->pdf_path)) {
            return null;
        }
        $file = self::webDir().$row->pdf_path;

        return is_file($file) && !is_link($file) ? $file : null;
    }

    public function status(int $digitalObjectId): ?string
    {
        return DB::table(self::TABLE)->where('digital_object_id', $digitalObjectId)->value('status');
    }

    /** Queues one master. Safe to call on every page view. */
    public function enqueue(int $digitalObjectId): void
    {
        $master = DB::table('digital_object')->where('id', $digitalObjectId)
            ->first(['id', 'object_id', 'checksum']);
        if ($master) {
            $this->queueRow($master);
        }
    }

    /**
     * Finds masters with no current display copy. Covers new uploads, replaced
     * files and the backfill of older records.
     */
    public function sweep(int $limit): int
    {
        $rows = DB::table('digital_object as d')
            ->leftJoin(self::TABLE.' as p', 'p.digital_object_id', '=', 'd.id')
            ->where('d.usage_id', self::MASTER_USAGE_ID)
            ->whereNull('d.parent_id')
            ->whereNotNull('d.object_id')
            ->where(function ($q) {
                foreach (OfficePreviewConverter::EXTENSIONS as $extension) {
                    $q->orWhere('d.name', 'like', '%.'.$extension);
                }
            })
            ->where(function ($q) {
                // Compared as bytes: core and plugin tables can use different collations.
                $q->whereNull('p.id')->orWhereRaw('CAST(p.source_checksum AS BINARY) <> CAST(d.checksum AS BINARY)');
            })
            ->orderBy('d.id')
            ->limit($limit)
            ->get(['d.id', 'd.object_id', 'd.checksum']);

        foreach ($rows as $row) {
            $this->queueRow($row);
        }

        return count($rows);
    }

    public function retryFailed(): int
    {
        return DB::table(self::TABLE)->where('status', 'failed')->update([
            'status' => 'pending',
            'attempts' => 0,
            'error_message' => null,
        ]);
    }

    /** A worker that died mid-run must not hold a row for ever. */
    public function releaseStale(): void
    {
        DB::table(self::TABLE)
            ->where('status', 'processing')
            ->where('started_at', '<', date('Y-m-d H:i:s', time() - self::STALE_SECONDS))
            ->update(['status' => 'pending']);
    }

    /** @return int[] digital object ids waiting for conversion */
    public function pendingIds(int $limit, ?int $onlyId = null): array
    {
        $query = DB::table(self::TABLE)->where('status', 'pending')->orderBy('id')->limit($limit);
        if (null !== $onlyId) {
            $query->where('digital_object_id', $onlyId);
        }

        return array_map('intval', $query->pluck('digital_object_id')->all());
    }

    /** @return array{ok:bool,error:?string} */
    public function process(int $digitalObjectId, OfficePreviewConverter $converter): array
    {
        $claimed = DB::table(self::TABLE)
            ->where('digital_object_id', $digitalObjectId)
            ->where('status', 'pending')
            ->update([
                'status' => 'processing',
                'started_at' => date('Y-m-d H:i:s'),
                'attempts' => DB::raw('attempts + 1'),
            ]);
        if (1 !== $claimed) {
            return ['ok' => false, 'error' => 'Already claimed'];
        }

        $master = DB::table('digital_object')->where('id', $digitalObjectId)->first();
        $error = null;
        $relative = $master ? $this->previewPath($master) : null;
        $source = $master ? $this->sourcePath($master) : null;

        if (null === $relative || null === $source) {
            $error = 'Master file is missing or outside uploads/r';
        } elseif ($this->isEncrypted($source)) {
            // A plain PDF of an encrypted master would undo the encryption at rest.
            $error = 'Master file is encrypted';
        } else {
            $result = $converter->convert(
                $source,
                strtolower(pathinfo((string) $master->name, PATHINFO_EXTENSION)),
                self::webDir().$relative
            );
            $error = $result['ok'] ? null : (string) $result['error'];
        }

        if (null !== $error) {
            $attempts = (int) DB::table(self::TABLE)->where('digital_object_id', $digitalObjectId)->value('attempts');
            DB::table(self::TABLE)->where('digital_object_id', $digitalObjectId)->update([
                'status' => $attempts >= self::MAX_ATTEMPTS ? 'failed' : 'pending',
                'error_message' => mb_substr($error, 0, 500),
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            return ['ok' => false, 'error' => $error];
        }

        DB::table(self::TABLE)->where('digital_object_id', $digitalObjectId)->update([
            'status' => 'ready',
            'source_checksum' => $master->checksum,
            'pdf_path' => $relative,
            'pdf_size' => (int) filesize(self::webDir().$relative),
            'error_message' => null,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        $this->syncTranscript($master, $result['text']);

        return ['ok' => true, 'error' => null];
    }

    /**
     * Removes the searchable text of records that gained a redaction after they
     * were converted. The display copy is already refused by canView().
     */
    public function withdrawRedactedText(): int
    {
        $count = 0;
        $rows = DB::table(self::TABLE.' as p')
            ->join('property as t', 't.object_id', '=', 'p.digital_object_id')
            ->where('t.name', 'transcript')
            ->where('t.scope', self::TEXT_SCOPE)
            ->get(['p.digital_object_id', 'p.object_id']);

        foreach ($rows as $row) {
            if (self::hasRedactions((int) $row->object_id)) {
                $this->deleteTranscript((int) $row->digital_object_id);
                $this->reindex((int) $row->object_id);
                ++$count;
            }
        }

        return $count;
    }

    /** Removes the display copy and row of masters that no longer exist. */
    public function prune(): int
    {
        $orphans = DB::table(self::TABLE.' as p')
            ->leftJoin('digital_object as d', 'd.id', '=', 'p.digital_object_id')
            ->whereNull('d.id')
            ->get(['p.id', 'p.pdf_path']);

        foreach ($orphans as $orphan) {
            if (preg_match(self::PREVIEW_PATH, (string) $orphan->pdf_path)) {
                $file = self::webDir().$orphan->pdf_path;
                if (is_file($file) && !is_link($file)) {
                    @unlink($file);
                }
            }
            DB::table(self::TABLE)->where('id', $orphan->id)->delete();
        }

        return count($orphans);
    }

    private function queueRow(object $master): void
    {
        $now = date('Y-m-d H:i:s');
        $inserted = DB::table(self::TABLE)->insertOrIgnore([
            'digital_object_id' => $master->id,
            'object_id' => $master->object_id,
            'source_checksum' => $master->checksum,
            'status' => 'pending',
            'created_at' => $now,
        ]);
        if ($inserted) {
            return;
        }

        // A replaced file: convert again. A row already queued or running is left alone.
        DB::table(self::TABLE)
            ->where('digital_object_id', $master->id)
            ->whereIn('status', ['ready', 'failed'])
            ->where(function ($q) use ($master) {
                $q->whereNull('source_checksum')->orWhere('source_checksum', '<>', (string) $master->checksum);
            })
            ->update([
                'status' => 'pending',
                'source_checksum' => $master->checksum,
                'attempts' => 0,
                'error_message' => null,
            ]);
    }

    private function syncTranscript(object $master, ?string $text): void
    {
        $objectId = (int) $master->object_id;
        $this->deleteTranscript((int) $master->id);

        // Search must not surface words a redaction hides.
        if (null !== $text && !self::hasRedactions($objectId)) {
            $culture = (string) \sfConfig::get('sf_default_culture', 'en');
            $propertyId = DB::table('property')->insertGetId([
                'object_id' => $master->id,
                'name' => 'transcript',
                'scope' => self::TEXT_SCOPE,
                'source_culture' => $culture,
            ]);
            DB::table('property_i18n')->insert(['id' => $propertyId, 'culture' => $culture, 'value' => $text]);
        }

        $this->reindex($objectId);
    }

    private function deleteTranscript(int $digitalObjectId): void
    {
        // property_i18n follows through its ON DELETE CASCADE foreign key.
        DB::table('property')
            ->where('object_id', $digitalObjectId)
            ->where('name', 'transcript')
            ->where('scope', self::TEXT_SCOPE)
            ->delete();
    }

    private function reindex(int $objectId): void
    {
        try {
            $resource = \QubitInformationObject::getById($objectId);
            if ($resource) {
                \QubitSearch::getInstance()->update($resource);
            }
        } catch (\Throwable $e) {
            error_log('OfficePreview reindex failed for '.$objectId.': '.$e->getMessage());
        }
    }

    private function previewPath(object $master): ?string
    {
        if (!preg_match('#^/uploads/r/([A-Za-z0-9][A-Za-z0-9._-]*)/#', (string) $master->path, $match)) {
            return null;
        }
        $id = (int) $master->id;

        return sprintf('/uploads/r/%s/office-preview/%d/office-%d.pdf', $match[1], $id % 1000, $id);
    }

    private function sourcePath(object $master): ?string
    {
        $root = realpath(self::webDir().'/uploads/r');
        $file = realpath(self::webDir().$master->path.$master->name);
        if (false === $root || false === $file || !str_starts_with($file, $root.'/') || !is_file($file)) {
            return null;
        }

        return $file;
    }

    private function isEncrypted(string $file): bool
    {
        return class_exists('\AtomFramework\Core\Security\EncryptionService')
            && \AtomFramework\Core\Security\EncryptionService::isEncryptedFile($file);
    }

    private static function webDir(): string
    {
        return rtrim((string) \sfConfig::get('sf_web_dir'), '/');
    }
}
