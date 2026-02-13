<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ResearchService - Core Research Portal Service
 *
 * Handles researcher management, bookings, collections, annotations,
 * citations, and researcher types/verification.
 *
 * @package ahgResearchPlugin
 * @version 2.0.0
 */
class ResearchService
{
    public function getResearcherByUserId(int $userId): ?object
    {
        return DB::table('research_researcher')->where('user_id', $userId)->first();
    }

    public function getResearcher(int $id): ?object
    {
        return DB::table('research_researcher')->where('id', $id)->first();
    }

    public function registerResearcher(array $data): int
    {
        $researcherId = DB::table('research_researcher')->insertGetId([
            'user_id' => $data['user_id'],
            'title' => $data['title'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'affiliation_type' => $data['affiliation_type'] ?? 'independent',
            'institution' => $data['institution'] ?? null,
            'department' => $data['department'] ?? null,
            'position' => $data['position'] ?? null,
            'student_id' => $data['student_id'] ?? null,
            'research_interests' => $data['research_interests'] ?? null,
            'current_project' => $data['current_project'] ?? null,
            'orcid_id' => $data['orcid_id'] ?? null,
            'id_type' => ($data['id_type'] ?: null),
            'id_number' => $data['id_number'] ?? null,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Create access request for researcher approval
        // Request Internal (level 1) clearance for new researchers
        DB::table('access_request')->insert([
            'request_type' => 'researcher',
            'scope_type' => 'single',
            'user_id' => $data['user_id'],
            'requested_classification_id' => 2, // Internal level
            'current_classification_id' => 1,   // Public level (default)
            'reason' => 'New researcher registration: ' . $data['first_name'] . ' ' . $data['last_name'],
            'justification' => $data['research_interests'] ?? $data['current_project'] ?? null,
            'urgency' => 'normal',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logAudit('create', 'Researcher', $researcherId, [], $data, $data['first_name'] . ' ' . $data['last_name']);
        return $researcherId;
    }

    public function updateResearcher(int $id, array $data): bool
    {
        $oldValues = (array)(DB::table('research_researcher')->where('id', $id)->first() ?? []);
        $data['updated_at'] = date('Y-m-d H:i:s');
        $result = DB::table('research_researcher')->where('id', $id)->update($data) > 0;
        if ($result) {
            $newValues = (array)(DB::table('research_researcher')->where('id', $id)->first() ?? []);
            $this->logAudit('update', 'Researcher', $id, $oldValues, $newValues, ($newValues['first_name'] ?? '') . ' ' . ($newValues['last_name'] ?? ''));
        }
        return $result;
    }

    public function approveResearcher(int $id, int $approvedBy, ?string $expiresAt = null): bool
    {
        $oldValues = (array)(DB::table('research_researcher')->where('id', $id)->first() ?? []);
        $result = DB::table('research_researcher')->where('id', $id)->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expiresAt ?? date('Y-m-d', strtotime('+1 year')),
        ]) > 0;
        if ($result) {
            $newValues = (array)(DB::table('research_researcher')->where('id', $id)->first() ?? []);
            $this->logAudit('approve', 'Researcher', $id, $oldValues, $newValues, ($newValues['first_name'] ?? '') . ' ' . ($newValues['last_name'] ?? ''));
        }
        return $result;
    }

    public function getResearchers(array $filters = []): array
    {
        $query = DB::table('research_researcher');
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('first_name', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('last_name', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'LIKE', '%' . $filters['search'] . '%');
            });
        }
        return $query->orderBy('last_name')->get()->toArray();
    }

    public function getReadingRooms(bool $activeOnly = true): array
    {
        $query = DB::table('research_reading_room');
        if ($activeOnly) $query->where('is_active', 1);
        return $query->orderBy('name')->get()->toArray();
    }

    public function getReadingRoom(int $id): ?object
    {
        return DB::table('research_reading_room')->where('id', $id)->first();
    }

    public function createBooking(array $data): int
    {
        $bookingId = DB::table('research_booking')->insertGetId([
            'researcher_id' => $data['researcher_id'],
            'reading_room_id' => $data['reading_room_id'],
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'purpose' => $data['purpose'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->logAudit('create', 'ResearchBooking', $bookingId, [], $data, 'Booking ' . $data['booking_date']);
        return $bookingId;
    }

    public function addMaterialRequest(int $bookingId, int $objectId, ?string $notes = null): int
    {
        return DB::table('research_material_request')->insertGetId([
            'booking_id' => $bookingId,
            'object_id' => $objectId,
            'notes' => $notes,
            'status' => 'requested',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getBooking(int $id): ?object
    {
        $booking = DB::table('research_booking as b')
            ->join('research_researcher as r', 'b.researcher_id', '=', 'r.id')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.id', $id)
            ->select('b.*', 'r.first_name', 'r.last_name', 'r.email', 'r.institution',
                'rm.name as room_name', 'rm.location as room_location')
            ->first();
        if ($booking) {
            $booking->materials = DB::table('research_material_request as m')
                ->leftJoin('information_object_i18n as i18n', function($join) {
                    $join->on('m.object_id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                })
                ->where('m.booking_id', $id)
                ->select('m.*', 'i18n.title as object_title')
                ->get()->toArray();
        }
        return $booking;
    }

    public function getResearcherBookings(int $researcherId, ?string $status = null): array
    {
        $query = DB::table('research_booking as b')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.researcher_id', $researcherId);
        if ($status) $query->where('b.status', $status);
        return $query->select('b.*', 'rm.name as room_name')
            ->orderBy('b.booking_date', 'desc')->get()->toArray();
    }

    public function confirmBooking(int $id, int $confirmedBy): bool
    {
        $oldValues = (array)(DB::table('research_booking')->where('id', $id)->first() ?? []);
        $result = DB::table('research_booking')->where('id', $id)->update([
            'status' => 'confirmed', 'confirmed_by' => $confirmedBy, 'confirmed_at' => date('Y-m-d H:i:s'),
        ]) > 0;
        if ($result) {
            $newValues = (array)(DB::table('research_booking')->where('id', $id)->first() ?? []);
            $this->logAudit('confirm', 'ResearchBooking', $id, $oldValues, $newValues, null);
        }
        return $result;
    }

    public function cancelBooking(int $id, ?string $reason = null): bool
    {
        $oldValues = (array)(DB::table('research_booking')->where('id', $id)->first() ?? []);
        $result = DB::table('research_booking')->where('id', $id)->update([
            'status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s'), 'cancellation_reason' => $reason,
        ]) > 0;
        if ($result) {
            $newValues = (array)(DB::table('research_booking')->where('id', $id)->first() ?? []);
            $this->logAudit('cancel', 'ResearchBooking', $id, $oldValues, $newValues, null);
        }
        return $result;
    }

    public function checkIn(int $bookingId): bool
    {
        return DB::table('research_booking')->where('id', $bookingId)
            ->update(['checked_in_at' => date('Y-m-d H:i:s')]) > 0;
    }

    public function checkOut(int $bookingId): bool
    {
        return DB::table('research_booking')->where('id', $bookingId)->update([
            'status' => 'completed', 'checked_out_at' => date('Y-m-d H:i:s'),
        ]) > 0;
    }

    public function saveSearch(int $researcherId, array $data): int
    {
        return DB::table('research_saved_search')->insertGetId([
            'researcher_id' => $researcherId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'search_query' => $data['search_query'],
            'search_filters' => isset($data['search_filters']) ? json_encode($data['search_filters']) : null,
            'alert_enabled' => $data['alert_enabled'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getSavedSearches(int $researcherId): array
    {
        return DB::table('research_saved_search')
            ->where('researcher_id', $researcherId)
            ->orderBy('created_at', 'desc')->get()->toArray();
    }

    public function deleteSavedSearch(int $id, int $researcherId): bool
    {
        return DB::table('research_saved_search')
            ->where('id', $id)->where('researcher_id', $researcherId)->delete() > 0;
    }

    public function createCollection(int $researcherId, array $data): int
    {
        return DB::table('research_collection')->insertGetId([
            'researcher_id' => $researcherId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_public' => $data['is_public'] ?? 0,
            'share_token' => bin2hex(random_bytes(32)),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getCollections(int $researcherId): array
    {
        $collections = DB::table('research_collection')
            ->where('researcher_id', $researcherId)->orderBy('name')->get()->toArray();
        foreach ($collections as &$c) {
            $c->item_count = DB::table('research_collection_item')->where('collection_id', $c->id)->count();
        }
        return $collections;
    }

    public function getCollection(int $id): ?object
    {
        $collection = DB::table('research_collection')->where('id', $id)->first();
        if ($collection) {
            $collection->items = DB::table('research_collection_item as ci')
                ->leftJoin('information_object_i18n as i18n', function($join) {
                    $join->on('ci.object_id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                })
                ->leftJoin('slug', 'ci.object_id', '=', 'slug.object_id')
                ->where('ci.collection_id', $id)
                ->select('ci.*', 'i18n.title as object_title', 'slug.slug')
                ->orderBy('ci.sort_order')->get()->toArray();
        }
        return $collection;
    }

    public function addToCollection(int $collectionId, int $objectId, ?string $notes = null): bool
    {
        if (DB::table('research_collection_item')->where('collection_id', $collectionId)->where('object_id', $objectId)->exists()) return false;
        $maxOrder = DB::table('research_collection_item')->where('collection_id', $collectionId)->max('sort_order') ?? 0;
        DB::table('research_collection_item')->insert([
            'collection_id' => $collectionId, 'object_id' => $objectId, 'notes' => $notes,
            'sort_order' => $maxOrder + 1, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function removeFromCollection(int $collectionId, int $objectId): bool
    {
        return DB::table('research_collection_item')
            ->where('collection_id', $collectionId)->where('object_id', $objectId)->delete() > 0;
    }

    public function createAnnotation(int $researcherId, array $data): int
    {
        return DB::table('research_annotation')->insertGetId([
            'researcher_id' => $researcherId,
            'object_id' => $data['object_id'],
            'digital_object_id' => $data['digital_object_id'] ?? null,
            'annotation_type' => $data['annotation_type'] ?? 'note',
            'title' => $data['title'] ?? null,
            'content' => $data['content'],
            'tags' => $data['tags'] ?? null,
            'is_private' => $data['is_private'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getAnnotations(int $researcherId, ?int $objectId = null): array
    {
        $query = DB::table('research_annotation as a')
            ->leftJoin('information_object_i18n as i18n', function($join) {
                $join->on('a.object_id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'a.object_id', '=', 'slug.object_id')
            ->where('a.researcher_id', $researcherId);
        if ($objectId) $query->where('a.object_id', $objectId);
        return $query->select('a.*', 'i18n.title as object_title', 'slug.slug as object_slug')
            ->orderBy('a.created_at', 'desc')->get()->toArray();
    }

    public function deleteAnnotation(int $id, int $researcherId): bool
    {
        return DB::table('research_annotation')
            ->where('id', $id)->where('researcher_id', $researcherId)->delete() > 0;
    }

    public function generateCitation(int $objectId, string $style = 'chicago'): array
    {
        $object = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function($join) {
                $join->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('actor_i18n as ri', function($join) {
                $join->on('io.repository_id', '=', 'ri.id')->where('ri.culture', '=', 'en');
            })
            ->where('io.id', $objectId)
            ->select('io.id', 'io.identifier', 'i18n.title', 'slug.slug', 'ri.authorized_form_of_name as repository_name')
            ->first();
        if (!$object) return ['error' => 'Object not found'];

        $dates = DB::table('event as e')
            ->join('event_i18n as ei', function($join) { $join->on('e.id', '=', 'ei.id')->where('ei.culture', '=', 'en'); })
            ->where('e.object_id', $objectId)->select('e.start_date', 'ei.date as date_display')->first();

        $creators = DB::table('event as e')
            ->join('actor_i18n as ai', function($join) { $join->on('e.actor_id', '=', 'ai.id')->where('ai.culture', '=', 'en'); })
            ->where('e.object_id', $objectId)->where('e.type_id', 111)
            ->pluck('ai.authorized_form_of_name')->toArray();

        $dateStr = $dates->date_display ?? ($dates && $dates->start_date ? date('Y', strtotime($dates->start_date)) : 'n.d.');
        $siteUrl = DB::table('setting')->join('setting_i18n', 'setting.id', '=', 'setting_i18n.id')->where('setting.name', 'siteBaseUrl')->value('setting_i18n.value') ?? '';
        $accessUrl = rtrim($siteUrl, '/') . '/' . $object->slug;
        $accessDate = date('F j, Y');
        $repo = $object->repository_name ?? 'Archive';

        $citation = match(strtolower($style)) {
            'chicago' => $this->fmtChicago($object->title, $creators, $dateStr, $repo, $accessUrl, $accessDate),
            'mla' => $this->fmtMLA($object->title, $creators, $dateStr, $repo, $accessUrl, $accessDate),
            'turabian' => $this->fmtChicago($object->title, $creators, $dateStr, $repo, $accessUrl, $accessDate),
            'apa' => $this->fmtAPA($object->title, $creators, $dateStr, $accessUrl),
            'harvard' => $this->fmtHarvard($object->title, $creators, $dateStr, $repo, $accessUrl, $accessDate),
            'unisa' => $this->fmtUnisaHarvard($object->title, $creators, $dateStr, $repo, $accessUrl, $accessDate),
            default => $this->fmtChicago($object->title, $creators, $dateStr, $repo, $accessUrl, $accessDate),
        };
        return ['style' => $style, 'citation' => $citation, 'object_title' => $object->title, 'url' => $accessUrl];
    }

    protected function fmtChicago($title, $creators, $date, $repo, $url, $accessed): string
    {
        $c = !empty($creators) ? implode(', ', $creators) . '. ' : '';
        return "{$c}\"{$title}.\" {$date}. {$repo}. {$url} (accessed {$accessed}).";
    }

    protected function fmtMLA($title, $creators, $date, $repo, $url, $accessed): string
    {
        $c = !empty($creators) ? implode(', ', $creators) . '. ' : '';
        return "{$c}\"{$title}.\" {$repo}, {$date}. {$url}. Accessed {$accessed}.";
    }

    protected function fmtAPA($title, $creators, $date, $url): string
    {
        $c = !empty($creators) ? implode(', ', $creators) : 'Unknown';
        return "{$c}. ({$date}). {$title}. Retrieved from {$url}";
    }

    /**
     * Format citation in Harvard style
     */
    protected function fmtHarvard($title, $creators, $date, $repo, $url, $accessed): string
    {
        $c = !empty($creators) ? implode(', ', $creators) : 'Anon';
        $year = is_numeric($date) ? $date : (preg_match('/\d{4}/', $date, $m) ? $m[0] : 'n.d.');
        return "{$c} ({$year}) '{$title}', {$repo}. Available at: {$url} (Accessed: {$accessed}).";
    }

    /**
     * Format citation in UNISA Harvard style
     */
    protected function fmtUnisaHarvard($title, $creators, $date, $repo, $url, $accessed): string
    {
        $formattedCreators = [];
        foreach ($creators as $creator) {
            $parts = explode(' ', trim($creator));
            if (count($parts) >= 2) {
                $surname = array_pop($parts);
                $initials = '';
                foreach ($parts as $part) {
                    $initials .= strtoupper(substr($part, 0, 1)) . '.';
                }
                $formattedCreators[] = "{$surname}, {$initials}";
            } else {
                $formattedCreators[] = $creator;
            }
        }
        $c = !empty($formattedCreators) ? implode(' & ', $formattedCreators) : 'Anon';
        $year = is_numeric($date) ? $date : (preg_match('/\d{4}/', $date, $m) ? $m[0] : 'n.d.');
        $accessedDate = date('d F Y', strtotime($accessed));
        return "{$c}. {$year}. <em>{$title}</em>. {$repo}. [Online]. Available from: {$url} [Accessed {$accessedDate}].";
    }

    public function logCitation(?int $researcherId, int $objectId, string $style, string $text): void
    {
        DB::table('research_citation_log')->insert([
            'researcher_id' => $researcherId, 'object_id' => $objectId,
            'citation_style' => $style, 'citation_text' => $text, 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getDashboardStats(): array
    {
        return [
            'researchers' => DB::table('research_researcher')->where('status', 'approved')->count(),
            'bookings_today' => DB::table('research_booking')
                ->where('booking_date', date('Y-m-d'))
                ->whereIn('status', ['pending', 'confirmed'])
                ->count(),
            'bookings_week' => DB::table('research_booking')
                ->whereBetween('booking_date', [date('Y-m-d'), date('Y-m-d', strtotime('+7 days'))])
                ->whereIn('status', ['pending', 'confirmed'])
                ->count(),
            'pending_requests' => DB::table('research_researcher')->where('status', 'pending')->count(),
        ];
    }

    /**
     * Generate finding aid data for a researcher collection.
     */
    public function getCollectionFindingAidData(int $collectionId, int $researcherId): ?array
    {
        // Verify collection belongs to researcher
        $collection = DB::table('research_collection')
            ->where('id', $collectionId)
            ->where('researcher_id', $researcherId)
            ->first();
        
        if (!$collection) {
            return null;
        }

        // Get all items in the collection with their information
        $items = DB::table('research_collection_item as ci')
            ->join('information_object as io', 'ci.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as lod', function($join) {
                $join->on('io.level_of_description_id', '=', 'lod.id')
                     ->where('lod.culture', '=', 'en');
            })
            ->leftJoin('actor_i18n as repo', function($join) {
                $join->on('io.repository_id', '=', 'repo.id')
                     ->where('repo.culture', '=', 'en');
            })
            ->where('ci.collection_id', $collectionId)
            ->select(
                'io.id',
                'io.identifier',
                'io.level_of_description_id',
                'io.repository_id',
                'io.lft',
                'io.rgt',
                'io.parent_id',
                'ioi.title',
                'ioi.scope_and_content',
                'ioi.extent_and_medium',
                'ioi.archival_history',
                'ioi.arrangement',
                'ioi.access_conditions',
                'ioi.reproduction_conditions',
                'ioi.physical_characteristics',
                'lod.name as level_of_description',
                'repo.authorized_form_of_name as repository_name',
                'ci.notes as researcher_notes',
                'ci.created_at'
            )
            ->orderBy('ci.created_at')
            ->get()->toArray();

        // Get descendants for each item if requested
        $allItems = [];
        foreach ($items as $item) {
            $allItems[] = $item;
            
            // Get descendants
            $descendants = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as ioi', function($join) {
                    $join->on('io.id', '=', 'ioi.id')
                         ->where('ioi.culture', '=', 'en');
                })
                ->leftJoin('term_i18n as lod', function($join) {
                    $join->on('io.level_of_description_id', '=', 'lod.id')
                         ->where('lod.culture', '=', 'en');
                })
                ->where('io.lft', '>', $item->lft)
                ->where('io.rgt', '<', $item->rgt)
                ->select(
                    'io.id',
                    'io.identifier',
                    'io.level_of_description_id',
                    'io.lft',
                    'io.rgt',
                    'io.parent_id',
                    'ioi.title',
                    'ioi.scope_and_content',
                    'ioi.extent_and_medium',
                    'lod.name as level_of_description'
                )
                ->orderBy('io.lft')
                ->get()->toArray();
            
            foreach ($descendants as $desc) {
                $desc->is_descendant = true;
                $desc->parent_item_id = $item->id;
                $allItems[] = $desc;
            }
        }

        // Get researcher info
        $researcher = DB::table('research_researcher')
            ->where('id', $researcherId)
            ->first();

        return [
            'collection' => $collection,
            'items' => $allItems,
            'researcher' => $researcher,
            'generated_at' => date('Y-m-d H:i:s'),
            'item_count' => count($items),
            'total_with_descendants' => count($allItems),
        ];
    }

    /**
     * Calculate hierarchy depth for an item.
     */
    public function getItemDepth(int $objectId): int
    {
        $item = DB::table('information_object')
            ->where('id', $objectId)
            ->first();
        
        if (!$item) return 0;
        
        return DB::table('information_object')
            ->where('lft', '<', $item->lft)
            ->where('rgt', '>', $item->rgt)
            ->count();
    }

    // =========================================================================
    // AUDIT LOGGING
    // =========================================================================

    protected function logAudit(string $action, string $entityType, int $entityId, array $oldValues, array $newValues, ?string $title = null): void
    {
        try {
            $auditServicePath = \sfConfig::get('sf_root_dir') . '/plugins/ahgAuditTrailPlugin/lib/Services/AhgAuditService.php';
            if (file_exists($auditServicePath)) {
                require_once $auditServicePath;
            }

            if (class_exists('AhgAuditTrail\\Services\\AhgAuditService')) {
                $changedFields = [];
                foreach ($newValues as $key => $val) {
                    if (($oldValues[$key] ?? null) !== $val) {
                        $changedFields[] = $key;
                    }
                }
                if ($action === 'delete') {
                    $changedFields = array_keys($oldValues);
                }

                \AhgAuditTrail\Services\AhgAuditService::logAction(
                    $action,
                    $entityType,
                    $entityId,
                    [
                        'title' => $title,
                        'module' => 'ahgResearchPlugin',
                        'action_name' => $action,
                        'old_values' => $oldValues,
                        'new_values' => $newValues,
                        'changed_fields' => $changedFields,
                    ]
                );
            }
        } catch (\Exception $e) {
            error_log("ResearchService AUDIT ERROR: " . $e->getMessage());
        }
    }

    // =========================================================================
    // RESEARCHER TYPES
    // =========================================================================

    /**
     * Get all researcher types.
     *
     * @param bool $activeOnly Only return active types
     * @return array List of researcher types
     */
    public function getResearcherTypes(bool $activeOnly = true): array
    {
        $query = DB::table('research_researcher_type');

        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        return $query->orderBy('sort_order')->get()->toArray();
    }

    /**
     * Get a researcher type by ID.
     *
     * @param int $id The type ID
     * @return object|null The researcher type or null
     */
    public function getResearcherType(int $id): ?object
    {
        return DB::table('research_researcher_type')->where('id', $id)->first();
    }

    /**
     * Get a researcher type by code.
     *
     * @param string $code The type code
     * @return object|null The researcher type or null
     */
    public function getResearcherTypeByCode(string $code): ?object
    {
        return DB::table('research_researcher_type')->where('code', $code)->first();
    }

    /**
     * Create a new researcher type.
     *
     * @param array $data Type data
     * @return int The new type ID
     */
    public function createResearcherType(array $data): int
    {
        $maxOrder = DB::table('research_researcher_type')->max('sort_order') ?? 0;

        return DB::table('research_researcher_type')->insertGetId([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'max_booking_days_advance' => $data['max_booking_days_advance'] ?? 14,
            'max_booking_hours_per_day' => $data['max_booking_hours_per_day'] ?? 4,
            'max_materials_per_booking' => $data['max_materials_per_booking'] ?? 10,
            'can_remote_access' => $data['can_remote_access'] ?? 0,
            'can_request_reproductions' => $data['can_request_reproductions'] ?? 1,
            'can_export_data' => $data['can_export_data'] ?? 1,
            'requires_id_verification' => $data['requires_id_verification'] ?? 1,
            'auto_approve' => $data['auto_approve'] ?? 0,
            'expiry_months' => $data['expiry_months'] ?? 12,
            'priority_level' => $data['priority_level'] ?? 5,
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? ($maxOrder + 10),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a researcher type.
     *
     * @param int $id The type ID
     * @param array $data Fields to update
     * @return bool Success status
     */
    public function updateResearcherType(int $id, array $data): bool
    {
        $allowed = [
            'name', 'code', 'description', 'max_booking_days_advance',
            'max_booking_hours_per_day', 'max_materials_per_booking',
            'can_remote_access', 'can_request_reproductions', 'can_export_data',
            'requires_id_verification', 'auto_approve', 'expiry_months',
            'priority_level', 'is_active', 'sort_order',
        ];

        $updateData = array_intersect_key($data, array_flip($allowed));
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('research_researcher_type')
            ->where('id', $id)
            ->update($updateData) >= 0;
    }

    /**
     * Delete a researcher type.
     *
     * @param int $id The type ID
     * @return array Result with success status
     */
    public function deleteResearcherType(int $id): array
    {
        // Check if any researchers are using this type
        $count = DB::table('research_researcher')
            ->where('researcher_type_id', $id)
            ->count();

        if ($count > 0) {
            return [
                'success' => false,
                'error' => "Cannot delete: {$count} researcher(s) are using this type",
            ];
        }

        $deleted = DB::table('research_researcher_type')->where('id', $id)->delete() > 0;

        return ['success' => $deleted];
    }

    /**
     * Get default researcher types for seeding.
     *
     * @return array Default researcher types
     */
    public function getDefaultTypes(): array
    {
        return [
            [
                'name' => 'General Public',
                'code' => 'public',
                'description' => 'Members of the general public with casual research needs',
                'max_booking_days_advance' => 7,
                'max_booking_hours_per_day' => 2,
                'max_materials_per_booking' => 5,
                'can_remote_access' => 0,
                'requires_id_verification' => 1,
                'auto_approve' => 0,
                'expiry_months' => 6,
                'priority_level' => 1,
            ],
            [
                'name' => 'Registered Researcher',
                'code' => 'researcher',
                'description' => 'Registered independent researchers',
                'max_booking_days_advance' => 14,
                'max_booking_hours_per_day' => 4,
                'max_materials_per_booking' => 10,
                'can_remote_access' => 0,
                'requires_id_verification' => 1,
                'auto_approve' => 0,
                'expiry_months' => 12,
                'priority_level' => 3,
            ],
            [
                'name' => 'Academic Staff',
                'code' => 'academic',
                'description' => 'University and college academic staff members',
                'max_booking_days_advance' => 30,
                'max_booking_hours_per_day' => 8,
                'max_materials_per_booking' => 20,
                'can_remote_access' => 1,
                'requires_id_verification' => 0,
                'auto_approve' => 1,
                'expiry_months' => 24,
                'priority_level' => 7,
            ],
            [
                'name' => 'Postgraduate Student',
                'code' => 'postgraduate',
                'description' => 'Masters and doctoral students',
                'max_booking_days_advance' => 21,
                'max_booking_hours_per_day' => 6,
                'max_materials_per_booking' => 15,
                'can_remote_access' => 0,
                'requires_id_verification' => 1,
                'auto_approve' => 0,
                'expiry_months' => 12,
                'priority_level' => 5,
            ],
            [
                'name' => 'Heritage Professional',
                'code' => 'professional',
                'description' => 'Archivists, librarians, and heritage professionals',
                'max_booking_days_advance' => 30,
                'max_booking_hours_per_day' => 8,
                'max_materials_per_booking' => 25,
                'can_remote_access' => 1,
                'requires_id_verification' => 0,
                'auto_approve' => 1,
                'expiry_months' => 24,
                'priority_level' => 7,
            ],
        ];
    }

    /**
     * Check if a researcher type allows a specific action.
     *
     * @param int $researcherId The researcher ID
     * @param string $permission Permission to check (can_remote_access, can_request_reproductions, can_export_data)
     * @return bool True if permitted
     */
    public function checkTypePermission(int $researcherId, string $permission): bool
    {
        $researcher = $this->getResearcher($researcherId);

        if (!$researcher || !$researcher->researcher_type_id) {
            // Default permissions if no type assigned
            return $permission !== 'can_remote_access';
        }

        $type = $this->getResearcherType($researcher->researcher_type_id);

        if (!$type) {
            return $permission !== 'can_remote_access';
        }

        return (bool) ($type->$permission ?? false);
    }

    /**
     * Get booking limits for a researcher based on their type.
     *
     * @param int $researcherId The researcher ID
     * @return array Booking limits
     */
    public function getResearcherBookingLimits(int $researcherId): array
    {
        $researcher = $this->getResearcher($researcherId);

        $defaults = [
            'max_booking_days_advance' => 14,
            'max_booking_hours_per_day' => 4,
            'max_materials_per_booking' => 10,
        ];

        if (!$researcher || !$researcher->researcher_type_id) {
            return $defaults;
        }

        $type = $this->getResearcherType($researcher->researcher_type_id);

        if (!$type) {
            return $defaults;
        }

        return [
            'max_booking_days_advance' => $type->max_booking_days_advance ?? $defaults['max_booking_days_advance'],
            'max_booking_hours_per_day' => $type->max_booking_hours_per_day ?? $defaults['max_booking_hours_per_day'],
            'max_materials_per_booking' => $type->max_materials_per_booking ?? $defaults['max_materials_per_booking'],
        ];
    }

    // =========================================================================
    // VERIFICATION SYSTEM
    // =========================================================================

    /**
     * Create a verification record.
     *
     * @param int $researcherId The researcher ID
     * @param array $data Verification data
     * @return int The verification ID
     */
    public function createVerification(int $researcherId, array $data): int
    {
        return DB::table('research_verification')->insertGetId([
            'researcher_id' => $researcherId,
            'verification_type' => $data['verification_type'],
            'document_type' => $data['document_type'] ?? null,
            'document_reference' => $data['document_reference'] ?? null,
            'document_path' => $data['document_path'] ?? null,
            'verification_data' => isset($data['verification_data']) ? json_encode($data['verification_data']) : null,
            'status' => 'pending',
            'expires_at' => $data['expires_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get verifications for a researcher.
     *
     * @param int $researcherId The researcher ID
     * @param string|null $status Filter by status
     * @return array List of verifications
     */
    public function getVerifications(int $researcherId, ?string $status = null): array
    {
        $query = DB::table('research_verification as v')
            ->leftJoin('user as u', 'v.verified_by', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->where('v.researcher_id', $researcherId)
            ->select('v.*', 'ai.authorized_form_of_name as verified_by_name');

        if ($status) {
            $query->where('v.status', $status);
        }

        return $query->orderBy('v.created_at', 'desc')->get()->toArray();
    }

    /**
     * Update a verification record.
     *
     * @param int $verificationId The verification ID
     * @param array $data Fields to update
     * @return bool Success status
     */
    public function updateVerification(int $verificationId, array $data): bool
    {
        $allowed = [
            'document_type', 'document_reference', 'document_path',
            'verification_data', 'status', 'verified_by', 'verified_at',
            'expires_at', 'rejection_reason', 'notes',
        ];

        $updateData = array_intersect_key($data, array_flip($allowed));
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        if (isset($updateData['verification_data']) && is_array($updateData['verification_data'])) {
            $updateData['verification_data'] = json_encode($updateData['verification_data']);
        }

        return DB::table('research_verification')
            ->where('id', $verificationId)
            ->update($updateData) >= 0;
    }

    /**
     * Verify a researcher (mark verification as verified).
     *
     * @param int $verificationId The verification ID
     * @param int $verifiedBy User ID who verified
     * @param string|null $notes Optional notes
     * @return bool Success status
     */
    public function verifyResearcher(int $verificationId, int $verifiedBy, ?string $notes = null): bool
    {
        $verification = DB::table('research_verification')
            ->where('id', $verificationId)
            ->first();

        if (!$verification) {
            return false;
        }

        // Update verification
        $updated = DB::table('research_verification')
            ->where('id', $verificationId)
            ->update([
                'status' => 'verified',
                'verified_by' => $verifiedBy,
                'verified_at' => date('Y-m-d H:i:s'),
                'notes' => $notes,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;

        if ($updated && $verification->verification_type === 'id_document') {
            // Update researcher ID verification status
            DB::table('research_researcher')
                ->where('id', $verification->researcher_id)
                ->update([
                    'id_verified' => 1,
                    'id_verified_by' => $verifiedBy,
                    'id_verified_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        return $updated;
    }

    /**
     * Reject a verification.
     *
     * @param int $verificationId The verification ID
     * @param int $rejectedBy User ID who rejected
     * @param string $reason Rejection reason
     * @return bool Success status
     */
    public function rejectVerification(int $verificationId, int $rejectedBy, string $reason): bool
    {
        return DB::table('research_verification')
            ->where('id', $verificationId)
            ->update([
                'status' => 'rejected',
                'verified_by' => $rejectedBy,
                'verified_at' => date('Y-m-d H:i:s'),
                'rejection_reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Check if a researcher has a valid verification of a specific type.
     *
     * @param int $researcherId The researcher ID
     * @param string $verificationType The verification type
     * @return bool True if valid verification exists
     */
    public function hasValidVerification(int $researcherId, string $verificationType): bool
    {
        return DB::table('research_verification')
            ->where('researcher_id', $researcherId)
            ->where('verification_type', $verificationType)
            ->where('status', 'verified')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', date('Y-m-d'));
            })
            ->exists();
    }

    /**
     * Get expired verifications that need renewal.
     *
     * @param int $daysBeforeExpiry Days before expiry to include
     * @return array List of expiring verifications
     */
    public function getExpiringVerifications(int $daysBeforeExpiry = 30): array
    {
        $expiryDate = date('Y-m-d', strtotime("+{$daysBeforeExpiry} days"));

        return DB::table('research_verification as v')
            ->join('research_researcher as r', 'v.researcher_id', '=', 'r.id')
            ->where('v.status', 'verified')
            ->whereNotNull('v.expires_at')
            ->where('v.expires_at', '<=', $expiryDate)
            ->where('v.expires_at', '>', date('Y-m-d'))
            ->select(
                'v.*',
                'r.first_name',
                'r.last_name',
                'r.email'
            )
            ->orderBy('v.expires_at')
            ->get()
            ->toArray();
    }

    // =========================================================================
    // API KEY MANAGEMENT
    // =========================================================================

    /**
     * Generate an API key for a researcher.
     *
     * @param int $researcherId The researcher ID
     * @param string $name Key name/description
     * @param array|null $permissions Optional permissions array
     * @param int $expiryDays Days until expiry (default 365)
     * @return array The generated key info
     */
    public function generateApiKey(int $researcherId, string $name, ?array $permissions = null, int $expiryDays = 365): array
    {
        $apiKey = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));

        $keyId = DB::table('research_api_key')->insertGetId([
            'researcher_id' => $researcherId,
            'name' => $name,
            'api_key' => $apiKey,
            'permissions' => $permissions ? json_encode($permissions) : null,
            'rate_limit' => 1000,
            'expires_at' => $expiresAt,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'id' => $keyId,
            'api_key' => $apiKey,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Validate an API key.
     *
     * @param string $apiKey The API key
     * @return object|null Researcher info if valid, null if invalid
     */
    public function validateApiKey(string $apiKey): ?object
    {
        $key = DB::table('research_api_key as k')
            ->join('research_researcher as r', 'k.researcher_id', '=', 'r.id')
            ->where('k.api_key', $apiKey)
            ->where('k.is_active', 1)
            ->where(function ($q) {
                $q->whereNull('k.expires_at')
                    ->orWhere('k.expires_at', '>', date('Y-m-d H:i:s'));
            })
            ->where('r.status', 'approved')
            ->select('r.*', 'k.id as api_key_id', 'k.permissions as api_permissions', 'k.rate_limit')
            ->first();

        if ($key) {
            // Update last used
            DB::table('research_api_key')
                ->where('api_key', $apiKey)
                ->update([
                    'last_used_at' => date('Y-m-d H:i:s'),
                    'request_count' => DB::raw('request_count + 1'),
                ]);
        }

        return $key;
    }

    /**
     * Get API keys for a researcher.
     *
     * @param int $researcherId The researcher ID
     * @return array List of API keys (without the actual key value)
     */
    public function getApiKeys(int $researcherId): array
    {
        return DB::table('research_api_key')
            ->where('researcher_id', $researcherId)
            ->select('id', 'name', 'permissions', 'rate_limit', 'last_used_at', 'request_count', 'expires_at', 'is_active', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Revoke an API key.
     *
     * @param int $keyId The key ID
     * @param int $researcherId The researcher ID (for verification)
     * @return bool Success status
     */
    public function revokeApiKey(int $keyId, int $researcherId): bool
    {
        return DB::table('research_api_key')
            ->where('id', $keyId)
            ->where('researcher_id', $researcherId)
            ->update(['is_active' => 0]) > 0;
    }
}
