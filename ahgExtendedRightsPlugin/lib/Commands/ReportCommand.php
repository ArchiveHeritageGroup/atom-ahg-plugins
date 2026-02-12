<?php

namespace AtomFramework\Console\Commands\Embargo;

use AtomFramework\Console\SymfonyBridgeCommand;

class ReportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'embargo:report';
    protected string $description = 'Generate embargo reports';
    protected string $symfonyTask = 'embargo:report';
}
