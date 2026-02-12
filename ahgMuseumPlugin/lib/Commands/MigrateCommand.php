<?php

namespace AtomFramework\Console\Commands\Museum;

use AtomFramework\Console\SymfonyBridgeCommand;

class MigrateCommand extends SymfonyBridgeCommand
{
    protected string $name = 'museum:migrate';
    protected string $description = 'Run museum metadata database migrations';
    protected string $symfonyTask = 'museum:migrate';
}
