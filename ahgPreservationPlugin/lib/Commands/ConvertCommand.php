<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\SymfonyBridgeCommand;

class ConvertCommand extends SymfonyBridgeCommand
{
    protected string $name = 'preservation:convert';
    protected string $description = 'Convert digital objects to preservation-safe formats';
    protected string $symfonyTask = 'preservation:convert';
}
