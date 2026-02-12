<?php

namespace AtomFramework\Console\Commands\Security;

use AtomFramework\Console\SymfonyBridgeCommand;

class UpdateCacheCommand extends SymfonyBridgeCommand
{
    protected string $name = 'security:update-cache';
    protected string $description = 'Update security clearance cache';
    protected string $symfonyTask = 'security:update-cache';
}
