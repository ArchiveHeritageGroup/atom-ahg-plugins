<?php

namespace AtomFramework\Console\Commands\Heritage;

use AtomFramework\Console\SymfonyBridgeCommand;

class RegionCommand extends SymfonyBridgeCommand
{
    protected string $name = 'heritage:region';
    protected string $description = 'Manage heritage accounting regions';
    protected string $symfonyTask = 'heritage:region';
}
