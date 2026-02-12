<?php

namespace AtomFramework\Console\Commands\DataMigration;

use AtomFramework\Console\SymfonyBridgeCommand;

class ArchivesCsvImportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'sector:archives-csv-import';
    protected string $description = 'Import archives CSV data with ISAD-G validation';
    protected string $symfonyTask = 'sector:archives-csv-import';
}
