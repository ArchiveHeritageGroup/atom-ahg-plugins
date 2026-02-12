<?php

namespace AtomFramework\Console\Commands\Nmmz;

use AtomFramework\Console\SymfonyBridgeCommand;

class ReportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'nmmz:report';
    protected string $description = 'Generate NMMZ heritage reports';
    protected string $symfonyTask = 'nmmz:report';
}
