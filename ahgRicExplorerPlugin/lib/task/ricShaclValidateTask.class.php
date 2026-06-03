<?php

/**
 * RiC SHACL Validate Task
 *
 * Validates the generated RiC-O graph against the RiC-O SHACL shapes
 * (tools/ric_shacl_shapes.ttl) using the bundled pyshacl wrapper, persists a
 * structured report to ric_shacl_report, and prints a summary. Degrades
 * gracefully when the SHACL engine (python3 + pyshacl/rdflib) is absent.
 *
 * Usage:
 *   php symfony ric:shacl-validate                  # Validate whole triplestore
 *   php symfony ric:shacl-validate --graph=URI      # Validate a named graph
 *   php symfony ric:shacl-validate --status         # Report engine availability only
 *
 * @package    ahgRicExplorerPlugin
 */
class ricShaclValidateTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('graph', null, sfCommandOption::PARAMETER_OPTIONAL, 'Named graph URI to validate', null),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_NONE, 'Show SHACL engine availability only'),
        ]);

        $this->namespace = 'ric';
        $this->name = 'shacl-validate';
        $this->briefDescription = 'Validate the RiC-O graph against RiC-O SHACL shapes';
        $this->detailedDescription = <<<'EOF'
Validates RiC-O linked data against the RiC-O SHACL shapes and stores a report.

Requires python3 with pyshacl and rdflib for full validation. When these are
not available the task falls back gracefully and records an unverified report.
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        // Bootstrap Laravel DB (Capsule) used by the service.
        $bootstrapFile = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrapFile)) {
            require_once $bootstrapFile;
        }

        // Namespaced plugin classes are loaded via require_once + new.
        $serviceFile = sfConfig::get('sf_root_dir')
            . '/atom-ahg-plugins/ahgRicExplorerPlugin/lib/Services/ShaclValidationService.class.php';
        if (!file_exists($serviceFile)) {
            $this->logSection('ric', 'ERROR: ShaclValidationService not found at ' . $serviceFile, null, 'ERROR');

            return 1;
        }
        require_once $serviceFile;

        $service = new \AhgRicExplorer\Services\ShaclValidationService();

        $status = $service->engineStatus();
        $this->logSection('ric', 'SHACL engine: ' . ($status['available'] ? 'AVAILABLE' : 'UNAVAILABLE') . ' (' . $status['reason'] . ')');

        if ($options['status']) {
            return $status['available'] ? 0 : 1;
        }

        $graph = !empty($options['graph']) ? $options['graph'] : null;
        $this->logSection('ric', 'Validating graph: ' . ($graph ?: 'ALL'));

        $report = $service->validateGraph($graph);

        $conforms = $report['conforms'];
        $this->logSection('ric', 'Engine: ' . $report['engine']);
        $this->logSection('ric', 'Data triples: ' . $report['data_triples']);

        if (null === $conforms) {
            $this->logSection('ric', 'Result: NOT VERIFIED - ' . $report['reason'], null, 'COMMENT');
        } elseif ($conforms) {
            $this->logSection('ric', 'Result: CONFORMS - data is valid against RiC-O shapes');
        } else {
            $stats = $report['statistics'];
            $sev = $stats['by_severity'] ?? [];
            $this->logSection('ric', sprintf(
                'Result: DOES NOT CONFORM - %d issue(s): %d violation(s), %d warning(s), %d info',
                (int) ($stats['total_violations'] ?? 0),
                (int) ($sev['Violation'] ?? 0),
                (int) ($sev['Warning'] ?? 0),
                (int) ($sev['Info'] ?? 0)
            ), null, 'ERROR');
        }

        $this->logSection('ric', 'Report saved: ric_shacl_report id=' . ($report['report_id'] ?? 0));

        return (false === $conforms) ? 1 : 0;
    }
}
