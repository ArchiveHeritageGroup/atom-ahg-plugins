<?php

/**
 * CLI Task: authority:ner-pipeline
 *
 * Process NER entities into authority stubs.
 * Run after NER extraction or daily.
 *
 * Usage:
 *   php symfony authority:ner-pipeline
 *   php symfony authority:ner-pipeline --dry-run
 *   php symfony authority:ner-pipeline --threshold=0.90
 */
class authorityNerPipelineTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be created without actually creating'),
            new sfCommandOption('threshold', null, sfCommandOption::PARAMETER_OPTIONAL, 'Minimum confidence threshold', '0.85'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Max entities to process', 100),
        ]);

        $this->namespace = 'authority';
        $this->name = 'ner-pipeline';
        $this->briefDescription = 'Create authority stubs from NER entities';
        $this->detailedDescription = <<<'EOF'
Processes pending NER entities and creates stub authority records
for persons, organizations, and places above the confidence threshold.

  php symfony authority:ner-pipeline
  php symfony authority:ner-pipeline --dry-run
  php symfony authority:ner-pipeline --threshold=0.90
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/src/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAuthorityPlugin/lib/Services/AuthorityNerPipelineService.php';

        $service = new \AhgAuthority\Services\AuthorityNerPipelineService();
        $dryRun = isset($options['dry-run']) && $options['dry-run'];
        $threshold = (float) $options['threshold'];
        $limit = (int) $options['limit'];

        $this->logSection('authority', sprintf('NER pipeline: threshold=%.2f, limit=%d, dry-run=%s',
            $threshold, $limit, $dryRun ? 'yes' : 'no'));

        // Get pending entities above threshold
        $pending = $service->getPendingEntities([
            'min_confidence' => $threshold,
            'limit'          => $limit,
        ]);

        $items = $pending['data'] ?? [];
        $created = 0;

        foreach ($items as $entity) {
            $entity = (object) $entity;
            $this->logSection('authority', sprintf('  [%s] %s (confidence: %.2f)',
                $entity->entity_type, $entity->entity_value, $entity->confidence));

            if (!$dryRun) {
                $actorId = $service->createStub((int) $entity->id, 1); // System user
                if ($actorId) {
                    $created++;
                    $this->logSection('authority', sprintf('    -> Created stub actor #%d', $actorId));
                }
            }
        }

        $this->logSection('authority', sprintf('Done: %d entities processed, %d stubs created',
            count($items), $created));
    }
}
