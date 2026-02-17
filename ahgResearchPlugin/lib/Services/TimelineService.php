<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * TimelineService - Research Timeline Management
 *
 * Manages timeline events for research projects, enabling researchers
 * to plot chronological events, auto-populate from collection items,
 * and visualize temporal relationships across archival records.
 *
 * Table: research_timeline_event
 *
 * @package ahgResearchPlugin
 * @version 2.0.0
 */
class TimelineService
{
    /**
     * Valid date type values.
     */
    private const VALID_DATE_TYPES = ['event', 'creation', 'accession', 'publication'];

    // =========================================================================
    // EVENT MANAGEMENT
    // =========================================================================

    /**
     * Create a new timeline event.
     *
     * @param int $projectId The research project ID
     * @param int $researcherId The researcher creating the event
     * @param array $data Keys: label, description, date_start, date_end,
     *                     date_type, source_type, source_id, position, color
     * @return int The new timeline event ID
     */
    public function createEvent(int $projectId, int $researcherId, array $data): int
    {
        $eventId = DB::table('research_timeline_event')->insertGetId([
            'project_id' => $projectId,
            'researcher_id' => $researcherId,
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'date_start' => $data['date_start'],
            'date_end' => $data['date_end'] ?? null,
            'date_type' => $data['date_type'] ?? 'event',
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'position' => $data['position'] ?? 0,
            'color' => $data['color'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logEvent(
            $researcherId,
            $projectId,
            'timeline_event_created',
            'timeline_event',
            $eventId,
            mb_substr($data['label'], 0, 200)
        );

        return $eventId;
    }

    /**
     * Get all timeline events for a project, ordered chronologically.
     *
     * @param int $projectId The project ID
     * @return array List of timeline events with researcher names
     */
    public function getProjectTimeline(int $projectId): array
    {
        return DB::table('research_timeline_event as te')
            ->leftJoin('research_researcher as r', 'te.researcher_id', '=', 'r.id')
            ->where('te.project_id', $projectId)
            ->select(
                'te.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name'
            )
            ->orderBy('te.date_start', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Update a timeline event.
     *
     * @param int $id The timeline event ID
     * @param array $data Fields to update: label, description, date_start,
     *                     date_end, date_type, source_type, source_id,
     *                     position, color
     * @return bool Success status
     */
    public function updateEvent(int $id, array $data): bool
    {
        $allowed = [
            'label', 'description', 'date_start', 'date_end',
            'date_type', 'source_type', 'source_id', 'position', 'color',
        ];

        $updateData = array_intersect_key($data, array_flip($allowed));

        if (empty($updateData)) {
            return false;
        }

        return DB::table('research_timeline_event')
            ->where('id', $id)
            ->update($updateData) >= 0;
    }

    /**
     * Delete a timeline event.
     *
     * @param int $id The timeline event ID
     * @return bool Success status
     */
    public function deleteEvent(int $id): bool
    {
        return DB::table('research_timeline_event')
            ->where('id', $id)
            ->delete() > 0;
    }

    /**
     * Auto-populate timeline events from a collection's items.
     *
     * Extracts dates from the AtoM event table for each item in the
     * collection and creates corresponding timeline events. Uses
     * information_object_i18n for labels.
     *
     * @param int $projectId The research project ID
     * @param int $collectionId The collection to extract dates from
     * @return int Number of timeline events created
     */
    public function autoPopulateFromCollection(int $projectId, int $collectionId): int
    {
        // Get the collection to find its researcher_id
        $collection = DB::table('research_collection')
            ->where('id', $collectionId)
            ->first();

        if (!$collection) {
            return 0;
        }

        $researcherId = $collection->researcher_id;

        // Get all collection items joined with information_object titles
        $items = DB::table('research_collection_item as ci')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('ci.object_id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', 'en');
            })
            ->where('ci.collection_id', $collectionId)
            ->select(
                'ci.object_id',
                'i18n.title'
            )
            ->get();

        $created = 0;

        foreach ($items as $item) {
            // Query the AtoM event table for dates associated with this
            // information object. The event table stores dates, date types,
            // and related information for archival descriptions.
            $events = DB::table('event as e')
                ->leftJoin('event_i18n as ei', function ($join) {
                    $join->on('e.id', '=', 'ei.id')
                        ->where('ei.culture', '=', 'en');
                })
                ->where('e.information_object_id', $item->object_id)
                ->whereNotNull('e.start_date')
                ->select(
                    'e.id as event_id',
                    'e.start_date',
                    'e.end_date',
                    'e.type_id',
                    'ei.date as date_display',
                    'ei.name as event_name'
                )
                ->get();

            foreach ($events as $event) {
                // Determine the date_type from the AtoM event type_id
                $dateType = $this->mapEventTypeToDateType($event->type_id);

                // Build a label from the item title and event context
                $label = $item->title ?? 'Item #' . $item->object_id;
                if (!empty($event->event_name)) {
                    $label .= ' - ' . $event->event_name;
                }

                // Parse start_date to DATE format (AtoM stores as string)
                $dateStart = $this->parseAtomDate($event->start_date);
                if (!$dateStart) {
                    continue;
                }

                $dateEnd = $this->parseAtomDate($event->end_date);

                // Avoid duplicate events for the same source
                $exists = DB::table('research_timeline_event')
                    ->where('project_id', $projectId)
                    ->where('source_type', 'information_object')
                    ->where('source_id', $item->object_id)
                    ->where('date_start', $dateStart)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('research_timeline_event')->insert([
                    'project_id' => $projectId,
                    'researcher_id' => $researcherId,
                    'label' => mb_substr($label, 0, 500),
                    'description' => $event->date_display ?? null,
                    'date_start' => $dateStart,
                    'date_end' => $dateEnd,
                    'date_type' => $dateType,
                    'source_type' => 'information_object',
                    'source_id' => $item->object_id,
                    'position' => $created,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $created++;
            }
        }

        return $created;
    }

    /**
     * Get a single timeline event by ID.
     *
     * @param int $id The timeline event ID
     * @return object|null The event or null
     */
    public function getEvent(int $id): ?object
    {
        return DB::table('research_timeline_event as te')
            ->leftJoin('research_researcher as r', 'te.researcher_id', '=', 'r.id')
            ->where('te.id', $id)
            ->select(
                'te.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name',
                'r.email as researcher_email'
            )
            ->first();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Map AtoM event type_id to timeline date_type.
     *
     * AtoM event type_id values (from QubitTerm constants):
     * - 111 = Creation
     * - 112 = Accumulation
     * - 113 = Contribution
     * - Other = generic event
     *
     * @param int|null $typeId The AtoM event type ID
     * @return string The mapped date_type
     */
    private function mapEventTypeToDateType(?int $typeId): string
    {
        return match ($typeId) {
            111 => 'creation',
            // QubitTerm::ACCUMULATION_ID, CONTRIBUTION_ID are accession-like
            112, 113 => 'accession',
            // Publication events
            114 => 'publication',
            default => 'event',
        };
    }

    /**
     * Parse an AtoM date string to Y-m-d format.
     *
     * AtoM stores dates in various formats. This method attempts
     * to normalize them to Y-m-d.
     *
     * @param string|null $dateStr The date string from AtoM
     * @return string|null The parsed date or null
     */
    private function parseAtomDate(?string $dateStr): ?string
    {
        if (empty($dateStr)) {
            return null;
        }

        // Already in Y-m-d format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return $dateStr;
        }

        // Year only (e.g., "1920")
        if (preg_match('/^\d{4}$/', $dateStr)) {
            return $dateStr . '-01-01';
        }

        // Year-month (e.g., "1920-05")
        if (preg_match('/^\d{4}-\d{2}$/', $dateStr)) {
            return $dateStr . '-01';
        }

        // Try PHP date parsing as fallback
        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    // =========================================================================
    // ACTIVITY LOGGING
    // =========================================================================

    /**
     * Log a canonical event to the research activity log.
     *
     * @param int $researcherId The researcher performing the action
     * @param int|null $projectId The project ID
     * @param string $type The activity type
     * @param string $entityType The entity type
     * @param int $entityId The entity ID
     * @param string|null $title Optional entity title for display
     */
    private function logEvent(int $researcherId, ?int $projectId, string $type, string $entityType, int $entityId, ?string $title = null): void
    {
        DB::table('research_activity_log')->insert([
            'researcher_id' => $researcherId,
            'project_id' => $projectId,
            'activity_type' => $type,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_title' => $title,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
