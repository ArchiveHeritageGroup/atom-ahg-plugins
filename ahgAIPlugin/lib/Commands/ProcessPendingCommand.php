<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\SymfonyBridgeCommand;

class ProcessPendingCommand extends SymfonyBridgeCommand
{
    protected string $name = 'ai:process-pending';
    protected string $description = 'Process pending AI extraction queue';
    protected string $symfonyTask = 'ai:process-pending';
}
