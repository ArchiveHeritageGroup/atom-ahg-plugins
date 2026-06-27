<?php

/**
 * Background task for the RDM POPIA scan (atom-ahg-plugins#169).
 *
 * The deterministic detectors are instant, but the NER augmentation calls the
 * AI gateway and can take tens of seconds per file — past php-fpm/nginx request
 * limits. So the web "Run POPIA scan" action launches this task via nohup; the
 * dataset shows 'scanning' until PopiaScanService writes the findings + verdict.
 *
 * Usage:
 *   php symfony rdm:scan --dataset-id=123
 */
class rdmScanTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('dataset-id', null, sfCommandOption::PARAMETER_REQUIRED, 'rdm_dataset.id to scan'),
        ]);

        $this->namespace = 'rdm';
        $this->name = 'scan';
        $this->briefDescription = 'Run the POPIA sensitivity scan for an RDM dataset in the background';
        $this->detailedDescription = <<<'EOF'
The [rdm:scan|INFO] task runs the POPIA sensitivity scan for one dataset.

  [php symfony rdm:scan --dataset-id=123|INFO]

Deterministic detectors (SA ID/email/phone/passport) + special-category lexicon
run always; NER augmentation is best-effort via the AI gateway. Findings land
'pending' for the human gate; the dataset verdict + status are updated.
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $datasetId = (int) ($options['dataset-id'] ?? 0);
        if ($datasetId <= 0) {
            $this->logSection('rdm:scan', 'Missing or invalid --dataset-id', null, 'ERROR');

            return 1;
        }

        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgRdmPlugin';
        require_once $pluginDir . '/lib/Services/PopiaScanService.php';

        try {
            $result = (new \AhgRdm\Services\PopiaScanService())->scanDataset($datasetId);
            $this->logSection('rdm:scan', sprintf(
                'dataset %d: verdict=%s findings=%d scanned=%d/%d files',
                $datasetId,
                $result['verdict'],
                $result['findings'],
                $result['scanned'],
                $result['files']
            ));

            return 0;
        } catch (\Throwable $e) {
            // Don't leave the dataset stuck on 'scanning'.
            try {
                \Illuminate\Database\Capsule\Manager::table('rdm_dataset')
                    ->where('id', $datasetId)
                    ->update(['status' => 'review', 'updated_at' => date('Y-m-d H:i:s')]);
            } catch (\Throwable $ignore) {
            }
            $this->logSection('rdm:scan', 'scan failed for dataset ' . $datasetId . ': ' . $e->getMessage(), null, 'ERROR');

            return 1;
        }
    }
}
