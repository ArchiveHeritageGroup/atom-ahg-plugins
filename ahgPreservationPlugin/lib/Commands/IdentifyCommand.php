<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\SymfonyBridgeCommand;

class IdentifyCommand extends SymfonyBridgeCommand
{
    protected string $name = 'preservation:identify';
    protected string $description = 'Identify file formats using Siegfried (PRONOM)';
    protected string $symfonyTask = 'preservation:identify';
}
