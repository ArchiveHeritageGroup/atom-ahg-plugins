<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CustodyHandoffService — Chain-of-custody for material requests
 *
 * Handles checkout, check-in, return verification, transfers,
 * spectrum_movement integration, and location_current auto-updates.
 *
 * @package    ahgResearchPlugin
 * @subpackage Services
 * @version    1.0.0
 */
class CustodyHandoffService
{
    private ?object $eventService = null;

    private function getEventService(): WorkflowEventService
    {
        if ($this->eventService === null) {
            $pluginsDir = \sfConfig::get('sf_plugins_dir');
            require_once $pluginsDir . '/ahgWorkflowPlugin/lib/Services/WorkflowEventService.php';
            $this->eventService = new WorkflowEventService();
        }
        return $this->eventService;
    }

    // =========================================================================
    // CHECKOUT
    // =========================================================================

    /**
     * Record checkout of material to a researcher.
     *
     * Creates custody handoff + spectrum_movement + updates access_status.
     *
     * @return array ['success' => bool, 'handoff_id' => int, 'movement_id' => ?int]
     */
    public function recordCheckout(
        int $requestId,
        int $staffId,
        int $researcherId,
        string $condition,
        ?string $barcode = null,
        ?string $notes = null,
        ?string $toLocation = null
    ): array {
        $request = DB::table('research_material_request')->where('id', $requestId)->first();
        if (!$request) {
            return ['success' => false, 'error' => 'Request not found'];
        }

        $fromLocation = $request->shelf_location ?? $request->location_code ?? 'Storage';
        $toLocationResolved = $toLocation ?? $request->location_current ?? 'Reading Room';

        // 1. Create spectrum_movement record
        $movementId = $this->createSpectrumMovement(
            $request->object_id,
            'research_checkout',
            $fromLocation,
            $toLocationResolved,
            $condition,
            null, // condition_after set later
            $staffId,
            "Checkout for material request #{$requestId}"
        );

        // 2. Create custody handoff
        $handoffId = DB::table('research_custody_handoff')->insertGetId([
            'material_request_id' => $requestId,
            'handoff_type' => 'checkout',
            'from_handler_id' => $staffId,
            'to_handler_id' => $researcherId,
            'from_location' => $fromLocation,
            'to_location' => $toLocationResolved,
            'condition_at_handoff' => $condition,
            'condition_notes' => $notes,
            'barcode_scanned' => $barcode,
            'spectrum_movement_id' => $movementId,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $staffId,
        ]);

        // 3. Update material request
        DB::table('research_material_request')->where('id', $requestId)->update([
            'location_current' => $toLocationResolved,
            'checkout_confirmed_at' => date('Y-m-d H:i:s'),
            'checkout_confirmed_by' => $staffId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // 4. Update physical location access_status
        $this->updateAccessStatus($request->object_id, 'in_use');

        // 5. Emit workflow event
        $this->getEventService()->emit('custody_checkout', [
            'object_id' => $requestId,
            'object_type' => 'research_material_request',
            'performed_by' => $staffId,
            'comment' => "Checked out to researcher. Condition: {$condition}",
            'metadata' => [
                'handoff_id' => $handoffId,
                'movement_id' => $movementId,
                'condition' => $condition,
                'barcode' => $barcode,
                'to_location' => $toLocationResolved,
            ],
        ]);

        return ['success' => true, 'handoff_id' => $handoffId, 'movement_id' => $movementId];
    }

    // =========================================================================
    // CONFIRM RECEIPT
    // =========================================================================

    /**
     * Confirm receipt (signature) on a handoff.
     */
    public function confirmReceipt(int $handoffId, int $confirmedBy): array
    {
        $handoff = DB::table('research_custody_handoff')->where('id', $handoffId)->first();
        if (!$handoff) {
            return ['success' => false, 'error' => 'Handoff not found'];
        }

        DB::table('research_custody_handoff')->where('id', $handoffId)->update([
            'signature_confirmed' => 1,
            'confirmed_at' => date('Y-m-d H:i:s'),
            'confirmed_by' => $confirmedBy,
        ]);

        return ['success' => true];
    }

    // =========================================================================
    // RETURN
    // =========================================================================

    /**
     * Record return of material from researcher.
     *
     * Creates custody handoff + spectrum_movement + updates status.
     */
    public function recordReturn(
        int $requestId,
        int $staffId,
        string $conditionBefore,
        string $conditionAfter,
        ?string $notes = null
    ): array {
        $request = DB::table('research_material_request')->where('id', $requestId)->first();
        if (!$request) {
            return ['success' => false, 'error' => 'Request not found'];
        }

        $fromLocation = $request->location_current ?? 'Reading Room';
        $toLocation = 'Return shelf (pending re-shelving)';

        // 1. Create spectrum_movement
        $movementId = $this->createSpectrumMovement(
            $request->object_id,
            'research_return',
            $fromLocation,
            $toLocation,
            $conditionBefore,
            $conditionAfter,
            $staffId,
            "Return for material request #{$requestId}"
        );

        // 2. Create custody handoff
        $handoffId = DB::table('research_custody_handoff')->insertGetId([
            'material_request_id' => $requestId,
            'handoff_type' => 'checkin',
            'from_handler_id' => null, // from researcher
            'to_handler_id' => $staffId,
            'from_location' => $fromLocation,
            'to_location' => $toLocation,
            'condition_at_handoff' => $conditionAfter,
            'condition_notes' => $notes,
            'spectrum_movement_id' => $movementId,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $staffId,
        ]);

        // 3. Update material request
        DB::table('research_material_request')->where('id', $requestId)->update([
            'status' => 'returned',
            'returned_at' => date('Y-m-d H:i:s'),
            'return_condition' => $conditionAfter,
            'location_current' => $toLocation,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // 4. Log status change
        DB::table('research_request_status_history')->insert([
            'request_id' => $requestId,
            'request_type' => 'material',
            'old_status' => $request->status,
            'new_status' => 'returned',
            'changed_by' => $staffId,
            'notes' => "Returned. Condition: {$conditionBefore} → {$conditionAfter}",
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // 5. Emit event
        $this->getEventService()->emit('custody_checkin', [
            'object_id' => $requestId,
            'object_type' => 'research_material_request',
            'performed_by' => $staffId,
            'from_status' => $request->status,
            'to_status' => 'returned',
            'comment' => "Returned. Condition: {$conditionBefore} → {$conditionAfter}",
            'metadata' => [
                'handoff_id' => $handoffId,
                'movement_id' => $movementId,
                'condition_before' => $conditionBefore,
                'condition_after' => $conditionAfter,
            ],
        ]);

        return ['success' => true, 'handoff_id' => $handoffId, 'movement_id' => $movementId];
    }

    // =========================================================================
    // RETURN VERIFICATION
    // =========================================================================

    /**
     * Verify return — re-assess condition and shelve.
     */
    public function verifyReturn(
        int $requestId,
        int $verifiedBy,
        string $condition,
        ?string $notes = null
    ): array {
        $request = DB::table('research_material_request')->where('id', $requestId)->first();
        if (!$request) {
            return ['success' => false, 'error' => 'Request not found'];
        }

        // Restore to original location
        $originalLocation = $request->shelf_location ?? $request->location_code ?? 'Storage';

        DB::table('research_material_request')->where('id', $requestId)->update([
            'return_verified_by' => $verifiedBy,
            'return_verified_at' => date('Y-m-d H:i:s'),
            'return_condition' => $condition,
            'location_current' => $originalLocation,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Update physical location
        $this->updateAccessStatus($request->object_id, 'available');
        $this->updatePhysicalCondition($request->object_id, $condition, $notes);

        // Log
        DB::table('research_request_status_history')->insert([
            'request_id' => $requestId,
            'request_type' => 'material',
            'old_status' => 'returned',
            'new_status' => 'verified',
            'changed_by' => $verifiedBy,
            'notes' => "Return verified. Final condition: {$condition}. " . ($notes ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->getEventService()->emit('custody_return_verified', [
            'object_id' => $requestId,
            'object_type' => 'research_material_request',
            'performed_by' => $verifiedBy,
            'from_status' => 'returned',
            'to_status' => 'verified',
            'comment' => "Return verified. Condition: {$condition}",
            'metadata' => [
                'condition' => $condition,
                'original_location' => $originalLocation,
            ],
        ]);

        return ['success' => true];
    }

    // =========================================================================
    // TRANSFER
    // =========================================================================

    /**
     * Staff-to-staff transfer of material.
     */
    public function recordTransfer(
        int $requestId,
        int $fromStaffId,
        int $toStaffId,
        string $condition,
        ?string $notes = null
    ): array {
        $request = DB::table('research_material_request')->where('id', $requestId)->first();
        if (!$request) {
            return ['success' => false, 'error' => 'Request not found'];
        }

        $movementId = $this->createSpectrumMovement(
            $request->object_id,
            'research_transfer',
            $request->location_current ?? 'Unknown',
            $request->location_current ?? 'Unknown',
            $condition,
            $condition,
            $fromStaffId,
            "Staff transfer for request #{$requestId}"
        );

        $handoffId = DB::table('research_custody_handoff')->insertGetId([
            'material_request_id' => $requestId,
            'handoff_type' => 'transfer',
            'from_handler_id' => $fromStaffId,
            'to_handler_id' => $toStaffId,
            'from_location' => $request->location_current,
            'to_location' => $request->location_current,
            'condition_at_handoff' => $condition,
            'condition_notes' => $notes,
            'spectrum_movement_id' => $movementId,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $fromStaffId,
        ]);

        $this->getEventService()->emit('custody_transfer', [
            'object_id' => $requestId,
            'object_type' => 'research_material_request',
            'performed_by' => $fromStaffId,
            'comment' => "Staff transfer. Condition: {$condition}",
            'metadata' => [
                'handoff_id' => $handoffId,
                'from_staff_id' => $fromStaffId,
                'to_staff_id' => $toStaffId,
            ],
        ]);

        return ['success' => true, 'handoff_id' => $handoffId, 'movement_id' => $movementId];
    }

    // =========================================================================
    // HISTORY & CHAIN
    // =========================================================================

    /**
     * Get all handoffs for a material request.
     */
    public function getHandoffHistory(int $requestId): array
    {
        return DB::table('research_custody_handoff as ch')
            ->leftJoin('user as from_u', 'ch.from_handler_id', '=', 'from_u.id')
            ->leftJoin('actor_i18n as from_ai', function ($join) {
                $join->on('from_u.id', '=', 'from_ai.id')
                     ->where('from_ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('user as to_u', 'ch.to_handler_id', '=', 'to_u.id')
            ->leftJoin('actor_i18n as to_ai', function ($join) {
                $join->on('to_u.id', '=', 'to_ai.id')
                     ->where('to_ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('user as conf_u', 'ch.confirmed_by', '=', 'conf_u.id')
            ->leftJoin('actor_i18n as conf_ai', function ($join) {
                $join->on('conf_u.id', '=', 'conf_ai.id')
                     ->where('conf_ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('ch.material_request_id', $requestId)
            ->select(
                'ch.*',
                DB::raw('COALESCE(from_ai.authorized_form_of_name, from_u.username) as from_handler_name'),
                DB::raw('COALESCE(to_ai.authorized_form_of_name, to_u.username) as to_handler_name'),
                DB::raw('COALESCE(conf_ai.authorized_form_of_name, conf_u.username) as confirmed_by_name')
            )
            ->orderBy('ch.created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get full custody chain for an information object.
     *
     * Combines: custody_handoff + spectrum_movement + provenance_event
     */
    public function getCustodyChain(int $objectId): array
    {
        // 1. Custody handoffs (via material requests for this object)
        $handoffs = DB::table('research_custody_handoff as ch')
            ->join('research_material_request as mr', 'ch.material_request_id', '=', 'mr.id')
            ->leftJoin('user as from_u', 'ch.from_handler_id', '=', 'from_u.id')
            ->leftJoin('actor_i18n as from_ai', function ($join) {
                $join->on('from_u.id', '=', 'from_ai.id')
                     ->where('from_ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('user as to_u', 'ch.to_handler_id', '=', 'to_u.id')
            ->leftJoin('actor_i18n as to_ai', function ($join) {
                $join->on('to_u.id', '=', 'to_ai.id')
                     ->where('to_ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('mr.object_id', $objectId)
            ->select(
                'ch.id',
                DB::raw("'custody_handoff' as source"),
                'ch.handoff_type as event_type',
                'ch.from_location',
                'ch.to_location',
                'ch.condition_at_handoff as condition_status',
                'ch.notes',
                'ch.signature_confirmed',
                DB::raw('COALESCE(from_ai.authorized_form_of_name, from_u.username) as from_handler'),
                DB::raw('COALESCE(to_ai.authorized_form_of_name, to_u.username) as to_handler'),
                'ch.created_at as event_date'
            )
            ->get()
            ->toArray();

        // 2. Spectrum movements
        $movements = DB::table('spectrum_movement as sm')
            ->where('sm.object_id', $objectId)
            ->select(
                'sm.id',
                DB::raw("'spectrum_movement' as source"),
                'sm.movement_reason as event_type',
                DB::raw('sm.location_from as from_location'),
                DB::raw('sm.location_to as to_location'),
                'sm.condition_before as condition_status',
                'sm.movement_note as notes',
                DB::raw('0 as signature_confirmed'),
                'sm.handler_name as from_handler',
                DB::raw('NULL as to_handler'),
                'sm.movement_date as event_date'
            )
            ->get()
            ->toArray();

        // 3. Provenance events (if table exists)
        $provenance = [];
        try {
            $provenance = DB::table('provenance_event as pe')
                ->join('provenance_record as pr', 'pe.provenance_record_id', '=', 'pr.id')
                ->leftJoin('actor_i18n as from_a', function ($join) {
                    $join->on('pe.from_agent_id', '=', 'from_a.id')
                         ->where('from_a.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->leftJoin('actor_i18n as to_a', function ($join) {
                    $join->on('pe.to_agent_id', '=', 'to_a.id')
                         ->where('to_a.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->where('pr.information_object_id', $objectId)
                ->select(
                    'pe.id',
                    DB::raw("'provenance' as source"),
                    'pe.event_type',
                    'pe.event_location as from_location',
                    DB::raw('NULL as to_location'),
                    DB::raw('NULL as condition_status'),
                    'pe.notes',
                    DB::raw('0 as signature_confirmed'),
                    'from_a.authorized_form_of_name as from_handler',
                    'to_a.authorized_form_of_name as to_handler',
                    DB::raw('COALESCE(pe.event_date, pe.event_date_start) as event_date')
                )
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            // Provenance tables may not exist
        }

        // Merge and sort
        $chain = array_merge($handoffs, $movements, $provenance);
        usort($chain, function ($a, $b) {
            $dateA = $a->event_date ?? '1900-01-01';
            $dateB = $b->event_date ?? '1900-01-01';
            return strtotime($dateB) - strtotime($dateA);
        });

        return $chain;
    }

    // =========================================================================
    // BATCH OPERATIONS
    // =========================================================================

    /**
     * Batch checkout multiple requests.
     */
    public function batchCheckout(
        array $requestIds,
        int $staffId,
        int $researcherId,
        string $defaultCondition = 'good',
        ?string $toLocation = null
    ): array {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        $correlationId = $this->getEventService()->startBulkOperation();

        foreach ($requestIds as $reqId) {
            $result = $this->recordCheckout($reqId, $staffId, $researcherId, $defaultCondition, null, null, $toLocation);
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Request #{$reqId}: " . ($result['error'] ?? 'Unknown error');
            }
        }

        $this->getEventService()->endBulkOperation();
        $results['correlation_id'] = $correlationId;

        return $results;
    }

    /**
     * Batch return multiple requests with per-item condition.
     *
     * @param array $items  [['request_id' => int, 'condition_before' => str, 'condition_after' => str, 'notes' => ?str], ...]
     */
    public function batchReturn(array $items, int $staffId): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        $correlationId = $this->getEventService()->startBulkOperation();

        foreach ($items as $item) {
            $result = $this->recordReturn(
                $item['request_id'],
                $staffId,
                $item['condition_before'] ?? 'good',
                $item['condition_after'] ?? 'good',
                $item['notes'] ?? null
            );
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Request #{$item['request_id']}: " . ($result['error'] ?? 'Unknown error');
            }
        }

        $this->getEventService()->endBulkOperation();
        $results['correlation_id'] = $correlationId;

        return $results;
    }

    // =========================================================================
    // LOCATION UPDATES
    // =========================================================================

    /**
     * Update location_current on a material request.
     */
    public function updateLocationCurrent(int $requestId, string $location): bool
    {
        $updated = DB::table('research_material_request')
            ->where('id', $requestId)
            ->update([
                'location_current' => $location,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;

        if ($updated) {
            $request = DB::table('research_material_request')->where('id', $requestId)->first();
            $this->getEventService()->emit('location_updated', [
                'object_id' => $requestId,
                'object_type' => 'research_material_request',
                'performed_by' => 0,
                'comment' => "Location updated to: {$location}",
                'metadata' => ['location' => $location, 'object_id' => $request->object_id ?? null],
            ]);
        }

        return $updated;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Create a spectrum_movement record.
     */
    private function createSpectrumMovement(
        int $objectId,
        string $reason,
        ?string $fromLocation,
        ?string $toLocation,
        ?string $conditionBefore,
        ?string $conditionAfter,
        int $staffId,
        string $note
    ): ?int {
        try {
            $reference = 'RR-' . str_pad($objectId, 8, '0', STR_PAD_LEFT) . '-' . date('YmdHis');

            return DB::table('spectrum_movement')->insertGetId([
                'object_id' => $objectId,
                'movement_reference' => $reference,
                'movement_date' => date('Y-m-d H:i:s'),
                'movement_reason' => $reason,
                'condition_before' => $conditionBefore,
                'condition_after' => $conditionAfter,
                'handler_name' => $this->resolveUserName($staffId),
                'movement_note' => $note,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $staffId,
                'workflow_state' => 'completed',
            ]);
        } catch (\Exception $e) {
            // spectrum_movement may not exist if ahgConditionPlugin not installed
            return null;
        }
    }

    /**
     * Update access_status on information_object_physical_location.
     */
    private function updateAccessStatus(int $objectId, string $status): void
    {
        try {
            DB::table('information_object_physical_location')
                ->where('information_object_id', $objectId)
                ->update([
                    'access_status' => $status,
                    'last_accessed_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        } catch (\Exception $e) {
            // Table may not exist
        }
    }

    /**
     * Update condition on information_object_physical_location.
     */
    private function updatePhysicalCondition(int $objectId, string $condition, ?string $notes): void
    {
        try {
            $update = ['updated_at' => date('Y-m-d H:i:s')];

            // Only update condition_status if it's a valid ENUM value
            $validConditions = ['excellent', 'good', 'fair', 'poor', 'critical'];
            if (in_array($condition, $validConditions)) {
                $update['condition_status'] = $condition;
            }
            if ($notes) {
                $update['condition_notes'] = $notes;
            }

            DB::table('information_object_physical_location')
                ->where('information_object_id', $objectId)
                ->update($update);
        } catch (\Exception $e) {
            // Table may not exist
        }
    }

    /**
     * Resolve a user ID to a display name.
     */
    private function resolveUserName(int $userId): string
    {
        $name = DB::table('actor_i18n')
            ->where('id', $userId)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->value('authorized_form_of_name');

        if ($name) {
            return $name;
        }

        return DB::table('user')->where('id', $userId)->value('username') ?? 'Unknown';
    }
}
