<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * RequestLifecycleService — Bridges research requests with V2.0 SLA + WorkflowEventService
 *
 * Handles triage, assignment, correspondence, closure, SLA computation,
 * and combined timeline for material and reproduction requests.
 *
 * @package    ahgResearchPlugin
 * @subpackage Services
 * @version    1.0.0
 */
class RequestLifecycleService
{
    private ?object $eventService = null;
    private ?object $slaService = null;

    // =========================================================================
    // LAZY SERVICE LOADING
    // =========================================================================

    private function getEventService(): WorkflowEventService
    {
        if ($this->eventService === null) {
            $pluginsDir = \sfConfig::get('sf_plugins_dir');
            require_once $pluginsDir . '/ahgWorkflowPlugin/lib/Services/WorkflowEventService.php';
            $this->eventService = new WorkflowEventService();
        }
        return $this->eventService;
    }

    private function getSlaService(): WorkflowSlaService
    {
        if ($this->slaService === null) {
            $pluginsDir = \sfConfig::get('sf_plugins_dir');
            require_once $pluginsDir . '/ahgWorkflowPlugin/lib/Services/WorkflowSlaService.php';
            $this->slaService = new WorkflowSlaService();
        }
        return $this->slaService;
    }

    // =========================================================================
    // TRIAGE
    // =========================================================================

    /**
     * Triage a request (approve / deny / needs-info).
     *
     * @param int    $requestId
     * @param string $requestType  'material' or 'reproduction'
     * @param string $decision     'approved', 'denied', 'needs_info'
     * @param int    $userId       Staff user who triages
     * @param string $notes        Triage notes
     * @return array ['success' => bool, 'sla' => ?array]
     */
    public function triageRequest(
        int $requestId,
        string $requestType,
        string $decision,
        int $userId,
        string $notes = ''
    ): array {
        $table = $this->resolveTable($requestType);
        $request = DB::table($table)->where('id', $requestId)->first();
        if (!$request) {
            return ['success' => false, 'error' => 'Request not found'];
        }

        $triageStatus = match ($decision) {
            'approved' => 'triage_approved',
            'denied' => 'triage_denied',
            'needs_info' => 'needs_information',
            default => 'pending_triage',
        };

        DB::table($table)->where('id', $requestId)->update([
            'triage_status' => $triageStatus,
            'triage_by' => $userId,
            'triage_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Log status history
        DB::table('research_request_status_history')->insert([
            'request_id' => $requestId,
            'request_type' => $requestType,
            'old_status' => $request->triage_status ?? 'pending_triage',
            'new_status' => $triageStatus,
            'changed_by' => $userId,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Emit workflow event
        $this->getEventService()->emit('request_triaged', [
            'object_id' => $requestId,
            'object_type' => "research_{$requestType}_request",
            'performed_by' => $userId,
            'from_status' => 'pending_triage',
            'to_status' => $triageStatus,
            'comment' => $notes,
            'metadata' => ['decision' => $decision, 'request_type' => $requestType],
        ]);

        // Compute SLA if approved
        $sla = null;
        if ($decision === 'approved') {
            $sla = $this->computeSla($requestId, $requestType);
        }

        return ['success' => true, 'triage_status' => $triageStatus, 'sla' => $sla];
    }

    // =========================================================================
    // ASSIGNMENT
    // =========================================================================

    /**
     * Assign a request to a staff member.
     */
    public function assignRequest(
        int $requestId,
        string $requestType,
        int $staffId,
        int $assignedBy
    ): array {
        $table = $this->resolveTable($requestType);
        $request = DB::table($table)->where('id', $requestId)->first();
        if (!$request) {
            return ['success' => false, 'error' => 'Request not found'];
        }

        $previousAssignee = $request->assigned_to ?? null;

        DB::table($table)->where('id', $requestId)->update([
            'assigned_to' => $staffId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->getEventService()->emit('request_assigned', [
            'object_id' => $requestId,
            'object_type' => "research_{$requestType}_request",
            'performed_by' => $assignedBy,
            'metadata' => [
                'from_user_id' => $previousAssignee,
                'to_user_id' => $staffId,
                'request_type' => $requestType,
            ],
        ]);

        return ['success' => true];
    }

    // =========================================================================
    // CORRESPONDENCE
    // =========================================================================

    /**
     * Add a correspondence message to a request.
     */
    public function addCorrespondence(
        int $requestId,
        string $requestType,
        int $senderId,
        string $senderType,
        string $body,
        bool $isInternal = false,
        ?string $subject = null,
        ?string $attachmentPath = null,
        ?string $attachmentName = null
    ): int {
        $id = DB::table('research_request_correspondence')->insertGetId([
            'request_id' => $requestId,
            'request_type' => $requestType,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'subject' => $subject,
            'body' => $body,
            'is_internal' => $isInternal ? 1 : 0,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Emit workflow event (skip for internal staff notes unless audit needed)
        $this->getEventService()->emit('correspondence_added', [
            'object_id' => $requestId,
            'object_type' => "research_{$requestType}_request",
            'performed_by' => $senderId,
            'comment' => $isInternal ? '[Internal] ' . substr($body, 0, 200) : substr($body, 0, 200),
            'metadata' => [
                'correspondence_id' => $id,
                'sender_type' => $senderType,
                'is_internal' => $isInternal,
                'request_type' => $requestType,
            ],
        ]);

        return $id;
    }

    /**
     * Get correspondence thread for a request.
     *
     * @param bool $includeInternal  If false, hides staff-only notes
     */
    public function getCorrespondence(
        int $requestId,
        string $requestType,
        bool $includeInternal = true
    ): array {
        $query = DB::table('research_request_correspondence as c')
            ->leftJoin('user as u', 'c.sender_id', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')
                     ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('research_researcher as rr', function ($join) {
                $join->on('c.sender_id', '=', 'rr.id')
                     ->where('c.sender_type', '=', 'researcher');
            })
            ->where('c.request_id', $requestId)
            ->where('c.request_type', $requestType);

        if (!$includeInternal) {
            $query->where('c.is_internal', 0);
        }

        return $query->select(
            'c.*',
            DB::raw("CASE
                WHEN c.sender_type = 'staff' THEN COALESCE(ai.authorized_form_of_name, u.username, 'Staff')
                WHEN c.sender_type = 'researcher' THEN CONCAT(COALESCE(rr.first_name, ''), ' ', COALESCE(rr.last_name, ''))
                ELSE 'System'
            END as sender_name")
        )
            ->orderBy('c.created_at', 'asc')
            ->get()
            ->toArray();
    }

    // =========================================================================
    // CLOSURE
    // =========================================================================

    /**
     * Close a request with a reason.
     */
    public function closeRequest(
        int $requestId,
        string $requestType,
        int $userId,
        string $reason,
        string $notes = ''
    ): array {
        $table = $this->resolveTable($requestType);
        $request = DB::table($table)->where('id', $requestId)->first();
        if (!$request) {
            return ['success' => false, 'error' => 'Request not found'];
        }

        $updateData = ['updated_at' => date('Y-m-d H:i:s')];

        if ($requestType === 'reproduction') {
            $updateData['closed_at'] = date('Y-m-d H:i:s');
            $updateData['closed_by'] = $userId;
            $updateData['closure_reason'] = $reason;
        }

        DB::table($table)->where('id', $requestId)->update($updateData);

        // Log status history
        DB::table('research_request_status_history')->insert([
            'request_id' => $requestId,
            'request_type' => $requestType,
            'old_status' => $request->status ?? '',
            'new_status' => 'closed',
            'changed_by' => $userId,
            'notes' => $notes ?: "Closed: {$reason}",
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->getEventService()->emit('request_closed', [
            'object_id' => $requestId,
            'object_type' => "research_{$requestType}_request",
            'performed_by' => $userId,
            'from_status' => $request->status ?? '',
            'to_status' => 'closed',
            'comment' => $notes ?: "Closed: {$reason}",
            'metadata' => ['closure_reason' => $reason, 'request_type' => $requestType],
        ]);

        return ['success' => true];
    }

    // =========================================================================
    // SLA
    // =========================================================================

    /**
     * Compute and store SLA due date for a request.
     */
    public function computeSla(int $requestId, string $requestType): array
    {
        $table = $this->resolveTable($requestType);
        $request = DB::table($table)->where('id', $requestId)->first();
        if (!$request) {
            return ['status' => 'no_policy'];
        }

        $priority = $request->priority ?? 'normal';
        $policy = $this->getSlaService()->resolvePolicy('requests', null, $priority);

        $sla = $this->getSlaService()->compute(
            $request->created_at,
            $policy,
            $request->sla_due_date ?? null,
            null, // not completed
            null  // use now
        );

        // Store computed due date if not already set
        if (empty($request->sla_due_date) && !empty($sla['due_at'])) {
            DB::table($table)->where('id', $requestId)->update([
                'sla_due_date' => date('Y-m-d', strtotime($sla['due_at'])),
            ]);
        }

        return $sla;
    }

    /**
     * Get SLA info for a request (display-time computation).
     */
    public function getRequestSla(int $requestId, string $requestType): array
    {
        $table = $this->resolveTable($requestType);
        $request = DB::table($table)->where('id', $requestId)->first();
        if (!$request) {
            return ['status' => 'no_policy'];
        }

        $priority = $request->priority ?? 'normal';
        $policy = $this->getSlaService()->resolvePolicy('requests', null, $priority);

        $completedAt = null;
        if ($requestType === 'reproduction' && !empty($request->completed_at)) {
            $completedAt = $request->completed_at;
        } elseif ($requestType === 'material' && $request->status === 'returned') {
            $completedAt = $request->returned_at;
        }

        return $this->getSlaService()->compute(
            $request->created_at,
            $policy,
            $request->sla_due_date ?? null,
            $completedAt,
            null
        );
    }

    // =========================================================================
    // TIMELINE
    // =========================================================================

    /**
     * Get combined timeline: status_history + workflow_history for a request.
     */
    public function getRequestTimeline(int $requestId, string $requestType): array
    {
        // Status history entries
        $statusHistory = DB::table('research_request_status_history as h')
            ->leftJoin('user as u', 'h.changed_by', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')
                     ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('h.request_id', $requestId)
            ->where('h.request_type', $requestType)
            ->select(
                'h.id',
                DB::raw("'status_change' as event_source"),
                DB::raw("CONCAT(COALESCE(h.old_status, ''), ' → ', h.new_status) as action"),
                'h.notes as comment',
                'h.changed_by as performed_by',
                DB::raw('COALESCE(ai.authorized_form_of_name, u.username) as performer_name'),
                'h.created_at as performed_at'
            )
            ->get()
            ->toArray();

        // Workflow history events
        $objectType = "research_{$requestType}_request";
        $workflowHistory = DB::table('ahg_workflow_history as wh')
            ->leftJoin('user as u', 'wh.performed_by', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')
                     ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('wh.object_id', $requestId)
            ->where('wh.object_type', $objectType)
            ->select(
                'wh.id',
                DB::raw("'workflow_event' as event_source"),
                'wh.action',
                'wh.comment',
                'wh.performed_by',
                DB::raw('COALESCE(ai.authorized_form_of_name, u.username) as performer_name'),
                'wh.performed_at'
            )
            ->get()
            ->toArray();

        // Correspondence entries
        $correspondence = DB::table('research_request_correspondence as c')
            ->leftJoin('user as u', 'c.sender_id', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')
                     ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('c.request_id', $requestId)
            ->where('c.request_type', $requestType)
            ->select(
                'c.id',
                DB::raw("'correspondence' as event_source"),
                DB::raw("CONCAT('Message from ', c.sender_type) as action"),
                DB::raw('SUBSTRING(c.body, 1, 200) as comment'),
                'c.sender_id as performed_by',
                DB::raw('COALESCE(ai.authorized_form_of_name, u.username) as performer_name'),
                'c.created_at as performed_at'
            )
            ->get()
            ->toArray();

        // Merge and sort by date descending
        $timeline = array_merge($statusHistory, $workflowHistory, $correspondence);
        usort($timeline, fn($a, $b) => strtotime($b->performed_at) - strtotime($a->performed_at));

        return $timeline;
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    /**
     * Get combined requests dashboard with SLA badges.
     *
     * @param array $filters  Keys: status, type, search, assigned_to, overdue_only
     * @param int   $limit
     * @param int   $offset
     * @return array ['material' => [...], 'reproduction' => [...], 'totals' => [...]]
     */
    public function getRequestsDashboard(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $material = $this->getMaterialRequests($filters, $limit, $offset);
        $reproduction = $this->getReproductionRequests($filters, $limit, $offset);

        // Compute SLA for each
        foreach ($material as &$req) {
            $req->sla = $this->getRequestSla($req->id, 'material');
        }
        foreach ($reproduction as &$req) {
            $req->sla = $this->getRequestSla($req->id, 'reproduction');
        }

        // Totals
        $totalMaterial = DB::table('research_material_request')->count();
        $totalReproduction = DB::table('research_reproduction_request')
            ->where('status', '!=', 'draft')
            ->count();

        $overdueCount = 0;
        foreach (array_merge($material, $reproduction) as $req) {
            if (isset($req->sla['status']) && in_array($req->sla['status'], ['overdue', 'breached'])) {
                $overdueCount++;
            }
        }

        return [
            'material' => $material,
            'reproduction' => $reproduction,
            'totals' => [
                'material' => $totalMaterial,
                'reproduction' => $totalReproduction,
                'overdue' => $overdueCount,
            ],
        ];
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function resolveTable(string $requestType): string
    {
        return match ($requestType) {
            'reproduction' => 'research_reproduction_request',
            default => 'research_material_request',
        };
    }

    private function getMaterialRequests(array $filters, int $limit, int $offset): array
    {
        $query = DB::table('research_material_request as mr')
            ->join('research_booking as b', 'mr.booking_id', '=', 'b.id')
            ->join('research_researcher as r', 'b.researcher_id', '=', 'r.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('mr.object_id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('user as staff', 'mr.assigned_to', '=', 'staff.id')
            ->leftJoin('actor_i18n as staff_name', function ($join) {
                $join->on('staff.id', '=', 'staff_name.id')
                     ->where('staff_name.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select(
                'mr.*',
                'b.booking_date',
                'r.first_name',
                'r.last_name',
                'r.email as researcher_email',
                'ioi.title as item_title',
                'staff_name.authorized_form_of_name as assigned_to_name'
            );

        $this->applyDashboardFilters($query, $filters, 'mr');

        return $query->orderBy('mr.created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();
    }

    private function getReproductionRequests(array $filters, int $limit, int $offset): array
    {
        $query = DB::table('research_reproduction_request as rr')
            ->join('research_researcher as r', 'rr.researcher_id', '=', 'r.id')
            ->leftJoin('user as staff', 'rr.assigned_to', '=', 'staff.id')
            ->leftJoin('actor_i18n as staff_name', function ($join) {
                $join->on('staff.id', '=', 'staff_name.id')
                     ->where('staff_name.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('rr.status', '!=', 'draft')
            ->select(
                'rr.*',
                'r.first_name',
                'r.last_name',
                'r.email as researcher_email',
                'staff_name.authorized_form_of_name as assigned_to_name'
            );

        $this->applyDashboardFilters($query, $filters, 'rr');

        return $query->orderBy('rr.created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();
    }

    private function applyDashboardFilters($query, array $filters, string $alias): void
    {
        if (!empty($filters['status'])) {
            $query->where("{$alias}.status", $filters['status']);
        }
        if (!empty($filters['assigned_to'])) {
            $query->where("{$alias}.assigned_to", $filters['assigned_to']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search, $alias) {
                $q->where('r.first_name', 'like', $search)
                    ->orWhere('r.last_name', 'like', $search)
                    ->orWhere('r.email', 'like', $search);
            });
        }
        if (!empty($filters['overdue_only'])) {
            $query->whereNotNull("{$alias}.sla_due_date")
                  ->where("{$alias}.sla_due_date", '<', date('Y-m-d'));
        }
    }

    /**
     * Get a single request with SLA info for the triage/view page.
     */
    public function getRequestWithSla(int $requestId, string $requestType): ?object
    {
        $table = $this->resolveTable($requestType);

        if ($requestType === 'reproduction') {
            $request = DB::table('research_reproduction_request as rr')
                ->join('research_researcher as r', 'rr.researcher_id', '=', 'r.id')
                ->where('rr.id', $requestId)
                ->select('rr.*', 'r.first_name', 'r.last_name', 'r.email as researcher_email', 'r.institution')
                ->first();
        } else {
            $request = DB::table('research_material_request as mr')
                ->join('research_booking as b', 'mr.booking_id', '=', 'b.id')
                ->join('research_researcher as r', 'b.researcher_id', '=', 'r.id')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('mr.object_id', '=', 'ioi.id')
                         ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->where('mr.id', $requestId)
                ->select('mr.*', 'b.booking_date', 'r.first_name', 'r.last_name', 'r.email as researcher_email', 'ioi.title as item_title')
                ->first();
        }

        if ($request) {
            $request->sla = $this->getRequestSla($requestId, $requestType);
            $request->correspondence_count = DB::table('research_request_correspondence')
                ->where('request_id', $requestId)
                ->where('request_type', $requestType)
                ->count();
        }

        return $request;
    }
}
