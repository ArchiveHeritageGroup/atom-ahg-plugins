<?php

namespace AtomFramework\Console\Commands\Display;

use AtomFramework\Console\SymfonyBridgeCommand;

class RefreshFacetCacheCommand extends SymfonyBridgeCommand
{
    protected string $name = 'ahg:refresh-facet-cache';
    protected string $description = 'Refresh display facet cache';
    protected string $symfonyTask = 'ahg:refresh-facet-cache';
}
