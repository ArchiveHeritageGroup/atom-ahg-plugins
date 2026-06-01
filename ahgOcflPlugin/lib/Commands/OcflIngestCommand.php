<?php

namespace AtomFramework\Console\Commands\Ocfl;

use AtomFramework\Console\BaseCommand;

/**
 * ocfl:ingest - snapshot an information_object's digital files into OCFL.
 *
 * If the OCFL object for the given IO id does not yet exist, this writes v1;
 * otherwise it writes a new vN with content reuse for unchanged digests. The
 * OCFL object id is `urn:atom:io:{id}` (stable, namespaced).
 *
 * Mirrors the Heratio ahg-ocfl `ocfl:ingest` artisan command.
 *
 * @copyright  The Archive and Heritage Group (Pty) Ltd
 * @license    AGPL-3.0-or-later
 */
class OcflIngestCommand extends BaseCommand
{
    protected string $name = 'ocfl:ingest';
    protected string $description = 'Snapshot an information object\'s digital files into OCFL (new object or new version)';
    protected string $detailedDescription = <<<'EOF'
Snapshot an information object's digital_object files into the OCFL storage root.

Examples:
  php bin/atom ocfl:ingest 1234
  php bin/atom ocfl:ingest 1234 --message="Master TIFFs re-scanned"
  php bin/atom ocfl:ingest 1234 --user="archivist@example.org"
EOF;

    protected function configure(): void
    {
        $this->addArgument('ioId', 'The information_object id to snapshot', true);
        $this->addOption('message', 'm', 'Free-text version message');
        $this->addOption('user', 'u', 'User name recorded in the inventory version block', 'cli');
        $this->addOption('user-address', null, 'Optional user address/URI recorded in the version block');
    }

    protected function handle(): int
    {
        require_once dirname(__DIR__) . '/bootstrap.php';
        $svc = new \AtomExtensions\Ocfl\Services\OcflService();

        $ioId = (int) $this->argument('ioId');
        $result = $svc->ingestInformationObject(
            $ioId,
            $this->option('message'),
            $this->option('user', 'cli'),
            $this->option('user-address')
        );

        switch ($result['status']) {
            case 'ok':
                $this->success($result['message']);

                return 0;
            case 'empty':
                $this->warning($result['message'] . '; nothing to ingest.');

                return 0;
            default:
                $this->error($result['message']);

                return 1;
        }
    }
}
