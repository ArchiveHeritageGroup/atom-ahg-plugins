<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\SymfonyBridgeCommand;

class SyncEntityCacheCommand extends SymfonyBridgeCommand
{
    protected string $name = 'ai:sync-entity-cache';
    protected string $description = 'Sync approved NER entities to heritage discovery cache';
    protected string $symfonyTask = 'ai:sync-entity-cache';
}
