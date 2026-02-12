<?php

namespace AtomFramework\Console\Commands\DataMigration;

use AtomFramework\Console\SymfonyBridgeCommand;

class DamCsvImportCommand extends SymfonyBridgeCommand
{
    protected string $name = 'sector:dam-csv-import';
    protected string $description = 'Import DAM CSV data with Dublin Core/IPTC validation';
    protected string $symfonyTask = 'sector:dam-csv-import';
}
