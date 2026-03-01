<?php

/**
 * CLI Task: authority:merge-report
 *
 * Generate a report of merge/split operations.
 * Run monthly or on demand.
 *
 * Usage:
 *   php symfony authority:merge-report
 */
class authorityMergeReportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'authority';
        $this->name = 'merge-report';
        $this->briefDescription = 'Generate authority merge/split report';
        $this->detailedDescription = <<<'EOF'
Generates a summary report of all merge and split operations
performed on authority records.

  php symfony authority:merge-report
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/src/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;

        $this->logSection('authority', 'Authority Merge/Split Report');
        $this->logSection('authority', str_repeat('=', 50));

        // Overall stats
        $total = $db::table('ahg_actor_merge')->count();
        $byType = $db::table('ahg_actor_merge')
            ->select('merge_type', \Illuminate\Database\Capsule\Manager::raw('COUNT(*) as count'))
            ->groupBy('merge_type')
            ->get()->all();

        $byStatus = $db::table('ahg_actor_merge')
            ->select('status', \Illuminate\Database\Capsule\Manager::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()->all();

        $this->logSection('authority', sprintf('Total operations: %d', $total));

        foreach ($byType as $row) {
            $this->logSection('authority', sprintf('  %s: %d', ucfirst($row->merge_type), $row->count));
        }

        $this->logSection('authority', '');
        $this->logSection('authority', 'By status:');
        foreach ($byStatus as $row) {
            $this->logSection('authority', sprintf('  %s: %d', ucfirst($row->status), $row->count));
        }

        // Transfer totals
        $totals = $db::table('ahg_actor_merge')
            ->where('status', 'completed')
            ->selectRaw('SUM(relations_transferred) as rels, SUM(resources_transferred) as res, SUM(contacts_transferred) as con, SUM(identifiers_transferred) as ids')
            ->first();

        if ($totals) {
            $this->logSection('authority', '');
            $this->logSection('authority', 'Total transfers (completed merges):');
            $this->logSection('authority', sprintf('  Relations: %d', $totals->rels ?? 0));
            $this->logSection('authority', sprintf('  Resources: %d', $totals->res ?? 0));
            $this->logSection('authority', sprintf('  Contacts: %d', $totals->con ?? 0));
            $this->logSection('authority', sprintf('  Identifiers: %d', $totals->ids ?? 0));
        }

        // Recent operations
        $recent = $db::table('ahg_actor_merge')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()->all();

        if (!empty($recent)) {
            $this->logSection('authority', '');
            $this->logSection('authority', 'Last 10 operations:');
            foreach ($recent as $op) {
                $this->logSection('authority', sprintf('  [%s] %s #%d -> status: %s (%s)',
                    $op->merge_type, $op->primary_actor_id, $op->id, $op->status, $op->created_at
                ));
            }
        }
    }
}
