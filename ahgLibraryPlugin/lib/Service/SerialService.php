<?php

declare(strict_types=1);

/**
 * SerialService
 *
 * Manages serial subscriptions and issue check-in.
 * Tracks subscription lifecycle, expected vs received issues, and gaps.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class SerialService
{
    protected static ?SerialService $instance = null;
    protected Logger $logger;

    public function __construct()
    {
        $this->initLogger();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function initLogger(): void
    {
        $this->logger = new Logger('library.serial');
        $logPath = \sfConfig::get('sf_log_dir', '/tmp') . '/library.log';
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath, 30, Logger::DEBUG)
        );
    }

    // ========================================================================
    // SUBSCRIPTIONS
    // ========================================================================

    /**
     * Create a subscription.
     */
    public function createSubscription(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        $id = DB::table('library_subscription')->insertGetId([
            'library_item_id'       => $data['library_item_id'],
            'vendor_name'           => $data['vendor_name'] ?? null,
            'subscription_number'   => $data['subscription_number'] ?? null,
            'subscription_status'   => 'active',
            'start_date'            => $data['start_date'] ?? date('Y-m-d'),
            'end_date'              => $data['end_date'] ?? null,
            'renewal_date'          => $data['renewal_date'] ?? null,
            'frequency'             => $data['frequency'] ?? 'monthly',
            'expected_issues_year'  => $data['expected_issues_year'] ?? 12,
            'cost_per_year'         => $data['cost_per_year'] ?? null,
            'currency'              => $data['currency'] ?? 'USD',
            'budget_id'             => $data['budget_id'] ?? null,
            'notes'                 => $data['notes'] ?? null,
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);

        $this->logger->info('Subscription created', ['id' => $id]);

        return $id;
    }

    /**
     * Update subscription.
     */
    public function updateSubscription(int $id, array $data): bool
    {
        $allowed = [
            'vendor_name', 'subscription_number', 'subscription_status',
            'start_date', 'end_date', 'renewal_date', 'frequency',
            'expected_issues_year', 'cost_per_year', 'currency', 'budget_id', 'notes',
        ];

        $update = array_intersect_key($data, array_flip($allowed));
        $update['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('library_subscription')->where('id', $id)->update($update) > 0;
    }

    /**
     * Get subscription with recent issues.
     */
    public function getSubscription(int $id): ?array
    {
        $sub = DB::table('library_subscription as s')
            ->leftJoin('library_item as li', 's.library_item_id', '=', 'li.id')
            ->leftJoin('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('s.id', $id)
            ->select(['s.*', 'li.call_number', 'li.issn', 'ioi.title'])
            ->first();

        if (!$sub) {
            return null;
        }

        $issues = DB::table('library_serial_issue')
            ->where('subscription_id', $id)
            ->orderBy('expected_date', 'desc')
            ->limit(50)
            ->get()
            ->all();

        return ['subscription' => $sub, 'issues' => $issues];
    }

    /**
     * List subscriptions.
     */
    public function listSubscriptions(array $params = []): array
    {
        $query = DB::table('library_subscription as s')
            ->leftJoin('library_item as li', 's.library_item_id', '=', 'li.id')
            ->leftJoin('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            });

        if (!empty($params['subscription_status'])) {
            $query->where('s.subscription_status', $params['subscription_status']);
        }

        if (!empty($params['q'])) {
            $q = '%' . $params['q'] . '%';
            $query->where(function ($qb) use ($q) {
                $qb->where('ioi.title', 'LIKE', $q)
                    ->orWhere('s.vendor_name', 'LIKE', $q)
                    ->orWhere('li.issn', 'LIKE', $q);
            });
        }

        $total = $query->count();
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = min(100, max(1, (int) ($params['limit'] ?? 25)));

        $rows = $query->select([
                's.*',
                'li.call_number', 'li.issn',
                'ioi.title',
            ])
            ->orderBy('ioi.title')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->all();

        return ['items' => $rows, 'total' => $total, 'page' => $page, 'pages' => (int) ceil($total / $limit)];
    }

    // ========================================================================
    // ISSUE CHECK-IN
    // ========================================================================

    /**
     * Check in a serial issue (received).
     */
    public function checkinIssue(int $subscriptionId, array $data): int
    {
        $now = date('Y-m-d H:i:s');

        $id = DB::table('library_serial_issue')->insertGetId([
            'subscription_id' => $subscriptionId,
            'volume'          => $data['volume'] ?? null,
            'issue_number'    => $data['issue_number'] ?? null,
            'issue_date'      => $data['issue_date'] ?? null,
            'expected_date'   => $data['expected_date'] ?? null,
            'received_date'   => $data['received_date'] ?? date('Y-m-d'),
            'issue_status'    => 'received',
            'supplement'      => $data['supplement'] ?? null,
            'notes'           => $data['notes'] ?? null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $this->logger->info('Serial issue checked in', [
            'id'             => $id,
            'subscription'   => $subscriptionId,
            'volume'         => $data['volume'] ?? '',
            'issue'          => $data['issue_number'] ?? '',
        ]);

        return $id;
    }

    /**
     * Mark an expected issue as missing/claimed.
     */
    public function claimIssue(int $issueId): bool
    {
        return DB::table('library_serial_issue')
            ->where('id', $issueId)
            ->update([
                'issue_status' => 'claimed',
                'updated_at'   => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Generate expected issues for a subscription based on frequency.
     */
    public function generateExpectedIssues(int $subscriptionId, string $startDate, string $endDate): int
    {
        $sub = DB::table('library_subscription')->where('id', $subscriptionId)->first();
        if (!$sub) {
            return 0;
        }

        $frequencyDays = $this->getFrequencyDays($sub->frequency);
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        $count = 0;
        $issueNum = 1;

        $now = date('Y-m-d H:i:s');

        while ($current <= $end) {
            $expectedDate = date('Y-m-d', $current);

            // Check if already exists
            $exists = DB::table('library_serial_issue')
                ->where('subscription_id', $subscriptionId)
                ->where('expected_date', $expectedDate)
                ->exists();

            if (!$exists) {
                DB::table('library_serial_issue')->insert([
                    'subscription_id' => $subscriptionId,
                    'issue_number'    => (string) $issueNum,
                    'expected_date'   => $expectedDate,
                    'issue_status'    => 'expected',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
                $count++;
            }

            $issueNum++;
            $current = strtotime('+' . $frequencyDays . ' days', $current);
        }

        return $count;
    }

    /**
     * Get gap analysis — expected but not received.
     */
    public function getGaps(int $subscriptionId): array
    {
        return DB::table('library_serial_issue')
            ->where('subscription_id', $subscriptionId)
            ->whereIn('issue_status', ['expected', 'claimed'])
            ->where('expected_date', '<', date('Y-m-d'))
            ->orderBy('expected_date')
            ->get()
            ->all();
    }

    /**
     * Get subscriptions due for renewal.
     */
    public function getDueForRenewal(int $daysAhead = 30): array
    {
        $cutoff = date('Y-m-d', strtotime('+' . $daysAhead . ' days'));

        return DB::table('library_subscription as s')
            ->leftJoin('library_item as li', 's.library_item_id', '=', 'li.id')
            ->leftJoin('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('s.subscription_status', 'active')
            ->where('s.renewal_date', '<=', $cutoff)
            ->select(['s.*', 'ioi.title', 'li.issn'])
            ->orderBy('s.renewal_date')
            ->get()
            ->all();
    }

    // ========================================================================
    // HELPERS
    // ========================================================================

    /**
     * Convert frequency name to days.
     */
    protected function getFrequencyDays(string $frequency): int
    {
        return match ($frequency) {
            'daily'         => 1,
            'weekly'        => 7,
            'biweekly'      => 14,
            'monthly'       => 30,
            'bimonthly'     => 60,
            'quarterly'     => 91,
            'triannual'     => 122,
            'semiannual'    => 182,
            'annual'        => 365,
            'biennial'      => 730,
            default         => 30,
        };
    }

    /**
     * Get serial statistics.
     */
    public function getStatistics(): array
    {
        return [
            'active_subscriptions' => DB::table('library_subscription')->where('subscription_status', 'active')->count(),
            'issues_received'      => DB::table('library_serial_issue')->where('issue_status', 'received')->count(),
            'issues_expected'      => DB::table('library_serial_issue')->where('issue_status', 'expected')
                ->where('expected_date', '<=', date('Y-m-d'))
                ->count(),
            'claimed'              => DB::table('library_serial_issue')->where('issue_status', 'claimed')->count(),
            'renewals_due'         => DB::table('library_subscription')
                ->where('subscription_status', 'active')
                ->where('renewal_date', '<=', date('Y-m-d', strtotime('+30 days')))
                ->count(),
        ];
    }
}
