<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\SymfonyBridgeCommand;

class VerifyBackupCommand extends SymfonyBridgeCommand
{
    protected string $name = 'preservation:verify-backup';
    protected string $description = 'Verify backup integrity and replication status';
    protected string $symfonyTask = 'preservation:verify-backup';
}
