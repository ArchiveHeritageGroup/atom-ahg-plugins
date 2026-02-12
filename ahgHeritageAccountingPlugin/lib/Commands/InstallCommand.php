<?php

namespace AtomFramework\Console\Commands\Heritage;

use AtomFramework\Console\SymfonyBridgeCommand;

class InstallCommand extends SymfonyBridgeCommand
{
    protected string $name = 'heritage:install';
    protected string $description = 'Install heritage accounting database schema';
    protected string $symfonyTask = 'heritage:install';
}
