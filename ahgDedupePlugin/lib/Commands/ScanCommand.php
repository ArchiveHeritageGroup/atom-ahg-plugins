<?php

namespace AtomFramework\Console\Commands\Dedupe;

use AtomFramework\Console\SymfonyBridgeCommand;

class ScanCommand extends SymfonyBridgeCommand
{
    protected string $name = 'dedupe:scan';
    protected string $description = 'Scan for duplicate records';
    protected string $symfonyTask = 'dedupe:scan';
}
