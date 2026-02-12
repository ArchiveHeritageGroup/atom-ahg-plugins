<?php

namespace AtomFramework\Console\Commands\Library;

use AtomFramework\Console\SymfonyBridgeCommand;

class ProcessCoversCommand extends SymfonyBridgeCommand
{
    protected string $name = 'library:process-covers';
    protected string $description = 'Process pending book cover downloads from Open Library';
    protected string $symfonyTask = 'library:process-covers';
}
