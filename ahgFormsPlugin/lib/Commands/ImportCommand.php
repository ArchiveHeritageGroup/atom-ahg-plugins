<?php

namespace AtomFramework\Console\Commands\Forms;

use AtomFramework\Console\SymfonyBridgeCommand;

class ImportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'forms:import';
    protected string $description = 'Import form definitions';
    protected string $symfonyTask = 'forms:import';
}
