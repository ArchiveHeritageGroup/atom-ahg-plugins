<?php

namespace AtomFramework\Console\Commands\Metadata;

use AtomFramework\Console\SymfonyBridgeCommand;

class ExportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'metadata:export';
    protected string $description = 'Export archival descriptions to various metadata standards';
    protected string $symfonyTask = 'metadata:export';
}
