<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\SymfonyBridgeCommand;

class FixityCommand extends SymfonyBridgeCommand
{
    protected string $name = 'preservation:fixity';
    protected string $description = 'Verify file integrity using checksums';
    protected string $symfonyTask = 'preservation:fixity';
}
