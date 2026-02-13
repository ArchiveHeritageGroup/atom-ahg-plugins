<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * JournalService - Research Journal/Logbook Management
 *
 * Handles CRUD for journal entries, auto-logging from research activities,
 * calendar views, and time tracking by project.
 *
 * @package ahgResearchPlugin
 * @version 2.1.0
 */
class JournalService
{
    /**
     * Create a manual journal entry.
     */
    public function createEntry(int $researcherId, array $data): int
    {
        return DB::table('research_journal_entry')->insertGetId([
            'researcher_id' => $researcherId,
            'project_id' => $data['project_id'] ?? null,
            'entry_date' => $data['entry_date'] ?? date('Y-m-d'),
            'title' => $data['title'] ?? null,
            'content' => $data['content'],
            'content_format' => $data['content_format'] ?? 'html',
            'entry_type' => $data['entry_type'] ?? 'manual',
            'time_spent_minutes' => $data['time_spent_minutes'] ?? null,
            'tags' => $data['tags'] ?? null,
            'is_private' => $data['is_private'] ?? 1,
            'related_entity_type' => $data['related_entity_type'] ?? null,
            'related_entity_id' => $data['related_entity_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Create an auto-logged journal entry from a research activity.
     */
    public function createAutoEntry(int $researcherId, string $entryType, string $title, string $content, ?int $projectId = null, ?string $relatedType = null, ?int $relatedId = null): int
    {
        return $this->createEntry($researcherId, [
            'project_id' => $projectId,
            'entry_date' => date('Y-m-d'),
            'title' => $title,
            'content' => $content,
            'content_format' => 'text',
            'entry_type' => $entryType,
            'related_entity_type' => $relatedType,
            'related_entity_id' => $relatedId,
        ]);
    }

    /**
     * Get a single journal entry.
     */
    public function getEntry(int $id): ?object
    {
        return DB::table('research_journal_entry as j')
            ->leftJoin('research_project as p', 'j.project_id', '=', 'p.id')
            ->where('j.id', $id)
            ->select('j.*', 'p.title as project_title')
            ->first();
    }

    /**
     * Update a journal entry.
     */
    public function updateEntry(int $id, int $researcherId, array $data): bool
    {
        $update = [];
        foreach (['title', 'content', 'content_format', 'entry_date', 'project_id', 'time_spent_minutes', 'tags', 'is_private'] as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }
        $update['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('research_journal_entry')
            ->where('id', $id)
            ->where('researcher_id', $researcherId)
            ->update($update) > 0;
    }

    /**
     * Delete a journal entry.
     */
    public function deleteEntry(int $id, int $researcherId): bool
    {
        return DB::table('research_journal_entry')
            ->where('id', $id)
            ->where('researcher_id', $researcherId)
            ->delete() > 0;
    }

    /**
     * Get entries for a researcher with optional filters.
     */
    public function getEntries(int $researcherId, array $filters = []): array
    {
        $query = DB::table('research_journal_entry as j')
            ->leftJoin('research_project as p', 'j.project_id', '=', 'p.id')
            ->where('j.researcher_id', $researcherId)
            ->select('j.*', 'p.title as project_title');

        if (!empty($filters['project_id'])) {
            $query->where('j.project_id', $filters['project_id']);
        }
        if (!empty($filters['entry_type'])) {
            $query->where('j.entry_type', $filters['entry_type']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('j.entry_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('j.entry_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['search'])) {
            $query->whereRaw('MATCH(j.title, j.content) AGAINST(? IN BOOLEAN MODE)', [$filters['search']]);
        }

        $query->orderBy('j.entry_date', 'desc')->orderBy('j.created_at', 'desc');

        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        return $query->get()->toArray();
    }

    /**
     * Get entries grouped by month for calendar view.
     */
    public function getEntriesByMonth(int $researcherId, string $yearMonth): array
    {
        return DB::table('research_journal_entry')
            ->where('researcher_id', $researcherId)
            ->whereRaw("DATE_FORMAT(entry_date, '%Y-%m') = ?", [$yearMonth])
            ->orderBy('entry_date')
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }

    /**
     * Get time spent per project for a researcher.
     */
    public function getTimeSpentByProject(int $researcherId): array
    {
        return DB::table('research_journal_entry as j')
            ->leftJoin('research_project as p', 'j.project_id', '=', 'p.id')
            ->where('j.researcher_id', $researcherId)
            ->whereNotNull('j.time_spent_minutes')
            ->groupBy('j.project_id', 'p.title')
            ->select(
                'j.project_id',
                DB::raw("COALESCE(p.title, 'No Project') as project_title"),
                DB::raw('SUM(j.time_spent_minutes) as total_minutes'),
                DB::raw('COUNT(*) as entry_count')
            )
            ->orderBy('total_minutes', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get dates with entries for calendar dots.
     */
    public function getEntryDates(int $researcherId, string $yearMonth): array
    {
        return DB::table('research_journal_entry')
            ->where('researcher_id', $researcherId)
            ->whereRaw("DATE_FORMAT(entry_date, '%Y-%m') = ?", [$yearMonth])
            ->groupBy('entry_date')
            ->select('entry_date', DB::raw('COUNT(*) as count'))
            ->pluck('count', 'entry_date')
            ->toArray();
    }
}
