<?php

namespace AtomFramework\Console\Commands\Privacy;

use AtomFramework\Console\SymfonyBridgeCommand;

class ScanPiiCommand extends SymfonyBridgeCommand
{
    protected string $name = 'privacy:scan-pii';
    protected string $description = 'Scan archival descriptions for PII (Personally Identifiable Information)';
    protected string $symfonyTask = 'privacy:scan-pii';
}
