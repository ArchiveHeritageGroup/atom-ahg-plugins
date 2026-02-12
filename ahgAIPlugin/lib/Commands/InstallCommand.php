<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\SymfonyBridgeCommand;

class InstallCommand extends SymfonyBridgeCommand
{
    protected string $name = 'ai:install';
    protected string $description = 'Install ahgAIPlugin database tables';
    protected string $symfonyTask = 'ai:install';
}
