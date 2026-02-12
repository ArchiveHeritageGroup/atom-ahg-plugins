<?php

namespace AtomFramework\Console\Commands\Cdpa;

use AtomFramework\Console\SymfonyBridgeCommand;

class ReportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'cdpa:report';
    protected string $description = 'Generate CDPA reports';
    protected string $symfonyTask = 'cdpa:report';
}
