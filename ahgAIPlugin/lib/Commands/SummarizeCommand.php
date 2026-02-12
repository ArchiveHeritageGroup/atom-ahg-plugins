<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\SymfonyBridgeCommand;

class SummarizeCommand extends SymfonyBridgeCommand
{
    protected string $name = 'ai:summarize';
    protected string $description = 'Generate summaries for archival records';
    protected string $symfonyTask = 'ai:summarize';
}
