<?php

namespace AtomFramework\Console\Commands\Display;

use AtomFramework\Console\SymfonyBridgeCommand;

class ReindexCommand extends SymfonyBridgeCommand
{
    protected string $name = 'display:reindex';
    protected string $description = 'Reindex display objects';
    protected string $symfonyTask = 'display:reindex';
}
