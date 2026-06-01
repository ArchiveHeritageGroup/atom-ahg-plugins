<?php

namespace AtomFramework\Console\Commands\Ocfl;

use AtomFramework\Console\BaseCommand;

/**
 * ocfl:init - initialise an OCFL v1.1 storage root.
 *
 * Writes the `0=ocfl_1.1` namaste declaration and the layout descriptor
 * (`ocfl_layout.json`) into the configured storage root (or a one-shot path).
 *
 * Mirrors the Heratio ahg-ocfl `ocfl:init` artisan command.
 *
 * @copyright  The Archive and Heritage Group (Pty) Ltd
 * @license    AGPL-3.0-or-later
 */
class OcflInitCommand extends BaseCommand
{
    protected string $name = 'ocfl:init';
    protected string $description = 'Initialise an OCFL v1.1 storage root (namaste + layout descriptor)';
    protected string $detailedDescription = <<<'EOF'
Initialise an OCFL v1.1 storage root.

Examples:
  php bin/atom ocfl:init                      # use the configured storage root
  php bin/atom ocfl:init /mnt/nas/heratio/ocfl   # one-shot override of the root path
EOF;

    protected function configure(): void
    {
        $this->addArgument('path', 'Optional absolute path overriding the configured storage root', false);
    }

    protected function handle(): int
    {
        $svc = $this->service();

        $path = $this->argument('path');
        $root = $svc->storageRoot(is_string($path) && '' !== $path ? $path : null);

        if (is_string($path) && '' !== $path) {
            $this->info("Targeting ad-hoc storage root: {$path}");
        } else {
            $this->info('Targeting configured storage root: ' . $svc->storageRootPath());
        }

        if ($root->isInitialized()) {
            $this->warning('Storage root already initialised (namaste declaration present). No changes made.');

            return 0;
        }

        $root->initialize();
        $this->success('Storage root initialised. Layout: ' . $root->layout->layout . ', digest: ' . $root->digester->algorithm);

        return 0;
    }

    private function service(): \AtomExtensions\Ocfl\Services\OcflService
    {
        require_once dirname(__DIR__) . '/bootstrap.php';

        return new \AtomExtensions\Ocfl\Services\OcflService();
    }
}
