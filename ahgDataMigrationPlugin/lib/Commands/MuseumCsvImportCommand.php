<?php

namespace AtomFramework\Console\Commands\DataMigration;

use AtomFramework\Console\SymfonyBridgeCommand;

class MuseumCsvImportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'sector:museum-csv-import';
    protected string $description = 'Import museum CSV data with Spectrum validation';
    protected string $symfonyTask = 'sector:museum-csv-import';
}
