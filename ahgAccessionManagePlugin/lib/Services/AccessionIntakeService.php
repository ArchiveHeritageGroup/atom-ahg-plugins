<?php

namespace AhgAccessionManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Accession Intake Service
 *
 * Manages the intake queue workflow and status transitions.
 * Status machine: draft -> submitted -> under_review -> accepted|rejected|returned
 *                 returned -> submitted (resubmission)
 */
class AccessionIntakeService
{
    /** Valid statuses */
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_RETURNED = 'returned';

    /** Valid status transitions: from => [allowed targets] */
    const TRANSITIONS = [
        'draft' => ['submitted'],
        'submitted' => ['under_review', 'returned'],
        'under_review' => ['accepted', 'rejected', 'returned'],
        'returned' => ['submitted'],
        'accepted' => [],
        'rejected' => [],
    ];

    /** Valid priorities */
    const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    /** Timeline event types */
    const EVENT_CREATED = 'created';
    const EVENT_SUBMITTED = 'submitted';
    const EVENT_ASSIGNED = 'assigned';
    const EVENT_REVIEWED = 'reviewed';
    const EVENT_ACCEPTED = 'accepted';
    const EVENT_REJECTED = 'rejected';
    const EVENT_RETURNED = 'returned';
    const EVENT_APPRAISED = 'appraised';
    const EVENT_CONTAINERIZED = 'containerized';
    const EVENT_RIGHTS_ASSIGNED = 'rights_assigned';
    const EVENT_DEACCESSIONED = 'deaccessioned';
    const EVENT_NOTE = 'note';

    protected ?int $tenantId;

    public function __construct(?int $tenantId = null)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Apply tenant_id filter to a query builder instance.
     */
    public function scopeQuery($query)
    {
        if ($this->tenantId !== null) {
            $query->where('tenant_id', $this->tenantId);
        }

        return $query;
    }

    // =========================================================================
    // INTAKE QUEUE
    // =========================================================================

    /**
     * Get the filterable intake queue, paginated.
     */
    public function getQueue(array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = max(1, min(100, (int) ($filters['limit'] ?? 30)));
        $offset = ($page - 1) * $limit;

        $query = DB::table('accession_v2 as v2')
            ->join('accession as a', 'v2.accession_id', '=', 'a.id')
            ->leftJoin('accession_i18n as ai', function ($j) {
                $j->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
            ->leftJoin('user as u', 'v2.assigned_to', '=', 'u.id')
            ->leftJoin('actor_i18n as assignee', function ($j) {
                $j->on('u.id', '=', 'assignee.id')
                    ->where('assignee.culture', '=', 'en');
            });

        $this->scopeQuery($query);

        // Filters
        if (!empty($filters['status'])) {
            $query->where('v2.status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $query->where('v2.priority', $filters['priority']);
        }
        if (!empty($filters['assigned_to'])) {
            $query->where('v2.assigned_to', $filters['assigned_to']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('v2.created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('v2.created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('a.identifier', 'LIKE', $search)
                    ->orWhere('ai.title', 'LIKE', $search);
            });
        }

        $total = $query->count();

        $sort = $filters['sort'] ?? 'created_at';
        $sortDir = strtolower($filters['sortDir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $validSorts = [
            'created_at' => 'v2.created_at',
            'submitted_at' => 'v2.submitted_at',
            'status' => 'v2.status',
            'priority' => 'v2.priority',
            'identifier' => 'a.identifier',
        ];
        $orderCol = $validSorts[$sort] ?? 'v2.created_at';

        $rows = $query->select(
            'v2.accession_id',
            'v2.status',
            'v2.priority',
            'v2.assigned_to',
            'v2.submitted_at',
            'v2.accepted_at',
            'v2.rejected_at',
            'v2.rejection_reason',
            'v2.intake_notes',
            'v2.created_at as v2_created_at',
            'a.identifier',
            'a.date',
            'ai.title',
            'slug.slug',
            'assignee.authorized_form_of_name as assignee_name'
        )
            ->orderBy($orderCol, $sortDir)
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->all();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Get queue statistics.
     */
    public function getQueueStats(): array
    {
        $base = DB::table('accession_v2');
        $this->scopeQuery($base);

        $byStatus = (clone $base)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->all();

        $byPriority = (clone $base)
            ->selectRaw('priority, COUNT(*) as cnt')
            ->groupBy('priority')
            ->pluck('cnt', 'priority')
            ->all();

        $avgTimeToAccept = (clone $base)
            ->where('status', self::STATUS_ACCEPTED)
            ->whereNotNull('submitted_at')
            ->whereNotNull('accepted_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, submitted_at, accepted_at)) as avg_hours')
            ->value('avg_hours');

        $overdue = (clone $base)
            ->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW])
            ->where('submitted_at', '<', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->count();

        return [
            'byStatus' => $byStatus,
            'byPriority' => $byPriority,
            'avgTimeToAcceptHours' => $avgTimeToAccept ? round($avgTimeToAccept, 1) : null,
            'overdue' => $overdue,
            'total' => array_sum($byStatus),
        ];
    }

    // =========================================================================
    // STATUS TRANSITIONS
    // =========================================================================

    /**
     * Validate a status transition.
     */
    protected function validateTransition(string $currentStatus, string $newStatus): bool
    {
        $allowed = self::TRANSITIONS[$currentStatus] ?? [];

        return in_array($newStatus, $allowed, true);
    }

    /**
     * Get the current V2 record for an accession.
     */
    public function getV2Record(int $accessionId): ?object
    {
        $query = DB::table('accession_v2')->where('accession_id', $accessionId);
        $this->scopeQuery($query);

        return $query->first();
    }

    /**
     * Ensure a V2 record exists (create if missing).
     */
    public function ensureV2Record(int $accessionId): void
    {
        $exists = DB::table('accession_v2')->where('accession_id', $accessionId)->exists();
        if (!$exists) {
            DB::table('accession_v2')->insert([
                'accession_id' => $accessionId,
                'status' => self::STATUS_DRAFT,
                'priority' => 'normal',
                'tenant_id' => $this->tenantId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Submit an accession for review.
     */
    public function submit(int $accessionId, int $userId): bool
    {
        $v2 = $this->getV2Record($accessionId);
        if (!$v2 || !$this->validateTransition($v2->status, self::STATUS_SUBMITTED)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        DB::table('accession_v2')
            ->where('accession_id', $accessionId)
            ->update([
                'status' => self::STATUS_SUBMITTED,
                'submitted_at' => $now,
                'updated_at' => $now,
            ]);

        $this->addTimelineEvent($accessionId, self::EVENT_SUBMITTED, $userId, 'Accession submitted for review');

        return true;
    }

    /**
     * Assign an accession to a user.
     */
    public function assign(int $accessionId, int $assigneeId, int $userId): bool
    {
        $v2 = $this->getV2Record($accessionId);
        if (!$v2) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        DB::table('accession_v2')
            ->where('accession_id', $accessionId)
            ->update([
                'assigned_to' => $assigneeId,
                'updated_at' => $now,
            ]);

        $assigneeName = DB::table('actor_i18n')
            ->where('id', $assigneeId)
            ->where('culture', 'en')
            ->value('authorized_form_of_name') ?? 'User #' . $assigneeId;

        $this->addTimelineEvent(
            $accessionId,
            self::EVENT_ASSIGNED,
            $userId,
            'Assigned to ' . $assigneeName,
            ['assignee_id' => $assigneeId]
        );

        return true;
    }

    /**
     * Move accession to under review.
     */
    public function review(int $accessionId, int $userId): bool
    {
        $v2 = $this->getV2Record($accessionId);
        if (!$v2 || !$this->validateTransition($v2->status, self::STATUS_UNDER_REVIEW)) {
            return false;
        }

        DB::table('accession_v2')
            ->where('accession_id', $accessionId)
            ->update([
                'status' => self::STATUS_UNDER_REVIEW,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $this->addTimelineEvent($accessionId, self::EVENT_REVIEWED, $userId, 'Accession under review');

        return true;
    }

    /**
     * Accept an accession.
     */
    public function accept(int $accessionId, int $userId): bool
    {
        $v2 = $this->getV2Record($accessionId);
        if (!$v2 || !$this->validateTransition($v2->status, self::STATUS_ACCEPTED)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        DB::table('accession_v2')
            ->where('accession_id', $accessionId)
            ->update([
                'status' => self::STATUS_ACCEPTED,
                'accepted_at' => $now,
                'updated_at' => $now,
            ]);

        $this->addTimelineEvent($accessionId, self::EVENT_ACCEPTED, $userId, 'Accession accepted');

        return true;
    }

    /**
     * Reject an accession.
     */
    public function reject(int $accessionId, string $reason, int $userId): bool
    {
        $v2 = $this->getV2Record($accessionId);
        if (!$v2 || !$this->validateTransition($v2->status, self::STATUS_REJECTED)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        DB::table('accession_v2')
            ->where('accession_id', $accessionId)
            ->update([
                'status' => self::STATUS_REJECTED,
                'rejected_at' => $now,
                'rejection_reason' => $reason,
                'updated_at' => $now,
            ]);

        $this->addTimelineEvent(
            $accessionId,
            self::EVENT_REJECTED,
            $userId,
            'Accession rejected: ' . $reason
        );

        return true;
    }

    /**
     * Return an accession for revision.
     */
    public function returnForRevision(int $accessionId, string $notes, int $userId): bool
    {
        $v2 = $this->getV2Record($accessionId);
        if (!$v2 || !$this->validateTransition($v2->status, self::STATUS_RETURNED)) {
            return false;
        }

        DB::table('accession_v2')
            ->where('accession_id', $accessionId)
            ->update([
                'status' => self::STATUS_RETURNED,
                'intake_notes' => $notes,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $this->addTimelineEvent(
            $accessionId,
            self::EVENT_RETURNED,
            $userId,
            'Returned for revision: ' . $notes
        );

        return true;
    }

    // =========================================================================
    // CHECKLIST
    // =========================================================================

    /**
     * Get checklist items for an accession.
     */
    public function getChecklist(int $accessionId): array
    {
        $query = DB::table('accession_intake_checklist')
            ->where('accession_id', $accessionId)
            ->orderBy('sort_order');

        $this->scopeQuery($query);

        return $query->get()->all();
    }

    /**
     * Apply a checklist template to an accession.
     */
    public function applyChecklistTemplate(int $accessionId, int $templateId): int
    {
        $template = DB::table('accession_intake_template')
            ->where('id', $templateId)
            ->first();

        if (!$template) {
            return 0;
        }

        $items = json_decode($template->items, true);
        if (!is_array($items)) {
            return 0;
        }

        // Remove existing checklist items
        DB::table('accession_intake_checklist')
            ->where('accession_id', $accessionId)
            ->delete();

        $count = 0;
        foreach ($items as $item) {
            DB::table('accession_intake_checklist')->insert([
                'accession_id' => $accessionId,
                'label' => $item['label'] ?? '',
                'sort_order' => $item['sort_order'] ?? $count,
                'tenant_id' => $this->tenantId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Toggle a checklist item's completion status.
     */
    public function toggleChecklistItem(int $itemId, int $userId): bool
    {
        $item = DB::table('accession_intake_checklist')
            ->where('id', $itemId)
            ->first();

        if (!$item) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $completed = !$item->is_completed;

        DB::table('accession_intake_checklist')
            ->where('id', $itemId)
            ->update([
                'is_completed' => $completed ? 1 : 0,
                'completed_by' => $completed ? $userId : null,
                'completed_at' => $completed ? $now : null,
            ]);

        return true;
    }

    /**
     * Get checklist completion progress.
     */
    public function getChecklistProgress(int $accessionId): array
    {
        $total = DB::table('accession_intake_checklist')
            ->where('accession_id', $accessionId)
            ->count();

        $completed = DB::table('accession_intake_checklist')
            ->where('accession_id', $accessionId)
            ->where('is_completed', 1)
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'percent' => $total > 0 ? round(($completed / $total) * 100) : 0,
        ];
    }

    /**
     * Get intake checklist templates.
     */
    public function getChecklistTemplates(): array
    {
        $query = DB::table('accession_intake_template')->orderBy('name');
        $this->scopeQuery($query);

        return $query->get()->all();
    }

    // =========================================================================
    // TIMELINE
    // =========================================================================

    /**
     * Add a timeline event.
     */
    public function addTimelineEvent(
        int $accessionId,
        string $eventType,
        ?int $actorId,
        ?string $description = null,
        ?array $metadata = null
    ): int {
        return DB::table('accession_timeline')->insertGetId([
            'accession_id' => $accessionId,
            'event_type' => $eventType,
            'actor_id' => $actorId,
            'description' => $description,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'tenant_id' => $this->tenantId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get timeline events for an accession.
     */
    public function getTimeline(int $accessionId): array
    {
        $query = DB::table('accession_timeline as t')
            ->leftJoin('actor_i18n as ai', function ($j) {
                $j->on('t.actor_id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->where('t.accession_id', $accessionId)
            ->select(
                't.*',
                'ai.authorized_form_of_name as actor_name'
            )
            ->orderBy('t.created_at', 'desc');

        $this->scopeQuery($query);

        return $query->get()->all();
    }

    // =========================================================================
    // ATTACHMENTS
    // =========================================================================

    /**
     * Add an attachment to an accession.
     *
     * @param array $file ['tmp_name' => ..., 'name' => ..., 'type' => ..., 'size' => ...]
     * @return int Attachment ID
     */
    public function addAttachment(int $accessionId, array $file, string $category, ?int $userId): int
    {
        $tenantDir = $this->tenantId ? $this->tenantId : 'default';
        $uploadDir = \sfConfig::get('sf_upload_dir', \sfConfig::get('sf_root_dir') . '/uploads')
            . '/accessions/' . $tenantDir . '/' . $accessionId;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('att_') . ($ext ? '.' . $ext : '');

        move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename);

        $id = DB::table('accession_attachment')->insertGetId([
            'accession_id' => $accessionId,
            'filename' => $filename,
            'original_name' => $file['name'],
            'mime_type' => $file['type'] ?? 'application/octet-stream',
            'file_size' => $file['size'] ?? 0,
            'category' => $category,
            'uploaded_by' => $userId,
            'tenant_id' => $this->tenantId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->addTimelineEvent($accessionId, self::EVENT_NOTE, $userId, 'Attachment added: ' . $file['name']);

        return $id;
    }

    /**
     * Delete an attachment.
     */
    public function deleteAttachment(int $attachmentId): bool
    {
        $att = DB::table('accession_attachment')->where('id', $attachmentId)->first();
        if (!$att) {
            return false;
        }

        $tenantDir = $att->tenant_id ?: 'default';
        $filePath = \sfConfig::get('sf_upload_dir', \sfConfig::get('sf_root_dir') . '/uploads')
            . '/accessions/' . $tenantDir . '/' . $att->accession_id . '/' . $att->filename;

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        DB::table('accession_attachment')->where('id', $attachmentId)->delete();

        return true;
    }

    /**
     * Get attachments for an accession.
     */
    public function getAttachments(int $accessionId): array
    {
        $query = DB::table('accession_attachment')
            ->where('accession_id', $accessionId)
            ->orderBy('created_at', 'desc');

        $this->scopeQuery($query);

        return $query->get()->all();
    }

    /**
     * Get the download path for an attachment.
     */
    public function getAttachmentPath(int $attachmentId): ?string
    {
        $att = DB::table('accession_attachment')->where('id', $attachmentId)->first();
        if (!$att) {
            return null;
        }

        $tenantDir = $att->tenant_id ?: 'default';

        return \sfConfig::get('sf_upload_dir', \sfConfig::get('sf_root_dir') . '/uploads')
            . '/accessions/' . $tenantDir . '/' . $att->accession_id . '/' . $att->filename;
    }

    // =========================================================================
    // V2 DATA CLEANUP (for cascade delete)
    // =========================================================================

    /**
     * Delete all V2 data for an accession (called from AccessionCrudService::delete).
     */
    public function deleteAllForAccession(int $accessionId): void
    {
        // Delete attachments (files + DB)
        $attachments = DB::table('accession_attachment')
            ->where('accession_id', $accessionId)
            ->get();
        foreach ($attachments as $att) {
            $this->deleteAttachment($att->id);
        }

        DB::table('accession_intake_checklist')->where('accession_id', $accessionId)->delete();
        DB::table('accession_timeline')->where('accession_id', $accessionId)->delete();
        DB::table('accession_v2')->where('accession_id', $accessionId)->delete();
    }
}
