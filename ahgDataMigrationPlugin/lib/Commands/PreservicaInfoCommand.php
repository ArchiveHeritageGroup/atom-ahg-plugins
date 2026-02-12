<?php

namespace AtomFramework\Console\Commands\DataMigration;

use AtomFramework\Console\SymfonyBridgeCommand;

class PreservicaInfoCommand extends SymfonyBridgeCommand
{
    protected string $name = 'preservica:info';
    protected string $description = 'Show Preservica format information and field mappings';
    protected string $symfonyTask = 'preservica:info';
}
