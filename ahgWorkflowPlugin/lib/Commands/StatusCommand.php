<?php

namespace AtomFramework\Console\Commands\Workflow;

use AtomFramework\Console\SymfonyBridgeCommand;

class StatusCommand extends SymfonyBridgeCommand
{
    protected string $name = 'workflow:status';
    protected string $description = 'View workflow task status and statistics';
    protected string $symfonyTask = 'workflow:status';
}
