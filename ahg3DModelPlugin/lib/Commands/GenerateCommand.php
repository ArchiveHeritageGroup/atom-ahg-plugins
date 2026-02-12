<?php

namespace AtomFramework\Console\Commands\Triposr;

use AtomFramework\Console\SymfonyBridgeCommand;

class GenerateCommand extends SymfonyBridgeCommand
{
    protected string $name = 'triposr:generate';
    protected string $description = 'Generate 3D model from 2D image using TripoSR';
    protected string $symfonyTask = 'triposr:generate';
}
