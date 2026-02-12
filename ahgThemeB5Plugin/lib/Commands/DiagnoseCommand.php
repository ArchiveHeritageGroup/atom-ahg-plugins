<?php

namespace AtomFramework\Console\Commands\Theme;

use AtomFramework\Console\SymfonyBridgeCommand;

class DiagnoseCommand extends SymfonyBridgeCommand
{
    protected string $name = 'theme:diagnose';
    protected string $description = 'Diagnose ahgThemeB5Plugin configuration issues';
    protected string $symfonyTask = 'theme:diagnose';
}
