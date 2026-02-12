<?php

namespace AtomFramework\Console\Commands\Cdpa;

use AtomFramework\Console\SymfonyBridgeCommand;

class StatusCommand extends SymfonyBridgeCommand
{
    protected string $name = 'cdpa:status';
    protected string $description = 'Check CDPA status';
    protected string $symfonyTask = 'cdpa:status';
}
