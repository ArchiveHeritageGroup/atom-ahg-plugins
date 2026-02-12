<?php

namespace AtomFramework\Console\Commands\Doi;

use AtomFramework\Console\SymfonyBridgeCommand;

class MintCommand extends SymfonyBridgeCommand
{
    protected string $name = 'doi:mint';
    protected string $description = 'Mint DOIs for archival records';
    protected string $symfonyTask = 'doi:mint';
}
