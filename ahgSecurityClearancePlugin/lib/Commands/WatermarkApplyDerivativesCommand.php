<?php

namespace AtomFramework\Console\Commands\Security;

use AtomFramework\Console\SymfonyBridgeCommand;

class WatermarkApplyDerivativesCommand extends SymfonyBridgeCommand
{
    protected string $name = 'watermark:apply-derivatives';
    protected string $description = 'Apply watermark to derivative images';
    protected string $symfonyTask = 'watermark:apply-derivatives';
}
