<?php

namespace AtomFramework\Console\Commands\Doi;

use AtomFramework\Console\SymfonyBridgeCommand;

class VerifyCommand extends SymfonyBridgeCommand
{
    protected string $name = 'doi:verify';
    protected string $description = 'Verify DOI resolution and metadata';
    protected string $symfonyTask = 'doi:verify';
}
