<?php

namespace AtomFramework\Console\Commands\Dedupe;

use AtomFramework\Console\SymfonyBridgeCommand;

class ReportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'dedupe:report';
    protected string $description = 'Generate deduplication reports';
    protected string $symfonyTask = 'dedupe:report';
}
