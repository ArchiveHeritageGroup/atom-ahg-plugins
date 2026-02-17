<?php

/**
 * NAZ Closure Check Task
 *
 * Checks closure periods:
 * - Identifies expired closures ready for release
 * - Warns about closures expiring soon
 * - Reports overdue review dates
 *
 * Usage: php symfony naz:closure-check [--days=30] [--process]
 */
class nazClosureCheckTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('days', null, sfCommandOption::PARAMETER_REQUIRED, 'Days until expiry to warn about', 30),
            new sfCommandOption('process', null, sfCommandOption::PARAMETER_NONE, 'Auto-release expired closures'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_REQUIRED, 'Output format (text|csv|json)', 'text'),
        ]);

        $this->namespace = 'naz';
        $this->name = 'closure-check';
        $this->briefDescription = 'Check closure periods for expiry and releases';
        $this->detailedDescription = <<<'EOF'
The [naz:closure-check|INFO] task checks closure periods for:
  - Expired closures ready to be released
  - Closures expiring within specified days
  - Overdue review dates

Examples:
  [php symfony naz:closure-check|INFO]                    Check all closures
  [php symfony naz:closure-check --days=90|INFO]          Warn about 90-day expirations
  [php symfony naz:closure-check --process|INFO]          Auto-release expired closures
  [php symfony naz:closure-check --format=csv|INFO]       Output as CSV
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $days = (int) $options['days'];
        $format = $options['format'];

        $this->logSection('naz', 'National Archives of Zimbabwe - Closure Period Check');
        $this->logSection('naz', 'Section 10 - 25-Year Rule Compliance');
        $this->log('');

        // Get expired closures
        $expired = \Illuminate\Database\Capsule\Manager::table('naz_closure_period as c')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('c.status', 'active')
            ->whereNotNull('c.end_date')
            ->whereRaw('c.end_date < CURDATE()')
            ->select(['c.*', 'ioi.title as record_title'])
            ->get();

        // Get expiring soon
        $expiring = \Illuminate\Database\Capsule\Manager::table('naz_closure_period as c')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('c.status', 'active')
            ->whereNotNull('c.end_date')
            ->whereRaw('c.end_date >= CURDATE()')
            ->whereRaw("c.end_date <= DATE_ADD(CURDATE(), INTERVAL {$days} DAY)")
            ->select(['c.*', 'ioi.title as record_title'])
            ->orderBy('c.end_date')
            ->get();

        // Get overdue reviews
        $overdueReviews = \Illuminate\Database\Capsule\Manager::table('naz_closure_period as c')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
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
                echo sprintf("EXPIRED,%d,\"%s\",%s,%s,%d,%s\n",
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
                echo sprintf("EXPIRING,%d,\"%s\",%s,%s,%d,%s\n",
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
        $this->logSection('expired', sprintf('Expired Closures: %d', $expired->count()), null, 'ERROR');

        if ($expired->count() > 0) {
            foreach ($expired as $c) {
                $expiredDays = floor((strtotime('now') - strtotime($c->end_date)) / 86400);
                $this->log(sprintf('  [%d] %s - Expired %d days ago (%s)',
                    $c->id,
                    $c->record_title ?? 'Record #'.$c->information_object_id,
                    $expiredDays,
                    $c->end_date
                ));

                if ($options['process']) {
                    \Illuminate\Database\Capsule\Manager::table('naz_closure_period')
                        ->where('id', $c->id)
                        ->update([
                            'status' => 'expired',
                            'release_notes' => 'Auto-released by CLI task on '.date('Y-m-d'),
                        ]);
                    $this->log('       -> Released');
                }
            }
        }
        $this->log('');

        $this->logSection('expiring', sprintf('Expiring in %d days: %d', $days, $expiring->count()), null, 'COMMENT');

        if ($expiring->count() > 0) {
            foreach ($expiring as $c) {
                $daysLeft = floor((strtotime($c->end_date) - strtotime('now')) / 86400);
                $this->log(sprintf('  [%d] %s - %d days remaining (%s)',
                    $c->id,
                    $c->record_title ?? 'Record #'.$c->information_object_id,
                    $daysLeft,
                    $c->end_date
                ));
            }
        }
        $this->log('');

        $this->logSection('reviews', sprintf('Overdue Reviews: %d', $overdueReviews->count()), null, 'COMMENT');

        if ($overdueReviews->count() > 0) {
            foreach ($overdueReviews as $c) {
                $overdueDays = floor((strtotime('now') - strtotime($c->review_date)) / 86400);
                $this->log(sprintf('  [%d] %s - Review overdue by %d days (%s)',
                    $c->id,
                    $c->record_title ?? 'Record #'.$c->information_object_id,
                    $overdueDays,
                    $c->review_date
                ));
            }
        }

        $this->log('');
        $this->logSection('summary', 'Summary');
        $this->log(sprintf('  Total Active Closures: %d',
            \Illuminate\Database\Capsule\Manager::table('naz_closure_period')
                ->where('status', 'active')->count()
        ));
        $this->log(sprintf('  Indefinite Closures: %d',
            \Illuminate\Database\Capsule\Manager::table('naz_closure_period')
                ->where('status', 'active')
                ->where('closure_type', 'indefinite')->count()
        ));

        return 0;
    }
}
