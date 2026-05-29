<?php

declare(strict_types=1);

/**
 * ILLService
 *
 * Manages Interlibrary Loan (ILL) requests — borrowing from and lending to other libraries.
 * All statuses driven by ahg_dropdown (ill_status, ill_direction taxonomies).
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class ILLService
{
    protected static ?ILLService $instance = null;
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
        $this->logger = new Logger('library.ill');
        $logPath = \sfConfig::get('sf_log_dir', '/tmp') . '/library.log';
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath, 30, Logger::DEBUG)
        );
    }

    // ========================================================================
    // CREATE REQUEST
    // ========================================================================

    /**
     * Create a new ILL request.
     *
     * @param string $direction 'borrow' (we request from another library) or 'lend' (another library requests from us)
     */
    public function createRequest(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        $id = DB::table('library_ill_request')->insertGetId([
            'request_number'     => $data['request_number'] ?? ('ILL-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5))),
            'patron_id'          => $data['patron_id'] ?? null,
            'direction'          => $data['direction'] ?? 'borrow',
            'status'             => 'submitted',
            'partner_library'    => $data['partner_library'] ?? $data['lending_library'] ?? $data['requesting_library'] ?? null,
            'title'              => $data['title'],
            'author'             => $data['author'] ?? null,
            'isbn'               => $data['isbn'] ?? null,
            'issn'               => $data['issn'] ?? null,
            'volume_issue'       => $data['volume_issue'] ?? null,
            'pages'              => $data['pages'] ?? $data['pages_needed'] ?? null,
            'request_date'       => $data['request_date'] ?? date('Y-m-d'),
            'needed_by_date'     => $data['needed_by_date'] ?? null,
            'cost'               => $data['cost'] ?? $data['shipping_cost'] ?? null,
            'currency'           => $data['currency'] ?? 'USD',
            'request_type'       => $data['request_type'] ?? 'BORROW',
            'borrowing_protocol' => $data['borrowing_protocol'] ?? 'AARC',
            'trading_partner_id' => $data['trading_partner_id'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);

        $this->logger->info('ILL request created', [
            'id'        => $id,
            'direction' => $data['direction'] ?? 'borrow',
            'title'     => $data['title'],
        ]);

        return $id;
    }

    // ========================================================================
    // STATUS TRANSITIONS
    // ========================================================================

    /**
     * ISO 10160/10161 ILL transaction state machine (#106). Maps each state to
     * the states it may legally transition to. Terminal states have no targets.
     */
    public const ILL_TRANSITIONS = [
        'submitted'        => ['pending', 'unfilled', 'conditional', 'cancelled'],
        'pending'          => ['shipped', 'unfilled', 'conditional', 'cancelled'],
        'conditional'      => ['shipped', 'unfilled', 'cancelled'],
        'shipped'          => ['received', 'lost'],
        'received'         => ['renew_requested', 'overdue', 'returned', 'recalled'],
        'renew_requested'  => ['renewed', 'received', 'overdue'],
        'renewed'          => ['received', 'overdue', 'returned'],
        'overdue'          => ['renewed', 'returned', 'lost'],
        'recalled'         => ['returned'],
        'returned'         => ['checked_in'],
        'checked_in'       => ['completed'],
        'completed'        => [],
        'unfilled'         => [],
        'cancelled'        => [],
        'lost'             => ['completed'],
    ];

    /** Allowed states list. */
    public static function illStates(): array
    {
        return array_keys(self::ILL_TRANSITIONS);
    }

    /** Whether a transition from $from to $to is legal under ISO 10160/10161. */
    public static function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, self::ILL_TRANSITIONS[$from] ?? [], true);
    }

    /**
     * Update ILL request status, enforcing the ISO 10160/10161 state machine,
     * recording history, setting date fields, and dispatching an EDI message on
     * the SHIPPED transition when a trading partner is configured.
     */
    public function updateStatus(int $requestId, string $newStatus, ?string $notes = null): bool
    {
        $request = DB::table('library_ill_request')->where('id', $requestId)->first();
        if (!$request) {
            return false;
        }

        $current = (string) ($request->status ?? 'submitted');
        if (!array_key_exists($newStatus, self::ILL_TRANSITIONS)) {
            $this->logger->warning('ILL: unknown target status', ['id' => $requestId, 'to' => $newStatus]);

            return false;
        }
        if (!self::canTransition($current, $newStatus)) {
            $this->logger->warning('ILL: illegal transition', ['id' => $requestId, 'from' => $current, 'to' => $newStatus]);

            return false;
        }

        $now = date('Y-m-d H:i:s');
        $update = ['status' => $newStatus, 'updated_at' => $now];

        switch ($newStatus) {
            case 'shipped':
                $update['shipped_date'] = date('Y-m-d');
                break;
            case 'received':
                $update['received_date'] = date('Y-m-d');
                $update['due_date'] = date('Y-m-d', strtotime('+30 days'));
                break;
            case 'returned':
                $update['return_date'] = date('Y-m-d');
                break;
            case 'completed':
            case 'cancelled':
            case 'unfilled':
                $update['closed_at'] = $now;
                $update['closed_reason'] = $newStatus;
                break;
        }

        if ($notes) {
            $update['notes'] = trim(($request->notes ?? '') . "\n[" . date('Y-m-d') . '] ' . $notes);
        }

        DB::table('library_ill_request')->where('id', $requestId)->update($update);
        $this->recordHistory($requestId, $current, $newStatus, $notes);

        // EDI dispatch on SHIPPED when an EDI trading partner is set (best-effort).
        if ($newStatus === 'shipped' && !empty($request->trading_partner_id)) {
            $this->dispatchEdi($request, 'shipped');
        }

        $this->logger->info('ILL status updated', ['id' => $requestId, 'from' => $current, 'to' => $newStatus]);

        return true;
    }

    /**
     * Append a row to the ILL status history audit trail.
     */
    private function recordHistory(int $requestId, string $from, string $to, ?string $notes): void
    {
        try {
            if (!DB::schema()->hasTable('library_ill_status_history')) {
                return;
            }
            DB::table('library_ill_status_history')->insert([
                'ill_request_id' => $requestId,
                'from_status'    => $from,
                'to_status'      => $to,
                'notes'          => $notes,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // history is best-effort
        }
    }

    /**
     * Best-effort EDI dispatch via the trading partner's adapter.
     */
    private function dispatchEdi(object $request, string $event): void
    {
        try {
            require_once __DIR__ . '/EdiAdapter.php';
            $partner = DB::table('library_trading_partner')->where('id', $request->trading_partner_id)->first();
            if (!$partner) {
                return;
            }
            $result = (new \EdiAdapter($partner))->sendIllRequest($request);
            if (!empty($result['edi_message_id'])) {
                DB::table('library_ill_request')->where('id', $request->id)
                    ->update(['edi_message_id' => $result['edi_message_id']]);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('ILL EDI dispatch failed', ['id' => $request->id, 'err' => $e->getMessage()]);
        }
    }

    // ========================================================================
    // QUERIES
    // ========================================================================

    /**
     * Get a single request.
     */
    public function find(int $id): ?object
    {
        return DB::table('library_ill_request as ill')
            ->leftJoin('library_patron as p', 'ill.patron_id', '=', 'p.id')
            ->where('ill.id', $id)
            ->select([
                'ill.*',
                'p.first_name', 'p.last_name', 'p.card_number as patron_barcode', 'p.email',
            ])
            ->first();
    }

    /**
     * Search ILL requests.
     */
    public function search(array $params = []): array
    {
        $query = DB::table('library_ill_request as ill')
            ->leftJoin('library_patron as p', 'ill.patron_id', '=', 'p.id');

        if (!empty($params['direction'])) {
            $query->where('ill.direction', $params['direction']);
        }

        if (!empty($params['ill_status'])) {
            $query->where('ill.status', $params['ill_status']);
        }

        if (!empty($params['q'])) {
            $q = '%' . $params['q'] . '%';
            $query->where(function ($qb) use ($q) {
                $qb->where('ill.title', 'LIKE', $q)
                    ->orWhere('ill.author', 'LIKE', $q)
                    ->orWhere('ill.isbn', 'LIKE', $q)
                    ->orWhere('ill.requesting_library', 'LIKE', $q)
                    ->orWhere('ill.lending_library', 'LIKE', $q);
            });
        }

        $total = $query->count();
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = min(100, max(1, (int) ($params['limit'] ?? 25)));

        $rows = $query->select([
                'ill.*',
                'p.first_name', 'p.last_name', 'p.card_number as patron_barcode',
            ])
            ->orderBy('ill.request_date', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->all();

        return ['items' => $rows, 'total' => $total, 'page' => $page, 'pages' => (int) ceil($total / $limit)];
    }

    /**
     * Get active ILL requests for a patron.
     */
    public function getPatronRequests(int $patronId): array
    {
        return DB::table('library_ill_request')
            ->where('patron_id', $patronId)
            ->whereNotIn('status', ['returned', 'cancelled'])
            ->orderBy('request_date', 'desc')
            ->get()
            ->all();
    }

    /**
     * Get overdue ILL items (borrowed from other libraries, not returned).
     */
    public function getOverdueItems(): array
    {
        return DB::table('library_ill_request')
            ->where('direction', 'borrow')
            ->where('status', 'received')
            ->where('due_date', '<', date('Y-m-d'))
            ->orderBy('due_date')
            ->get()
            ->all();
    }

    // ========================================================================
    // STATISTICS
    // ========================================================================

    /**
     * Get ILL statistics.
     */
    public function getStatistics(): array
    {
        return [
            'borrow_active'  => DB::table('library_ill_request')
                ->where('direction', 'borrow')
                ->whereNotIn('status', ['returned', 'cancelled'])
                ->count(),
            'lend_active'    => DB::table('library_ill_request')
                ->where('direction', 'lend')
                ->whereNotIn('status', ['returned', 'cancelled'])
                ->count(),
            'total_requests' => DB::table('library_ill_request')->count(),
            'overdue'        => DB::table('library_ill_request')
                ->where('status', 'received')
                ->where('due_date', '<', date('Y-m-d'))
                ->count(),
        ];
    }
}
