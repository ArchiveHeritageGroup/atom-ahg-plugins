<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\SymfonyBridgeCommand;

class PackageCommand extends SymfonyBridgeCommand
{
    protected string $name = 'preservation:package';
    protected string $description = 'Manage OAIS preservation packages';
    protected string $symfonyTask = 'preservation:package';
}
