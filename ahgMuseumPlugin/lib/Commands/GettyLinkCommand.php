<?php

namespace AtomFramework\Console\Commands\Museum;

use AtomFramework\Console\SymfonyBridgeCommand;

class GettyLinkCommand extends SymfonyBridgeCommand
{
    protected string $name = 'museum:getty-link';
    protected string $description = 'Link taxonomy terms to Getty vocabularies (AAT, TGN, ULAN)';
    protected string $symfonyTask = 'museum:getty-link';
}
