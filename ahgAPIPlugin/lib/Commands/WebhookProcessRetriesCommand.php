<?php

namespace AtomFramework\Console\Commands\Api;

use AtomFramework\Console\SymfonyBridgeCommand;

class WebhookProcessRetriesCommand extends SymfonyBridgeCommand
{
    protected string $name = 'api:webhook-process-retries';
    protected string $description = 'Process pending webhook retries';
    protected string $symfonyTask = 'api:webhook-process-retries';
}
