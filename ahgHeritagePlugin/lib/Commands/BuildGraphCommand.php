<?php

namespace AtomFramework\Console\Commands\Heritage;

use AtomFramework\Console\SymfonyBridgeCommand;

class BuildGraphCommand extends SymfonyBridgeCommand
{
    protected string $name = 'heritage:build-graph';
    protected string $description = 'Build entity relationship graph from entity cache';
    protected string $symfonyTask = 'heritage:build-graph';
}
