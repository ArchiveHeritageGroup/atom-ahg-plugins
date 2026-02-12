<?php

namespace AtomFramework\Console\Commands\Embargo;

use AtomFramework\Console\SymfonyBridgeCommand;

class ProcessCommand extends SymfonyBridgeCommand
{
    protected string $name = 'embargo:process';
    protected string $description = 'Process embargo expiry: auto-lift and send notifications';
    protected string $symfonyTask = 'embargo:process';
}
