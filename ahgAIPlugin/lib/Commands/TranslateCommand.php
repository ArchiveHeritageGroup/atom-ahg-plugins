<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\SymfonyBridgeCommand;

class TranslateCommand extends SymfonyBridgeCommand
{
    protected string $name = 'ai:translate';
    protected string $description = 'Translate archival records between cultures';
    protected string $symfonyTask = 'ai:translate';
}
