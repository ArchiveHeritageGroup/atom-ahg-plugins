<?php

namespace AtomFramework\Console\Commands\Cdpa;

use AtomFramework\Console\SymfonyBridgeCommand;

class LicenseCheckCommand extends SymfonyBridgeCommand
{
    protected string $name = 'cdpa:license-check';
    protected string $description = 'Check CDPA license compliance';
    protected string $symfonyTask = 'cdpa:license-check';
}
