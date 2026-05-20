<?php

namespace AtomExtensions\SharePoint\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * PostIngestHookService — runs after ingest:commit to close GCIS RFB-001
 * compliance gaps 1, 2, 4, 5:
 *
 *   1. PII pre-scan (clause 4.1.1.14.c)
 *   2. Label → security_classification mapping (clause 4.1.1.12.c)
 *   4. OAIS AIP packaging per IO (clause 4.1.1.6 sophistication)
 *   5. Auto-version v1 baseline on SP ingest (clause 4.6.2)
 *
 * Plus gap 3: write the sp_* cross-reference columns onto each new IO so the
 * link back to the active SharePoint item survives outside the sidecar.
 *
 * Inputs come from ingest_file.sidecar_json — the SP auto-ingest path writes
 * sp_item_id / sp_drive_id / sp_etag / sp_retention_label / sp_web_url there.
 *
 * LOCAL ONLY per SP NO-PUSH policy. Lives in atom-ahg-plugins/ahgSharePointPlugin/
 * but must never be committed.
 *
 * @phase A (2026-05-17)
 */
class PostIngestHookService
{
    /**
     * Run all post-ingest hooks for every IO created by the given ingest job.
     *
     * @return array{processed:int, baselines:int, sp_xref:int, classifications:int, aips:int, pii_scans:int, errors:int}
     */
    public function runForJob(int $jobId): array
    {
        $stats = [
            'processed' => 0,
            'baselines' => 0,
            'sp_xref' => 0,
            'classifications' => 0,
            'aips' => 0,
            'pii_scans' => 0,
            'errors' => 0,
        ];

        $job = DB::table('ingest_job')->where('id', $jobId)->first();
        if (!$job) {
            return $stats; // unknown job
        }
        $session = DB::table('ingest_session')->where('id', $job->session_id)->first();

        $rows = DB::table('ingest_row as r')
            ->leftJoin('ingest_file as f', function ($j) use ($job) {
                $j->on('r.session_id', '=', 'f.session_id');
            })
            ->where('r.session_id', $job->session_id)
            ->whereNotNull('r.created_atom_id')
            ->select('r.id as row_id', 'r.created_atom_id', 'r.enriched_data', DB::raw('MAX(f.sidecar_json) as sidecar_json'))
            ->groupBy('r.id', 'r.created_atom_id', 'r.enriched_data')
            ->get();

        foreach ($rows as $row) {
            $atomId = (int) $row->created_atom_id;
            $stats['processed']++;

            $sidecar = $this->safeDecode($row->sidecar_json);
            $enriched = $this->safeDecode($row->enriched_data);

            try {
                if ($this->writeSpCrossReference($atomId, $sidecar, $enriched)) {
                    $stats['sp_xref']++;
                }
                if ($this->writeV1Baseline($atomId)) {
                    $stats['baselines']++;
                }
                if ($this->applyClassificationFromLabel($atomId, $sidecar['sp_retention_label'] ?? null)) {
                    $stats['classifications']++;
                }
                if ($this->generateAip($atomId, $session)) {
                    $stats['aips']++;
                }
                if ($this->scanPii($atomId)) {
                    $stats['pii_scans']++;
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
                error_log(sprintf('PostIngestHookService row %d atom_id=%d: %s', $row->row_id, $atomId, $e->getMessage()));
            }
        }
        return $stats;
    }

    /**
     * Gap 3 — write sp_item_id / sp_drive_id / sp_etag / sp_retention_label /
     * sp_web_url onto information_object so the cross-reference is queryable
     * and surfaces on the IO show page (via a small partial).
     */
    private function writeSpCrossReference(int $atomId, array $sidecar, array $enriched): bool
    {
        $itemId = $sidecar['sp_item_id'] ?? ($enriched['sp_item_id'] ?? null);
        if (empty($itemId)) {
            return false;
        }
        $update = array_filter([
            'sp_item_id'         => $itemId,
            'sp_drive_id'        => $sidecar['sp_drive_id']        ?? null,
            'sp_etag'            => $sidecar['sp_etag']            ?? null,
            'sp_retention_label' => $sidecar['sp_retention_label'] ?? null,
            'sp_web_url'         => $sidecar['sp_web_url']         ?? null,
            'sp_ingested_at'     => date('Y-m-d H:i:s'),
        ], fn ($v) => $v !== null);
        DB::table('information_object')->where('id', $atomId)->update($update);
        return true;
    }

    /**
     * Gap 5 — write a v1 baseline via VersionWriter if version control is
     * installed and this IO has no version row yet.
     */
    private function writeV1Baseline(int $atomId): bool
    {
        $vcDir = \sfConfig::get('sf_plugins_dir') . '/ahgVersionControlPlugin/lib/Services';
        if (!is_file($vcDir . '/SnapshotBuilder.php') || !is_file($vcDir . '/VersionWriter.php')) {
            return false;
        }
        if (!class_exists('\\AhgVersionControl\\Services\\SnapshotBuilder', false)) {
            require_once $vcDir . '/SnapshotBuilder.php';
        }
        if (!class_exists('\\AhgVersionControl\\Services\\VersionWriter', false)) {
            require_once $vcDir . '/VersionWriter.php';
        }
        $existing = DB::table('information_object_version')->where('information_object_id', $atomId)->exists();
        if ($existing) {
            return false; // already versioned (e.g. earlier hook run, or backfill)
        }
        $snap = (new \AhgVersionControl\Services\SnapshotBuilder())->buildForInformationObject($atomId);
        (new \AhgVersionControl\Services\VersionWriter())->write(
            'information_object',
            $atomId,
            $snap,
            'Initial baseline from SharePoint ingest',
        );
        return true;
    }

    /**
     * Gap 2 — map M365 retention label → MISS security_classification level
     * via ahg_settings:sharepoint.label_classification_map (JSON).
     */
    private function applyClassificationFromLabel(int $atomId, ?string $label): bool
    {
        if (empty($label)) {
            return false;
        }
        $mapJson = DB::table('ahg_settings')
            ->where('setting_key', 'sharepoint.label_classification_map')
            ->value('setting_value');
        $map = $mapJson ? (json_decode((string) $mapJson, true) ?: []) : $this->defaultLabelMap();
        if (empty($map[$label])) {
            return false;
        }
        $config = $map[$label];
        $classificationId = $config['classification_id'] ?? null;
        if (!$classificationId) {
            return false;
        }
        // Upsert object_security_classification
        $existing = DB::table('object_security_classification')->where('object_id', $atomId)->first();
        if ($existing) {
            DB::table('object_security_classification')->where('object_id', $atomId)->update([
                'classification_id' => $classificationId,
                'classified_at'     => date('Y-m-d H:i:s'),
                'classified_by'     => $config['classified_by'] ?? null,
            ]);
        } else {
            DB::table('object_security_classification')->insert([
                'object_id'         => $atomId,
                'classification_id' => $classificationId,
                'classified_at'     => date('Y-m-d H:i:s'),
                'classified_by'     => $config['classified_by'] ?? null,
            ]);
        }
        return true;
    }

    /**
     * Default label→classification map used when ahg_settings is unconfigured.
     * Operators should override this via Admin → AHG Settings.
     */
    private function defaultLabelMap(): array
    {
        // Look up classification IDs by code (idempotent — only emits a mapping
        // when the target classification row actually exists in the DB).
        $rows = DB::table('security_classification')->select('id', 'code')->get();
        $byCode = [];
        foreach ($rows as $r) {
            $byCode[strtolower((string) $r->code)] = (int) $r->id;
        }
        $map = [];
        foreach ([
            'Restricted-Archive'  => 'restricted',
            'Confidential-Archive' => 'confidential',
            'Secret-Archive'      => 'secret',
            'Top Secret-Archive'  => 'top_secret',
        ] as $label => $code) {
            if (isset($byCode[$code])) {
                $map[$label] = ['classification_id' => $byCode[$code]];
            }
        }
        return $map;
    }

    /**
     * Gap 4 — generate an OAIS AIP package (objects + metadata + manifest).
     */
    private function generateAip(int $atomId, ?object $session): bool
    {
        if (!$session) {
            return false;
        }
        $generateAip = (bool) (($session->ai_processing_options ?? null) ? (json_decode((string) $session->ai_processing_options, true)['generate_aip'] ?? true) : true);
        if (!$generateAip) {
            return false;
        }

        $base = \sfConfig::get('sf_upload_dir') ?: '/usr/share/nginx/archive/uploads';
        $aipDir = $base . '/aip/' . $atomId;
        if (is_dir($aipDir)) {
            return false; // already packaged
        }
        @mkdir($aipDir . '/objects', 0775, true);
        @mkdir($aipDir . '/metadata', 0775, true);

        // Copy digital objects
        $bytes = 0; $count = 0;
        $dos = DB::table('digital_object')->where('object_id', $atomId)->select('path', 'name')->get();
        foreach ($dos as $do) {
            if (empty($do->path) || empty($do->name)) {
                continue;
            }
            $src = rtrim($base, '/') . '/' . ltrim((string) $do->path, '/') . '/' . (string) $do->name;
            if (!is_file($src)) {
                continue;
            }
            $dst = $aipDir . '/objects/' . basename($do->name);
            if (copy($src, $dst)) {
                $bytes += filesize($dst);
                $count++;
            }
        }

        // Manifest
        $culture = $this->culture();
        $ioRow = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->where('io.id', $atomId)
            ->select('io.id', 'io.identifier', 'io.sp_item_id', 'io.sp_retention_label', 'io.sp_web_url', 'ioi.title', 'ioi.scope_and_content')
            ->first();

        $manifest = [
            'oais_aip_version' => '1.0',
            'created_at'       => date('c'),
            'information_object_id' => $atomId,
            'identifier'       => $ioRow->identifier  ?? null,
            'title'            => $ioRow->title       ?? null,
            'sharepoint'       => [
                'item_id'         => $ioRow->sp_item_id ?? null,
                'retention_label' => $ioRow->sp_retention_label ?? null,
                'web_url'         => $ioRow->sp_web_url ?? null,
            ],
            'digital_objects'  => ['count' => $count, 'bytes' => $bytes],
            'scope_and_content'=> strip_tags((string) ($ioRow->scope_and_content ?? '')),
        ];
        file_put_contents($aipDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // PREMIS-lite events
        $premis = [
            'events' => [[
                'event_type'        => 'ingestion',
                'event_date_time'   => date('c'),
                'event_detail'      => 'OAIS AIP packaged from SharePoint ingest',
                'source_system'     => 'Microsoft SharePoint Online',
            ]],
        ];
        file_put_contents($aipDir . '/metadata/premis.json', json_encode($premis, JSON_PRETTY_PRINT));

        // sha256
        $hash = hash_init('sha256');
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($aipDir));
        foreach ($it as $f) {
            if ($f->isFile()) {
                $files[] = $f->getPathname();
            }
        }
        sort($files);
        foreach ($files as $f) {
            hash_update($hash, basename($f) . ':');
            hash_update_file($hash, $f);
        }
        file_put_contents($aipDir . '/checksum.sha256', hash_final($hash));
        return true;
    }

    /**
     * Gap 1 — invoke ahgPrivacyPlugin's PII scan on this IO.
     * Best-effort: if the plugin isn't installed OR the symfony task fails,
     * we don't block the ingest.
     */
    private function scanPii(int $atomId): bool
    {
        $svcPath = \sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Services/PiiScanService.php';
        if (!is_file($svcPath)) {
            return false;
        }
        require_once $svcPath;
        $candidates = [
            '\\AtomExtensions\\Privacy\\Services\\PiiScanService',
            '\\AhgPrivacy\\Services\\PiiScanService',
            '\\PiiScanService',
        ];
        $cls = null;
        foreach ($candidates as $c) {
            if (class_exists($c)) {
                $cls = $c;
                break;
            }
        }
        if (!$cls) {
            return false;
        }
        try {
            $svc = new $cls();
            if (method_exists($svc, 'scanInformationObject')) {
                $svc->scanInformationObject($atomId);
                return true;
            }
            if (method_exists($svc, 'scan')) {
                $svc->scan($atomId);
                return true;
            }
        } catch (\Throwable $e) {
            error_log('PostIngestHookService PII scan failed for IO ' . $atomId . ': ' . $e->getMessage());
        }
        return false;
    }

    private function safeDecode(?string $json): array
    {
        if (empty($json)) {
            return [];
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private function culture(): string
    {
        if (class_exists('\\AtomExtensions\\Helpers\\CultureHelper')) {
            return \AtomExtensions\Helpers\CultureHelper::getCulture();
        }
        return class_exists('\\sfContext') ? \sfContext::getInstance()->getUser()->getCulture() : 'en';
    }
}
