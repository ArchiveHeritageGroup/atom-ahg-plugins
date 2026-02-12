<?php

namespace AtomFramework\Console\Commands\Forms;

use AtomFramework\Console\SymfonyBridgeCommand;

class ListCommand extends SymfonyBridgeCommand
{
    protected string $name = 'forms:list';
    protected string $description = 'List available forms';
    protected string $symfonyTask = 'forms:list';
}
