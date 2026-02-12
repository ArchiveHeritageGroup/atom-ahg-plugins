<?php

namespace AtomFramework\Console\Commands\DataMigration;

use AtomFramework\Console\SymfonyBridgeCommand;

class GalleryCsvImportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'sector:gallery-csv-import';
    protected string $description = 'Import gallery CSV data with CCO validation';
    protected string $symfonyTask = 'sector:gallery-csv-import';
}
