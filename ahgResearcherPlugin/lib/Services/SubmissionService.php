<?php

namespace AhgResearcherPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

class SubmissionService
{
    /**
     * Staging directory for uploaded files.
     */
    public function getStagingDir(int $submissionId): string
    {
        $dir = \sfConfig::get('sf_root_dir') . '/downloads/researcher-submissions/' . $submissionId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    // ─── SUBMISSIONS ────────────────────────────────────────────

    /**
     * Create a new draft submission.
     */
    public function createSubmission(int $userId, array $data): int
    {
        $researcherId = $this->resolveResearcherId($userId);

        return (int) DB::table('researcher_submission')->insertGetId([
            'researcher_id'   => $researcherId,
            'user_id'         => $userId,
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'repository_id'   => $data['repository_id'] ?: null,
            'parent_object_id' => $data['parent_object_id'] ?: null,
            'source_type'     => $data['source_type'] ?? 'online',
            'source_file'     => $data['source_file'] ?? null,
            'include_images'  => $data['include_images'] ?? 1,
            'status'          => 'draft',
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get a single submission with items, files, and reviews.
     */
    public function getSubmission(int $id): ?array
    {
        $sub = DB::table('researcher_submission as s')
            ->leftJoin('user as u', 's.user_id', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($j) {
                $j->on('u.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->where('s.id', $id)
            ->select('s.*', 'ai.authorized_form_of_name as user_name')
            ->first();

        if (!$sub) {
            return null;
        }

        $items = DB::table('researcher_submission_item')
            ->where('submission_id', $id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->toArray();

        $itemIds = array_column($items, 'id');
        $files = [];
        if (!empty($itemIds)) {
            $allFiles = DB::table('researcher_submission_file')
                ->whereIn('item_id', $itemIds)
                ->orderBy('sort_order')
                ->get()
                ->toArray();
            foreach ($allFiles as $f) {
                $files[$f->item_id][] = $f;
            }
        }

        $reviews = DB::table('researcher_submission_review as r')
            ->leftJoin('user as u', 'r.reviewer_id', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($j) {
                $j->on('u.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->where('r.submission_id', $id)
            ->select('r.*', 'ai.authorized_form_of_name as reviewer_name')
            ->orderBy('r.created_at', 'desc')
            ->get()
            ->toArray();

        return [
            'submission' => $sub,
            'items'      => $items,
            'files'      => $files,
            'reviews'    => $reviews,
        ];
    }

    /**
     * List submissions for a user (or all for archivists).
     */
    public function getSubmissions(?int $userId = null, array $filters = []): array
    {
        $q = DB::table('researcher_submission as s')
            ->leftJoin('user as u', 's.user_id', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($j) {
                $j->on('u.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->select('s.*', 'ai.authorized_form_of_name as user_name');

        if ($userId) {
            $q->where('s.user_id', $userId);
        }

        if (!empty($filters['status'])) {
            $q->where('s.status', $filters['status']);
        }

        if (!empty($filters['source_type'])) {
            $q->where('s.source_type', $filters['source_type']);
        }

        return $q->orderBy('s.updated_at', 'desc')->get()->toArray();
    }

    /**
     * Get submissions pending review (for archivists).
     */
    public function getPendingReviews(array $filters = []): array
    {
        return $this->getSubmissions(null, array_merge($filters, [
            'status' => $filters['status'] ?? 'submitted',
        ]));
    }

    /**
     * Update submission metadata.
     */
    public function updateSubmission(int $id, array $data): void
    {
        $allowed = ['title', 'description', 'repository_id', 'parent_object_id'];
        $update = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $update[$key] = $data[$key] ?: null;
            }
        }
        $update['updated_at'] = date('Y-m-d H:i:s');

        DB::table('researcher_submission')->where('id', $id)->update($update);
    }

    /**
     * Delete a draft submission and all associated data.
     */
    public function deleteSubmission(int $id): void
    {
        $sub = DB::table('researcher_submission')->where('id', $id)->first();
        if (!$sub || $sub->status !== 'draft') {
            return;
        }

        // Cascade delete handled by FK, but clean up files
        $stagingDir = $this->getStagingDir($id);
        if (is_dir($stagingDir)) {
            $this->removeDirectory($stagingDir);
        }

        DB::table('researcher_submission')->where('id', $id)->delete();
    }

    /**
     * Recalculate submission totals.
     */
    public function recalculateTotals(int $submissionId): void
    {
        $itemCount = DB::table('researcher_submission_item')
            ->where('submission_id', $submissionId)
            ->where('item_type', 'description')
            ->count();

        $itemIds = DB::table('researcher_submission_item')
            ->where('submission_id', $submissionId)
            ->pluck('id')
            ->toArray();

        $fileCount = 0;
        $fileSize = 0;
        if (!empty($itemIds)) {
            $fileCount = DB::table('researcher_submission_file')
                ->whereIn('item_id', $itemIds)
                ->count();
            $fileSize = DB::table('researcher_submission_file')
                ->whereIn('item_id', $itemIds)
                ->sum('file_size');
        }

        DB::table('researcher_submission')->where('id', $submissionId)->update([
            'total_items'     => $itemCount,
            'total_files'     => $fileCount,
            'total_file_size' => $fileSize,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    // ─── ITEMS ──────────────────────────────────────────────────

    /**
     * Add an item to a submission.
     */
    public function addItem(int $submissionId, array $data): int
    {
        $maxSort = DB::table('researcher_submission_item')
            ->where('submission_id', $submissionId)
            ->max('sort_order') ?? 0;

        $id = (int) DB::table('researcher_submission_item')->insertGetId([
            'submission_id'         => $submissionId,
            'parent_item_id'        => $data['parent_item_id'] ?? null,
            'item_type'             => $data['item_type'] ?? 'description',
            'title'                 => $data['title'],
            'identifier'            => $data['identifier'] ?? null,
            'level_of_description'  => $data['level_of_description'] ?? 'item',
            'scope_and_content'     => $data['scope_and_content'] ?? null,
            'extent_and_medium'     => $data['extent_and_medium'] ?? null,
            'date_display'          => $data['date_display'] ?? null,
            'date_start'            => $data['date_start'] ?: null,
            'date_end'              => $data['date_end'] ?: null,
            'creators'              => $data['creators'] ?? null,
            'subjects'              => $data['subjects'] ?? null,
            'places'                => $data['places'] ?? null,
            'genres'                => $data['genres'] ?? null,
            'access_conditions'     => $data['access_conditions'] ?? null,
            'reproduction_conditions' => $data['reproduction_conditions'] ?? null,
            'notes'                 => $data['notes'] ?? null,
            'repository_name'       => $data['repository_name'] ?? null,
            'repository_address'    => $data['repository_address'] ?? null,
            'repository_contact'    => $data['repository_contact'] ?? null,
            'reference_object_id'   => $data['reference_object_id'] ?? null,
            'reference_slug'        => $data['reference_slug'] ?? null,
            'sort_order'            => $maxSort + 1,
            'created_at'            => date('Y-m-d H:i:s'),
            'updated_at'            => date('Y-m-d H:i:s'),
        ]);

        $this->recalculateTotals($submissionId);

        return $id;
    }

    /**
     * Update an existing item.
     */
    public function updateItem(int $itemId, array $data): void
    {
        $allowed = [
            'title', 'identifier', 'level_of_description', 'scope_and_content',
            'extent_and_medium', 'date_display', 'date_start', 'date_end',
            'creators', 'subjects', 'places', 'genres',
            'access_conditions', 'reproduction_conditions', 'notes',
            'repository_name', 'repository_address', 'repository_contact',
            'parent_item_id', 'sort_order',
        ];
        $update = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $update[$key] = $data[$key] ?: null;
            }
        }
        $update['updated_at'] = date('Y-m-d H:i:s');

        DB::table('researcher_submission_item')->where('id', $itemId)->update($update);
    }

    /**
     * Delete an item and its files.
     */
    public function deleteItem(int $itemId): void
    {
        $item = DB::table('researcher_submission_item')->where('id', $itemId)->first();
        if (!$item) {
            return;
        }

        // Clean up physical files
        $files = DB::table('researcher_submission_file')
            ->where('item_id', $itemId)->get()->toArray();
        foreach ($files as $f) {
            if (file_exists($f->stored_path)) {
                @unlink($f->stored_path);
            }
        }

        // Move child items to root level (detach from parent)
        DB::table('researcher_submission_item')
            ->where('parent_item_id', $itemId)
            ->update(['parent_item_id' => null]);

        DB::table('researcher_submission_item')->where('id', $itemId)->delete();
        $this->recalculateTotals($item->submission_id);
    }

    /**
     * Get a single item by ID.
     */
    public function getItem(int $itemId): ?object
    {
        return DB::table('researcher_submission_item')->where('id', $itemId)->first();
    }

    // ─── FILES ──────────────────────────────────────────────────

    /**
     * Add a file to an item from an uploaded file array ($_FILES).
     */
    public function addFile(int $itemId, array $uploadedFile): ?int
    {
        $item = DB::table('researcher_submission_item')->where('id', $itemId)->first();
        if (!$item) {
            return null;
        }

        $stagingDir = $this->getStagingDir($item->submission_id);
        $originalName = basename($uploadedFile['name']);
        $storedName = uniqid('rsf_') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $storedPath = $stagingDir . '/' . $storedName;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $storedPath)) {
            return null;
        }

        $checksum = hash_file('sha256', $storedPath);

        $maxSort = DB::table('researcher_submission_file')
            ->where('item_id', $itemId)->max('sort_order') ?? 0;

        $id = (int) DB::table('researcher_submission_file')->insertGetId([
            'item_id'       => $itemId,
            'original_name' => $originalName,
            'stored_name'   => $storedName,
            'stored_path'   => $storedPath,
            'mime_type'     => $uploadedFile['type'] ?? mime_content_type($storedPath),
            'file_size'     => filesize($storedPath),
            'checksum'      => $checksum,
            'sort_order'    => $maxSort + 1,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->recalculateTotals($item->submission_id);

        return $id;
    }

    /**
     * Add a file from raw data (for exchange import with embedded images).
     */
    public function addFileFromData(int $itemId, string $filename, string $data, string $mimeType, ?string $caption = null): ?int
    {
        $item = DB::table('researcher_submission_item')->where('id', $itemId)->first();
        if (!$item) {
            return null;
        }

        $stagingDir = $this->getStagingDir($item->submission_id);
        $storedName = uniqid('rsf_') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $storedPath = $stagingDir . '/' . $storedName;

        file_put_contents($storedPath, $data);
        $checksum = hash_file('sha256', $storedPath);

        $maxSort = DB::table('researcher_submission_file')
            ->where('item_id', $itemId)->max('sort_order') ?? 0;

        $id = (int) DB::table('researcher_submission_file')->insertGetId([
            'item_id'       => $itemId,
            'original_name' => $filename,
            'stored_name'   => $storedName,
            'stored_path'   => $storedPath,
            'mime_type'     => $mimeType,
            'file_size'     => strlen($data),
            'checksum'      => $checksum,
            'caption'       => $caption,
            'sort_order'    => $maxSort + 1,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->recalculateTotals($item->submission_id);

        return $id;
    }

    /**
     * Delete a file.
     */
    public function deleteFile(int $fileId): void
    {
        $file = DB::table('researcher_submission_file')->where('id', $fileId)->first();
        if (!$file) {
            return;
        }

        if (file_exists($file->stored_path)) {
            @unlink($file->stored_path);
        }

        $itemId = $file->item_id;
        DB::table('researcher_submission_file')->where('id', $fileId)->delete();

        $item = DB::table('researcher_submission_item')->where('id', $itemId)->first();
        if ($item) {
            $this->recalculateTotals($item->submission_id);
        }
    }

    /**
     * Get a file by ID.
     */
    public function getFile(int $fileId): ?object
    {
        return DB::table('researcher_submission_file')->where('id', $fileId)->first();
    }

    // ─── WORKFLOW ───────────────────────────────────────────────

    /**
     * Submit a draft for review — starts the workflow.
     */
    public function submitForReview(int $id, int $userId): bool
    {
        $sub = DB::table('researcher_submission')->where('id', $id)->first();
        if (!$sub || !in_array($sub->status, ['draft', 'returned'])) {
            return false;
        }

        // Validate: must have at least one description item
        $descCount = DB::table('researcher_submission_item')
            ->where('submission_id', $id)
            ->where('item_type', 'description')
            ->count();

        if ($descCount === 0) {
            return false;
        }

        $taskId = null;

        // Try to start workflow if ahgWorkflowPlugin is available
        try {
            $workflowServiceFile = \sfConfig::get('sf_plugins_dir')
                . '/ahgWorkflowPlugin/lib/Services/WorkflowService.php';

            if (file_exists($workflowServiceFile)) {
                require_once $workflowServiceFile;
                $workflowService = new \AhgWorkflowPlugin\Services\WorkflowService();
                $taskId = $workflowService->startWorkflow($id, $userId, 'researcher_submission', 100);
            }
        } catch (\Exception $e) {
            // Workflow plugin not available — continue without it
        }

        DB::table('researcher_submission')->where('id', $id)->update([
            'status'          => 'submitted',
            'workflow_task_id' => $taskId,
            'submitted_at'    => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        // Add review log entry
        DB::table('researcher_submission_review')->insert([
            'submission_id' => $id,
            'reviewer_id'   => $userId,
            'action'        => 'comment',
            'comment'       => 'Submitted for review.',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * Resubmit a returned submission.
     */
    public function resubmit(int $id, int $userId): bool
    {
        return $this->submitForReview($id, $userId);
    }

    /**
     * Check if the workflow for a submission is complete (all steps approved).
     */
    public function isWorkflowComplete(int $submissionId): bool
    {
        // Check ahg_workflow_history for 'completed' action
        try {
            $completed = DB::table('ahg_workflow_history')
                ->where('object_id', $submissionId)
                ->where('object_type', 'researcher_submission')
                ->where('action', 'completed')
                ->exists();

            if ($completed) {
                return true;
            }
        } catch (\Exception $e) {
            // Table may not exist if workflow plugin not installed
        }

        // Fallback: check if status was set to approved manually
        $sub = DB::table('researcher_submission')->where('id', $submissionId)->first();

        return $sub && $sub->status === 'approved';
    }

    /**
     * Check if a submission was returned by reviewer.
     */
    public function isReturned(int $submissionId): bool
    {
        try {
            return DB::table('ahg_workflow_task')
                ->where('object_id', $submissionId)
                ->where('object_type', 'researcher_submission')
                ->where('status', 'returned')
                ->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Mark submission as approved.
     */
    public function markApproved(int $submissionId): void
    {
        DB::table('researcher_submission')->where('id', $submissionId)->update([
            'status'     => 'approved',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark submission as published.
     */
    public function markPublished(int $submissionId): void
    {
        DB::table('researcher_submission')->where('id', $submissionId)->update([
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    // ─── DASHBOARD ──────────────────────────────────────────────

    /**
     * Get dashboard statistics for a user.
     */
    public function getDashboardStats(?int $userId = null): array
    {
        $q = DB::table('researcher_submission');
        if ($userId) {
            $q->where('user_id', $userId);
        }

        $all = (clone $q)->count();
        $draft = (clone $q)->where('status', 'draft')->count();
        $submitted = (clone $q)->where('status', 'submitted')->count();
        $underReview = (clone $q)->where('status', 'under_review')->count();
        $approved = (clone $q)->where('status', 'approved')->count();
        $published = (clone $q)->where('status', 'published')->count();
        $returned = (clone $q)->where('status', 'returned')->count();
        $rejected = (clone $q)->where('status', 'rejected')->count();

        return [
            'total'        => $all,
            'draft'        => $draft,
            'submitted'    => $submitted,
            'under_review' => $underReview,
            'approved'     => $approved,
            'published'    => $published,
            'returned'     => $returned,
            'rejected'     => $rejected,
            'pending'      => $submitted + $underReview,
        ];
    }

    // ─── HELPERS ────────────────────────────────────────────────

    /**
     * Resolve researcher_id from user_id (if ahgResearchPlugin is installed).
     */
    protected function resolveResearcherId(int $userId): ?int
    {
        try {
            $researcher = DB::table('research_researcher')
                ->where('user_id', $userId)
                ->first();

            return $researcher ? (int) $researcher->id : null;
        } catch (\Exception $e) {
            // Table doesn't exist — ahgResearchPlugin not installed
            return null;
        }
    }

    /**
     * Get list of repositories for dropdowns.
     */
    public function getRepositories(string $culture = 'en'): array
    {
        return DB::table('repository as r')
            ->join('actor_i18n as ai', function ($j) use ($culture) {
                $j->on('r.id', '=', 'ai.id')->where('ai.culture', '=', $culture);
            })
            ->select('r.id', 'ai.authorized_form_of_name as name')
            ->orderBy('ai.authorized_form_of_name')
            ->get()
            ->toArray();
    }

    /**
     * Recursively remove a directory.
     */
    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
