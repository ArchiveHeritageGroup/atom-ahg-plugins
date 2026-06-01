<?php

namespace AtomFramework\Console\Commands\Ocfl;

use AtomFramework\Console\BaseCommand;

/**
 * ocfl:export - export one OCFL object to a portable tarball.
 *
 * Output: <ocfl_export_path>/<sanitised-id>.tar (plain POSIX tar, no
 * compression, so it streams large preservation masters and is inspectable
 * with stock `tar`).
 *
 * Mirrors the Heratio ahg-ocfl `ocfl:export` artisan command.
 *
 * @copyright  The Archive and Heritage Group (Pty) Ltd
 * @license    AGPL-3.0-or-later
 */
class OcflExportCommand extends BaseCommand
{
    protected string $name = 'ocfl:export';
    protected string $description = 'Export an OCFL object to a tarball under the configured export path';
    protected string $detailedDescription = <<<'EOF'
Export a single OCFL object (all versions, inventory, namaste, sidecars) to a
self-contained tar archive.

Examples:
  php bin/atom ocfl:export 1234
EOF;

    protected function configure(): void
    {
        $this->addArgument('ioId', 'The information_object id to export', true);
    }

    protected function handle(): int
    {
        if (!class_exists('PharData')) {
            $this->error('ocfl:export requires the PHP Phar extension (PharData). It appears to be unavailable.');

            return 1;
        }

        require_once dirname(__DIR__) . '/bootstrap.php';
        $svc = new \AtomExtensions\Ocfl\Services\OcflService();

        $result = $svc->exportObject((int) $this->argument('ioId'));
        if ('ok' === $result['status']) {
            $this->success($result['message']);

            return 0;
        }

        $this->error($result['message']);

        return 1;
    }
}
