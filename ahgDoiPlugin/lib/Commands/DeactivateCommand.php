<?php

namespace AtomFramework\Console\Commands\Doi;

use AtomFramework\Console\SymfonyBridgeCommand;

class DeactivateCommand extends SymfonyBridgeCommand
{
    protected string $name = 'doi:deactivate';
    protected string $description = 'Deactivate DOIs';
    protected string $symfonyTask = 'doi:deactivate';
}
