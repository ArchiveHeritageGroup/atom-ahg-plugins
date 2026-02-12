<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\SymfonyBridgeCommand;

class SuggestDescriptionCommand extends SymfonyBridgeCommand
{
    protected string $name = 'ai:suggest-description';
    protected string $description = 'Generate AI description suggestions for archival records';
    protected string $symfonyTask = 'ai:suggest-description';
}
