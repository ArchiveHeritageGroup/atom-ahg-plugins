<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\SymfonyBridgeCommand;

class SchedulerCommand extends SymfonyBridgeCommand
{
    protected string $name = 'preservation:scheduler';
    protected string $description = 'Run scheduled preservation workflows';
    protected string $symfonyTask = 'preservation:scheduler';
}
