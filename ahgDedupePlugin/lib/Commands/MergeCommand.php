<?php

namespace AtomFramework\Console\Commands\Dedupe;

use AtomFramework\Console\SymfonyBridgeCommand;

class MergeCommand extends SymfonyBridgeCommand
{
    protected string $name = 'dedupe:merge';
    protected string $description = 'Merge duplicate records';
    protected string $symfonyTask = 'dedupe:merge';
}
