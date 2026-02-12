<?php

namespace AtomFramework\Console\Commands\Display;

use AtomFramework\Console\SymfonyBridgeCommand;

class AutoDetectCommand extends SymfonyBridgeCommand
{
    protected string $name = 'display:auto-detect';
    protected string $description = 'Auto-detect GLAM object types';
    protected string $symfonyTask = 'display:auto-detect';
}
