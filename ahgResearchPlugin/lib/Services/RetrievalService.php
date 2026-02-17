<?php

/**
 * RetrievalService - Manages material retrieval queues, scheduling, and call slips
 *
 * Handles retrieval scheduling, queue management, call slip generation,
 * and batch processing of material requests.
 *
 * @package    ahgResearchPlugin
 * @subpackage Services
 */

use Illuminate\Database\Capsule\Manager as DB;

class RetrievalService
{
    // =========================================================================
    // QUEUE MANAGEMENT
    // =========================================================================

    /**
     * Get all request queues
     *
     * @param bool $activeOnly
     * @return array
     */
    public function getQueues(bool $activeOnly = true): array
    {
        $query = DB::table('research_request_queue');

        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        return $query->orderBy('sort_order')->get()->toArray();
    }

    /**
     * Get a specific queue
     *
     * @param int $queueId
     * @return object|null
     */
    public function getQueue(int $queueId): ?object
    {
        return DB::table('research_request_queue')
            ->where('id', $queueId)
            ->first();
    }

    /**
     * Get queue by code
     *
     * @param string $code
     * @return object|null
     */
    public function getQueueByCode(string $code): ?object
    {
        return DB::table('research_request_queue')
            ->where('code', $code)
            ->first();
    }

    /**
     * Get requests in a queue
     *
     * @param int      $queueId
     * @param int|null $limit
     * @param int      $offset
     * @return array
     */
    public function getQueueRequests(int $queueId, ?int $limit = 50, int $offset = 0): array
    {
        $queue = $this->getQueue($queueId);
        if (!$queue) {
            return [];
        }

        $query = DB::table('research_material_request as mr')
            ->join('research_booking as b', 'mr.booking_id', '=', 'b.id')
            ->join('research_researcher as r', 'b.researcher_id', '=', 'r.id')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('mr.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            });

        // Apply queue filters
        if ($queue->filter_status) {
            $statuses = explode(',', $queue->filter_status);
            $query->whereIn('mr.status', $statuses);
        }

        if ($queue->filter_room_id) {
            $query->where('b.reading_room_id', $queue->filter_room_id);
        }

        if ($queue->filter_priority) {
            $query->where('mr.priority', $queue->filter_priority);
        }

        // Apply sorting
        $sortField = $queue->sort_field ?? 'created_at';
        $sortDir = $queue->sort_direction ?? 'ASC';

        // Map sort fields to actual columns
        $sortMap = [
            'created_at' => 'mr.created_at',
            'priority' => 'mr.priority',
            'booking_date' => 'b.booking_date',
            'retrieved_at' => 'mr.retrieved_at',
            'updated_at' => 'mr.updated_at',
        ];

        $sortColumn = $sortMap[$sortField] ?? 'mr.created_at';

        $query->select(
            'mr.*',
            'b.booking_date',
            'b.start_time',
            'b.end_time',
            'b.reading_room_id',
            'r.first_name',
            'r.last_name',
            'r.email',
            'rm.name as room_name',
            'ioi.title as item_title'
        )
            ->orderBy($sortColumn, $sortDir);

        if ($limit) {
            $query->limit($limit)->offset($offset);
        }

        return $query->get()->toArray();
    }

    /**
     * Get queue counts for dashboard
     *
     * @return array
     */
    public function getQueueCounts(): array
    {
        $queues = $this->getQueues();
        $counts = [];

        foreach ($queues as $queue) {
            $query = DB::table('research_material_request as mr')
                ->join('research_booking as b', 'mr.booking_id', '=', 'b.id');

            if ($queue->filter_status) {
                $statuses = explode(',', $queue->filter_status);
                $query->whereIn('mr.status', $statuses);
            }

            if ($queue->filter_room_id) {
                $query->where('b.reading_room_id', $queue->filter_room_id);
            }

            if ($queue->filter_priority) {
                $query->where('mr.priority', $queue->filter_priority);
            }

            $counts[$queue->code] = [
                'id' => $queue->id,
                'name' => $queue->name,
                'code' => $queue->code,
                'color' => $queue->color,
                'icon' => $queue->icon,
                'count' => $query->count(),
            ];
        }

        return $counts;
    }

    /**
     * Move request to a queue
     *
     * @param int $requestId
     * @param int $queueId
     * @return bool
     */
    public function moveToQueue(int $requestId, int $queueId): bool
    {
        return DB::table('research_material_request')
            ->where('id', $requestId)
            ->update([
                'queue_id' => $queueId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    // =========================================================================
    // RETRIEVAL SCHEDULING
    // =========================================================================

    /**
     * Get retrieval schedules for a room
     *
     * @param int  $roomId
     * @param bool $activeOnly
     * @return array
     */
    public function getRetrievalSchedules(int $roomId, bool $activeOnly = true): array
    {
        $query = DB::table('research_retrieval_schedule')
            ->where('reading_room_id', $roomId);

        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        return $query->orderBy('retrieval_time')->get()->toArray();
    }

    /**
     * Create a retrieval schedule
     *
     * @param array $data
     * @return int
     */
    public function createRetrievalSchedule(array $data): int
    {
        return DB::table('research_retrieval_schedule')->insertGetId([
            'reading_room_id' => $data['reading_room_id'],
            'name' => $data['name'],
            'day_of_week' => $data['day_of_week'] ?? null,
            'retrieval_time' => $data['retrieval_time'],
            'cutoff_minutes_before' => $data['cutoff_minutes_before'] ?? 30,
            'max_items_per_run' => $data['max_items_per_run'] ?? 50,
            'storage_location' => $data['storage_location'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get next retrieval run
     *
     * @param int $roomId
     * @return object|null
     */
    public function getNextRetrievalRun(int $roomId): ?object
    {
        $now = date('H:i:s');
        $today = date('w'); // 0 = Sunday

        // Find the next scheduled retrieval
        $schedule = DB::table('research_retrieval_schedule')
            ->where('reading_room_id', $roomId)
            ->where('is_active', 1)
            ->where(function ($q) use ($today, $now) {
                $q->where(function ($q2) use ($today, $now) {
                    $q2->whereNull('day_of_week')
                        ->where('retrieval_time', '>', $now);
                })
                    ->orWhere(function ($q2) use ($today, $now) {
                        $q2->where('day_of_week', $today)
                            ->where('retrieval_time', '>', $now);
                    })
                    ->orWhere('day_of_week', '>', $today)
                    ->orWhereNull('day_of_week');
            })
            ->orderBy('retrieval_time')
            ->first();

        return $schedule;
    }

    /**
     * Get requests ready for next retrieval
     *
     * @param int    $scheduleId
     * @param string $date
     * @return array
     */
    public function getRequestsForRetrieval(int $scheduleId, string $date): array
    {
        $schedule = DB::table('research_retrieval_schedule')
            ->where('id', $scheduleId)
            ->first();

        if (!$schedule) {
            return [];
        }

        $cutoffTime = date('H:i:s', strtotime($schedule->retrieval_time) - ($schedule->cutoff_minutes_before * 60));

        return DB::table('research_material_request as mr')
            ->join('research_booking as b', 'mr.booking_id', '=', 'b.id')
            ->join('research_researcher as r', 'b.researcher_id', '=', 'r.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('mr.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('b.reading_room_id', $schedule->reading_room_id)
            ->where('b.booking_date', $date)
            ->where('mr.status', 'requested')
            ->where('mr.created_at', '<=', $date . ' ' . $cutoffTime)
            ->select(
                'mr.*',
                'b.booking_date',
                'b.start_time',
                'b.end_time',
                'r.first_name',
                'r.last_name',
                'ioi.title as item_title'
            )
            ->orderBy('mr.priority', 'desc')
            ->orderBy('b.start_time')
            ->limit($schedule->max_items_per_run)
            ->get()
            ->toArray();
    }

    // =========================================================================
    // STATUS MANAGEMENT
    // =========================================================================

    /**
     * Update request status with history
     *
     * @param int         $requestId
     * @param string      $newStatus
     * @param int|null    $userId
     * @param string|null $notes
     * @return bool
     */
    public function updateRequestStatus(
        int $requestId,
        string $newStatus,
        ?int $userId = null,
        ?string $notes = null
    ): bool {
        $request = DB::table('research_material_request')
            ->where('id', $requestId)
            ->first();

        if (!$request) {
            return false;
        }

        $oldStatus = $request->status;

        // Update the request
        $updateData = [
            'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Set specific timestamps based on status
        if ($newStatus === 'retrieved' && !$request->retrieved_at) {
            $updateData['retrieved_at'] = date('Y-m-d H:i:s');
            $updateData['retrieved_by'] = $userId;
        } elseif ($newStatus === 'returned' && !$request->returned_at) {
            $updateData['returned_at'] = date('Y-m-d H:i:s');
        }

        $updated = DB::table('research_material_request')
            ->where('id', $requestId)
            ->update($updateData) > 0;

        // Log status change
        if ($updated) {
            DB::table('research_request_status_history')->insert([
                'request_id' => $requestId,
                'request_type' => 'material',
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $userId,
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $updated;
    }

    /**
     * Batch update request statuses
     *
     * @param array       $requestIds
     * @param string      $newStatus
     * @param int|null    $userId
     * @param string|null $notes
     * @return int Number of updated requests
     */
    public function batchUpdateStatus(
        array $requestIds,
        string $newStatus,
        ?int $userId = null,
        ?string $notes = null
    ): int {
        $updated = 0;

        foreach ($requestIds as $requestId) {
            if ($this->updateRequestStatus($requestId, $newStatus, $userId, $notes)) {
                $updated++;
            }
        }

        return $updated;
    }

    // =========================================================================
    // CALL SLIP GENERATION
    // =========================================================================

    /**
     * Get print template
     *
     * @param string $code
     * @return object|null
     */
    public function getPrintTemplate(string $code): ?object
    {
        return DB::table('research_print_template')
            ->where('code', $code)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Generate call slip data for a request
     *
     * @param int $requestId
     * @return array
     */
    public function getCallSlipData(int $requestId): array
    {
        $request = DB::table('research_material_request as mr')
            ->join('research_booking as b', 'mr.booking_id', '=', 'b.id')
            ->join('research_researcher as r', 'b.researcher_id', '=', 'r.id')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->leftJoin('information_object as io', 'mr.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('mr.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('actor_i18n as repo', function ($join) {
                $join->on('io.repository_id', '=', 'repo.id')
                    ->where('repo.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('research_reading_room_seat as seat', 'b.seat_id', '=', 'seat.id')
            ->where('mr.id', $requestId)
            ->select(
                'mr.*',
                'b.booking_date',
                'b.start_time',
                'b.end_time',
                'b.seat_id',
                'r.first_name',
                'r.last_name',
                'r.email',
                'r.card_number',
                'rm.name as room_name',
                'io.identifier as reference_code',
                'ioi.title as item_title',
                'repo.authorized_form_of_name as repository_name',
                'seat.seat_number',
                'seat.seat_label'
            )
            ->first();

        if (!$request) {
            return [];
        }

        return [
            'request_id' => $request->id,
            'request_date' => date('Y-m-d'),
            'request_barcode' => 'REQ-' . str_pad($request->id, 8, '0', STR_PAD_LEFT),
            'priority' => ucfirst($request->priority ?? 'normal'),
            'item_title' => $request->item_title ?? 'Untitled',
            'reference_code' => $request->reference_code ?? '',
            'location_code' => $request->location_code ?? '',
            'shelf_location' => $request->shelf_location ?? '',
            'box_number' => $request->box_number ?? '',
            'folder_number' => $request->folder_number ?? '',
            'researcher_name' => trim($request->first_name . ' ' . $request->last_name),
            'researcher_email' => $request->email,
            'researcher_card' => $request->card_number ?? '',
            'booking_date' => $request->booking_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'reading_room' => $request->room_name,
            'seat_number' => $request->seat_number ?? $request->seat_label ?? '',
            'handling_instructions' => $request->handling_instructions ?? '',
            'notes' => $request->notes ?? '',
            'repository_name' => $request->repository_name ?? 'Archive',
        ];
    }

    /**
     * Render call slip HTML
     *
     * @param int    $requestId
     * @param string $templateCode
     * @return string
     */
    public function renderCallSlip(int $requestId, string $templateCode = 'call_slip_standard'): string
    {
        $template = $this->getPrintTemplate($templateCode);
        if (!$template) {
            return '<p>Template not found</p>';
        }

        $data = $this->getCallSlipData($requestId);
        if (empty($data)) {
            return '<p>Request not found</p>';
        }

        $html = $template->template_html;

        // Replace placeholders
        foreach ($data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', htmlspecialchars($value ?? ''), $html);
        }

        // Wrap with CSS
        $css = $template->css_styles ?? '';
        $fullHtml = '<style>' . $css . '</style>' . $html;

        return $fullHtml;
    }

    /**
     * Render multiple call slips for batch printing
     *
     * @param array  $requestIds
     * @param string $templateCode
     * @return string
     */
    public function renderBatchCallSlips(array $requestIds, string $templateCode = 'call_slip_standard'): string
    {
        $template = $this->getPrintTemplate($templateCode);
        if (!$template) {
            return '<p>Template not found</p>';
        }

        $html = '<style>' . ($template->css_styles ?? '') . '</style>';
        $html .= '<div class="call-slips">';

        foreach ($requestIds as $requestId) {
            $data = $this->getCallSlipData($requestId);
            if (empty($data)) {
                continue;
            }

            $slipHtml = $template->template_html;
            foreach ($data as $key => $value) {
                $slipHtml = str_replace('{{' . $key . '}}', htmlspecialchars($value ?? ''), $slipHtml);
            }

            $html .= '<div class="call-slip-page" style="page-break-after: always;">';
            $html .= $slipHtml;
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Mark call slip as printed
     *
     * @param int      $requestId
     * @param int|null $printedBy
     * @return bool
     */
    public function markCallSlipPrinted(int $requestId, ?int $printedBy = null): bool
    {
        return DB::table('research_material_request')
            ->where('id', $requestId)
            ->update([
                'paging_slip_printed' => 1,
                'call_slip_printed_at' => date('Y-m-d H:i:s'),
                'call_slip_printed_by' => $printedBy,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    // =========================================================================
    // WALK-IN VISITORS
    // =========================================================================

    /**
     * Register a walk-in visitor
     *
     * @param array $data
     * @return int
     */
    public function registerWalkIn(array $data): int
    {
        return DB::table('research_walk_in_visitor')->insertGetId([
            'reading_room_id' => $data['reading_room_id'],
            'visit_date' => $data['visit_date'] ?? date('Y-m-d'),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'id_type' => $data['id_type'] ?? null,
            'id_number' => $data['id_number'] ?? null,
            'organization' => $data['organization'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'research_topic' => $data['research_topic'] ?? null,
            'rules_acknowledged' => $data['rules_acknowledged'] ?? 0,
            'rules_acknowledged_at' => ($data['rules_acknowledged'] ?? 0) ? date('Y-m-d H:i:s') : null,
            'photo_permission' => $data['photo_permission'] ?? 0,
            'seat_id' => $data['seat_id'] ?? null,
            'check_in_time' => $data['check_in_time'] ?? date('H:i:s'),
            'checked_in_by' => $data['checked_in_by'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check out a walk-in visitor
     *
     * @param int      $visitorId
     * @param int|null $checkedOutBy
     * @return bool
     */
    public function checkOutWalkIn(int $visitorId, ?int $checkedOutBy = null): bool
    {
        return DB::table('research_walk_in_visitor')
            ->where('id', $visitorId)
            ->update([
                'check_out_time' => date('H:i:s'),
                'checked_out_by' => $checkedOutBy,
            ]) > 0;
    }

    /**
     * Get current walk-in visitors for a room
     *
     * @param int    $roomId
     * @param string $date
     * @return array
     */
    public function getCurrentWalkIns(int $roomId, ?string $date = null): array
    {
        $date = $date ?? date('Y-m-d');

        return DB::table('research_walk_in_visitor as w')
            ->leftJoin('research_reading_room_seat as s', 'w.seat_id', '=', 's.id')
            ->where('w.reading_room_id', $roomId)
            ->where('w.visit_date', $date)
            ->whereNull('w.check_out_time')
            ->select('w.*', 's.seat_number', 's.seat_label')
            ->orderBy('w.check_in_time')
            ->get()
            ->toArray();
    }

    /**
     * Convert walk-in to registered researcher
     *
     * @param int $visitorId
     * @param int $researcherId
     * @return bool
     */
    public function convertWalkInToResearcher(int $visitorId, int $researcherId): bool
    {
        return DB::table('research_walk_in_visitor')
            ->where('id', $visitorId)
            ->update([
                'converted_to_researcher_id' => $researcherId,
            ]) > 0;
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    /**
     * Get retrieval statistics
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getRetrievalStatistics(string $startDate, string $endDate): array
    {
        $totalRequests = DB::table('research_material_request as mr')
            ->join('research_booking as b', 'mr.booking_id', '=', 'b.id')
            ->whereBetween('b.booking_date', [$startDate, $endDate])
            ->count();

        $byStatus = DB::table('research_material_request as mr')
            ->join('research_booking as b', 'mr.booking_id', '=', 'b.id')
            ->whereBetween('b.booking_date', [$startDate, $endDate])
            ->select('mr.status', DB::raw('COUNT(*) as count'))
            ->groupBy('mr.status')
            ->get()
            ->toArray();

        $byPriority = DB::table('research_material_request as mr')
            ->join('research_booking as b', 'mr.booking_id', '=', 'b.id')
            ->whereBetween('b.booking_date', [$startDate, $endDate])
            ->select('mr.priority', DB::raw('COUNT(*) as count'))
            ->groupBy('mr.priority')
            ->get()
            ->toArray();

        $avgRetrievalTime = DB::table('research_material_request')
            ->whereNotNull('retrieved_at')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, retrieved_at)) as avg_minutes')
            ->value('avg_minutes');

        $walkInCount = DB::table('research_walk_in_visitor')
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->count();

        return [
            'total_requests' => $totalRequests,
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'avg_retrieval_time_minutes' => round($avgRetrievalTime ?? 0, 1),
            'walk_in_visitors' => $walkInCount,
        ];
    }
}
