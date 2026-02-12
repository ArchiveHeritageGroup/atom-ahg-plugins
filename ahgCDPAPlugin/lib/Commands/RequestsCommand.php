<?php

namespace AtomFramework\Console\Commands\Cdpa;

use AtomFramework\Console\SymfonyBridgeCommand;

class RequestsCommand extends SymfonyBridgeCommand
{
    protected string $name = 'cdpa:requests';
    protected string $description = 'Manage CDPA requests';
    protected string $symfonyTask = 'cdpa:requests';
}
