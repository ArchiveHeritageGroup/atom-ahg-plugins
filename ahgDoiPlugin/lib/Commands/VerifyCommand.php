<?php

namespace AtomFramework\Console\Commands\Doi;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Verify DOI resolution and metadata.
 */
class VerifyCommand extends BaseCommand
{
    protected string $name = 'doi:verify';
    protected string $description = 'Verify DOI resolution and metadata';
    protected string $detailedDescription = <<<'EOF'
    Check that minted DOIs resolve to the correct landing pages.

    Examples:
      php bin/atom doi:verify                Verify DOIs not checked recently
      php bin/atom doi:verify --all          Verify all DOIs
      php bin/atom doi:verify --limit=100    Verify up to 100 DOIs
    EOF;

    protected function configure(): void
    {
        $this->addOption('all', 'a', 'Verify all DOIs');
        $this->addOption('limit', 'l', 'Maximum DOIs to verify', '50');
    }

    protected function handle(): int
    {
        $serviceFile = $this->getAtomRoot() . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';
        if (!file_exists($serviceFile)) {
            $this->error("DoiService not found at: {$serviceFile}");

            return 1;
        }

        require_once $serviceFile;

        $service = new \ahgDoiPlugin\Services\DoiService();
        $limit = (int) $this->option('limit', '50');

        // Get DOIs to verify
        $query = DB::table('ahg_doi')
            ->where('status', 'findable')
            ->orderBy('last_sync_at');

        if (!$this->hasOption('all')) {
            // Only verify DOIs not checked in last 7 days
            $query->where(function ($q) {
                $q->whereNull('last_sync_at')
                    ->orWhere('last_sync_at', '<', date('Y-m-d H:i:s', strtotime('-7 days')));
            });
        }

        $dois = $query->limit($limit)->get();

        if ($dois->isEmpty()) {
            $this->info('No DOIs to verify');

            return 0;
        }

        $this->info("Verifying {$dois->count()} DOIs...");

        $success = 0;
        $failed = 0;

        foreach ($dois as $doi) {
            $result = $service->verifyDoi($doi->id);

            if ($result['resolves']) {
                $this->success("{$doi->doi}: OK");
                ++$success;
            } else {
                $this->error("{$doi->doi}: FAILED (HTTP {$result['http_code']})");
                ++$failed;
            }

            // Update last check time
            DB::table('ahg_doi')
                ->where('id', $doi->id)
                ->update(['last_sync_at' => date('Y-m-d H:i:s')]);
        }

        $this->newline();
        $this->info("Verification complete: {$success} OK, {$failed} failed");

        return $failed > 0 ? 1 : 0;
    }
}
