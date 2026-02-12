<?php

namespace AtomFramework\Console\Commands\DataMigration;

use AtomFramework\Console\SymfonyBridgeCommand;

class PreservicaImportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'preservica:import';
    protected string $description = 'Import data from Preservica OPEX or PAX format';
    protected string $symfonyTask = 'preservica:import';
}
