<?php

namespace AtomFramework\Console\Commands\Api;

use AtomFramework\Console\BaseCommand;

/**
 * Delete expired Idempotency-Key rows from ahg_api_idempotency_key.
 *
 * AtoM/Symfony-1.x port of the Heratio `api:prune-idempotency` artisan
 * command (ahg-api issue #652). Run daily from cron alongside other prunes:
 *
 *   php bin/atom api:prune-idempotency
 *   php bin/atom api:prune-idempotency --all   (delete every row)
 */
class PruneIdempotencyCommand extends BaseCommand
{
    protected string $name = 'api:prune-idempotency';
    protected string $description = 'Delete expired Idempotency-Key rows from ahg_api_idempotency_key';
    protected string $detailedDescription = <<<'EOF'
    Deletes expired Idempotency-Key replay rows from ahg_api_idempotency_key.

    Examples:
      php bin/atom api:prune-idempotency
      php bin/atom api:prune-idempotency --all

    Options:
      --all   Delete every row, ignoring expires_at (full reset)
    EOF;

    protected function configure(): void
    {
        $this->addOption('all', null, 'Delete every row, ignoring expires_at', null);
    }

    protected function handle(): int
    {
        // Load the IdempotencyService (namespaced plugin classes are not autoloaded).
        $servicePath = $this->getAtomRoot() . '/plugins/ahgAPIPlugin/lib/Services/IdempotencyService.php';
        if (!file_exists($servicePath)) {
            $servicePath = $this->getPluginsRoot() . '/ahgAPIPlugin/lib/Services/IdempotencyService.php';
        }

        if (!file_exists($servicePath)) {
            $this->warning('IdempotencyService not found — nothing to prune.');

            return 0;
        }

        require_once $servicePath;

        $service = new \AhgAPIPlugin\Service\IdempotencyService();
        $all = $this->hasOption('all');

        $deleted = $service->prune($all);

        $this->success(sprintf('Pruned %d idempotency-key row(s).', $deleted));

        return 0;
    }
}
