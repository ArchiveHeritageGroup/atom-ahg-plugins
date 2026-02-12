<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\SymfonyBridgeCommand;

class NerExtractCommand extends SymfonyBridgeCommand
{
    protected string $name = 'ai:ner-extract';
    protected string $description = 'Extract named entities from archival records';
    protected string $symfonyTask = 'ai:ner-extract';
}
