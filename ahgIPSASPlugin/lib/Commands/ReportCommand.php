<?php

namespace AtomFramework\Console\Commands\Ipsas;

use AtomFramework\Console\SymfonyBridgeCommand;

class ReportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'ipsas:report';
    protected string $description = 'Generate IPSAS heritage asset reports';
    protected string $symfonyTask = 'ipsas:report';
}
