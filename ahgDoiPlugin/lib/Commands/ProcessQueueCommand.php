<?php

namespace AtomFramework\Console\Commands\Doi;

use AtomFramework\Console\SymfonyBridgeCommand;

class ProcessQueueCommand extends SymfonyBridgeCommand
{
    protected string $name = 'doi:process-queue';
    protected string $description = 'Process pending DOI queue';
    protected string $symfonyTask = 'doi:process-queue';
}
