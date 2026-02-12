<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\SymfonyBridgeCommand;

class ReplicateCommand extends SymfonyBridgeCommand
{
    protected string $name = 'preservation:replicate';
    protected string $description = 'Replicate files to backup targets';
    protected string $symfonyTask = 'preservation:replicate';
}
