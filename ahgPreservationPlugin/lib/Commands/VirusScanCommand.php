<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\SymfonyBridgeCommand;

class VirusScanCommand extends SymfonyBridgeCommand
{
    protected string $name = 'preservation:virus-scan';
    protected string $description = 'Scan digital objects for viruses using ClamAV';
    protected string $symfonyTask = 'preservation:virus-scan';
}
