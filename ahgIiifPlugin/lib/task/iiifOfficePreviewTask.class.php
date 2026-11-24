<?php

/**
 * Background worker for Office document display copies (#231).
 *
 * Each run finds masters with no current copy (new uploads, replaced files and the
 * backfill of older records), converts a batch and removes copies of deleted masters.
 */
class iiifOfficePreviewTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Documents to convert in this run', 10),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_REQUIRED, 'Convert one master digital object id'),
            new sfCommandOption('retry-failed', null, sfCommandOption::PARAMETER_NONE, 'Queue failed documents again'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Report the queue without converting'),
        ]);
        $this->namespace = 'iiif';
        $this->name = 'office-preview';
        $this->briefDescription = 'Generate PDF display copies of Office documents (#231)';
        $this->detailedDescription = <<<'EOD'
The [iiif:office-preview|INFO] task converts Office masters to a PDF display copy
in a network-isolated sandbox and indexes their text. Run it from cron.

  [php symfony iiif:office-preview --limit=10|INFO]
  [php symfony iiif:office-preview --id=12345|INFO]
  [php symfony iiif:office-preview --retry-failed|INFO]
  [php symfony iiif:office-preview --dry-run|INFO]
EOD;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        require_once sfConfig::get('sf_plugins_dir').'/ahgIiifPlugin/lib/Services/OfficePreviewService.php';

        if (!\AhgIiif\Services\OfficePreviewService::isInstalled()) {
            $this->logSection('office', 'Table iiif_office_preview is missing. Apply the plugin install SQL.', null, 'ERROR');

            return 1;
        }

        $service = new \AhgIiif\Services\OfficePreviewService();
        $limit = max(1, (int) $options['limit']);
        $onlyId = empty($options['id']) ? null : (int) $options['id'];

        if ($options['dry-run']) {
            $this->logSection('office', count($service->pendingIds(100000)).' queued');

            return 0;
        }

        // Clean-up first: withdrawing redacted text must not wait on the sandbox.
        $service->releaseStale();
        $service->prune();
        $service->withdrawRedactedText();

        $converter = new \AhgIiif\Services\OfficePreviewConverter();
        if (!$converter->sandboxAvailable()) {
            $this->logSection('office', 'Sandbox unavailable: bubblewrap must be able to create a network namespace for this user. Nothing converted.', null, 'ERROR');

            return 1;
        }

        if ($options['retry-failed']) {
            $this->logSection('office', $service->retryFailed().' failed documents queued again');
        }
        if (null !== $onlyId) {
            $service->enqueue($onlyId);
        } else {
            $service->sweep(500);
        }

        $done = 0;
        $failed = 0;
        foreach ($service->pendingIds($limit, $onlyId) as $id) {
            $result = $service->process($id, $converter);
            if ($result['ok']) {
                ++$done;
            } else {
                ++$failed;
                $this->logSection('office', $id.': '.$result['error'], null, 'ERROR');
            }
        }
        $this->logSection('office', "{$done} converted, {$failed} failed");

        return 0;
    }
}
