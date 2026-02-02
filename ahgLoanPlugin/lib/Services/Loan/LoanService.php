<?php

declare(strict_types=1);

namespace AhgLoan\Services\Loan;

use ahgCorePlugin\Services\AhgTaxonomyService;
use AhgLoan\Adapters\SectorAdapterInterface;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Core Loan Management Service.
 *
 * Provides sector-agnostic loan management functionality.
 * Supports Museums, Galleries, Archives, and DAM through adapters.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class LoanService
{
    /** Loan types */
    public const TYPE_OUT = 'out';
    public const TYPE_IN = 'in';

    /** Loan purposes (common across sectors) */
    public const PURPOSES = [
        'exhibition' => 'Exhibition/Display',
        'research' => 'Research/Study',
        'conservation' => 'Conservation/Restoration',
        'photography' => 'Photography/Imaging',
        'education' => 'Educational Program',
        'filming' => 'Film/Media Production',
        'publication' => 'Publication',
        'long_term' => 'Long-term Loan',
        'licensing' => 'Licensing (Digital)',
        'other' => 'Other',
    ];

    /** Insurance types */
    public const INSURANCE_TYPES = [
        'borrower' => 'Borrower (Wall-to-Wall)',
        'lender' => 'Lender Coverage',
        'shared' => 'Shared Coverage',
        'government' => 'Government Indemnity',
        'self' => 'Self-Insured',
        'none' => 'No Insurance Required',
    ];

    /**
     * @deprecated Use AhgTaxonomyService::getLoanStatuses() instead
     */
    public const STATUSES = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'preparing' => 'Preparing',
        'dispatched' => 'Dispatched',
        'in_transit' => 'In Transit',
        'received' => 'Received',
        'on_loan' => 'On Loan',
        'return_requested' => 'Return Requested',
        'returned' => 'Returned',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ];

    private ConnectionInterface $db;
    private LoggerInterface $logger;
    private ?SectorAdapterInterface $sectorAdapter = null;
    private string $sector = 'museum';
    private AhgTaxonomyService $taxonomyService;

    public function __construct(
        ConnectionInterface $db,
        ?LoggerInterface $logger = null,
        ?AhgTaxonomyService $taxonomyService = null
    ) {
        $this->db = $db;
        $this->logger = $logger ?? new NullLogger();
        $this->taxonomyService = $taxonomyService ?? new AhgTaxonomyService();
    }

    /**
     * Set the sector adapter for sector-specific behavior.
     */
    public function setSectorAdapter(SectorAdapterInterface $adapter): self
    {
        $this->sectorAdapter = $adapter;
        $this->sector = $adapter->getSectorCode();

        return $this;
    }

    /**
     * Get current sector.
     */
    public function getSector(): string
    {
        return $this->sector;
    }

    /**
     * Create a new loan record.
     *
     * @param string $type   Loan type (out/in)
     * @param array  $data   Loan data
     * @param int    $userId Creating user ID
     *
     * @return int Loan ID
     */
    public function create(string $type, array $data, int $userId): int
    {
        $now = date('Y-m-d H:i:s');

        // Generate loan number with sector prefix
        $loanNumber = $this->generateLoanNumber($type);

        // Apply sector-specific validation
        if ($this->sectorAdapter) {
            $data = $this->sectorAdapter->validateLoanData($data);
        }

        $loanId = $this->db->table('ahg_loan')->insertGetId([
            'loan_number' => $loanNumber,
            'loan_type' => $type,
            'sector' => $this->sector,
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

            // Status
            'status' => 'draft',

            // Internal tracking
            'internal_approver_id' => $data['internal_approver_id'] ?? null,
            'approved_date' => null,
            'notes' => $data['notes'] ?? null,

            // Sector-specific data (JSON)
            'sector_data' => isset($data['sector_data']) ? json_encode($data['sector_data']) : null,

            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Add loan objects if provided
        if (!empty($data['objects'])) {
            foreach ($data['objects'] as $object) {
                $this->addObject($loanId, $object);
            }
        }

        // Trigger sector-specific post-create actions
        if ($this->sectorAdapter) {
            $this->sectorAdapter->onLoanCreated($loanId, $data);
        }

        $this->logger->info('Loan created', [
            'loan_id' => $loanId,
            'loan_number' => $loanNumber,
            'type' => $type,
            'sector' => $this->sector,
        ]);

        return $loanId;
    }

    /**
     * Get loan by ID.
     */
    public function get(int $loanId): ?array
    {
        $loan = $this->db->table('ahg_loan')
            ->where('id', $loanId)
            ->first();

        if (!$loan) {
            return null;
        }

        $data = (array) $loan;

        // Decode sector data
        if ($data['sector_data']) {
            $data['sector_data'] = json_decode($data['sector_data'], true);
        }

        // Get objects
        $data['objects'] = $this->getObjects($loanId);

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

        // Apply sector-specific enrichment
        if ($this->sectorAdapter) {
            $data = $this->sectorAdapter->enrichLoanData($data);
        }

        return $data;
    }

    /**
     * Get loan by loan number.
     */
    public function getByNumber(string $loanNumber): ?array
    {
        $loan = $this->db->table('ahg_loan')
            ->where('loan_number', $loanNumber)
            ->first();

        if (!$loan) {
            return null;
        }

        return $this->get($loan->id);
    }

    /**
     * Update loan.
     */
    public function update(int $loanId, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Handle sector data
        if (isset($data['sector_data']) && is_array($data['sector_data'])) {
            $data['sector_data'] = json_encode($data['sector_data']);
        }

        // Remove non-updateable fields
        unset($data['id'], $data['loan_number'], $data['loan_type'], $data['sector'], $data['created_by'], $data['created_at']);

        return $this->db->table('ahg_loan')
            ->where('id', $loanId)
            ->update($data) > 0;
    }

    /**
     * Update loan status.
     */
    public function updateStatus(int $loanId, string $status, int $userId, ?string $comment = null): bool
    {
        $loan = $this->get($loanId);
        if (!$loan) {
            return false;
        }

        $previousStatus = $loan['status'];

        $updateData = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Set approved date if transitioning to approved
        if ('approved' === $status && 'approved' !== $previousStatus) {
            $updateData['approved_date'] = date('Y-m-d H:i:s');
            $updateData['internal_approver_id'] = $userId;
        }

        $result = $this->db->table('ahg_loan')
            ->where('id', $loanId)
            ->update($updateData);

        // Log status change
        $this->db->table('ahg_loan_status_history')->insert([
            'loan_id' => $loanId,
            'from_status' => $previousStatus,
            'to_status' => $status,
            'changed_by' => $userId,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Trigger sector-specific status change actions
        if ($this->sectorAdapter) {
            $this->sectorAdapter->onStatusChanged($loanId, $previousStatus, $status);
        }

        return $result > 0;
    }

    /**
     * Add object to loan.
     */
    public function addObject(int $loanId, array $object): int
    {
        return $this->db->table('ahg_loan_object')->insertGetId([
            'loan_id' => $loanId,
            'information_object_id' => $object['information_object_id'] ?? null,
            'external_object_id' => $object['external_object_id'] ?? null,
            'object_title' => $object['object_title'] ?? null,
            'object_identifier' => $object['object_identifier'] ?? null,
            'object_type' => $object['object_type'] ?? null,
            'insurance_value' => $object['insurance_value'] ?? null,
            'condition_report_id' => $object['condition_report_id'] ?? null,
            'special_requirements' => $object['special_requirements'] ?? null,
            'display_requirements' => $object['display_requirements'] ?? null,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Remove object from loan.
     */
    public function removeObject(int $loanId, int $objectId): bool
    {
        return $this->db->table('ahg_loan_object')
            ->where('loan_id', $loanId)
            ->where('id', $objectId)
            ->delete() > 0;
    }

    /**
     * Get objects for a loan.
     */
    public function getObjects(int $loanId): array
    {
        $objects = $this->db->table('ahg_loan_object as lo')
            ->leftJoin('information_object as io', 'io.id', '=', 'lo.information_object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('ioi.id', '=', 'io.id')
                    ->where('ioi.culture', '=', 'en');
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

        // Apply sector-specific object enrichment
        if ($this->sectorAdapter) {
            $objects = array_map(
                fn ($obj) => $this->sectorAdapter->enrichObjectData($obj),
                $objects
            );
        }

        return $objects;
    }

    /**
     * Search loans.
     */
    public function search(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = $this->db->table('ahg_loan as l');

        // Filter by sector if set
        if ($this->sector) {
            $query->where('l.sector', $this->sector);
        }

        // Apply filters
        if (!empty($filters['loan_type'])) {
            $query->where('l.loan_type', $filters['loan_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('l.status', $filters['status']);
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
                ->whereNotIn('l.status', ['closed', 'cancelled', 'returned']);
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
            ->orderByDesc('l.created_at')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        // Add object count to results
        foreach ($results as &$loan) {
            $loan['object_count'] = $this->db->table('ahg_loan_object')
                ->where('loan_id', $loan['id'])
                ->count();
        }

        return [
            'total' => $total,
            'results' => $results,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Get loans due soon.
     */
    public function getDueSoon(int $days = 30): array
    {
        $futureDate = date('Y-m-d', strtotime("+{$days} days"));

        $query = $this->db->table('ahg_loan as l')
            ->where('l.end_date', '<=', $futureDate)
            ->where('l.end_date', '>=', date('Y-m-d'))
            ->whereNull('l.return_date')
            ->whereNotIn('l.status', ['closed', 'cancelled', 'returned']);

        if ($this->sector) {
            $query->where('l.sector', $this->sector);
        }

        return $query->orderBy('l.end_date')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Get overdue loans.
     */
    public function getOverdue(): array
    {
        $query = $this->db->table('ahg_loan as l')
            ->where('l.end_date', '<', date('Y-m-d'))
            ->whereNull('l.return_date')
            ->whereNotIn('l.status', ['closed', 'cancelled', 'returned']);

        if ($this->sector) {
            $query->where('l.sector', $this->sector);
        }

        return $query->orderBy('l.end_date')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Add document to loan.
     */
    public function addDocument(int $loanId, string $documentType, string $filePath, array $metadata = []): int
    {
        return $this->db->table('ahg_loan_document')->insertGetId([
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
        return $this->db->table('ahg_loan_document')
            ->where('loan_id', $loanId)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Extend loan end date.
     */
    public function extend(int $loanId, string $newEndDate, ?string $reason, int $userId): bool
    {
        $loan = $this->get($loanId);
        if (!$loan) {
            return false;
        }

        // Log extension
        $this->db->table('ahg_loan_extension')->insert([
            'loan_id' => $loanId,
            'previous_end_date' => $loan['end_date'],
            'new_end_date' => $newEndDate,
            'reason' => $reason,
            'approved_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->update($loanId, ['end_date' => $newEndDate]);
    }

    /**
     * Record loan return.
     */
    public function recordReturn(int $loanId, string $returnDate, ?string $notes, int $userId): bool
    {
        $updated = $this->update($loanId, [
            'return_date' => $returnDate,
        ]);

        if ($updated) {
            $this->updateStatus($loanId, 'returned', $userId, $notes ?? 'Objects returned');
        }

        return $updated;
    }

    /**
     * Get loan statistics.
     */
    public function getStatistics(): array
    {
        $query = $this->db->table('ahg_loan');

        if ($this->sector) {
            $query->where('sector', $this->sector);
        }

        $stats = [
            'total_loans' => $query->count(),
            'active_loans_out' => 0,
            'active_loans_in' => 0,
            'overdue' => 0,
            'due_this_month' => 0,
            'total_insurance_value' => 0,
            'by_purpose' => [],
            'by_status' => [],
        ];

        // Active by type
        $activeQuery = $this->db->table('ahg_loan')
            ->whereNotIn('status', ['closed', 'cancelled', 'returned']);

        if ($this->sector) {
            $activeQuery->where('sector', $this->sector);
        }

        $active = $activeQuery->selectRaw('loan_type, COUNT(*) as count')
            ->groupBy('loan_type')
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
        $dueSoonQuery = $this->db->table('ahg_loan')
            ->where('end_date', '<=', $monthEnd)
            ->where('end_date', '>=', date('Y-m-01'))
            ->whereNull('return_date')
            ->whereNotIn('status', ['closed', 'cancelled', 'returned']);

        if ($this->sector) {
            $dueSoonQuery->where('sector', $this->sector);
        }

        $stats['due_this_month'] = $dueSoonQuery->count();

        // Total insurance value
        $insuranceQuery = $this->db->table('ahg_loan')
            ->where('loan_type', 'out')
            ->whereNotIn('status', ['closed', 'cancelled', 'returned']);

        if ($this->sector) {
            $insuranceQuery->where('sector', $this->sector);
        }

        $stats['total_insurance_value'] = $insuranceQuery->sum('insurance_value') ?? 0;

        // By purpose
        $purposeQuery = $this->db->table('ahg_loan')
            ->selectRaw('purpose, COUNT(*) as count')
            ->groupBy('purpose');

        if ($this->sector) {
            $purposeQuery->where('sector', $this->sector);
        }

        $byPurpose = $purposeQuery->get();
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
        $typePrefix = self::TYPE_OUT === $type ? 'LO' : 'LI';
        $sectorPrefix = strtoupper(substr($this->sector, 0, 3));
        $year = date('Y');

        $pattern = "{$sectorPrefix}-{$typePrefix}-{$year}-%";

        $lastLoan = $this->db->table('ahg_loan')
            ->where('loan_number', 'LIKE', $pattern)
            ->orderByDesc('loan_number')
            ->first();

        if ($lastLoan) {
            $parts = explode('-', $lastLoan->loan_number);
            $seq = (int) end($parts) + 1;
        } else {
            $seq = 1;
        }

        return sprintf('%s-%s-%s-%04d', $sectorPrefix, $typePrefix, $year, $seq);
    }

    /**
     * Get purposes for dropdown.
     */
    public function getPurposes(): array
    {
        // Get sector-specific purposes if available
        if ($this->sectorAdapter) {
            return $this->sectorAdapter->getPurposes();
        }

        return self::PURPOSES;
    }

    /**
     * Get insurance types for dropdown.
     */
    public function getInsuranceTypes(): array
    {
        return self::INSURANCE_TYPES;
    }

    /**
     * Get statuses for dropdown from database.
     */
    public function getStatuses(): array
    {
        return $this->taxonomyService->getLoanStatuses(false);
    }

    /**
     * Get statuses with colors from database.
     */
    public function getStatusesWithColors(): array
    {
        return $this->taxonomyService->getLoanStatusesWithColors();
    }
}
