<?php

namespace AtomFramework\Console\Commands\Naz;

use AtomFramework\Console\SymfonyBridgeCommand;

class TransferDueCommand extends SymfonyBridgeCommand
{
    protected string $name = 'naz:transfer-due';
    protected string $description = 'Check for records due for transfer';
    protected string $symfonyTask = 'naz:transfer-due';
}
