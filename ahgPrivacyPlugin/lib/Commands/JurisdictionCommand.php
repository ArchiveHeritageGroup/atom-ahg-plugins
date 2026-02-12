<?php

namespace AtomFramework\Console\Commands\Privacy;

use AtomFramework\Console\SymfonyBridgeCommand;

class JurisdictionCommand extends SymfonyBridgeCommand
{
    protected string $name = 'privacy:jurisdiction';
    protected string $description = 'Manage privacy compliance jurisdictions';
    protected string $symfonyTask = 'privacy:jurisdiction';
}
