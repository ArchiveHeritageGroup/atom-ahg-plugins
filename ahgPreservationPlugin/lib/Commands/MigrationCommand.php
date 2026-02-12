<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\SymfonyBridgeCommand;

class MigrationCommand extends SymfonyBridgeCommand
{
    protected string $name = 'preservation:migration';
    protected string $description = 'Format migration planning and obsolescence reporting';
    protected string $symfonyTask = 'preservation:migration';
}
