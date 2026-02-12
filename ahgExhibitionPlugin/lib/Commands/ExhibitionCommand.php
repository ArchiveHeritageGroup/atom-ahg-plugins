<?php

namespace AtomFramework\Console\Commands\Museum;

use AtomFramework\Console\SymfonyBridgeCommand;

class ExhibitionCommand extends SymfonyBridgeCommand
{
    protected string $name = 'museum:exhibition';
    protected string $description = 'Manage museum exhibitions';
    protected string $symfonyTask = 'museum:exhibition';
}
