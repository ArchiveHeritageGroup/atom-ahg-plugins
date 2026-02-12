<?php

namespace AtomFramework\Console\Commands\DataMigration;

use AtomFramework\Console\SymfonyBridgeCommand;

class PreservicaExportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'preservica:export';
    protected string $description = 'Export data to Preservica OPEX or PAX format';
    protected string $symfonyTask = 'preservica:export';
}
