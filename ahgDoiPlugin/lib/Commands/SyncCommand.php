<?php

namespace AtomFramework\Console\Commands\Doi;

use AtomFramework\Console\SymfonyBridgeCommand;

class SyncCommand extends SymfonyBridgeCommand
{
    protected string $name = 'doi:sync';
    protected string $description = 'Sync DOI metadata with DataCite';
    protected string $symfonyTask = 'doi:sync';
}
