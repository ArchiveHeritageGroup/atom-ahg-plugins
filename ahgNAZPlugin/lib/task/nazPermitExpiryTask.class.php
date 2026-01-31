<?php

/**
 * NAZ Permit Expiry Task
 *
 * Manages research permit expirations:
 * - Lists expiring permits
 * - Auto-expires past permits
 * - Sends expiry warnings
 *
 * Usage: php symfony naz:permit-expiry [--days=30] [--process]
 */
class nazPermitExpiryTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('days', null, sfCommandOption::PARAMETER_REQUIRED, 'Days until expiry to warn about', 30),
            new sfCommandOption('process', null, sfCommandOption::PARAMETER_NONE, 'Auto-expire past permits'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_REQUIRED, 'Output format (text|csv|json)', 'text'),
        ]);

        $this->namespace = 'naz';
        $this->name = 'permit-expiry';
        $this->briefDescription = 'Check research permits for expiry';
        $this->detailedDescription = <<<'EOF'
The [naz:permit-expiry|INFO] task manages research permit expirations:
  - Lists permits expiring within specified days
  - Auto-expires permits past their end date
  - Identifies pending applications

Examples:
  [php symfony naz:permit-expiry|INFO]                     Check all permits
  [php symfony naz:permit-expiry --days=14|INFO]           Warn about 14-day expirations
  [php symfony naz:permit-expiry --process|INFO]           Auto-expire past permits
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $days = (int) $options['days'];
        $format = $options['format'];

        $this->logSection('naz', 'National Archives of Zimbabwe - Research Permit Expiry Check');
        $this->log('');

        // Get expired permits
        $expired = \Illuminate\Database\Capsule\Manager::table('naz_research_permit as p')
            ->join('naz_researcher as r', 'p.researcher_id', '=', 'r.id')
            ->where('p.status', 'active')
            ->whereRaw('p.end_date < CURDATE()')
            ->select([
                'p.*',
                'r.first_name',
                'r.last_name',
                'r.researcher_type',
                'r.email',
            ])
            ->get();

        // Get expiring soon
        $expiring = \Illuminate\Database\Capsule\Manager::table('naz_research_permit as p')
            ->join('naz_researcher as r', 'p.researcher_id', '=', 'r.id')
            ->where('p.status', 'active')
            ->whereRaw('p.end_date >= CURDATE()')
            ->whereRaw("p.end_date <= DATE_ADD(CURDATE(), INTERVAL {$days} DAY)")
            ->select([
                'p.*',
                'r.first_name',
                'r.last_name',
                'r.researcher_type',
                'r.email',
            ])
            ->orderBy('p.end_date')
            ->get();

        // Get pending applications
        $pending = \Illuminate\Database\Capsule\Manager::table('naz_research_permit as p')
            ->join('naz_researcher as r', 'p.researcher_id', '=', 'r.id')
            ->where('p.status', 'pending')
            ->select([
                'p.*',
                'r.first_name',
                'r.last_name',
                'r.researcher_type',
                'r.email',
            ])
            ->orderBy('p.created_at')
            ->get();

        if ('json' === $format) {
            echo json_encode([
                'expired' => $expired->toArray(),
                'expiring_soon' => $expiring->toArray(),
                'pending' => $pending->toArray(),
            ], JSON_PRETTY_PRINT);

            return 0;
        }

        if ('csv' === $format) {
            echo "Status,Permit Number,Researcher,Type,End Date,Days,Email\n";
            foreach ($expired as $p) {
                $diff = floor((strtotime('now') - strtotime($p->end_date)) / 86400);
                echo sprintf("EXPIRED,%s,\"%s %s\",%s,%s,%d,%s\n",
                    $p->permit_number,
                    $p->first_name,
                    $p->last_name,
                    $p->researcher_type,
                    $p->end_date,
                    $diff,
                    $p->email
                );
            }
            foreach ($expiring as $p) {
                $diff = floor((strtotime($p->end_date) - strtotime('now')) / 86400);
                echo sprintf("EXPIRING,%s,\"%s %s\",%s,%s,%d,%s\n",
                    $p->permit_number,
                    $p->first_name,
                    $p->last_name,
                    $p->researcher_type,
                    $p->end_date,
                    $diff,
                    $p->email
                );
            }

            return 0;
        }

        // Text output
        $this->logSection('expired', sprintf('Expired Permits: %d', $expired->count()), null, 'ERROR');

        if ($expired->count() > 0) {
            foreach ($expired as $p) {
                $expiredDays = floor((strtotime('now') - strtotime($p->end_date)) / 86400);
                $this->log(sprintf('  [%s] %s %s (%s) - Expired %d days ago',
                    $p->permit_number,
                    $p->first_name,
                    $p->last_name,
                    $p->researcher_type,
                    $expiredDays
                ));

                if ($options['process']) {
                    \Illuminate\Database\Capsule\Manager::table('naz_research_permit')
                        ->where('id', $p->id)
                        ->update(['status' => 'expired']);
                    $this->log('       -> Status updated to expired');
                }
            }
        }
        $this->log('');

        $this->logSection('expiring', sprintf('Expiring in %d days: %d', $days, $expiring->count()), null, 'COMMENT');

        if ($expiring->count() > 0) {
            foreach ($expiring as $p) {
                $daysLeft = floor((strtotime($p->end_date) - strtotime('now')) / 86400);
                $urgency = $daysLeft <= 7 ? ' [URGENT]' : '';
                $this->log(sprintf('  [%s] %s %s - %d days remaining%s',
                    $p->permit_number,
                    $p->first_name,
                    $p->last_name,
                    $daysLeft,
                    $urgency
                ));
            }
        }
        $this->log('');

        $this->logSection('pending', sprintf('Pending Applications: %d', $pending->count()));

        if ($pending->count() > 0) {
            foreach ($pending as $p) {
                $waitDays = floor((strtotime('now') - strtotime($p->created_at)) / 86400);
                $this->log(sprintf('  [%s] %s %s (%s) - Waiting %d days',
                    $p->permit_number,
                    $p->first_name,
                    $p->last_name,
                    $p->researcher_type,
                    $waitDays
                ));
            }
        }

        $this->log('');
        $this->logSection('stats', 'Statistics');

        $stats = \Illuminate\Database\Capsule\Manager::table('naz_research_permit')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        foreach ($stats as $status => $count) {
            $this->log(sprintf('  %s: %d', ucfirst($status), $count));
        }

        // Revenue from foreign permits
        $revenue = \Illuminate\Database\Capsule\Manager::table('naz_research_permit')
            ->where('fee_paid', 1)
            ->whereYear('payment_date', date('Y'))
            ->sum('fee_amount');

        $this->log('');
        $this->log(sprintf('  Permit Revenue (This Year): US$%.2f', $revenue));

        return 0;
    }
}
