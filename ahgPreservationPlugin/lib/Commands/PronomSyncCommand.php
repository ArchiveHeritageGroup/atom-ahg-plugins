<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\SymfonyBridgeCommand;

class PronomSyncCommand extends SymfonyBridgeCommand
{
    protected string $name = 'preservation:pronom-sync';
    protected string $description = 'Sync format registry from PRONOM (UK National Archives)';
    protected string $symfonyTask = 'preservation:pronom-sync';
}
