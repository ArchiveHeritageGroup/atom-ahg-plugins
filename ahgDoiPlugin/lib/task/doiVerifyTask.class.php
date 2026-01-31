<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to verify DOIs resolve correctly.
 */
class doiVerifyTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('all', null, sfCommandOption::PARAMETER_NONE, 'Verify all DOIs'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum DOIs to verify', 50),
        ]);

        $this->namespace = 'doi';
        $this->name = 'verify';
        $this->briefDescription = 'Verify DOIs resolve correctly';
        $this->detailedDescription = <<<EOF
Check that minted DOIs resolve to the correct landing pages.

Examples:
  php symfony doi:verify                # Verify DOIs not checked recently
  php symfony doi:verify --all          # Verify all DOIs
  php symfony doi:verify --limit=100    # Verify up to 100 DOIs
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';

        $service = new \ahgDoiPlugin\Services\DoiService();
        $limit = (int) ($options['limit'] ?? 50);

        // Get DOIs to verify
        $query = DB::table('ahg_doi')
            ->where('status', 'findable')
            ->orderBy('last_sync_at');

        if (!$options['all']) {
            // Only verify DOIs not checked in last 7 days
            $query->where(function ($q) {
                $q->whereNull('last_sync_at')
                    ->orWhere('last_sync_at', '<', date('Y-m-d H:i:s', strtotime('-7 days')));
            });
        }

        $dois = $query->limit($limit)->get();

        if ($dois->isEmpty()) {
            $this->logSection('doi', 'No DOIs to verify');

            return 0;
        }

        $this->logSection('doi', "Verifying {$dois->count()} DOIs...");

        $success = 0;
        $failed = 0;

        foreach ($dois as $doi) {
            $result = $service->verifyDoi($doi->id);

            if ($result['resolves']) {
                $this->logSection('doi', "{$doi->doi}: OK", null, 'INFO');
                ++$success;
            } else {
                $this->logSection('doi', "{$doi->doi}: FAILED (HTTP {$result['http_code']})", null, 'ERROR');
                ++$failed;
            }

            // Update last check time
            DB::table('ahg_doi')
                ->where('id', $doi->id)
                ->update(['last_sync_at' => date('Y-m-d H:i:s')]);
        }

        $this->logSection('doi', "Verification complete: {$success} OK, {$failed} failed");

        return $failed > 0 ? 1 : 0;
    }
}
