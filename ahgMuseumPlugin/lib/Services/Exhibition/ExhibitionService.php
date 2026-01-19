<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Exhibition;

use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Exhibition Management Service.
 *
 * Comprehensive exhibition management including:
 * - Exhibition lifecycle (planning to closing)
 * - Object selection and placement
 * - Storyline/narrative creation
 * - Installation tracking
 * - Event scheduling
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ExhibitionService
{
    /** Exhibition types */
    public const TYPES = [
        'permanent' => 'Permanent Exhibition',
        'temporary' => 'Temporary Exhibition',
        'traveling' => 'Traveling Exhibition',
        'online' => 'Online/Virtual Exhibition',
        'pop_up' => 'Pop-up Exhibition',
    ];

    /** Exhibition statuses */
    public const STATUSES = [
        'concept' => ['label' => 'Concept', 'color' => '#9e9e9e', 'order' => 1],
        'planning' => ['label' => 'Planning', 'color' => '#2196f3', 'order' => 2],
        'preparation' => ['label' => 'Preparation', 'color' => '#ff9800', 'order' => 3],
        'installation' => ['label' => 'Installation', 'color' => '#9c27b0', 'order' => 4],
        'open' => ['label' => 'Open', 'color' => '#4caf50', 'order' => 5],
        'closing' => ['label' => 'Closing', 'color' => '#ff5722', 'order' => 6],
        'closed' => ['label' => 'Closed', 'color' => '#795548', 'order' => 7],
        'archived' => ['label' => 'Archived', 'color' => '#607d8b', 'order' => 8],
        'canceled' => ['label' => 'Canceled', 'color' => '#f44336', 'order' => 9],
    ];

    /** Object status in exhibition */
    public const OBJECT_STATUSES = [
        'proposed' => 'Proposed',
        'confirmed' => 'Confirmed',
        'on_loan_request' => 'Loan Requested',
        'installed' => 'Installed',
        'removed' => 'Removed',
        'returned' => 'Returned',
    ];

    /** Valid status transitions */
    private const STATUS_TRANSITIONS = [
        'concept' => ['planning', 'canceled'],
        'planning' => ['concept', 'preparation', 'canceled'],
        'preparation' => ['planning', 'installation', 'canceled'],
        'installation' => ['preparation', 'open', 'canceled'],
        'open' => ['closing'],
        'closing' => ['open', 'closed'],
        'closed' => ['archived'],
        'archived' => [],
        'canceled' => ['concept'],
    ];

    private ConnectionInterface $db;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $db,
        ?LoggerInterface $logger = null
    ) {
        $this->db = $db;
        $this->logger = $logger ?? new NullLogger();
    }

    // =========================================================================
    // Exhibition CRUD
    // =========================================================================

    /**
     * Create a new exhibition.
     *
     * @param array $data   Exhibition data
     * @param int   $userId Creating user ID
     *
     * @return int Exhibition ID
     */
    public function create(array $data, int $userId): int
    {
        $now = date('Y-m-d H:i:s');

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        $exhibitionId = $this->db->table('exhibition')->insertGetId([
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'theme' => $data['theme'] ?? null,
            'exhibition_type' => $data['exhibition_type'] ?? 'temporary',
            'status' => 'concept',
            'planning_start_date' => $data['planning_start_date'] ?? null,
            'opening_date' => $data['opening_date'] ?? null,
            'closing_date' => $data['closing_date'] ?? null,
            'venue_id' => $data['venue_id'] ?? null,
            'venue_name' => $data['venue_name'] ?? null,
            'is_external_venue' => $data['is_external_venue'] ?? false,
            'curator_id' => $data['curator_id'] ?? null,
            'curator_name' => $data['curator_name'] ?? null,
            'organized_by' => $data['organized_by'] ?? null,
            'budget_amount' => $data['budget_amount'] ?? null,
            'budget_currency' => $data['budget_currency'] ?? 'ZAR',
            'project_code' => $data['project_code'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Log status history
        $this->logStatusChange($exhibitionId, null, 'concept', $userId, 'Exhibition created');

        $this->logger->info('Exhibition created', [
            'exhibition_id' => $exhibitionId,
            'title' => $data['title'],
            'type' => $data['exhibition_type'] ?? 'temporary',
        ]);

        return $exhibitionId;
    }

    /**
     * Get exhibition by ID.
     *
     * @param int  $exhibitionId Exhibition ID
     * @param bool $includeDetails Include objects, sections, etc.
     *
     * @return array|null Exhibition data
     */
    public function get(int $exhibitionId, bool $includeDetails = false): ?array
    {
        $exhibition = $this->db->table('exhibition')
            ->where('id', $exhibitionId)
            ->first();

        if (!$exhibition) {
            return null;
        }

        $data = (array) $exhibition;
        $data['status_info'] = self::STATUSES[$data['status']] ?? null;
        $data['type_label'] = self::TYPES[$data['exhibition_type']] ?? $data['exhibition_type'];

        // Calculate days until opening/since closing
        $data['timing'] = $this->calculateTiming($data);

        if ($includeDetails) {
            $data['sections'] = $this->getSections($exhibitionId);
            $data['objects'] = $this->getObjects($exhibitionId);
            $data['storylines'] = $this->getStorylines($exhibitionId);
            $data['events'] = $this->getEvents($exhibitionId);
            $data['checklists'] = $this->getChecklists($exhibitionId);
            $data['statistics'] = $this->getExhibitionStatistics($exhibitionId);
        }

        return $data;
    }

    /**
     * Get exhibition by slug.
     */
    public function getBySlug(string $slug): ?array
    {
        $exhibition = $this->db->table('exhibition')
            ->where('slug', $slug)
            ->first();

        if (!$exhibition) {
            return null;
        }

        return $this->get($exhibition->id, true);
    }

    /**
     * Update exhibition.
     *
     * @param int   $exhibitionId Exhibition ID
     * @param array $data         Update data
     * @param int   $userId       User ID
     *
     * @return bool Success
     */
    public function update(int $exhibitionId, array $data, int $userId): bool
    {
        $data['updated_by'] = $userId;
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Don't update protected fields
        unset($data['id'], $data['created_by'], $data['created_at']);

        return $this->db->table('exhibition')
            ->where('id', $exhibitionId)
            ->update($data) > 0;
    }

    /**
     * Transition exhibition status.
     *
     * @param int         $exhibitionId Exhibition ID
     * @param string      $newStatus    New status
     * @param int         $userId       User ID
     * @param string|null $reason       Reason for transition
     *
     * @return bool Success
     *
     * @throws \InvalidArgumentException If transition is not valid
     */
    public function transitionStatus(
        int $exhibitionId,
        string $newStatus,
        int $userId,
        ?string $reason = null
    ): bool {
        $exhibition = $this->get($exhibitionId);
        if (!$exhibition) {
            throw new \InvalidArgumentException("Exhibition not found: {$exhibitionId}");
        }

        $currentStatus = $exhibition['status'];

        // Check if transition is valid
        $validTransitions = self::STATUS_TRANSITIONS[$currentStatus] ?? [];
        if (!in_array($newStatus, $validTransitions)) {
            throw new \InvalidArgumentException(
                "Invalid transition from '{$currentStatus}' to '{$newStatus}'"
            );
        }

        $updated = $this->db->table('exhibition')
            ->where('id', $exhibitionId)
            ->update([
                'status' => $newStatus,
                'updated_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        if ($updated) {
            $this->logStatusChange($exhibitionId, $currentStatus, $newStatus, $userId, $reason);

            $this->logger->info('Exhibition status changed', [
                'exhibition_id' => $exhibitionId,
                'from' => $currentStatus,
                'to' => $newStatus,
            ]);
        }

        return $updated > 0;
    }

    /**
     * Search exhibitions.
     *
     * @param array $filters Search filters
     * @param int   $limit   Maximum results
     * @param int   $offset  Result offset
     *
     * @return array Search results
     */
    public function search(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = $this->db->table('exhibition');

        // Apply filters
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['exhibition_type'])) {
            $query->where('exhibition_type', $filters['exhibition_type']);
        }

        if (!empty($filters['venue_id'])) {
            $query->where('venue_id', $filters['venue_id']);
        }

        if (!empty($filters['curator_id'])) {
            $query->where('curator_id', $filters['curator_id']);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('opening_date', $filters['year']);
        }

        if (!empty($filters['current'])) {
            // Currently open exhibitions
            $today = date('Y-m-d');
            $query->where('status', 'open')
                ->where('opening_date', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('closing_date')
                        ->orWhere('closing_date', '>=', $today);
                });
        }

        if (!empty($filters['upcoming'])) {
            $query->where('opening_date', '>', date('Y-m-d'))
                ->whereIn('status', ['concept', 'planning', 'preparation', 'installation']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('theme', 'LIKE', "%{$search}%")
                    ->orWhere('curator_name', 'LIKE', "%{$search}%");
            });
        }

        $total = $query->count();

        $results = $query
            ->orderByDesc('opening_date')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function ($row) {
                $data = (array) $row;
                $data['status_info'] = self::STATUSES[$data['status']] ?? null;
                $data['type_label'] = self::TYPES[$data['exhibition_type']] ?? $data['exhibition_type'];

                return $data;
            })
            ->all();

        return [
            'total' => $total,
            'results' => $results,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    // =========================================================================
    // Sections
    // =========================================================================

    /**
     * Add section to exhibition.
     *
     * @param int   $exhibitionId Exhibition ID
     * @param array $data         Section data
     *
     * @return int Section ID
     */
    public function addSection(int $exhibitionId, array $data): int
    {
        $sequence = $this->db->table('exhibition_section')
            ->where('exhibition_id', $exhibitionId)
            ->max('sequence_order') ?? 0;

        return $this->db->table('exhibition_section')->insertGetId([
            'exhibition_id' => $exhibitionId,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'description' => $data['description'] ?? null,
            'narrative' => $data['narrative'] ?? null,
            'section_type' => $data['section_type'] ?? 'gallery',
            'sequence_order' => $data['sequence_order'] ?? $sequence + 1,
            'gallery_name' => $data['gallery_name'] ?? null,
            'floor_level' => $data['floor_level'] ?? null,
            'square_meters' => $data['square_meters'] ?? null,
            'theme' => $data['theme'] ?? null,
            'color_scheme' => $data['color_scheme'] ?? null,
            'target_temperature_min' => $data['target_temperature_min'] ?? null,
            'target_temperature_max' => $data['target_temperature_max'] ?? null,
            'target_humidity_min' => $data['target_humidity_min'] ?? null,
            'target_humidity_max' => $data['target_humidity_max'] ?? null,
            'max_lux_level' => $data['max_lux_level'] ?? null,
            'has_audio_guide' => $data['has_audio_guide'] ?? false,
            'audio_guide_number' => $data['audio_guide_number'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get sections for exhibition.
     */
    public function getSections(int $exhibitionId): array
    {
        $sections = $this->db->table('exhibition_section')
            ->where('exhibition_id', $exhibitionId)
            ->orderBy('sequence_order')
            ->get()
            ->map(fn ($s) => (array) $s)
            ->all();

        // Add object count per section
        foreach ($sections as &$section) {
            $section['object_count'] = $this->db->table('exhibition_object')
                ->where('section_id', $section['id'])
                ->count();
        }

        return $sections;
    }

    /**
     * Update section order.
     */
    public function reorderSections(int $exhibitionId, array $sectionOrder): bool
    {
        foreach ($sectionOrder as $order => $sectionId) {
            $this->db->table('exhibition_section')
                ->where('id', $sectionId)
                ->where('exhibition_id', $exhibitionId)
                ->update(['sequence_order' => $order + 1]);
        }

        return true;
    }

    /**
     * Update a section.
     */
    public function updateSection(int $sectionId, array $data): bool
    {
        $updateData = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['gallery_name'])) {
            $updateData['gallery_name'] = $data['gallery_name'];
        }
        if (isset($data['theme'])) {
            $updateData['theme'] = $data['theme'];
        }
        if (isset($data['display_order'])) {
            $updateData['sequence_order'] = (int) $data['display_order'];
        }

        return $this->db->table('exhibition_section')
            ->where('id', $sectionId)
            ->update($updateData) > 0;
    }

    /**
     * Delete a section.
     */
    public function deleteSection(int $sectionId): bool
    {
        // First unassign objects from this section
        $this->db->table('exhibition_object')
            ->where('section_id', $sectionId)
            ->update(['section_id' => null]);

        // Then delete the section
        return $this->db->table('exhibition_section')
            ->where('id', $sectionId)
            ->delete() > 0;
    }

    // =========================================================================
    // Objects
    // =========================================================================

    /**
     * Add object to exhibition.
     *
     * @param int   $exhibitionId Exhibition ID
     * @param int   $objectId     Information object ID
     * @param array $data         Placement data
     *
     * @return int Exhibition object ID
     */
    public function addObject(int $exhibitionId, int $objectId, array $data = []): int
    {
        // Check availability
        $conflicts = $this->checkObjectAvailability($objectId, $exhibitionId);
        if (!empty($conflicts)) {
            $this->logger->warning('Object may have scheduling conflicts', [
                'object_id' => $objectId,
                'exhibition_id' => $exhibitionId,
                'conflicts' => count($conflicts),
            ]);
        }

        $sequence = $this->db->table('exhibition_object')
            ->where('exhibition_id', $exhibitionId)
            ->where('section_id', $data['section_id'] ?? null)
            ->max('sequence_order') ?? 0;

        return $this->db->table('exhibition_object')->insertGetId([
            'exhibition_id' => $exhibitionId,
            'section_id' => $data['section_id'] ?? null,
            'information_object_id' => $objectId,
            'sequence_order' => $data['sequence_order'] ?? $sequence + 1,
            'display_position' => $data['display_position'] ?? null,
            'status' => 'proposed',
            'requires_loan' => $data['requires_loan'] ?? false,
            'lender_institution' => $data['lender_institution'] ?? null,
            'display_case_required' => $data['display_case_required'] ?? false,
            'mount_required' => $data['mount_required'] ?? false,
            'mount_description' => $data['mount_description'] ?? null,
            'special_lighting' => $data['special_lighting'] ?? false,
            'lighting_notes' => $data['lighting_notes'] ?? null,
            'security_level' => $data['security_level'] ?? 'standard',
            'climate_controlled' => $data['climate_controlled'] ?? false,
            'max_lux_level' => $data['max_lux_level'] ?? null,
            'rotation_required' => $data['rotation_required'] ?? false,
            'max_display_days' => $data['max_display_days'] ?? null,
            'insurance_value' => $data['insurance_value'] ?? null,
            'label_text' => $data['label_text'] ?? null,
            'label_credits' => $data['label_credits'] ?? null,
            'extended_label' => $data['extended_label'] ?? null,
            'installation_notes' => $data['installation_notes'] ?? null,
            'handling_notes' => $data['handling_notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get objects in exhibition with details.
     */
    public function getObjects(int $exhibitionId, ?int $sectionId = null): array
    {
        $query = $this->db->table('exhibition_object as eo')
            ->leftJoin('information_object as io', 'io.id', '=', 'eo.information_object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('ioi.id', '=', 'io.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('exhibition_section as es', 'es.id', '=', 'eo.section_id')
            ->leftJoin('digital_object as do', 'do.object_id', '=', 'io.id')
            ->leftJoin('slug', 'slug.object_id', '=', 'io.id')
            ->where('eo.exhibition_id', $exhibitionId);

        if ($sectionId !== null) {
            $query->where('eo.section_id', $sectionId);
        }

        return $query
            ->select(
                'eo.*',
                'slug.slug as object_slug',
                'io.identifier',
                'ioi.title as object_title',
                'es.title as section_title',
                'do.path as thumbnail_path',
                'do.name as thumbnail_name'
            )
            ->orderBy('eo.section_id')
            ->orderBy('eo.sequence_order')
            ->get()
            ->map(function ($row) {
                $data = (array) $row;
                $data['status_label'] = self::OBJECT_STATUSES[$data['status']] ?? $data['status'];
                if (!empty($data['thumbnail_path']) && !empty($data['thumbnail_name'])) {
                    // Path already contains /uploads/, just append filename
                    $data['thumbnail_url'] = rtrim($data['thumbnail_path'], '/').'/'.ltrim($data['thumbnail_name'], '/');
                }

                return $data;
            })
            ->all();
    }

    /**
     * Update object placement in exhibition.
     */
    public function updateObject(int $exhibitionObjectId, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        unset($data['id'], $data['exhibition_id'], $data['information_object_id'], $data['created_at']);

        return $this->db->table('exhibition_object')
            ->where('id', $exhibitionObjectId)
            ->update($data) > 0;
    }

    /**
     * Update object status (confirm, install, remove, etc.)
     */
    public function updateObjectStatus(
        int $exhibitionObjectId,
        string $status,
        int $userId,
        ?string $notes = null
    ): bool {
        $update = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ('installed' === $status) {
            $update['installed_by'] = $userId;
            $update['installed_at'] = date('Y-m-d H:i:s');
        } elseif (in_array($status, ['removed', 'returned'])) {
            $update['removed_by'] = $userId;
            $update['removed_at'] = date('Y-m-d H:i:s');
        }

        if ($notes) {
            $current = $this->db->table('exhibition_object')
                ->where('id', $exhibitionObjectId)
                ->value('installation_notes');
            $update['installation_notes'] = $current
                ? $current."\n\n[".date('Y-m-d')."] ".$notes
                : "[".$date('Y-m-d')."] ".$notes;
        }

        return $this->db->table('exhibition_object')
            ->where('id', $exhibitionObjectId)
            ->update($update) > 0;
    }

    /**
     * Remove object from exhibition.
     */
    public function removeObject(int $exhibitionObjectId): bool
    {
        return $this->db->table('exhibition_object')
            ->where('id', $exhibitionObjectId)
            ->delete() > 0;
    }

    /**
     * Reorder exhibition objects.
     *
     * @param array $order Array of ['id' => objectId, 'sequence_order' => order]
     */
    public function reorderObjects(array $order): void
    {
        foreach ($order as $item) {
            if (!empty($item['id']) && isset($item['sequence_order'])) {
                $this->db->table('exhibition_object')
                    ->where('id', (int) $item['id'])
                    ->update(['sequence_order' => (int) $item['sequence_order']]);
            }
        }
    }

    /**
     * Check object availability for exhibition dates.
     */
    public function checkObjectAvailability(int $objectId, int $exhibitionId): array
    {
        $exhibition = $this->get($exhibitionId);
        if (!$exhibition || !$exhibition['opening_date']) {
            return [];
        }

        $openingDate = $exhibition['opening_date'];
        $closingDate = $exhibition['closing_date'] ?? date('Y-m-d', strtotime('+1 year'));

        // Check other exhibitions
        $conflicts = $this->db->table('exhibition_object as eo')
            ->join('exhibition as e', 'e.id', '=', 'eo.exhibition_id')
            ->where('eo.information_object_id', $objectId)
            ->where('eo.exhibition_id', '!=', $exhibitionId)
            ->whereIn('eo.status', ['confirmed', 'installed'])
            ->where(function ($q) use ($openingDate, $closingDate) {
                $q->whereBetween('e.opening_date', [$openingDate, $closingDate])
                    ->orWhereBetween('e.closing_date', [$openingDate, $closingDate])
                    ->orWhere(function ($q2) use ($openingDate, $closingDate) {
                        $q2->where('e.opening_date', '<=', $openingDate)
                            ->where('e.closing_date', '>=', $closingDate);
                    });
            })
            ->select('e.id', 'e.title', 'e.opening_date', 'e.closing_date')
            ->get()
            ->all();

        // Check loans
        $loanConflicts = $this->db->table('loan_object as lo')
            ->join('loan as l', 'l.id', '=', 'lo.loan_id')
            ->where('lo.information_object_id', $objectId)
            ->whereNull('l.return_date')
            ->where(function ($q) use ($openingDate, $closingDate) {
                $q->whereBetween('l.start_date', [$openingDate, $closingDate])
                    ->orWhereBetween('l.end_date', [$openingDate, $closingDate]);
            })
            ->select('l.id as loan_id', 'l.loan_number', 'l.start_date', 'l.end_date')
            ->get()
            ->all();

        return [
            'exhibitions' => $conflicts,
            'loans' => $loanConflicts,
        ];
    }

    // =========================================================================
    // Storylines/Narratives
    // =========================================================================

    /**
     * Create storyline for exhibition.
     */
    public function createStoryline(int $exhibitionId, array $data, int $userId): int
    {
        $slug = $this->generateSlug($data['title'], 'exhibition_storyline', 'slug');

        return $this->db->table('exhibition_storyline')->insertGetId([
            'exhibition_id' => $exhibitionId,
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'narrative_type' => $data['narrative_type'] ?? 'thematic',
            'introduction' => $data['introduction'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'conclusion' => $data['conclusion'] ?? null,
            'sequence_order' => $data['sequence_order'] ?? 0,
            'is_primary' => $data['is_primary'] ?? false,
            'target_audience' => $data['target_audience'] ?? 'all',
            'reading_level' => $data['reading_level'] ?? 'intermediate',
            'estimated_duration_minutes' => $data['estimated_duration_minutes'] ?? null,
            'has_audio' => $data['has_audio'] ?? false,
            'audio_file_path' => $data['audio_file_path'] ?? null,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get storylines for exhibition.
     */
    public function getStorylines(int $exhibitionId): array
    {
        $storylines = $this->db->table('exhibition_storyline')
            ->where('exhibition_id', $exhibitionId)
            ->orderBy('is_primary', 'desc')
            ->orderBy('sequence_order')
            ->get()
            ->map(fn ($s) => (array) $s)
            ->all();

        // Add stop count
        foreach ($storylines as &$storyline) {
            $storyline['stop_count'] = $this->db->table('exhibition_storyline_stop')
                ->where('storyline_id', $storyline['id'])
                ->count();
        }

        return $storylines;
    }

    /**
     * Update storyline.
     */
    public function updateStoryline(int $storylineId, array $data): void
    {
        $update = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (isset($data['title'])) {
            $update['title'] = $data['title'];
        }
        if (isset($data['description'])) {
            $update['description'] = $data['description'];
        }
        if (isset($data['narrative_type'])) {
            $update['narrative_type'] = $data['narrative_type'];
        }
        if (isset($data['target_audience'])) {
            $update['target_audience'] = $data['target_audience'];
        }
        if (isset($data['is_primary'])) {
            $update['is_primary'] = $data['is_primary'];
        }
        if (isset($data['estimated_duration_minutes'])) {
            $update['estimated_duration_minutes'] = $data['estimated_duration_minutes'];
        }

        $this->db->table('exhibition_storyline')
            ->where('id', $storylineId)
            ->update($update);
    }

    /**
     * Delete storyline and its stops.
     */
    public function deleteStoryline(int $storylineId): void
    {
        // Delete stops first
        $this->db->table('exhibition_storyline_stop')
            ->where('storyline_id', $storylineId)
            ->delete();

        // Delete storyline
        $this->db->table('exhibition_storyline')
            ->where('id', $storylineId)
            ->delete();
    }

    /**
     * Add stop to storyline.
     */
    public function addStorylineStop(int $storylineId, ?int $exhibitionObjectId, array $data): int
    {
        $sequence = $this->db->table('exhibition_storyline_stop')
            ->where('storyline_id', $storylineId)
            ->max('sequence_order') ?? 0;

        return $this->db->table('exhibition_storyline_stop')->insertGetId([
            'storyline_id' => $storylineId,
            'exhibition_object_id' => $exhibitionObjectId,
            'sequence_order' => $data['sequence_order'] ?? $sequence + 1,
            'stop_number' => $data['stop_number'] ?? (string) ($sequence + 1),
            'title' => $data['title'] ?? null,
            'narrative_text' => $data['narrative_text'] ?? null,
            'key_points' => isset($data['key_points']) ? json_encode($data['key_points']) : null,
            'discussion_questions' => $data['discussion_questions'] ?? null,
            'connection_to_next' => $data['connection_to_next'] ?? null,
            'connection_to_theme' => $data['connection_to_theme'] ?? null,
            'audio_transcript' => $data['audio_transcript'] ?? null,
            'audio_duration_seconds' => $data['audio_duration_seconds'] ?? null,
            'suggested_viewing_minutes' => $data['suggested_viewing_minutes'] ?? 2,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update storyline stop.
     */
    public function updateStorylineStop(int $stopId, array $data): void
    {
        $update = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (isset($data['title'])) {
            $update['title'] = $data['title'];
        }
        if (isset($data['narrative_text'])) {
            $update['narrative_text'] = $data['narrative_text'];
        }
        if (isset($data['sequence_order'])) {
            $update['sequence_order'] = $data['sequence_order'];
        }
        if (isset($data['audio_duration_seconds'])) {
            $update['audio_duration_seconds'] = $data['audio_duration_seconds'];
        }
        if (array_key_exists('exhibition_object_id', $data)) {
            $update['exhibition_object_id'] = $data['exhibition_object_id'];
        }

        $this->db->table('exhibition_storyline_stop')
            ->where('id', $stopId)
            ->update($update);
    }

    /**
     * Delete storyline stop.
     */
    public function deleteStorylineStop(int $stopId): void
    {
        $this->db->table('exhibition_storyline_stop')
            ->where('id', $stopId)
            ->delete();
    }

    /**
     * Get storyline with all stops.
     */
    public function getStorylineWithStops(int $storylineId): ?array
    {
        $storyline = $this->db->table('exhibition_storyline')
            ->where('id', $storylineId)
            ->first();

        if (!$storyline) {
            return null;
        }

        $data = (array) $storyline;

        // Get stops with object details
        $data['stops'] = $this->db->table('exhibition_storyline_stop as ss')
            ->join('exhibition_object as eo', 'eo.id', '=', 'ss.exhibition_object_id')
            ->leftJoin('information_object as io', 'io.id', '=', 'eo.information_object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('ioi.id', '=', 'io.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('digital_object as do', 'do.object_id', '=', 'io.id')
            ->leftJoin('slug', 'slug.object_id', '=', 'io.id')
            ->where('ss.storyline_id', $storylineId)
            ->orderBy('ss.sequence_order')
            ->select(
                'ss.*',
                'eo.information_object_id',
                'slug.slug as object_slug',
                'io.identifier',
                'ioi.title as object_title',
                'do.path as thumbnail_path',
                'do.name as thumbnail_name'
            )
            ->get()
            ->map(function ($row) {
                $item = (array) $row;
                if ($item['key_points']) {
                    $item['key_points'] = json_decode($item['key_points'], true);
                }
                if (!empty($item['thumbnail_path']) && !empty($item['thumbnail_name'])) {
                    // Path already contains /uploads/, just append filename
                    $item['thumbnail_url'] = rtrim($item['thumbnail_path'], '/').'/'.ltrim($item['thumbnail_name'], '/');
                }

                return $item;
            })
            ->all();

        return $data;
    }

    // =========================================================================
    // Events
    // =========================================================================

    /**
     * Create exhibition event.
     */
    public function createEvent(int $exhibitionId, array $data, int $userId): int
    {
        return $this->db->table('exhibition_event')->insertGetId([
            'exhibition_id' => $exhibitionId,
            'title' => $data['title'],
            'event_type' => $data['event_type'],
            'description' => $data['description'] ?? null,
            'event_date' => $data['event_date'],
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'is_recurring' => $data['is_recurring'] ?? false,
            'recurrence_pattern' => $data['recurrence_pattern'] ?? null,
            'venue_id' => $data['venue_id'] ?? null,
            'gallery_id' => $data['gallery_id'] ?? null,
            'location_notes' => $data['location_notes'] ?? null,
            'max_attendees' => $data['max_attendees'] ?? null,
            'requires_registration' => $data['requires_registration'] ?? false,
            'registration_url' => $data['registration_url'] ?? null,
            'is_free' => $data['is_free'] ?? true,
            'ticket_price' => $data['ticket_price'] ?? null,
            'presenter_name' => $data['presenter_name'] ?? null,
            'presenter_bio' => $data['presenter_bio'] ?? null,
            'status' => 'scheduled',
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update event.
     */
    public function updateEvent(int $eventId, array $data): void
    {
        $update = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $fields = ['title', 'event_type', 'description', 'event_date', 'start_time',
                   'end_time', 'location', 'max_attendees', 'requires_registration',
                   'is_free', 'ticket_price', 'presenter_name'];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        $this->db->table('exhibition_event')
            ->where('id', $eventId)
            ->update($update);
    }

    /**
     * Delete event.
     */
    public function deleteEvent(int $eventId): void
    {
        $this->db->table('exhibition_event')
            ->where('id', $eventId)
            ->delete();
    }

    /**
     * Get events for exhibition.
     */
    public function getEvents(int $exhibitionId, bool $upcomingOnly = false): array
    {
        $query = $this->db->table('exhibition_event')
            ->where('exhibition_id', $exhibitionId);

        if ($upcomingOnly) {
            $query->where('event_date', '>=', date('Y-m-d'))
                ->where('status', '!=', 'canceled');
        }

        return $query
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($e) => (array) $e)
            ->all();
    }

    // =========================================================================
    // Checklists
    // =========================================================================

    /**
     * Create checklist from template.
     */
    public function createChecklistFromTemplate(
        int $exhibitionId,
        int $templateId,
        ?int $assignedTo = null
    ): int {
        $template = $this->db->table('exhibition_checklist_template')
            ->where('id', $templateId)
            ->first();

        if (!$template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }

        $checklistId = $this->db->table('exhibition_checklist')->insertGetId([
            'exhibition_id' => $exhibitionId,
            'template_id' => $templateId,
            'name' => $template->name,
            'checklist_type' => $template->checklist_type,
            'status' => 'not_started',
            'assigned_to' => $assignedTo,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Create items from template
        $items = json_decode($template->items ?? '[]', true);
        foreach ($items as $order => $item) {
            $this->db->table('exhibition_checklist_item')->insert([
                'checklist_id' => $checklistId,
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'category' => $item['category'] ?? null,
                'is_required' => $item['required'] ?? false,
                'is_completed' => false,
                'sequence_order' => $order,
            ]);
        }

        return $checklistId;
    }

    /**
     * Get checklists for exhibition.
     */
    public function getChecklists(int $exhibitionId): array
    {
        $checklists = $this->db->table('exhibition_checklist')
            ->where('exhibition_id', $exhibitionId)
            ->orderBy('checklist_type')
            ->get()
            ->map(fn ($c) => (array) $c)
            ->all();

        foreach ($checklists as &$checklist) {
            $items = $this->db->table('exhibition_checklist_item')
                ->where('checklist_id', $checklist['id'])
                ->orderBy('sequence_order')
                ->get()
                ->map(fn ($i) => (array) $i)
                ->all();

            $checklist['items'] = $items;
            $checklist['total_items'] = count($items);
            $checklist['completed_items'] = count(array_filter($items, fn ($i) => $i['is_completed']));
            $checklist['progress'] = $checklist['total_items'] > 0
                ? round(($checklist['completed_items'] / $checklist['total_items']) * 100)
                : 0;
        }

        return $checklists;
    }

    /**
     * Complete checklist item.
     */
    public function completeChecklistItem(int $itemId, int $userId, ?string $notes = null): bool
    {
        return $this->db->table('exhibition_checklist_item')
            ->where('id', $itemId)
            ->update([
                'is_completed' => true,
                'completed_at' => date('Y-m-d H:i:s'),
                'completed_by' => $userId,
                'notes' => $notes,
            ]) > 0;
    }

    /**
     * Add item to checklist.
     */
    public function addChecklistItem(int $checklistId, array $data): int
    {
        $sequence = $this->db->table('exhibition_checklist_item')
            ->where('checklist_id', $checklistId)
            ->max('sequence_order') ?? 0;

        return $this->db->table('exhibition_checklist_item')->insertGetId([
            'checklist_id' => $checklistId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'is_required' => $data['is_required'] ?? false,
            'sequence_order' => $sequence + 1,
            'is_completed' => false,
        ]);
    }

    // =========================================================================
    // Statistics & Reports
    // =========================================================================

    /**
     * Get statistics for a single exhibition.
     */
    public function getExhibitionStatistics(int $exhibitionId): array
    {
        $stats = [
            'object_count' => 0,
            'objects_by_status' => [],
            'section_count' => 0,
            'storyline_count' => 0,
            'event_count' => 0,
            'total_insurance_value' => 0,
            'checklist_progress' => [],
        ];

        // Object counts
        $objectCounts = $this->db->table('exhibition_object')
            ->where('exhibition_id', $exhibitionId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        foreach ($objectCounts as $row) {
            $stats['objects_by_status'][$row->status] = $row->count;
            $stats['object_count'] += $row->count;
        }

        // Section count
        $stats['section_count'] = $this->db->table('exhibition_section')
            ->where('exhibition_id', $exhibitionId)
            ->count();

        // Storyline count
        $stats['storyline_count'] = $this->db->table('exhibition_storyline')
            ->where('exhibition_id', $exhibitionId)
            ->count();

        // Event count
        $stats['event_count'] = $this->db->table('exhibition_event')
            ->where('exhibition_id', $exhibitionId)
            ->where('status', '!=', 'canceled')
            ->count();

        // Total insurance value
        $stats['total_insurance_value'] = $this->db->table('exhibition_object')
            ->where('exhibition_id', $exhibitionId)
            ->sum('insurance_value') ?? 0;

        // Checklist progress
        $checklists = $this->getChecklists($exhibitionId);
        foreach ($checklists as $checklist) {
            $stats['checklist_progress'][$checklist['checklist_type']] = $checklist['progress'];
        }

        return $stats;
    }

    /**
     * Get overall exhibition statistics.
     */
    public function getStatistics(): array
    {
        $stats = [
            'total_exhibitions' => 0,
            'by_status' => [],
            'by_type' => [],
            'current_exhibitions' => 0,
            'upcoming_exhibitions' => 0,
            'total_objects_on_display' => 0,
            'total_insurance_value' => 0,
        ];

        // Count by status
        $byStatus = $this->db->table('exhibition')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        foreach ($byStatus as $row) {
            $stats['by_status'][$row->status] = $row->count;
            $stats['total_exhibitions'] += $row->count;
        }

        // Count by type
        $byType = $this->db->table('exhibition')
            ->selectRaw('exhibition_type, COUNT(*) as count')
            ->groupBy('exhibition_type')
            ->get();

        foreach ($byType as $row) {
            $stats['by_type'][$row->exhibition_type] = $row->count;
        }

        // Current exhibitions
        $stats['current_exhibitions'] = $stats['by_status']['open'] ?? 0;

        // Upcoming exhibitions
        $stats['upcoming_exhibitions'] = $this->db->table('exhibition')
            ->where('opening_date', '>', date('Y-m-d'))
            ->whereIn('status', ['concept', 'planning', 'preparation', 'installation'])
            ->count();

        // Objects on display
        $stats['total_objects_on_display'] = $this->db->table('exhibition_object as eo')
            ->join('exhibition as e', 'e.id', '=', 'eo.exhibition_id')
            ->where('e.status', 'open')
            ->where('eo.status', 'installed')
            ->count();

        // Total insurance value (current exhibitions)
        $stats['total_insurance_value'] = $this->db->table('exhibition_object as eo')
            ->join('exhibition as e', 'e.id', '=', 'eo.exhibition_id')
            ->where('e.status', 'open')
            ->sum('eo.insurance_value') ?? 0;

        return $stats;
    }

    /**
     * Generate object list report for exhibition.
     */
    public function generateObjectList(int $exhibitionId): array
    {
        return $this->db->table('exhibition_object as eo')
            ->join('exhibition as e', 'e.id', '=', 'eo.exhibition_id')
            ->leftJoin('exhibition_section as es', 'es.id', '=', 'eo.section_id')
            ->leftJoin('information_object as io', 'io.id', '=', 'eo.information_object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('ioi.id', '=', 'io.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('eo.exhibition_id', $exhibitionId)
            ->orderBy('es.sequence_order')
            ->orderBy('eo.sequence_order')
            ->leftJoin('slug', 'slug.object_id', '=', 'io.id')
            ->select(
                'eo.id',
                'eo.information_object_id',
                'slug.slug as object_slug',
                'io.identifier',
                'ioi.title as object_title',
                'es.id as section_id',
                'es.title as section_title',
                'es.gallery_name',
                'eo.display_position',
                'eo.status',
                'eo.insurance_value',
                'eo.requires_loan',
                'eo.lender_institution',
                'eo.label_text'
            )
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Log status change.
     */
    private function logStatusChange(
        int $exhibitionId,
        ?string $fromStatus,
        string $toStatus,
        int $userId,
        ?string $reason
    ): void {
        $this->db->table('exhibition_status_history')->insert([
            'exhibition_id' => $exhibitionId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $userId,
            'change_reason' => $reason,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Calculate timing information.
     */
    private function calculateTiming(array $exhibition): array
    {
        $timing = [
            'days_until_opening' => null,
            'days_since_opening' => null,
            'days_until_closing' => null,
            'days_since_closing' => null,
            'duration_days' => null,
            'is_current' => false,
        ];

        $today = new \DateTime();

        if ($exhibition['opening_date']) {
            $openingDate = new \DateTime($exhibition['opening_date']);
            $diff = $today->diff($openingDate);

            if ($openingDate > $today) {
                $timing['days_until_opening'] = $diff->days;
            } else {
                $timing['days_since_opening'] = $diff->days;
            }
        }

        if ($exhibition['closing_date']) {
            $closingDate = new \DateTime($exhibition['closing_date']);
            $diff = $today->diff($closingDate);

            if ($closingDate > $today) {
                $timing['days_until_closing'] = $diff->days;
            } else {
                $timing['days_since_closing'] = $diff->days;
            }
        }

        if ($exhibition['opening_date'] && $exhibition['closing_date']) {
            $openingDate = new \DateTime($exhibition['opening_date']);
            $closingDate = new \DateTime($exhibition['closing_date']);
            $timing['duration_days'] = $openingDate->diff($closingDate)->days;
        }

        // Is current?
        if ('open' === $exhibition['status']) {
            $timing['is_current'] = true;
        }

        return $timing;
    }

    /**
     * Generate unique slug.
     */
    private function generateSlug(string $title, string $table = 'exhibition', string $column = 'slug'): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $baseSlug = $slug;
        $counter = 1;

        while ($this->db->table($table)->where($column, $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            ++$counter;
        }

        return $slug;
    }

    /**
     * Get available types.
     */
    public function getTypes(): array
    {
        return self::TYPES;
    }

    /**
     * Get available statuses.
     */
    public function getStatuses(): array
    {
        return self::STATUSES;
    }

    /**
     * Get valid transitions for a status.
     */
    public function getValidTransitions(string $status): array
    {
        return self::STATUS_TRANSITIONS[$status] ?? [];
    }

    /**
     * Get checklist templates.
     */
    public function getChecklistTemplates(): array
    {
        return $this->db->table('exhibition_checklist_template')
            ->orderBy('checklist_type')
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => (array) $t)
            ->all();
    }
}
