<?php

namespace AtomFramework\Console\Commands\Statistics;

use AtomFramework\Console\SymfonyBridgeCommand;

class AggregateCommand extends SymfonyBridgeCommand
{
    protected string $name = 'statistics:aggregate';
    protected string $description = 'Aggregate usage statistics for reporting';
    protected string $symfonyTask = 'statistics:aggregate';
}
