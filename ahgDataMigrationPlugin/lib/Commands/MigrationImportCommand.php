<?php

namespace AtomFramework\Console\Commands\DataMigration;

use AtomFramework\Console\SymfonyBridgeCommand;

class MigrationImportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'migration:import';
    protected string $description = 'Import data using saved field mappings';
    protected string $symfonyTask = 'migration:import';
}
