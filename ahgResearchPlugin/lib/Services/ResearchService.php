<?php
use Illuminate\Database\Capsule\Manager as DB;

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
        return $query->select('a.*', 'i18n.title as object_title', 'slug.slug')
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
            $userId = null;
            $username = null;
            if (class_exists('sfContext') && \sfContext::hasInstance()) {
                $user = \sfContext::getInstance()->getUser();
                if ($user && $user->isAuthenticated()) {
                    $userId = $user->getAttribute('user_id');
                    if ($userId) {
                        $userRecord = DB::table('user')->where('id', $userId)->first();
                        $username = $userRecord->username ?? null;
                    }
                }
            }

            $changedFields = [];
            foreach ($newValues as $key => $val) {
                if (($oldValues[$key] ?? null) !== $val) $changedFields[] = $key;
            }
            if ($action === 'delete') $changedFields = array_keys($oldValues);

            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', 
                mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), 
                mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, 
                mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));

            DB::table('ahg_audit_log')->insert([
                'uuid' => $uuid,
                'user_id' => $userId,
                'username' => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'session_id' => session_id() ?: null,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'entity_title' => $title,
                'module' => 'ahgResearchPlugin',
                'action_name' => $action,
                'old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
                'new_values' => !empty($newValues) ? json_encode($newValues) : null,
                'changed_fields' => !empty($changedFields) ? json_encode($changedFields) : null,
                'status' => 'success',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            error_log("ResearchService AUDIT ERROR: " . $e->getMessage());
        }
    }
}
