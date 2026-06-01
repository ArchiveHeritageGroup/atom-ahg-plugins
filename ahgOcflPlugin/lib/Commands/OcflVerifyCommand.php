<?php

namespace AtomFramework\Console\Commands\Ocfl;

use AtomFramework\Console\BaseCommand;

/**
 * ocfl:verify - validate fixity + structure for one or all OCFL objects.
 *
 * Exit 0 on success, 1 if any object reports an error.
 *
 * Mirrors the Heratio ahg-ocfl `ocfl:verify` artisan command.
 *
 * @copyright  The Archive and Heritage Group (Pty) Ltd
 * @license    AGPL-3.0-or-later
 */
class OcflVerifyCommand extends BaseCommand
{
    protected string $name = 'ocfl:verify';
    protected string $description = 'Validate fixity + structure for one OCFL object or the entire storage root';
    protected string $detailedDescription = <<<'EOF'
Validate fixity (sidecar + per-file digests) and basic structure of OCFL objects.

Examples:
  php bin/atom ocfl:verify          # verify the whole storage root
  php bin/atom ocfl:verify 1234     # verify the OCFL object for information_object 1234
EOF;

    protected function configure(): void
    {
        $this->addArgument('ioId', 'Optional information_object id (omit to verify the whole root)', false);
    }

    protected function handle(): int
    {
        require_once dirname(__DIR__) . '/bootstrap.php';
        $svc = new \AtomExtensions\Ocfl\Services\OcflService();
        $root = $svc->storageRoot();

        if (!$root->isInitialized()) {
            $this->error('Storage root is not initialised (run `php bin/atom ocfl:init` first).');

            return 1;
        }

        $ioArg = $this->argument('ioId');
        if (null !== $ioArg && '' !== $ioArg) {
            $objectId = $svc->resolveObjectId((int) $ioArg);
            if (!$root->exists($objectId)) {
                $this->error("OCFL object for IO {$ioArg} not found ({$objectId}).");

                return 1;
            }

            return $this->report([$objectId => $root->verify($objectId)]);
        }

        $ids = $root->list();
        if ([] === $ids) {
            $this->info('Storage root is empty; nothing to verify.');

            return 0;
        }

        $all = [];
        foreach ($ids as $id) {
            $all[$id] = $root->verify($id);
        }

        return $this->report($all);
    }

    /** @param array<string, array<int, string>> $results */
    private function report(array $results): int
    {
        $failed = 0;
        foreach ($results as $id => $errors) {
            if ([] === $errors) {
                $this->success("OK  {$id}");

                continue;
            }
            ++$failed;
            $this->error("FAIL  {$id}");
            foreach ($errors as $e) {
                $this->line('      - ' . $e);
            }
        }

        if ($failed > 0) {
            $this->error("Verification failed for {$failed} object(s).");

            return 1;
        }

        $this->success('All objects verified OK.');

        return 0;
    }
}
