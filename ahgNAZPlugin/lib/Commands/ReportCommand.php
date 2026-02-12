<?php

namespace AtomFramework\Console\Commands\Naz;

use AtomFramework\Console\SymfonyBridgeCommand;

class ReportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'naz:report';
    protected string $description = 'Generate NAZ compliance reports';
    protected string $symfonyTask = 'naz:report';
}
