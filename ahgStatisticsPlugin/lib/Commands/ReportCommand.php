<?php

namespace AtomFramework\Console\Commands\Statistics;

use AtomFramework\Console\SymfonyBridgeCommand;

class ReportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'statistics:report';
    protected string $description = 'Generate statistics reports';
    protected string $symfonyTask = 'statistics:report';
}
