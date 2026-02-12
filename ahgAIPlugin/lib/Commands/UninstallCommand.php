<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\SymfonyBridgeCommand;

class UninstallCommand extends SymfonyBridgeCommand
{
    protected string $name = 'ai:uninstall';
    protected string $description = 'Uninstall ahgAIPlugin';
    protected string $symfonyTask = 'ai:uninstall';
}
