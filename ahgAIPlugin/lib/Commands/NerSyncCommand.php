<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\SymfonyBridgeCommand;

class NerSyncCommand extends SymfonyBridgeCommand
{
    protected string $name = 'ai:ner-sync';
    protected string $description = 'Sync NER corrections to training server';
    protected string $symfonyTask = 'ai:ner-sync';
}
