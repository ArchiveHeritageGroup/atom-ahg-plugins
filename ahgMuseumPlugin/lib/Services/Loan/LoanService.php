<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Loan;

use arMuseumMetadataPlugin\Services\Workflow\WorkflowEngine;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Loan Management Service.
 *
 * Comprehensive loan management for museum objects including:
 * - Loan out (lending to other institutions)
 * - Loan in (borrowing from other institutions)
 * - Loan agreement generation
 * - Insurance and valuation tracking
 * - Due date monitoring and reminders
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class LoanService
{
    /** Loan types */
    public const TYPE_OUT = 'out';
    public const TYPE_IN = 'in';

    /** Loan purposes */
    public const PURPOSES = [
        'exhibition' => 'Exhibition',
        'research' => 'Research/Study',
        'conservation' => 'Conservation Treatment',
        'photography' => 'Photography/Imaging',
        'education' => 'Educational Program',
        'filming' => 'Film/Television Production',
        'long_term' => 'Long-term Display',
        'other' => 'Other',
    ];

    /** Insurance types */
    public const INSURANCE_TYPES = [
        'borrower' => 'Borrower (Wall-to-Wall)',
        'lender' => 'Lender Coverage',
        'shared' => 'Shared Coverage',
        'government' => 'Government Indemnity',
        'self' => 'Self-Insured',
    ];

    private ConnectionInterface $db;
    private WorkflowEngine $workflow;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $db,
        WorkflowEngine $workflow,
        ?LoggerInterface $logger = null
    ) {
        $this->db = $db;
        $this->workflow = $workflow;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Create a new loan record.
     *
     * @param string $type    Loan type (out/in)
     * @param array  $data    Loan data
     * @param int    $userId  Creating user ID
     *
     * @return int Loan ID
     */
    public function create(string $type, array $data, int $userId): int
    {
        $now = date('Y-m-d H:i:s');

        // Generate loan number
        $loanNumber = $this->generateLoanNumber($type);

        $loanId = $this->db->table('loan')->insertGetId([
            'loan_number' => $loanNumber,
            'loan_type' => $type,
            'purpose' => $data['purpose'] ?? 'exhibition',
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,

            // Partner institution
            'partner_institution' => $data['partner_institution'],
            'partner_contact_name' => $data['partner_contact_name'] ?? null,
            'partner_contact_email' => $data['partner_contact_email'] ?? null,
            'partner_contact_phone' => $data['partner_contact_phone'] ?? null,
            'partner_address' => $data['partner_address'] ?? null,

            // Dates
            'request_date' => $data['request_date'] ?? $now,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'return_date' => null,

            // Insurance
            'insurance_type' => $data['insurance_type'] ?? 'borrower',
            'insurance_value' => $data['insurance_value'] ?? null,
            'insurance_currency' => $data['insurance_currency'] ?? 'ZAR',
            'insurance_policy_number' => $data['insurance_policy_number'] ?? null,
            'insurance_provider' => $data['insurance_provider'] ?? null,

            // Fees
            'loan_fee' => $data['loan_fee'] ?? null,
            'loan_fee_currency' => $data['loan_fee_currency'] ?? 'ZAR',

            // Internal tracking
            'internal_approver_id' => $data['internal_approver_id'] ?? null,
            'approved_date' => null,
            'notes' => $data['notes'] ?? null,

            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Add loan objects
        if (!empty($data['objects'])) {
            foreach ($data['objects'] as $object) {
                $this->addObject($loanId, $object);
            }
        }

        // Initialize workflow
        $workflowId = self::TYPE_OUT === $type ? 'loan_out' : 'loan_in';
        $this->workflow->createInstance($workflowId, 'loan', $loanId, $userId, [
            'loan_number' => $loanNumber,
            'partner' => $data['partner_institution'],
        ]);

        $this->logger->info('Loan created', [
            'loan_id' => $loanId,
            'loan_number' => $loanNumber,
            'type' => $type,
        ]);

        return $loanId;
    }

    /**
     * Get loan by ID.
     *
     * @param int $loanId Loan ID
     *
     * @return array|null Loan data
     */
    public function get(int $loanId): ?array
    {
        $loan = $this->db->table('loan')
            ->where('id', $loanId)
            ->first();

        if (!$loan) {
            return null;
        }

        $data = (array) $loan;

        // Get objects
        $data['objects'] = $this->getObjects($loanId);

        // Get workflow instance
        $data['workflow'] = $this->workflow->getInstanceForEntity('loan', $loanId);

        // Get documents
        $data['documents'] = $this->getDocuments($loanId);

        // Calculate days remaining/overdue
        if ($data['end_date'] && !$data['return_date']) {
            $endDate = new \DateTime($data['end_date']);
            $today = new \DateTime();
            $diff = $today->diff($endDate);
            $data['days_remaining'] = $endDate > $today ? $diff->days : -$diff->days;
            $data['is_overdue'] = $endDate < $today;
        }

        return $data;
    }

    /**
     * Get loan by loan number.
     */
    public function getByNumber(string $loanNumber): ?array
    {
        $loan = $this->db->table('loan')
            ->where('loan_number', $loanNumber)
            ->first();

        if (!$loan) {
            return null;
        }

        return $this->get($loan->id);
    }

    /**
     * Update loan.
     *
     * @param int   $loanId Loan ID
     * @param array $data   Update data
     *
     * @return bool Success
     */
    public function update(int $loanId, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Remove non-updateable fields
        unset($data['id'], $data['loan_number'], $data['loan_type'], $data['created_by'], $data['created_at']);

        return $this->db->table('loan')
            ->where('id', $loanId)
            ->update($data) > 0;
    }

    /**
     * Add object to loan.
     *
     * @param int   $loanId Loan ID
     * @param array $object Object data
     *
     * @return int Loan object ID
     */
    public function addObject(int $loanId, array $object): int
    {
        return $this->db->table('loan_object')->insertGetId([
            'loan_id' => $loanId,
            'information_object_id' => $object['information_object_id'],
            'object_title' => $object['object_title'] ?? null,
            'object_identifier' => $object['object_identifier'] ?? null,
            'insurance_value' => $object['insurance_value'] ?? null,
            'condition_report_id' => $object['condition_report_id'] ?? null,
            'special_requirements' => $object['special_requirements'] ?? null,
            'display_requirements' => $object['display_requirements'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Remove object from loan.
     */
    public function removeObject(int $loanId, int $objectId): bool
    {
        return $this->db->table('loan_object')
            ->where('loan_id', $loanId)
            ->where('information_object_id', $objectId)
            ->delete() > 0;
    }

    /**
     * Get objects for a loan.
     */
    public function getObjects(int $loanId): array
    {
        return $this->db->table('loan_object as lo')
            ->leftJoin('information_object as io', 'io.id', '=', 'lo.information_object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('ioi.id', '=', 'io.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('lo.loan_id', $loanId)
            ->select(
                'lo.*',
                'io.identifier',
                'ioi.title as io_title'
            )
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Transition loan workflow state.
     *
     * @param int         $loanId     Loan ID
     * @param string      $transition Transition to execute
     * @param int         $userId     User ID
     * @param string|null $comment    Comment
     *
     * @return array Updated workflow instance
     */
    public function transition(int $loanId, string $transition, int $userId, ?string $comment = null): array
    {
        $instance = $this->workflow->getInstanceForEntity('loan', $loanId);
        if (!$instance) {
            throw new \RuntimeException("No workflow instance for loan {$loanId}");
        }

        return $this->workflow->transition($instance['id'], $transition, $userId, $comment);
    }

    /**
     * Search loans.
     *
     * @param array $filters Search filters
     * @param int   $limit   Maximum results
     * @param int   $offset  Result offset
     *
     * @return array Search results
     */
    public function search(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = $this->db->table('loan as l')
            ->leftJoin('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            });

        // Apply filters
        if (!empty($filters['loan_type'])) {
            $query->where('l.loan_type', $filters['loan_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('wi.current_state', $filters['status']);
        }

        if (!empty($filters['partner'])) {
            $query->where('l.partner_institution', 'LIKE', "%{$filters['partner']}%");
        }

        if (!empty($filters['date_from'])) {
            $query->where('l.start_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('l.end_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['overdue'])) {
            $query->where('l.end_date', '<', date('Y-m-d'))
                ->whereNull('l.return_date')
                ->where('wi.is_complete', false);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('l.loan_number', 'LIKE', "%{$search}%")
                    ->orWhere('l.title', 'LIKE', "%{$search}%")
                    ->orWhere('l.partner_institution', 'LIKE', "%{$search}%");
            });
        }

        $total = $query->count();

        $results = $query
            ->select(
                'l.*',
                'wi.current_state',
                'wi.is_complete as workflow_complete'
            )
            ->orderByDesc('l.created_at')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        return [
            'total' => $total,
            'results' => $results,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Get loans due soon.
     *
     * @param int $days Days threshold
     *
     * @return array Loans due within threshold
     */
    public function getDueSoon(int $days = 30): array
    {
        $futureDate = date('Y-m-d', strtotime("+{$days} days"));

        return $this->db->table('loan as l')
            ->leftJoin('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->where('l.end_date', '<=', $futureDate)
            ->where('l.end_date', '>=', date('Y-m-d'))
            ->whereNull('l.return_date')
            ->where('wi.is_complete', false)
            ->select('l.*', 'wi.current_state')
            ->orderBy('l.end_date')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Get overdue loans.
     *
     * @return array Overdue loans
     */
    public function getOverdue(): array
    {
        return $this->db->table('loan as l')
            ->leftJoin('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->where('l.end_date', '<', date('Y-m-d'))
            ->whereNull('l.return_date')
            ->where('wi.is_complete', false)
            ->select('l.*', 'wi.current_state')
            ->orderBy('l.end_date')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Add document to loan.
     *
     * @param int    $loanId       Loan ID
     * @param string $documentType Document type
     * @param string $filePath     File path
     * @param array  $metadata     Additional metadata
     *
     * @return int Document ID
     */
    public function addDocument(int $loanId, string $documentType, string $filePath, array $metadata = []): int
    {
        return $this->db->table('loan_document')->insertGetId([
            'loan_id' => $loanId,
            'document_type' => $documentType,
            'file_path' => $filePath,
            'file_name' => $metadata['file_name'] ?? basename($filePath),
            'mime_type' => $metadata['mime_type'] ?? null,
            'file_size' => $metadata['file_size'] ?? null,
            'description' => $metadata['description'] ?? null,
            'uploaded_by' => $metadata['uploaded_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get documents for loan.
     */
    public function getDocuments(int $loanId): array
    {
        return $this->db->table('loan_document')
            ->where('loan_id', $loanId)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Extend loan end date.
     *
     * @param int         $loanId     Loan ID
     * @param string      $newEndDate New end date
     * @param string|null $reason     Extension reason
     * @param int         $userId     User ID
     *
     * @return bool Success
     */
    public function extend(int $loanId, string $newEndDate, ?string $reason, int $userId): bool
    {
        $loan = $this->get($loanId);
        if (!$loan) {
            return false;
        }

        // Log extension
        $this->db->table('loan_extension')->insert([
            'loan_id' => $loanId,
            'previous_end_date' => $loan['end_date'],
            'new_end_date' => $newEndDate,
            'reason' => $reason,
            'approved_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Update loan
        return $this->update($loanId, ['end_date' => $newEndDate]);
    }

    /**
     * Record loan return.
     *
     * @param int         $loanId     Loan ID
     * @param string      $returnDate Return date
     * @param string|null $notes      Return notes
     * @param int         $userId     User ID
     *
     * @return bool Success
     */
    public function recordReturn(int $loanId, string $returnDate, ?string $notes, int $userId): bool
    {
        $updated = $this->update($loanId, [
            'return_date' => $returnDate,
            'notes' => $notes ? ($this->get($loanId)['notes'] ?? '')."\n\nReturn notes: ".$notes : null,
        ]);

        if ($updated) {
            // Transition workflow to return state
            try {
                $this->transition($loanId, 'receive_return', $userId, 'Object returned');
            } catch (\Exception $e) {
                $this->logger->warning('Could not transition workflow on return', [
                    'loan_id' => $loanId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $updated;
    }

    /**
     * Get loan statistics.
     *
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        $stats = [
            'total_loans' => 0,
            'active_loans_out' => 0,
            'active_loans_in' => 0,
            'overdue' => 0,
            'due_this_month' => 0,
            'total_insurance_value' => 0,
            'by_purpose' => [],
            'by_status' => [],
        ];

        // Total
        $stats['total_loans'] = $this->db->table('loan')->count();

        // Active by type
        $active = $this->db->table('loan as l')
            ->join('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->where('wi.is_complete', false)
            ->selectRaw('l.loan_type, COUNT(*) as count')
            ->groupBy('l.loan_type')
            ->get();

        foreach ($active as $row) {
            if ('out' === $row->loan_type) {
                $stats['active_loans_out'] = $row->count;
            } else {
                $stats['active_loans_in'] = $row->count;
            }
        }

        // Overdue
        $stats['overdue'] = count($this->getOverdue());

        // Due this month
        $monthEnd = date('Y-m-t');
        $stats['due_this_month'] = $this->db->table('loan as l')
            ->join('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->where('l.end_date', '<=', $monthEnd)
            ->where('l.end_date', '>=', date('Y-m-01'))
            ->whereNull('l.return_date')
            ->where('wi.is_complete', false)
            ->count();

        // Total insurance value (active loans out)
        $insuranceSum = $this->db->table('loan as l')
            ->join('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->where('l.loan_type', 'out')
            ->where('wi.is_complete', false)
            ->sum('l.insurance_value');

        $stats['total_insurance_value'] = $insuranceSum ?? 0;

        // By purpose
        $byPurpose = $this->db->table('loan')
            ->selectRaw('purpose, COUNT(*) as count')
            ->groupBy('purpose')
            ->get();

        foreach ($byPurpose as $row) {
            $stats['by_purpose'][$row->purpose] = $row->count;
        }

        return $stats;
    }

    /**
     * Generate unique loan number.
     */
    private function generateLoanNumber(string $type): string
    {
        $prefix = self::TYPE_OUT === $type ? 'LO' : 'LI';
        $year = date('Y');

        // Get next sequence
        $lastLoan = $this->db->table('loan')
            ->where('loan_number', 'LIKE', "{$prefix}-{$year}-%")
            ->orderByDesc('loan_number')
            ->first();

        if ($lastLoan) {
            $parts = explode('-', $lastLoan->loan_number);
            $seq = (int) end($parts) + 1;
        } else {
            $seq = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $seq);
    }

    /**
     * Get purposes for dropdown.
     */
    public function getPurposes(): array
    {
        return self::PURPOSES;
    }

    /**
     * Get insurance types for dropdown.
     */
    public function getInsuranceTypes(): array
    {
        return self::INSURANCE_TYPES;
    }
}
