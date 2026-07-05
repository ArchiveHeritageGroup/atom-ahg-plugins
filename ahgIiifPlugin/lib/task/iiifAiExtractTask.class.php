<?php

/**
 * CLI task for IIIF AI Extract (#220).
 *
 * Runs a region-scoped VLM extraction task over one object's canvases via the
 * AHG AI gateway and stores the result (draft) in iiif_ai_extract.
 */
class iiifAiExtractTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('object-id', null, sfCommandOption::PARAMETER_REQUIRED, 'Information object ID'),
            new sfCommandOption('canvas-index', null, sfCommandOption::PARAMETER_OPTIONAL, 'Canvas index (0-based)', 0),
            new sfCommandOption('region', null, sfCommandOption::PARAMETER_OPTIONAL, "IIIF region: 'full' or x,y,w,h", 'full'),
            new sfCommandOption('task', null, sfCommandOption::PARAMETER_OPTIONAL, 'caption|describe|transcribe|entities|tags', 'describe'),
            new sfCommandOption('all-canvases', null, sfCommandOption::PARAMETER_NONE, 'Run on every canvas of the object'),
        ]);
        $this->namespace = 'iiif';
        $this->name = 'ai-extract';
        $this->briefDescription = 'Region-scoped VLM extraction over IIIF canvases (#220)';
        $this->detailedDescription = <<<EOD
The [iiif:ai-extract|INFO] task runs an AI vision extraction task over a
digital object's IIIF canvases (via the AHG AI gateway) and stores the result.

Examples:
  [php symfony iiif:ai-extract --object-id=902722 --task=describe|INFO]
  [php symfony iiif:ai-extract --object-id=902722 --task=transcribe --region=100,100,800,400|INFO]
  [php symfony iiif:ai-extract --object-id=902722 --task=tags --all-canvases|INFO]
EOD;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $objectId = (int) ($options['object-id'] ?? 0);
        if (!$objectId) {
            $this->logSection('iiif', 'Missing --object-id', null, 'ERROR');

            return 1;
        }

        require_once sfConfig::get('sf_plugins_dir') . '/ahgIiifPlugin/lib/Services/IiifAiExtractService.php';
        $service = new \AhgIiif\Services\IiifAiExtractService();

        $task = (string) $options['task'];
        $canvases = $service->listCanvases($objectId);
        if (empty($canvases)) {
            $this->logSection('iiif', "No image canvases for object {$objectId}", null, 'ERROR');

            return 1;
        }

        $indexes = !empty($options['all-canvases'])
            ? array_column($canvases, 'index')
            : [(int) $options['canvas-index']];

        $ok = 0;
        foreach ($indexes as $idx) {
            $result = $service->extractRegion($objectId, (int) $idx, (string) $options['region'], $task, null);
            if (!empty($result['success'])) {
                $ok++;
                $preview = mb_substr((string) $result['text'], 0, 120);
                $this->logSection('iiif', sprintf('canvas %d [%s] #%d: %s', $idx, $task, $result['extract_id'], $preview));
            } else {
                $this->logSection('iiif', sprintf('canvas %d [%s] FAILED: %s', $idx, $task, $result['error']), null, 'ERROR');
            }
        }

        $this->logSection('iiif', sprintf('Done: %d/%d canvases extracted', $ok, count($indexes)));

        return $ok > 0 ? 0 : 1;
    }
}
