<?php

namespace AtomFramework\Console\Commands\Naz;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Check research permits for expiry.
 */
class PermitExpiryCommand extends BaseCommand
{
    protected string $name = 'naz:permit-expiry';
    protected string $description = 'Check for expiring permits';
    protected string $detailedDescription = <<<'EOF'
    Manages research permit expirations:
      - Lists permits expiring within specified days
      - Auto-expires permits past their end date
      - Identifies pending applications

    Examples:
      php bin/atom naz:permit-expiry                     Check all permits
      php bin/atom naz:permit-expiry --days=14           Warn about 14-day expirations
      php bin/atom naz:permit-expiry --process           Auto-expire past permits
    EOF;

    protected function configure(): void
    {
        $this->addOption('days', 'd', 'Days until expiry to warn about', '30');
        $this->addOption('process', 'p', 'Auto-expire past permits');
        $this->addOption('format', null, 'Output format (text|csv|json)', 'text');
    }

    protected function handle(): int
    {
        $days = (int) $this->option('days', '30');
        $format = $this->option('format', 'text');

        $this->bold('  National Archives of Zimbabwe - Research Permit Expiry Check');
        $this->newline();

        // Get expired permits
        $expired = DB::table('naz_research_permit as p')
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
        $expiring = DB::table('naz_research_permit as p')
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
        $pending = DB::table('naz_research_permit as p')
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
                echo sprintf(
                    "EXPIRED,%s,\"%s %s\",%s,%s,%d,%s\n",
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
                echo sprintf(
                    "EXPIRING,%s,\"%s %s\",%s,%s,%d,%s\n",
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
        $this->error(sprintf('  Expired Permits: %d', $expired->count()));

        if ($expired->count() > 0) {
            foreach ($expired as $p) {
                $expiredDays = floor((strtotime('now') - strtotime($p->end_date)) / 86400);
                $this->line(sprintf(
                    '    [%s] %s %s (%s) - Expired %d days ago',
                    $p->permit_number,
                    $p->first_name,
                    $p->last_name,
                    $p->researcher_type,
                    $expiredDays
                ));

                if ($this->hasOption('process')) {
                    DB::table('naz_research_permit')
                        ->where('id', $p->id)
                        ->update(['status' => 'expired']);
                    $this->line('         -> Status updated to expired');
                }
            }
        }
        $this->newline();

        $this->warning(sprintf('  Expiring in %d days: %d', $days, $expiring->count()));

        if ($expiring->count() > 0) {
            foreach ($expiring as $p) {
                $daysLeft = floor((strtotime($p->end_date) - strtotime('now')) / 86400);
                $urgency = $daysLeft <= 7 ? ' [URGENT]' : '';
                $this->line(sprintf(
                    '    [%s] %s %s - %d days remaining%s',
                    $p->permit_number,
                    $p->first_name,
                    $p->last_name,
                    $daysLeft,
                    $urgency
                ));
            }
        }
        $this->newline();

        $this->info(sprintf('  Pending Applications: %d', $pending->count()));

        if ($pending->count() > 0) {
            foreach ($pending as $p) {
                $waitDays = floor((strtotime('now') - strtotime($p->created_at)) / 86400);
                $this->line(sprintf(
                    '    [%s] %s %s (%s) - Waiting %d days',
                    $p->permit_number,
                    $p->first_name,
                    $p->last_name,
                    $p->researcher_type,
                    $waitDays
                ));
            }
        }

        $this->newline();
        $this->info('  Statistics');

        $stats = DB::table('naz_research_permit')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        foreach ($stats as $status => $count) {
            $this->line(sprintf('    %s: %d', ucfirst($status), $count));
        }

        // Revenue from foreign permits
        $revenue = DB::table('naz_research_permit')
            ->where('fee_paid', 1)
            ->whereYear('payment_date', date('Y'))
            ->sum('fee_amount');

        $this->newline();
        $this->line(sprintf('    Permit Revenue (This Year): US$%.2f', $revenue));

        return 0;
    }
}
