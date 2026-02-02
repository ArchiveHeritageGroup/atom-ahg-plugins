<?php

declare(strict_types=1);

namespace AhgLoan\Services\Loan;

use ahgCorePlugin\Services\AhgTaxonomyService;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Courier Management Service.
 *
 * Manages courier providers and shipment tracking for loan objects.
 * Supports art-specialist couriers and general transport providers.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class CourierManagementService
{
    /**
     * @deprecated Use AhgTaxonomyService::getShipmentTypes() instead
     */
    public const SHIPMENT_TYPES = [
        'outbound' => 'Outbound (To Borrower)',
        'return' => 'Return (To Lender)',
    ];

    /**
     * @deprecated Use AhgTaxonomyService::getShipmentStatuses() instead
     */
    public const SHIPMENT_STATUSES = [
        'planned' => 'Planned',
        'picked_up' => 'Picked Up',
        'in_transit' => 'In Transit',
        'customs' => 'In Customs',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'failed' => 'Delivery Failed',
        'returned' => 'Returned to Sender',
    ];

    /**
     * @deprecated Use AhgTaxonomyService::getCostTypes() instead
     */
    public const COST_TYPES = [
        'transport' => 'Transport/Shipping',
        'insurance' => 'Insurance',
        'conservation' => 'Conservation',
        'framing' => 'Framing/Mounting',
        'crating' => 'Crating/Packing',
        'customs' => 'Customs/Duties',
        'courier_fee' => 'Courier Fee',
        'handling' => 'Handling',
        'photography' => 'Photography',
        'other' => 'Other',
    ];

    private ConnectionInterface $db;
    private LoggerInterface $logger;
    private AhgTaxonomyService $taxonomyService;

    public function __construct(ConnectionInterface $db, ?LoggerInterface $logger = null)
    {
        $this->db = $db;
        $this->logger = $logger ?? new NullLogger();
        $this->taxonomyService = new AhgTaxonomyService();
    }

    /**
     * Get shipment types from database.
     */
    public function getShipmentTypes(): array
    {
        return $this->taxonomyService->getShipmentTypes(false);
    }

    /**
     * Get shipment statuses from database.
     */
    public function getShipmentStatuses(): array
    {
        return $this->taxonomyService->getShipmentStatuses(false);
    }

    /**
     * Get cost types from database.
     */
    public function getCostTypes(): array
    {
        return $this->taxonomyService->getCostTypes(false);
    }

    // =========================================================================
    // COURIER MANAGEMENT
    // =========================================================================

    /**
     * Create a new courier provider.
     */
    public function createCourier(array $data): int
    {
        return $this->db->table('loan_courier')->insertGetId([
            'company_name' => $data['company_name'],
            'contact_name' => $data['contact_name'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'address' => $data['address'] ?? null,
            'website' => $data['website'] ?? null,
            'is_art_specialist' => $data['is_art_specialist'] ?? false,
            'has_climate_control' => $data['has_climate_control'] ?? false,
            'has_gps_tracking' => $data['has_gps_tracking'] ?? false,
            'insurance_coverage' => $data['insurance_coverage'] ?? null,
            'insurance_currency' => $data['insurance_currency'] ?? 'ZAR',
            'quality_rating' => $data['quality_rating'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get courier by ID.
     */
    public function getCourier(int $courierId): ?array
    {
        $courier = $this->db->table('loan_courier')
            ->where('id', $courierId)
            ->first();

        return $courier ? (array) $courier : null;
    }

    /**
     * Get all couriers.
     */
    public function getCouriers(bool $activeOnly = true): array
    {
        $query = $this->db->table('loan_courier');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('company_name')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Get art specialist couriers.
     */
    public function getArtSpecialistCouriers(): array
    {
        return $this->db->table('loan_courier')
            ->where('is_active', true)
            ->where('is_art_specialist', true)
            ->orderBy('company_name')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Update courier.
     */
    public function updateCourier(int $courierId, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        unset($data['id'], $data['created_at']);

        return $this->db->table('loan_courier')
            ->where('id', $courierId)
            ->update($data) > 0;
    }

    // =========================================================================
    // SHIPMENT MANAGEMENT
    // =========================================================================

    /**
     * Create a new shipment.
     */
    public function createShipment(int $loanId, array $data, int $userId): int
    {
        $shipmentNumber = $this->generateShipmentNumber($loanId);

        $shipmentId = $this->db->table('loan_shipment')->insertGetId([
            'loan_id' => $loanId,
            'courier_id' => $data['courier_id'] ?? null,
            'shipment_type' => $data['shipment_type'] ?? 'outbound',
            'shipment_number' => $shipmentNumber,
            'tracking_number' => $data['tracking_number'] ?? null,
            'waybill_number' => $data['waybill_number'] ?? null,
            'origin_address' => $data['origin_address'] ?? null,
            'destination_address' => $data['destination_address'] ?? null,
            'scheduled_pickup' => $data['scheduled_pickup'] ?? null,
            'scheduled_delivery' => $data['scheduled_delivery'] ?? null,
            'status' => 'planned',
            'handling_instructions' => $data['handling_instructions'] ?? null,
            'special_requirements' => $data['special_requirements'] ?? null,
            'shipping_cost' => $data['shipping_cost'] ?? null,
            'insurance_cost' => $data['insurance_cost'] ?? null,
            'customs_cost' => $data['customs_cost'] ?? null,
            'cost_currency' => $data['cost_currency'] ?? 'ZAR',
            'courier_names' => $data['courier_names'] ?? null,
            'courier_contact' => $data['courier_contact'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Calculate total cost
        $this->updateShipmentTotalCost($shipmentId);

        $this->logger->info('Shipment created', [
            'shipment_id' => $shipmentId,
            'loan_id' => $loanId,
            'number' => $shipmentNumber,
        ]);

        return $shipmentId;
    }

    /**
     * Get shipment by ID.
     */
    public function getShipment(int $shipmentId): ?array
    {
        $shipment = $this->db->table('loan_shipment as s')
            ->leftJoin('loan_courier as c', 'c.id', '=', 's.courier_id')
            ->where('s.id', $shipmentId)
            ->select('s.*', 'c.company_name as courier_name')
            ->first();

        if (!$shipment) {
            return null;
        }

        $data = (array) $shipment;
        $data['events'] = $this->getShipmentEvents($shipmentId);

        return $data;
    }

    /**
     * Get shipments for a loan.
     */
    public function getShipmentsForLoan(int $loanId): array
    {
        return $this->db->table('loan_shipment as s')
            ->leftJoin('loan_courier as c', 'c.id', '=', 's.courier_id')
            ->where('s.loan_id', $loanId)
            ->select('s.*', 'c.company_name as courier_name')
            ->orderByDesc('s.created_at')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Update shipment.
     */
    public function updateShipment(int $shipmentId, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        unset($data['id'], $data['loan_id'], $data['created_at'], $data['created_by']);

        $result = $this->db->table('loan_shipment')
            ->where('id', $shipmentId)
            ->update($data) > 0;

        // Recalculate total cost if cost fields changed
        if (isset($data['shipping_cost']) || isset($data['insurance_cost']) || isset($data['customs_cost'])) {
            $this->updateShipmentTotalCost($shipmentId);
        }

        return $result;
    }

    /**
     * Update shipment status.
     */
    public function updateShipmentStatus(int $shipmentId, string $status, ?string $location = null, ?string $description = null): bool
    {
        $now = date('Y-m-d H:i:s');

        // Update status
        $updateData = [
            'status' => $status,
            'updated_at' => $now,
        ];

        // Update actual dates based on status
        if ('picked_up' === $status) {
            $updateData['actual_pickup'] = $now;
        } elseif ('delivered' === $status) {
            $updateData['actual_delivery'] = $now;
        }

        $this->db->table('loan_shipment')
            ->where('id', $shipmentId)
            ->update($updateData);

        // Add tracking event
        $this->addShipmentEvent($shipmentId, $status, $location, $description);

        return true;
    }

    /**
     * Add tracking event to shipment.
     */
    public function addShipmentEvent(int $shipmentId, string $eventType, ?string $location = null, ?string $description = null): int
    {
        return $this->db->table('loan_shipment_event')->insertGetId([
            'shipment_id' => $shipmentId,
            'event_time' => date('Y-m-d H:i:s'),
            'event_type' => $eventType,
            'location' => $location,
            'description' => $description ?? (self::SHIPMENT_STATUSES[$eventType] ?? $eventType),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get shipment events.
     */
    public function getShipmentEvents(int $shipmentId): array
    {
        return $this->db->table('loan_shipment_event')
            ->where('shipment_id', $shipmentId)
            ->orderByDesc('event_time')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Generate shipment number.
     */
    private function generateShipmentNumber(int $loanId): string
    {
        $loan = $this->db->table('loan')->where('id', $loanId)->first();
        $count = $this->db->table('loan_shipment')
            ->where('loan_id', $loanId)
            ->count();

        $loanNumber = $loan ? $loan->loan_number : 'UNKNOWN';

        return $loanNumber.'-SH'.str_pad((string) ($count + 1), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Update total cost for shipment.
     */
    private function updateShipmentTotalCost(int $shipmentId): void
    {
        $shipment = $this->db->table('loan_shipment')
            ->where('id', $shipmentId)
            ->first();

        if ($shipment) {
            $total = ($shipment->shipping_cost ?? 0)
                + ($shipment->insurance_cost ?? 0)
                + ($shipment->customs_cost ?? 0);

            $this->db->table('loan_shipment')
                ->where('id', $shipmentId)
                ->update(['total_cost' => $total]);
        }
    }

    // =========================================================================
    // COST TRACKING
    // =========================================================================

    /**
     * Add cost to loan.
     */
    public function addCost(int $loanId, array $data, int $userId): int
    {
        return $this->db->table('loan_cost')->insertGetId([
            'loan_id' => $loanId,
            'cost_type' => $data['cost_type'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'ZAR',
            'vendor' => $data['vendor'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? null,
            'invoice_date' => $data['invoice_date'] ?? null,
            'paid' => $data['paid'] ?? false,
            'paid_date' => $data['paid_date'] ?? null,
            'paid_by' => $data['paid_by'] ?? 'borrower',
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get costs for a loan.
     */
    public function getCostsForLoan(int $loanId): array
    {
        return $this->db->table('loan_cost')
            ->where('loan_id', $loanId)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Get total costs for a loan.
     */
    public function getTotalCosts(int $loanId): array
    {
        $costs = $this->getCostsForLoan($loanId);

        $total = 0;
        $byType = [];
        $byPayer = ['lender' => 0, 'borrower' => 0, 'shared' => 0];
        $unpaid = 0;

        foreach ($costs as $cost) {
            $total += $cost['amount'];
            $byType[$cost['cost_type']] = ($byType[$cost['cost_type']] ?? 0) + $cost['amount'];
            $byPayer[$cost['paid_by']] = ($byPayer[$cost['paid_by']] ?? 0) + $cost['amount'];
            if (!$cost['paid']) {
                $unpaid += $cost['amount'];
            }
        }

        return [
            'total' => $total,
            'by_type' => $byType,
            'by_payer' => $byPayer,
            'unpaid' => $unpaid,
            'paid' => $total - $unpaid,
            'currency' => $costs[0]['currency'] ?? 'ZAR',
        ];
    }

    /**
     * Update cost.
     */
    public function updateCost(int $costId, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        unset($data['id'], $data['loan_id'], $data['created_at'], $data['created_by']);

        return $this->db->table('loan_cost')
            ->where('id', $costId)
            ->update($data) > 0;
    }

    /**
     * Mark cost as paid.
     */
    public function markCostPaid(int $costId, ?string $paidDate = null): bool
    {
        return $this->db->table('loan_cost')
            ->where('id', $costId)
            ->update([
                'paid' => true,
                'paid_date' => $paidDate ?? date('Y-m-d'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Delete cost.
     */
    public function deleteCost(int $costId): bool
    {
        return $this->db->table('loan_cost')
            ->where('id', $costId)
            ->delete() > 0;
    }

    // =========================================================================
    // PACKING LISTS
    // =========================================================================

    /**
     * Create packing list.
     */
    public function createPackingList(int $loanId, array $data): int
    {
        $listNumber = $this->generatePackingListNumber($loanId);

        return $this->db->table('loan_packing_list')->insertGetId([
            'loan_id' => $loanId,
            'shipment_id' => $data['shipment_id'] ?? null,
            'list_number' => $listNumber,
            'crate_count' => $data['crate_count'] ?? 1,
            'total_weight_kg' => $data['total_weight_kg'] ?? null,
            'total_volume_cbm' => $data['total_volume_cbm'] ?? null,
            'packing_date' => $data['packing_date'] ?? null,
            'packed_by' => $data['packed_by'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get packing list by ID.
     */
    public function getPackingList(int $listId): ?array
    {
        $list = $this->db->table('loan_packing_list')
            ->where('id', $listId)
            ->first();

        if (!$list) {
            return null;
        }

        $data = (array) $list;
        $data['items'] = $this->getPackingItems($listId);

        return $data;
    }

    /**
     * Get packing lists for a loan.
     */
    public function getPackingListsForLoan(int $loanId): array
    {
        return $this->db->table('loan_packing_list')
            ->where('loan_id', $loanId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Add item to packing list.
     */
    public function addPackingItem(int $listId, array $data): int
    {
        $maxItem = $this->db->table('loan_packing_item')
            ->where('packing_list_id', $listId)
            ->max('item_number') ?? 0;

        return $this->db->table('loan_packing_item')->insertGetId([
            'packing_list_id' => $listId,
            'loan_object_id' => $data['loan_object_id'] ?? null,
            'crate_number' => $data['crate_number'] ?? 1,
            'item_number' => $maxItem + 1,
            'object_description' => $data['object_description'] ?? null,
            'height_cm' => $data['height_cm'] ?? null,
            'width_cm' => $data['width_cm'] ?? null,
            'depth_cm' => $data['depth_cm'] ?? null,
            'weight_kg' => $data['weight_kg'] ?? null,
            'packing_materials' => $data['packing_materials'] ?? null,
            'orientation' => $data['orientation'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get packing items.
     */
    public function getPackingItems(int $listId): array
    {
        return $this->db->table('loan_packing_item')
            ->where('packing_list_id', $listId)
            ->orderBy('crate_number')
            ->orderBy('item_number')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Generate packing list number.
     */
    private function generatePackingListNumber(int $loanId): string
    {
        $loan = $this->db->table('loan')->where('id', $loanId)->first();
        $count = $this->db->table('loan_packing_list')
            ->where('loan_id', $loanId)
            ->count();

        $loanNumber = $loan ? $loan->loan_number : 'UNKNOWN';

        return $loanNumber.'-PL'.str_pad((string) ($count + 1), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Verify packing list.
     */
    public function verifyPackingList(int $listId, string $verifiedBy): bool
    {
        return $this->db->table('loan_packing_list')
            ->where('id', $listId)
            ->update([
                'verified_by' => $verifiedBy,
                'verification_date' => date('Y-m-d'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get shipment types for dropdown.
     */
    public function getShipmentTypes(): array
    {
        return self::SHIPMENT_TYPES;
    }

    /**
     * Get shipment statuses for dropdown.
     */
    public function getShipmentStatuses(): array
    {
        return self::SHIPMENT_STATUSES;
    }

    /**
     * Get cost types for dropdown.
     */
    public function getCostTypes(): array
    {
        return self::COST_TYPES;
    }

    /**
     * Get active shipments (not delivered).
     */
    public function getActiveShipments(): array
    {
        return $this->db->table('loan_shipment as s')
            ->leftJoin('loan as l', 'l.id', '=', 's.loan_id')
            ->leftJoin('loan_courier as c', 'c.id', '=', 's.courier_id')
            ->whereNotIn('s.status', ['delivered', 'failed', 'returned'])
            ->select('s.*', 'l.loan_number', 'l.partner_institution', 'c.company_name as courier_name')
            ->orderBy('s.scheduled_delivery')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Get shipments due for delivery.
     */
    public function getShipmentsDueSoon(int $days = 7): array
    {
        $futureDate = date('Y-m-d', strtotime("+{$days} days"));

        return $this->db->table('loan_shipment as s')
            ->leftJoin('loan as l', 'l.id', '=', 's.loan_id')
            ->where('s.scheduled_delivery', '<=', $futureDate)
            ->where('s.scheduled_delivery', '>=', date('Y-m-d'))
            ->whereIn('s.status', ['planned', 'picked_up', 'in_transit', 'out_for_delivery'])
            ->select('s.*', 'l.loan_number', 'l.partner_institution')
            ->orderBy('s.scheduled_delivery')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }
}
