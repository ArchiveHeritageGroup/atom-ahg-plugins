<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\SymfonyBridgeCommand;

class SpellcheckCommand extends SymfonyBridgeCommand
{
    protected string $name = 'ai:spellcheck';
    protected string $description = 'Check spelling in archival records';
    protected string $symfonyTask = 'ai:spellcheck';
}
