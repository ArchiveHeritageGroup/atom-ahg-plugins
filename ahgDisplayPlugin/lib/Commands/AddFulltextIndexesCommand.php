<?php

namespace AtomFramework\Console\Commands\Display;

use AtomFramework\Console\SymfonyBridgeCommand;

class AddFulltextIndexesCommand extends SymfonyBridgeCommand
{
    protected string $name = 'ahg:add-fulltext-indexes';
    protected string $description = 'Add fulltext indexes for search optimization';
    protected string $symfonyTask = 'ahg:add-fulltext-indexes';
}
