<?php

namespace AtomFramework\Console\Commands\Naz;

use AtomFramework\Console\SymfonyBridgeCommand;

class PermitExpiryCommand extends SymfonyBridgeCommand
{
    protected string $name = 'naz:permit-expiry';
    protected string $description = 'Check for expiring permits';
    protected string $symfonyTask = 'naz:permit-expiry';
}
