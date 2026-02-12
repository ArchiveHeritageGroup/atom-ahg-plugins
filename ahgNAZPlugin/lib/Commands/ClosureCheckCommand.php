<?php

namespace AtomFramework\Console\Commands\Naz;

use AtomFramework\Console\SymfonyBridgeCommand;

class ClosureCheckCommand extends SymfonyBridgeCommand
{
    protected string $name = 'naz:closure-check';
    protected string $description = 'Check closure period compliance';
    protected string $symfonyTask = 'naz:closure-check';
}
