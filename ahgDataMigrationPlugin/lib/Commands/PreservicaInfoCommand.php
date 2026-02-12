<?php

namespace AtomFramework\Console\Commands\DataMigration;

use AtomFramework\Console\BaseCommand;

/**
 * CLI command to show Preservica format information and field mappings.
 */
class PreservicaInfoCommand extends BaseCommand
{
    protected string $name = 'preservica:info';
    protected string $description = 'Show Preservica format information and field mappings';

    protected string $detailedDescription = <<<'EOF'
    Display information about supported Preservica formats and available
    field mappings for OPEX and XIP/PAX formats.

    Examples:
      php bin/atom preservica:info
      php bin/atom preservica:info --format=opex
      php bin/atom preservica:info --show-fields
      php bin/atom preservica:info --show-atom-fields
    EOF;

    protected function configure(): void
    {
        $this->addOption('format', null, 'Show info for specific format: opex or xip');
        $this->addOption('show-fields', null, 'Show all available field mappings');
        $this->addOption('show-atom-fields', null, 'Show all AtoM target fields');
    }

    protected function handle(): int
    {
        $formatOpt = $this->option('format');

        $this->newline();
        $this->line('========================================================');
        $this->line('           PRESERVICA DATA MIGRATION SUPPORT             ');
        $this->line('========================================================');
        $this->newline();

        if (!$formatOpt || $formatOpt === 'opex') {
            $this->info('OPEX (Open Preservation Exchange) Format');
            $this->line('  - XML-based metadata format');
            $this->line('  - Uses Dublin Core elements (dc: and dcterms:)');
            $this->line('  - Includes fixity/checksum information');
            $this->line('  - Security descriptors for access control');
            $this->line('  - File extension: .opex');
            $this->newline();
        }

        if (!$formatOpt || $formatOpt === 'xip') {
            $this->info('XIP/PAX (Preservica Archive eXchange) Format');
            $this->line('  - ZIP package containing XIP metadata + content files');
            $this->line('  - Structural Objects (archival hierarchy)');
            $this->line('  - Content Objects (digital objects)');
            $this->line('  - Representations and Generations');
            $this->line('  - Embedded Dublin Core metadata');
            $this->line('  - File extension: .pax');
            $this->newline();
        }

        if ($this->hasOption('show-fields')) {
            $pluginPath = $this->getAtomRoot() . '/plugins/ahgDataMigrationPlugin';
            $mappingFile = $pluginPath . '/lib/Mappings/PreservicaMapping.php';
            if (file_exists($mappingFile)) {
                require_once $mappingFile;
            }

            $this->info('OPEX -> AtoM Field Mappings');
            $this->line(str_repeat('-', 60));

            $opexMapping = \ahgDataMigrationPlugin\Mappings\PreservicaMapping::getOpexToAtomMapping();
            foreach ($opexMapping as $source => $target) {
                $this->line(sprintf('  %-28s -> %s', $source, $target));
            }
            $this->newline();

            $this->info('XIP -> AtoM Field Mappings');
            $this->line(str_repeat('-', 60));

            $xipMapping = \ahgDataMigrationPlugin\Mappings\PreservicaMapping::getXipToAtomMapping();
            foreach ($xipMapping as $source => $target) {
                $this->line(sprintf('  %-28s -> %s', $source, $target));
            }
            $this->newline();
        }

        if ($this->hasOption('show-atom-fields')) {
            $pluginPath = $this->getAtomRoot() . '/plugins/ahgDataMigrationPlugin';
            $mappingFile = $pluginPath . '/lib/Mappings/PreservicaMapping.php';
            if (file_exists($mappingFile)) {
                require_once $mappingFile;
            }

            $this->info('Available AtoM Target Fields');
            $this->line(str_repeat('-', 60));

            $atomFields = \ahgDataMigrationPlugin\Mappings\PreservicaMapping::getAtomTargetFields();
            foreach ($atomFields as $key => $fieldName) {
                $this->line(sprintf('  %-28s %s', $key, $fieldName));
            }
            $this->newline();
        }

        $this->info('Usage Examples');
        $this->line(str_repeat('-', 60));
        $this->line('  IMPORT:');
        $this->line('    php bin/atom preservica:import /path/to/file.opex');
        $this->line('    php bin/atom preservica:import /path/to/pkg.pax --format=xip');
        $this->line('    php bin/atom preservica:import /path/dir --batch');
        $this->newline();
        $this->line('  EXPORT:');
        $this->line('    php bin/atom preservica:export 123');
        $this->line('    php bin/atom preservica:export 123 --format=xip');
        $this->line('    php bin/atom preservica:export 123 --hierarchy');
        $this->line('    php bin/atom preservica:export --repository=5');
        $this->newline();

        return 0;
    }
}
