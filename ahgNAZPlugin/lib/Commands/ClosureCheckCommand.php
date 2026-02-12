<?php

namespace AtomFramework\Console\Commands\Naz;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Check closure periods for expiry and releases.
 */
class ClosureCheckCommand extends BaseCommand
{
    protected string $name = 'naz:closure-check';
    protected string $description = 'Check closure period compliance';
    protected string $detailedDescription = <<<'EOF'
    Checks closure periods for:
      - Expired closures ready to be released
      - Closures expiring within specified days
      - Overdue review dates

    Examples:
      php bin/atom naz:closure-check                    Check all closures
      php bin/atom naz:closure-check --days=90          Warn about 90-day expirations
      php bin/atom naz:closure-check --process          Auto-release expired closures
      php bin/atom naz:closure-check --format=csv       Output as CSV
    EOF;

    protected function configure(): void
    {
        $this->addOption('days', 'd', 'Days until expiry to warn about', '30');
        $this->addOption('process', 'p', 'Auto-release expired closures');
        $this->addOption('format', null, 'Output format (text|csv|json)', 'text');
    }

    protected function handle(): int
    {
        $days = (int) $this->option('days', '30');
        $format = $this->option('format', 'text');

        $this->bold('  National Archives of Zimbabwe - Closure Period Check');
        $this->info('  Section 10 - 25-Year Rule Compliance');
        $this->newline();

        // Get expired closures
        $expired = DB::table('naz_closure_period as c')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('c.status', 'active')
            ->whereNotNull('c.end_date')
            ->whereRaw('c.end_date < CURDATE()')
            ->select(['c.*', 'ioi.title as record_title'])
            ->get();

        // Get expiring soon
        $expiring = DB::table('naz_closure_period as c')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('c.status', 'active')
            ->whereNotNull('c.end_date')
            ->whereRaw('c.end_date >= CURDATE()')
            ->whereRaw("c.end_date <= DATE_ADD(CURDATE(), INTERVAL {$days} DAY)")
            ->select(['c.*', 'ioi.title as record_title'])
            ->orderBy('c.end_date')
            ->get();

        // Get overdue reviews
        $overdueReviews = DB::table('naz_closure_period as c')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('c.status', 'active')
            ->whereNotNull('c.review_date')
            ->whereRaw('c.review_date < CURDATE()')
            ->select(['c.*', 'ioi.title as record_title'])
            ->get();

        if ('json' === $format) {
            echo json_encode([
                'expired' => $expired->toArray(),
                'expiring_soon' => $expiring->toArray(),
                'overdue_reviews' => $overdueReviews->toArray(),
            ], JSON_PRETTY_PRINT);

            return 0;
        }

        if ('csv' === $format) {
            echo "Type,ID,Record Title,End Date,Review Date,Days,Status\n";
            foreach ($expired as $c) {
                $diff = (strtotime('now') - strtotime($c->end_date)) / 86400;
                echo sprintf(
                    "EXPIRED,%d,\"%s\",%s,%s,%d,%s\n",
                    $c->id,
                    str_replace('"', '""', $c->record_title ?? 'N/A'),
                    $c->end_date,
                    $c->review_date ?? '',
                    $diff,
                    $c->status
                );
            }
            foreach ($expiring as $c) {
                $diff = (strtotime($c->end_date) - strtotime('now')) / 86400;
                echo sprintf(
                    "EXPIRING,%d,\"%s\",%s,%s,%d,%s\n",
                    $c->id,
                    str_replace('"', '""', $c->record_title ?? 'N/A'),
                    $c->end_date,
                    $c->review_date ?? '',
                    $diff,
                    $c->status
                );
            }

            return 0;
        }

        // Text output
        $this->error(sprintf('  Expired Closures: %d', $expired->count()));

        if ($expired->count() > 0) {
            foreach ($expired as $c) {
                $expiredDays = floor((strtotime('now') - strtotime($c->end_date)) / 86400);
                $this->line(sprintf(
                    '    [%d] %s - Expired %d days ago (%s)',
                    $c->id,
                    $c->record_title ?? 'Record #' . $c->information_object_id,
                    $expiredDays,
                    $c->end_date
                ));

                if ($this->hasOption('process')) {
                    DB::table('naz_closure_period')
                        ->where('id', $c->id)
                        ->update([
                            'status' => 'expired',
                            'release_notes' => 'Auto-released by CLI task on ' . date('Y-m-d'),
                        ]);
                    $this->line('         -> Released');
                }
            }
        }
        $this->newline();

        $this->warning(sprintf('  Expiring in %d days: %d', $days, $expiring->count()));

        if ($expiring->count() > 0) {
            foreach ($expiring as $c) {
                $daysLeft = floor((strtotime($c->end_date) - strtotime('now')) / 86400);
                $this->line(sprintf(
                    '    [%d] %s - %d days remaining (%s)',
                    $c->id,
                    $c->record_title ?? 'Record #' . $c->information_object_id,
                    $daysLeft,
                    $c->end_date
                ));
            }
        }
        $this->newline();

        $this->warning(sprintf('  Overdue Reviews: %d', $overdueReviews->count()));

        if ($overdueReviews->count() > 0) {
            foreach ($overdueReviews as $c) {
                $overdueDays = floor((strtotime('now') - strtotime($c->review_date)) / 86400);
                $this->line(sprintf(
                    '    [%d] %s - Review overdue by %d days (%s)',
                    $c->id,
                    $c->record_title ?? 'Record #' . $c->information_object_id,
                    $overdueDays,
                    $c->review_date
                ));
            }
        }

        $this->newline();
        $this->info('  Summary');
        $this->line(sprintf(
            '    Total Active Closures: %d',
            DB::table('naz_closure_period')
                ->where('status', 'active')->count()
        ));
        $this->line(sprintf(
            '    Indefinite Closures: %d',
            DB::table('naz_closure_period')
                ->where('status', 'active')
                ->where('closure_type', 'indefinite')->count()
        ));

        return 0;
    }
}
