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
            'patron_id'            => $data['patron_id'] ?? null,
            'direction'            => $data['direction'] ?? 'borrow',
            'ill_status'           => 'submitted',
            'requesting_library'   => $data['requesting_library'] ?? null,
            'lending_library'      => $data['lending_library'] ?? null,
            'title'                => $data['title'],
            'author'               => $data['author'] ?? null,
            'isbn'                 => $data['isbn'] ?? null,
            'issn'                 => $data['issn'] ?? null,
            'volume_issue'         => $data['volume_issue'] ?? null,
            'pages_needed'         => $data['pages_needed'] ?? null,
            'request_date'         => $data['request_date'] ?? date('Y-m-d'),
            'needed_by_date'       => $data['needed_by_date'] ?? null,
            'shipping_cost'        => $data['shipping_cost'] ?? null,
            'currency'             => $data['currency'] ?? 'USD',
            'notes'                => $data['notes'] ?? null,
            'created_at'           => $now,
            'updated_at'           => $now,
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
     * Update ILL request status.
     */
    public function updateStatus(int $requestId, string $newStatus, ?string $notes = null): bool
    {
        $request = DB::table('library_ill_request')->where('id', $requestId)->first();
        if (!$request) {
            return false;
        }

        $update = [
            'ill_status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Set date fields based on status
        switch ($newStatus) {
            case 'sent':
                $update['sent_date'] = date('Y-m-d');
                break;
            case 'received':
                $update['received_date'] = date('Y-m-d');
                $update['due_date'] = date('Y-m-d', strtotime('+30 days'));
                break;
            case 'returned':
                $update['returned_date'] = date('Y-m-d');
                break;
        }

        if ($notes) {
            $update['notes'] = trim(($request->notes ?? '') . "\n[" . date('Y-m-d') . '] ' . $notes);
        }

        DB::table('library_ill_request')
            ->where('id', $requestId)
            ->update($update);

        $this->logger->info('ILL status updated', ['id' => $requestId, 'status' => $newStatus]);

        return true;
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
                'p.first_name', 'p.last_name', 'p.patron_barcode', 'p.email',
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
            $query->where('ill.ill_status', $params['ill_status']);
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
                'p.first_name', 'p.last_name', 'p.patron_barcode',
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
            ->whereNotIn('ill_status', ['returned', 'cancelled'])
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
            ->where('ill_status', 'received')
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
                ->whereNotIn('ill_status', ['returned', 'cancelled'])
                ->count(),
            'lend_active'    => DB::table('library_ill_request')
                ->where('direction', 'lend')
                ->whereNotIn('ill_status', ['returned', 'cancelled'])
                ->count(),
            'total_requests' => DB::table('library_ill_request')->count(),
            'overdue'        => DB::table('library_ill_request')
                ->where('ill_status', 'received')
                ->where('due_date', '<', date('Y-m-d'))
                ->count(),
        ];
    }
}
