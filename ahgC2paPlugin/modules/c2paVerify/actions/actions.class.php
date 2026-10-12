<?php
/**
 * PSIS / AtoM-AHG - public content-credentials pages.
 *
 * PSIS twin of Heratio's VerifyController (byId), VerifyObjectController,
 * VerifyObjectDownloadController and PublicCheckController. Routes are
 * registered in ahgC2paPluginConfiguration:
 *
 *   GET      /c2pa/check                    executeCheck        drop-in file check
 *   POST     /c2pa/check                    executeCheck
 *   GET      /c2pa/record/:id               executeRecord       record verification page (badge target)
 *   GET      /c2pa/object/:id               executeObject       one file's credentials chain
 *   GET      /c2pa/object/:id/download      executeDownload     master with credentials attached
 *   GET      /c2pa/object/:id/credentials   executeCredentials  signed sidecar manifest
 *   GET      /c2pa/object/:id/badge         executeBadge        embeddable SVG badge
 *   GET      /c2pa/object/:id/badge-json    executeBadgeJson    CORS-open JSON status
 *
 * Record and object pages answer only for records the visitor may read; the
 * download and sidecar answer only when the visitor may read the master.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

use AhgC2pa\Manifest\ManifestBuilder;
use AhgC2pa\Services\C2paService;
use AhgC2pa\Services\ManifestPresenter;
use AhgC2pa\Services\ProvenanceRecordService;
use AhgC2pa\Services\RecordAccess;
use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

class c2paVerifyActions extends AhgController
{
    private const MAX_UPLOAD_BYTES = 25 * 1024 * 1024;
    private const MAX_CHECKS_PER_10_MIN = 30;
    private const SCAN_LIMIT_BYTES = 8 * 1024 * 1024;

    private const CHECK_EXTENSIONS = ['jpg', 'jpeg', 'png', 'tif', 'tiff', 'webp', 'gif', 'avif', 'jp2', 'pdf', 'json'];

    public function boot(): void
    {
        require_once dirname(__DIR__, 3) . '/lib/c2pa_bootstrap.php';
        \C2paBootstrap::load();
    }

    // ------------------------------------------------------------------
    // Record + object pages
    // ------------------------------------------------------------------

    public function executeRecord($request)
    {
        $ioId = (int) $request->getParameter('id');
        $this->reference = (string) $ioId;
        $this->object = null;
        $this->chain = [];
        $this->summary = null;

        if ($ioId <= 0 || !RecordAccess::canRead($ioId)) {
            $this->getResponse()->setStatusCode(404);

            return sfView::SUCCESS;
        }

        $this->object = ProvenanceRecordService::loadInformationObject($ioId);
        if ($this->object === null) {
            $this->getResponse()->setStatusCode(404);

            return sfView::SUCCESS;
        }

        $service = \C2paBootstrap::provenanceService();
        $chain = [];
        $verified = 0;
        $signed = 0;
        foreach ($service->listForObject($ioId) as $record) {
            $verification = $service->verifyRecord((int) $record->id);
            if (($verification['status'] ?? null) === 'verified') {
                $verified++;
            }
            if ($record->manifest_id !== null) {
                $signed++;
            }
            $chain[] = [
                'record'          => $record,
                'verification'    => $verification,
                'inference_steps' => ProvenanceRecordService::decodeSteps($record),
            ];
        }

        $this->chain = $chain;
        $this->summary = [
            'total'    => count($chain),
            'signed'   => $signed,
            'verified' => $verified,
            'tampered' => $signed - $verified,
        ];

        return sfView::SUCCESS;
    }

    public function executeObject($request)
    {
        $doId = (int) $request->getParameter('id');
        $this->object = self::loadDigitalObject($doId);
        if ($this->object === null || $this->object->io_id === null || !RecordAccess::canRead((int) $this->object->io_id)) {
            $this->forward404();
        }

        $analysis = $this->analyse($doId);
        $this->state = $analysis['state'];
        $this->chain = $analysis['chain'];
        $this->signer = $analysis['signer'];
        $this->signedAt = $analysis['signed_at'];
        $this->counts = $analysis['counts'];
        $this->downloadable = RecordAccess::canReadMaster($doId)
            && \C2paBootstrap::digitalObjectService()->resolveMasterForDownload($doId) !== null;
    }

    public function executeBadgeJson($request)
    {
        $doId = (int) $request->getParameter('id');
        $payload = [
            'status'           => ManifestPresenter::STATE_ABSENT,
            'signer'           => null,
            'signed_at'        => null,
            'assertions_count' => 0,
            'verify_url'       => $this->absoluteUrl('@ahg_c2pa_object?id=' . $doId),
        ];

        $do = self::loadDigitalObject($doId);
        if ($do !== null && $do->io_id !== null && RecordAccess::canRead((int) $do->io_id)) {
            $analysis = $this->analyse($doId);
            $payload['status'] = $analysis['state'];
            $payload['signer'] = $analysis['signer'];
            $payload['signed_at'] = $analysis['signed_at'];
            $payload['assertions_count'] = $analysis['counts']['assertions'];
        }

        $this->corsHeaders();

        return $this->renderJson($payload);
    }

    public function executeBadge($request)
    {
        $doId = (int) $request->getParameter('id');
        $state = ManifestPresenter::STATE_ABSENT;
        $do = self::loadDigitalObject($doId);
        if ($do !== null && $do->io_id !== null && RecordAccess::canRead((int) $do->io_id)) {
            $state = $this->analyse($doId)['state'];
        }

        $this->corsHeaders();
        $response = $this->getResponse();
        $response->setContentType('image/svg+xml; charset=utf-8');
        $response->setHttpHeader('Cache-Control', 'public, max-age=300');

        return $this->renderText(self::renderSvg($state));
    }

    // ------------------------------------------------------------------
    // Download with content credentials
    // ------------------------------------------------------------------

    public function executeDownload($request)
    {
        $doId = (int) $request->getParameter('id');
        $master = $this->readableMaster($doId);
        $plainCopy = $this->plaintextCopy($master);
        if ($plainCopy !== null) {
            $master['path'] = $plainCopy;
        }

        try {
            $mime = $master['mime'] ?? 'application/octet-stream';
            $embedded = $this->tryEmbed($doId, $master);
            if ($embedded !== null) {
                return $this->streamFile($embedded, self::embeddedName($master['name'], $master['path']), $mime, ['X-C2PA-Credentials' => 'embedded'], true);
            }

            $sidecarUrl = $this->absoluteUrl('@ahg_c2pa_object_credentials?id=' . $doId);

            return $this->streamFile($master['path'], $master['name'], $mime, [
                'X-C2PA-Credentials' => 'sidecar',
                'X-C2PA-Manifest'    => $sidecarUrl,
                'Link'               => '<' . $sidecarUrl . '>; rel="c2pa-manifest"; type="application/c2pa+json"',
            ], false);
        } finally {
            C2paService::removeTemp($plainCopy);
        }
    }

    public function executeCredentials($request)
    {
        $doId = (int) $request->getParameter('id');
        $master = $this->readableMaster($doId);
        $plainCopy = $this->plaintextCopy($master);
        try {
            $signed = $this->buildSignedManifest($doId, $plainCopy === null ? $master : array_merge($master, ['path' => $plainCopy]));
        } finally {
            C2paService::removeTemp($plainCopy);
        }
        if ($signed === null) {
            $this->forward404();
        }

        $stem = pathinfo($master['name'] !== '' ? $master['name'] : 'download', PATHINFO_FILENAME) ?: 'download';
        $response = $this->getResponse();
        $response->setContentType('application/c2pa+json');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . self::asciiFilename($stem . '.c2pa') . '"');
        $response->setHttpHeader('X-Content-Type-Options', 'nosniff');
        $response->setHttpHeader('Cache-Control', 'no-store, private');

        // Canonical bytes verbatim - re-encoding would reorder keys.
        return $this->renderText(ManifestBuilder::toCanonicalJson($signed));
    }

    /**
     * The master, when this visitor may read it and it is on disk; 404 otherwise.
     *
     * @return array{path:string, name:string, mime:?string, object_id:int}
     */
    private function readableMaster(int $doId): array
    {
        $master = RecordAccess::canReadMaster($doId)
            ? \C2paBootstrap::digitalObjectService()->resolveMasterForDownload($doId)
            : null;
        if ($master === null) {
            $this->forward404();
        }

        return $master;
    }

    /**
     * Decrypted temp copy of an encrypted-at-rest master (caller removes it),
     * null when the master is stored in the clear. 404 when it cannot be read.
     *
     * @param array{path:string} $master
     */
    private function plaintextCopy(array $master): ?string
    {
        if (!C2paService::isEncryptedAtRest($master['path'])) {
            return null;
        }
        try {
            return C2paService::decryptToTemp($master['path']);
        } catch (\Throwable $e) {
            error_log('[c2pa] cannot decrypt master for download: ' . $e->getMessage());
            $this->forward404();
        }
    }

    // ------------------------------------------------------------------
    // Public check page
    // ------------------------------------------------------------------

    public function executeCheck($request)
    {
        $this->state = null;
        $this->result = null;
        $this->fileName = null;
        $this->errorMsg = null;

        if (!$request->isMethod('post')) {
            return sfView::SUCCESS;
        }

        // Public page: cap checks per address so it cannot be used to load the server.
        // ponytail: APCu, per php-fpm pool; move to nginx limit_req if abuse is seen.
        if (function_exists('apcu_inc')) {
            $key = 'c2pa_check_' . md5((string) $request->getRemoteAddress());
            $count = apcu_inc($key, 1, $ok, 600);
            if ($count === false) {
                apcu_store($key, 1, 600);
                $count = 1;
            }
            if ($count > self::MAX_CHECKS_PER_10_MIN) {
                $this->errorMsg = $this->context->getI18N()->__('Too many checks from your address. Please try again in a few minutes.');
                $this->getResponse()->setStatusCode(429);

                return sfView::SUCCESS;
            }
        }

        $file = $request->getFiles('file');
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->errorMsg = $this->context->getI18N()->__('Please choose a file to check.');

            return sfView::SUCCESS;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_INI_SIZE || ($file['error'] ?? 0) === UPLOAD_ERR_FORM_SIZE
            || (int) ($file['size'] ?? 0) > self::MAX_UPLOAD_BYTES) {
            $this->errorMsg = $this->context->getI18N()->__('That file is too large. The limit is 25 MB.');

            return sfView::SUCCESS;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            $this->errorMsg = $this->context->getI18N()->__('The upload did not arrive. Please try again.');

            return sfView::SUCCESS;
        }

        $this->fileName = (string) ($file['name'] ?? '');
        $ext = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::CHECK_EXTENSIONS, true)) {
            $this->errorMsg = $this->context->getI18N()->__('That file type is not supported. Upload a JPEG, PNG, TIFF, WebP or PDF file, or a .c2pa.json sidecar manifest.');

            return sfView::SUCCESS;
        }

        // Read straight from PHP's upload temp file; it is never moved anywhere
        // else and PHP deletes it when the request ends.
        try {
            $this->result = $this->verifyFile((string) $file['tmp_name']);
        } catch (\Throwable $e) {
            error_log('[c2pa] public check fell back to neutral: ' . $e->getMessage());
            $this->result = self::neutralResult();
        }
        $this->state = $this->result['state'];

        return sfView::SUCCESS;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * @return array{state:string, signer:?string, signed_at:?string, counts:array<string,int>, chain:list<array<string,mixed>>}
     */
    private function analyse(int $digitalObjectId): array
    {
        $result = [
            'state'     => ManifestPresenter::STATE_ABSENT,
            'signer'    => null,
            'signed_at' => null,
            'counts'    => ['records' => 0, 'signed' => 0, 'verified' => 0, 'invalid' => 0, 'assertions' => 0],
            'chain'     => [],
        ];

        try {
            $service = \C2paBootstrap::provenanceService();
            foreach ($service->listForDigitalObject($digitalObjectId) as $record) {
                $isSigned = $record->manifest_id !== null;
                try {
                    $verification = $service->verifyRecord((int) $record->id);
                } catch (\Throwable $e) {
                    $verification = ['status' => 'failed', 'ok' => false, 'errors' => [$e->getMessage()], 'manifest' => null, 'kid' => null];
                }

                $status = (string) ($verification['status'] ?? 'unsigned');
                $result['counts']['records']++;
                if ($isSigned) {
                    $result['counts']['signed']++;
                    if ($status === 'verified') {
                        $result['counts']['verified']++;
                        $result['signer'] ??= ManifestPresenter::cleanStr($verification['kid'] ?? null);
                        $result['signed_at'] ??= (string) ($record->updated_at ?? $record->created_at ?? '') ?: null;
                    } else {
                        $result['counts']['invalid']++;
                    }
                }

                $assertions = ManifestPresenter::readAssertions($verification['manifest'] ?? null);
                $result['counts']['assertions'] += count($assertions);
                $result['chain'][] = [
                    'record'          => $record,
                    'verification'    => $verification,
                    'entry_state'     => !$isSigned ? ManifestPresenter::STATE_ABSENT : ($status === 'verified' ? ManifestPresenter::STATE_VERIFIED : ManifestPresenter::STATE_INVALID),
                    'assertions'      => $assertions,
                    'inference_steps' => ProvenanceRecordService::decodeSteps($record),
                ];
            }
        } catch (\Throwable $e) {
            error_log('[c2pa] object analysis failed for ' . $digitalObjectId . ': ' . $e->getMessage());

            return $result;
        }

        if ($result['counts']['invalid'] > 0) {
            $result['state'] = ManifestPresenter::STATE_INVALID;
        } elseif ($result['counts']['verified'] > 0) {
            $result['state'] = ManifestPresenter::STATE_VERIFIED;
        }

        return $result;
    }

    /**
     * @return array<string,mixed>
     */
    private function verifyFile(string $path): array
    {
        $manifest = $this->extractManifest($path);
        if ($manifest === null) {
            return self::neutralResult();
        }

        $verification = C2paService::verify($manifest, C2paService::publicKeyResolver());

        return [
            'state'          => $verification['ok'] ? ManifestPresenter::STATE_VERIFIED : ManifestPresenter::STATE_INVALID,
            'signer'         => ManifestPresenter::cleanStr($manifest['claim_signature']['kid'] ?? null),
            'errors'         => $verification['errors'],
            'assertions'     => ManifestPresenter::readAssertions($manifest),
            'found_manifest' => true,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function neutralResult(): array
    {
        return ['state' => ManifestPresenter::STATE_ABSENT, 'signer' => null, 'errors' => [], 'assertions' => [], 'found_manifest' => false];
    }

    /**
     * Recover a manifest from an uploaded file in pure PHP: the file IS a JSON
     * manifest, or a PDF carrying one as an associated file, or its bytes embed
     * the canonical JSON form. A native JUMBF-only manifest reads as "absent",
     * the honest answer, rather than a false failure.
     *
     * @return array<string,mixed>|null
     */
    private function extractManifest(string $path): ?array
    {
        $size = @filesize($path);
        if ($size === false || $size === 0) {
            return null;
        }

        $head = (string) @file_get_contents($path, false, null, 0, 64);
        if (preg_match('/^\s*\{/', $head) === 1) {
            $decoded = json_decode((string) @file_get_contents($path, false, null, 0, self::SCAN_LIMIT_BYTES), true);
            if (self::looksLikeManifest($decoded)) {
                return $decoded;
            }
        }

        if (str_starts_with($head, '%PDF-')) {
            $bytes = \C2paBootstrap::c2paService()->extractC2paFromPdf($path);
            $decoded = $bytes === null ? null : json_decode($bytes, true);
            if (self::looksLikeManifest($decoded)) {
                return $decoded;
            }
        }

        $blob = @file_get_contents($path, false, null, 0, self::SCAN_LIMIT_BYTES);
        if (!is_string($blob) || $blob === '') {
            return null;
        }
        $anchor = strpos($blob, '"claim_signature"');
        if ($anchor === false) {
            $anchor = strpos($blob, '"manifest_label"');
        }
        if ($anchor === false) {
            return null;
        }
        $start = strrpos(substr($blob, 0, $anchor), '{');
        if ($start === false) {
            return null;
        }
        $candidate = self::balancedObjectFrom($blob, $start);
        $decoded = $candidate === null ? null : json_decode($candidate, true);

        return self::looksLikeManifest($decoded) ? $decoded : null;
    }

    private static function balancedObjectFrom(string $blob, int $start): ?string
    {
        $depth = 0;
        $inString = false;
        $escaped = false;
        $len = strlen($blob);

        for ($i = $start; $i < $len; $i++) {
            $ch = $blob[$i];
            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($ch === '\\') {
                    $escaped = true;
                } elseif ($ch === '"') {
                    $inString = false;
                }

                continue;
            }
            if ($ch === '"') {
                $inString = true;
            } elseif ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($blob, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    private static function looksLikeManifest(mixed $decoded): bool
    {
        return is_array($decoded)
            && isset($decoded['assertions']) && is_array($decoded['assertions'])
            && isset($decoded['claim']) && is_array($decoded['claim']);
    }

    /**
     * Build + sign a fresh manifest for the master (with its EXIF/IPTC/XMP
     * standard-metadata assertions). Null on any fault or without a key.
     *
     * @param array{path:string, name:string, mime:?string, object_id:int} $master
     *
     * @return array<string,mixed>|null
     */
    private function buildSignedManifest(int $doId, array $master): ?array
    {
        try {
            $c2pa = \C2paBootstrap::c2paService();
            if (!$c2pa->canSign()) {
                return null;
            }
            $manifest = $c2pa->manifestForDigitalObject(
                $master['object_id'] > 0 ? $master['object_id'] : $doId,
                $doId,
                $master['path'],
                ProvenanceRecordService::frameworkVersion(),
            );

            return $c2pa->signManifest($manifest);
        } catch (\Throwable $e) {
            error_log('[c2pa] download manifest build/sign failed for ' . $doId . ': ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Embed a fresh manifest into a temp copy (never the original). Null on any
     * degrade, in which case the caller streams the original + sidecar link.
     *
     * @param array{path:string, name:string, mime:?string, object_id:int} $master
     */
    private function tryEmbed(int $doId, array $master): ?string
    {
        $src = $master['path'];
        $c2pa = \C2paBootstrap::c2paService();
        $isPdf = (bool) preg_match('/\.pdf$/i', $src);
        if (!$isPdf && (!$c2pa->canEmbed() || !C2paService::isEmbeddableFormat($src))) {
            return null;
        }

        $signed = $this->buildSignedManifest($doId, $master);
        if ($signed === null) {
            return null;
        }

        try {
            $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION)) ?: 'bin';
            $dest = rtrim(sys_get_temp_dir(), '/') . '/c2pa-dl-' . bin2hex(random_bytes(8)) . '.' . $ext;
            $out = $isPdf ? $c2pa->embedInPdf($src, $signed, $dest) : $c2pa->embed($src, $signed, $dest);
            if ($out === null || !is_readable($out)) {
                if (is_file($dest)) {
                    @unlink($dest);
                }

                return null;
            }

            return $out;
        } catch (\Throwable $e) {
            error_log('[c2pa] download embed fell back to original for ' . $doId . ': ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param array<string,string> $extraHeaders
     */
    private function streamFile(string $path, string $downloadName, string $mime, array $extraHeaders, bool $deleteAfter)
    {
        $response = $this->getResponse();
        $response->clearHttpHeaders();
        $response->setContentType($mime);
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . self::asciiFilename($downloadName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
        $size = @filesize($path);
        if (is_int($size) && $size > 0) {
            $response->setHttpHeader('Content-Length', (string) $size);
        }
        $response->setHttpHeader('X-Content-Type-Options', 'nosniff');
        $response->setHttpHeader('Cache-Control', 'no-store, private');
        foreach ($extraHeaders as $k => $v) {
            $response->setHttpHeader($k, $v);
        }
        $response->sendHttpHeaders();

        try {
            readfile($path);
        } finally {
            if ($deleteAfter && is_file($path)) {
                @unlink($path);
            }
        }

        return sfView::NONE;
    }

    private function corsHeaders(): void
    {
        $response = $this->getResponse();
        $response->setHttpHeader('Access-Control-Allow-Origin', '*');
        $response->setHttpHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->setHttpHeader('X-Content-Type-Options', 'nosniff');
    }

    private function absoluteUrl(string $route): string
    {
        try {
            $this->context->getConfiguration()->loadHelpers(['Url']);

            return (string) url_for($route, true);
        } catch (\Throwable) {
            return '';
        }
    }

    private static function renderSvg(string $state): string
    {
        [$colour, $label] = match ($state) {
            ManifestPresenter::STATE_VERIFIED => ['#1a7f37', "Content Credentials \u{2713}"],
            ManifestPresenter::STATE_INVALID  => ['#cf222e', "Content Credentials \u{2717}"],
            default                           => ['#6c757d', 'No Content Credentials'],
        };
        $leftText = 'Verify';
        $leftW = max(46, 14 + strlen($leftText) * 7);
        $rightW = max(120, 14 + mb_strlen($label) * 7);
        $total = $leftW + $rightW;
        $leftMid = intdiv($leftW, 2);
        $rightMid = $leftW + intdiv($rightW, 2);
        $x = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $eLabel = $x($label);
        $eState = $x($state);

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{$total}" height="20" role="img" aria-label="Content Credentials: {$eState}">
  <title>Content Credentials: {$eState}</title>
  <linearGradient id="s" x2="0" y2="100%">
    <stop offset="0" stop-color="#bbb" stop-opacity=".1"/>
    <stop offset="1" stop-opacity=".1"/>
  </linearGradient>
  <clipPath id="r"><rect width="{$total}" height="20" rx="3"/></clipPath>
  <g clip-path="url(#r)">
    <rect width="{$leftW}" height="20" fill="#444"/>
    <rect x="{$leftW}" width="{$rightW}" height="20" fill="{$colour}"/>
    <rect width="{$total}" height="20" fill="url(#s)"/>
  </g>
  <g fill="#fff" text-anchor="middle" font-family="Verdana,DejaVu Sans,Geneva,sans-serif" font-size="11">
    <text x="{$leftMid}" y="14" fill="#010101" fill-opacity=".3">{$leftText}</text>
    <text x="{$leftMid}" y="13">{$leftText}</text>
    <text x="{$rightMid}" y="14" fill="#010101" fill-opacity=".3">{$eLabel}</text>
    <text x="{$rightMid}" y="13">{$eLabel}</text>
  </g>
</svg>
SVG;
    }

    private static function embeddedName(string $originalName, string $srcPath): string
    {
        $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
        $stem = pathinfo($originalName !== '' ? $originalName : basename($srcPath), PATHINFO_FILENAME) ?: 'download';

        return $ext !== '' ? "{$stem}.c2pa.{$ext}" : "{$stem}.c2pa";
    }

    private static function asciiFilename(string $name): string
    {
        $ascii = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '_', $name), '_');

        return $ascii === '' ? 'download' : $ascii;
    }

    private static function loadDigitalObject(int $doId): ?object
    {
        if ($doId <= 0) {
            return null;
        }
        $do = DB::table('digital_object')->where('id', $doId)->first(['id', 'object_id', 'name', 'mime_type', 'parent_id']);
        if ($do === null) {
            return null;
        }
        $do->io_id = null;
        $do->io_title = null;
        $do->io_ref = null;
        $do->io_slug = null;
        $io = $do->object_id ? ProvenanceRecordService::loadInformationObject((int) $do->object_id) : null;
        if ($io !== null) {
            $do->io_id = (int) $io->id;
            $do->io_title = $io->title;
            $do->io_ref = $io->identifier;
            $do->io_slug = $io->slug;
        }

        return $do;
    }
}
