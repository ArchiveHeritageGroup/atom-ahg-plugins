<?php

namespace AtomFramework\Console\Commands\DataMigration;

use AtomFramework\Console\SymfonyBridgeCommand;

class LibraryCsvImportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'sector:library-csv-import';
    protected string $description = 'Import library CSV data with MARC/RDA validation';
    protected string $symfonyTask = 'sector:library-csv-import';
}
