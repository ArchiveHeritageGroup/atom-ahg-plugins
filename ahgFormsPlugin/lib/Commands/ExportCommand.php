<?php

namespace AtomFramework\Console\Commands\Forms;

use AtomFramework\Console\SymfonyBridgeCommand;

class ExportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'forms:export';
    protected string $description = 'Export form definitions';
    protected string $symfonyTask = 'forms:export';
}
