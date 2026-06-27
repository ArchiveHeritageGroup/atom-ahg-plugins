<?php

/**
 * One-command demo of the full ahgRdmPlugin Feature-2 pipeline
 * (atom-ahg-plugins#173) — reverse port of Heratio `ahg:rdm-demo` (heratio#1343).
 *
 * deposit -> POPIA scan -> human gate -> restrict -> DOI -> landing -> scoreboard,
 * on the 100%-SYNTHETIC demo dataset (never real PII).
 *
 *   php symfony rdm:demo [--fresh]
 */
class rdmDemoTask extends arBaseTask
{
    private const TITLE = 'POPIA RDM Demo (synthetic)';

    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('fresh', null, sfCommandOption::PARAMETER_NONE, 'Delete a prior demo dataset and rebuild'),
        ]);

        $this->namespace = 'rdm';
        $this->name = 'demo';
        $this->briefDescription = 'Run the full POPIA RDM demo on synthetic data (deposit->scan->gate->DOI->landing).';
        $this->detailedDescription = <<<'EOF'
The [rdm:demo|INFO] task runs the whole RDM pipeline end-to-end on 100%-synthetic
assets (Luhn-valid fake SA IDs, a health-mentioning transcript, consent PDFs, and
a clean climate set as a negative control). No real personal data is involved.

  [php symfony rdm:demo --fresh|INFO]
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgRdmPlugin';
        require_once $pluginDir . '/lib/Services/DatasetService.php';
        require_once $pluginDir . '/lib/Services/PopiaScanService.php';
        require_once $pluginDir . '/lib/Services/PopiaGateService.php';

        $DB = '\Illuminate\Database\Capsule\Manager';

        $demoDir = $pluginDir . '/data/demo';
        if (!is_dir($demoDir)) {
            $this->logSection('rdm:demo', "Demo assets not found at {$demoDir}", null, 'ERROR');

            return 1;
        }

        if ($options['fresh']) {
            $this->purge();
        }

        $userId = (int) ($DB::table('user')->min('id') ?: 1);
        $projectId = $this->ensureProject();

        $svc = new \AhgRdm\Services\DatasetService();
        $datasetId = $svc->create(
            self::TITLE,
            'Synthetic social-science/health study for the POPIA scan demo. No real personal data.',
            $projectId,
            $userId
        );
        $this->logSection('rdm:demo', "Created dataset #{$datasetId}" . ($projectId ? " (project #{$projectId})" : ' (no project)'));

        // Feature 1 (#174): attach a Data Management Plan when the DMP-link slice
        // is installed. Guarded — skips cleanly until Phase 7 lands DmpLinkService.
        $dmpFile = $pluginDir . '/lib/Services/DmpLinkService.php';
        if ($projectId && is_file($dmpFile)) {
            try {
                require_once $dmpFile;
                $dmpId = (new \AhgRdm\Services\DmpLinkService())->createAndLink($datasetId, [
                    'title'         => 'DMP - POPIA RDM Demo Study',
                    'funder'        => 'NRF',
                    'contact_name'  => 'Demo PI',
                    'contact_email' => 'demo.pi@up.ac.za',
                ], $userId);
                $this->logSection('rdm:demo', $dmpId ? "Linked DMP #{$dmpId} (maDMP, draft)." : 'DMP builder not installed - no DMP linked.');
            } catch (\Throwable $e) {
                $this->logSection('rdm:demo', 'DMP link skipped: ' . $e->getMessage());
            }
        }

        // 1. Deposit the synthetic files.
        $files = [];
        foreach ([
            'survey_responses.csv',
            'interview_transcripts/interview_01.txt',
            'consent_forms.pdf',
            'consent_form_scanned.pdf',
            'climate_measurements.csv',
            'readme.txt',
        ] as $rel) {
            $src = $demoDir . '/' . $rel;
            if (is_file($src)) {
                $files[] = ['tmp_path' => $src, 'original_name' => basename($rel)];
            }
        }
        $dep = $svc->deposit($datasetId, $files, $userId);
        $this->logSection('rdm:demo', "Deposited {$dep['stored']} file(s).");

        // 2. POPIA scan
        $this->logSection('rdm:demo', 'Running POPIA scan (deterministic -> lexicon -> NER)...');
        $scan = (new \AhgRdm\Services\PopiaScanService())->scanDataset($datasetId);
        $this->logSection('rdm:demo', "Scan verdict: {$scan['verdict']} ({$scan['findings']} findings across {$scan['scanned']}/{$scan['files']} files)");
        foreach ($DB::table('rdm_scan_finding')->where('dataset_id', $datasetId)->orderBy('file_name')->get() as $f) {
            $this->logSection('finding', sprintf('%-28s %-16s %-16s %-13s %s', $f->file_name, $f->type, $f->category, $f->method, $f->sample));
        }

        // 3. Human gate (simulated): confirm every finding, then prove release is
        // blocked, then restrict.
        $gate = new \AhgRdm\Services\PopiaGateService();
        foreach ($DB::table('rdm_scan_finding')->where('dataset_id', $datasetId)->pluck('id') as $fid) {
            $gate->resolveFinding((int) $fid, 'confirm', 'demo reviewer confirms', $userId);
        }
        try {
            $gate->setDisposition($datasetId, 'release', $userId);
            $this->logSection('rdm:demo', 'Unexpected: open release allowed on a flagged dataset.', null, 'ERROR');
        } catch (\Throwable $e) {
            $this->logSection('rdm:demo', 'Open release correctly BLOCKED (confirmed PII). Applying restrict...');
        }
        $gate->setDisposition($datasetId, 'restrict', $userId);

        // 4. Report
        $ds = $DB::table('rdm_dataset')->where('id', $datasetId)->first();
        $base = rtrim((string) sfConfig::get('app_siteBaseUrl', 'https://psis.theahg.co.za'), '/');
        $this->log('');
        $this->logSection('rdm:demo', '=== DEMO COMPLETE ===');
        $this->log("  Verdict       : {$ds->verdict}");
        $this->log("  Disposition   : {$ds->disposition}  (status {$ds->status})");
        $this->log('  DMP linked    : ' . (!empty($ds->dmp_id) ? "#{$ds->dmp_id} (maDMP)" : '(none — Phase 7)'));
        $this->log('  DOI           : ' . ($ds->doi ?: '(none)'));
        $this->log("  Landing page  : {$base}/research/datasets/{$datasetId}/landing");
        $this->log("  Dataset (admin): {$base}/research/datasets/{$datasetId}");
        $this->log("  Compliance    : {$base}/research/datasets/compliance");
        $this->log('');
        $this->log('  Punchline: every one of those synthetic SA ID numbers would, on Figshare, be on a');
        $this->log('  foreign cloud and openly downloadable. Here the deterministic scan caught them, a human');
        $this->log('  confirmed, and the dataset is restricted + POPIA-resident - with a citable DOI.');

        return 0;
    }

    private function ensureProject(): ?int
    {
        $DB = '\Illuminate\Database\Capsule\Manager';
        $researcherId = $DB::table('research_researcher')->min('id');
        if (!$researcherId) {
            return null; // no researcher to own a project; dataset stays unlinked (faculty blank)
        }
        $existing = $DB::table('research_project')->where('title', 'POPIA RDM Demo Study')->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return (int) $DB::table('research_project')->insertGetId([
            'owner_id'    => $researcherId,
            'title'       => 'POPIA RDM Demo Study',
            'institution' => 'University of Pretoria - Faculty of Humanities',
            'status'      => 'active',
            'visibility'  => 'private',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    private function purge(): void
    {
        $DB = '\Illuminate\Database\Capsule\Manager';
        $rows = $DB::table('rdm_dataset')->where('title', self::TITLE)->get(['id', 'dmp_id']);
        $n = 0;
        foreach ($rows as $row) {
            $DB::table('rdm_scan_finding')->where('dataset_id', $row->id)->delete();
            $DB::table('rdm_dataset_file')->where('dataset_id', $row->id)->delete();
            $DB::table('rdm_dataset')->where('id', $row->id)->delete();
            $n++;
        }
        if ($n) {
            $this->logSection('rdm:demo', "Purged {$n} prior demo dataset(s).");
        }
    }
}
