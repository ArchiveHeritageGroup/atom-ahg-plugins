<?php

/**
 * CLI task to show Preservica format information and field mappings.
 *
 * Usage:
 *   php symfony preservica:info
 *   php symfony preservica:info --format=opex
 *   php symfony preservica:info --show-fields
 */
class preservicaInfoTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_REQUIRED, 'Show info for specific format: opex or xip'),
            new sfCommandOption('show-fields', null, sfCommandOption::PARAMETER_NONE, 'Show all available field mappings'),
            new sfCommandOption('show-atom-fields', null, sfCommandOption::PARAMETER_NONE, 'Show all AtoM target fields'),
        ]);

        $this->namespace = 'preservica';
        $this->name = 'info';
        $this->briefDescription = 'Show Preservica format information and field mappings';
        $this->detailedDescription = <<<EOF
The [preservica:info|INFO] task displays information about supported Preservica
formats and available field mappings.

Examples:
  [php symfony preservica:info|INFO]
  [php symfony preservica:info --format=opex|INFO]
  [php symfony preservica:info --show-fields|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        // Load framework
        \AhgCore\Core\AhgDb::init();

        $this->logSection('preservica', '');
        $this->logSection('preservica', '╔══════════════════════════════════════════════════════════════╗');
        $this->logSection('preservica', '║           PRESERVICA DATA MIGRATION SUPPORT                   ║');
        $this->logSection('preservica', '╚══════════════════════════════════════════════════════════════╝');
        $this->logSection('preservica', '');

        // Show format information
        if (!$options['format'] || $options['format'] === 'opex') {
            $this->logSection('preservica', '┌─────────────────────────────────────────────────────────────┐');
            $this->logSection('preservica', '│ OPEX (Open Preservation Exchange) Format                    │');
            $this->logSection('preservica', '├─────────────────────────────────────────────────────────────┤');
            $this->logSection('preservica', '│ • XML-based metadata format                                 │');
            $this->logSection('preservica', '│ • Uses Dublin Core elements (dc: and dcterms:)              │');
            $this->logSection('preservica', '│ • Includes fixity/checksum information                      │');
            $this->logSection('preservica', '│ • Security descriptors for access control                   │');
            $this->logSection('preservica', '│ • File extension: .opex                                     │');
            $this->logSection('preservica', '└─────────────────────────────────────────────────────────────┘');
            $this->logSection('preservica', '');
        }

        if (!$options['format'] || $options['format'] === 'xip') {
            $this->logSection('preservica', '┌─────────────────────────────────────────────────────────────┐');
            $this->logSection('preservica', '│ XIP/PAX (Preservica Archive eXchange) Format                │');
            $this->logSection('preservica', '├─────────────────────────────────────────────────────────────┤');
            $this->logSection('preservica', '│ • ZIP package containing XIP metadata + content files       │');
            $this->logSection('preservica', '│ • Structural Objects (archival hierarchy)                   │');
            $this->logSection('preservica', '│ • Content Objects (digital objects)                         │');
            $this->logSection('preservica', '│ • Representations and Generations                           │');
            $this->logSection('preservica', '│ • Embedded Dublin Core metadata                             │');
            $this->logSection('preservica', '│ • File extension: .pax                                      │');
            $this->logSection('preservica', '└─────────────────────────────────────────────────────────────┘');
            $this->logSection('preservica', '');
        }

        // Show field mappings
        if ($options['show-fields']) {
            $this->logSection('preservica', '┌─────────────────────────────────────────────────────────────┐');
            $this->logSection('preservica', '│ OPEX → AtoM Field Mappings                                  │');
            $this->logSection('preservica', '├────────────────────────────┬────────────────────────────────┤');
            $this->logSection('preservica', '│ OPEX Field                 │ AtoM Field                     │');
            $this->logSection('preservica', '├────────────────────────────┼────────────────────────────────┤');

            $opexMapping = \ahgDataMigrationPlugin\Mappings\PreservicaMapping::getOpexToAtomMapping();
            foreach ($opexMapping as $source => $target) {
                $source = str_pad($source, 26);
                $target = str_pad($target, 30);
                $this->logSection('preservica', "│ {$source} │ {$target} │");
            }
            $this->logSection('preservica', '└────────────────────────────┴────────────────────────────────┘');
            $this->logSection('preservica', '');

            $this->logSection('preservica', '┌─────────────────────────────────────────────────────────────┐');
            $this->logSection('preservica', '│ XIP → AtoM Field Mappings                                   │');
            $this->logSection('preservica', '├────────────────────────────┬────────────────────────────────┤');
            $this->logSection('preservica', '│ XIP Field                  │ AtoM Field                     │');
            $this->logSection('preservica', '├────────────────────────────┼────────────────────────────────┤');

            $xipMapping = \ahgDataMigrationPlugin\Mappings\PreservicaMapping::getXipToAtomMapping();
            foreach ($xipMapping as $source => $target) {
                $source = str_pad($source, 26);
                $target = str_pad($target, 30);
                $this->logSection('preservica', "│ {$source} │ {$target} │");
            }
            $this->logSection('preservica', '└────────────────────────────┴────────────────────────────────┘');
            $this->logSection('preservica', '');
        }

        // Show AtoM fields
        if ($options['show-atom-fields']) {
            $this->logSection('preservica', '┌─────────────────────────────────────────────────────────────┐');
            $this->logSection('preservica', '│ Available AtoM Target Fields                                │');
            $this->logSection('preservica', '├────────────────────────────┬────────────────────────────────┤');
            $this->logSection('preservica', '│ Field Key                  │ Display Name                   │');
            $this->logSection('preservica', '├────────────────────────────┼────────────────────────────────┤');

            $atomFields = \ahgDataMigrationPlugin\Mappings\PreservicaMapping::getAtomTargetFields();
            foreach ($atomFields as $key => $name) {
                $key = str_pad($key, 26);
                $name = str_pad($name, 30);
                $this->logSection('preservica', "│ {$key} │ {$name} │");
            }
            $this->logSection('preservica', '└────────────────────────────┴────────────────────────────────┘');
            $this->logSection('preservica', '');
        }

        // Show usage examples
        $this->logSection('preservica', '┌─────────────────────────────────────────────────────────────┐');
        $this->logSection('preservica', '│ Usage Examples                                              │');
        $this->logSection('preservica', '├─────────────────────────────────────────────────────────────┤');
        $this->logSection('preservica', '│ IMPORT:                                                     │');
        $this->logSection('preservica', '│   php symfony preservica:import /path/to/file.opex         │');
        $this->logSection('preservica', '│   php symfony preservica:import /path/to/pkg.pax --format=xip │');
        $this->logSection('preservica', '│   php symfony preservica:import /path/dir --batch          │');
        $this->logSection('preservica', '│                                                             │');
        $this->logSection('preservica', '│ EXPORT:                                                     │');
        $this->logSection('preservica', '│   php symfony preservica:export 123                        │');
        $this->logSection('preservica', '│   php symfony preservica:export 123 --format=xip           │');
        $this->logSection('preservica', '│   php symfony preservica:export 123 --hierarchy            │');
        $this->logSection('preservica', '│   php symfony preservica:export --repository=5             │');
        $this->logSection('preservica', '└─────────────────────────────────────────────────────────────┘');
        $this->logSection('preservica', '');

        return 0;
    }
}
