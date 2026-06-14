<?php

/**
 * CLI task to import a SKOS RDF/XML file into a taxonomy.
 *
 * --dry-run parses + reports what WOULD be created, performing NO database
 * writes (safe to run for verification). Without --dry-run it creates terms.
 *
 * Usage:
 *   php symfony skos:import --taxonomy-id=42 --file=/path/vocab.rdf --dry-run
 *   php symfony skos:import --taxonomy-id=42 --file=/path/vocab.rdf
 */
class skosImportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('taxonomy-id', null, sfCommandOption::PARAMETER_REQUIRED, 'Target taxonomy id'),
            new sfCommandOption('file', null, sfCommandOption::PARAMETER_REQUIRED, 'Path to a SKOS RDF/XML file'),
            new sfCommandOption('culture', null, sfCommandOption::PARAMETER_OPTIONAL, 'Culture/language code', 'en'),
            new sfCommandOption('parent-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Default parent term id for concepts with external/no broader'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Parse and report only; make no database writes'),
        ]);

        $this->namespace = 'skos';
        $this->name = 'import';
        $this->briefDescription = 'Import a SKOS RDF/XML file into a taxonomy';
        $this->detailedDescription = <<<'EOF'
Import SKOS concepts (prefLabel, altLabels, scopeNote, broader hierarchy) from
an RDF/XML file into a taxonomy. Use --dry-run first to preview with no writes.

  php symfony skos:import --taxonomy-id=42 --file=vocab.rdf --dry-run
  php symfony skos:import --taxonomy-id=42 --file=vocab.rdf
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        $taxonomyId = (int) ($options['taxonomy-id'] ?? 0);
        if ($taxonomyId < 1) {
            throw new sfCommandException('--taxonomy-id is required and must be a positive integer.');
        }
        $file = (string) ($options['file'] ?? '');
        if ($file === '' || !is_readable($file)) {
            throw new sfCommandException('--file is required and must be a readable path.');
        }
        $culture = $options['culture'] ?: 'en';
        $parentId = !empty($options['parent-id']) ? (int) $options['parent-id'] : null;
        $dryRun = (bool) ($options['dry-run'] ?? false);

        $service = new \AhgTermTaxonomy\Services\SkosImportService($culture);
        $concepts = $service->parse(file_get_contents($file));
        $this->logSection('skos', sprintf('Parsed %d concept(s) from %s', count($concepts), basename($file)));

        $report = $service->import($taxonomyId, $concepts, $parentId, $dryRun);

        if ($dryRun) {
            $this->logSection('skos', sprintf(
                'DRY RUN — would create %d, skip %d (already present) in taxonomy %d',
                $report['wouldCreate'],
                $report['skipped'],
                $taxonomyId
            ));
        } else {
            $this->logSection('skos', sprintf(
                'Imported %d, skipped %d into taxonomy %d',
                $report['created'],
                $report['skipped'],
                $taxonomyId
            ));
            $this->logSection('skos', 'Tip: run "php bin/atom propel:build-nested-set" if term tree ordering looks off.');
        }

        foreach ($report['errors'] as $err) {
            $this->logSection('skos', $err, null, 'ERROR');
        }
    }
}
