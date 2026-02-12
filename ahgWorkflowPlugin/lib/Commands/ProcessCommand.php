<?php

namespace AtomFramework\Console\Commands\Workflow;

use AtomFramework\Console\SymfonyBridgeCommand;

class ProcessCommand extends SymfonyBridgeCommand
{
    protected string $name = 'workflow:process';
    protected string $description = 'Process workflow operations (notifications, escalation, cleanup)';
    protected string $symfonyTask = 'workflow:process';
}
