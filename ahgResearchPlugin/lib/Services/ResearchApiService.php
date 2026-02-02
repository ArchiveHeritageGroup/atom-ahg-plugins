<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ResearchApiService - REST API Service
 *
 * Handles API authentication and provides methods for REST endpoints.
 *
 * @package ahgResearchPlugin
 * @version 2.0.0
 */
class ResearchApiService
{
    private ?object $authenticatedResearcher = null;
    private ?int $apiKeyId = null;

    // =========================================================================
    // AUTHENTICATION
    // =========================================================================

    /**
     * Authenticate request using API key.
     *
     * @param string $apiKey The API key
     * @return array Result with researcher or error
     */
    public function authenticate(string $apiKey): array
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

        if (!$key) {
            return ['success' => false, 'error' => 'Invalid or expired API key'];
        }

        // Update usage stats
        DB::table('research_api_key')
            ->where('id', $key->api_key_id)
            ->update([
                'last_used_at' => date('Y-m-d H:i:s'),
                'request_count' => DB::raw('request_count + 1'),
            ]);

        $this->authenticatedResearcher = $key;
        $this->apiKeyId = $key->api_key_id;

        return ['success' => true, 'researcher' => $key];
    }

    /**
     * Log an API request.
     *
     * @param string $endpoint The endpoint called
     * @param string $method HTTP method
     * @param array|null $params Request parameters
     * @param int $responseCode HTTP response code
     * @param int $responseTimeMs Response time in milliseconds
     */
    public function logRequest(
        string $endpoint,
        string $method,
        ?array $params,
        int $responseCode,
        int $responseTimeMs
    ): void {
        DB::table('research_api_log')->insert([
            'api_key_id' => $this->apiKeyId,
            'researcher_id' => $this->authenticatedResearcher->id ?? null,
            'endpoint' => $endpoint,
            'method' => $method,
            'request_params' => $params ? json_encode($params) : null,
            'response_code' => $responseCode,
            'response_time_ms' => $responseTimeMs,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check if a permission is allowed for the current API key.
     *
     * @param string $permission Permission to check
     * @return bool True if allowed
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->authenticatedResearcher) {
            return false;
        }

        $permissions = json_decode($this->authenticatedResearcher->api_permissions ?? '[]', true);

        // Empty permissions means all allowed
        if (empty($permissions)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }

    // =========================================================================
    // PROFILE ENDPOINTS
    // =========================================================================

    /**
     * GET /api/research/profile
     *
     * Get authenticated researcher's profile.
     *
     * @return array Profile data
     */
    public function getProfile(): array
    {
        if (!$this->authenticatedResearcher) {
            return ['error' => 'Not authenticated'];
        }

        $r = $this->authenticatedResearcher;

        return [
            'id' => $r->id,
            'first_name' => $r->first_name,
            'last_name' => $r->last_name,
            'email' => $r->email,
            'affiliation_type' => $r->affiliation_type,
            'institution' => $r->institution,
            'department' => $r->department,
            'position' => $r->position,
            'orcid_id' => $r->orcid_id,
            'orcid_verified' => (bool) $r->orcid_verified,
            'status' => $r->status,
            'expires_at' => $r->expires_at,
            'created_at' => $r->created_at,
        ];
    }

    // =========================================================================
    // PROJECT ENDPOINTS
    // =========================================================================

    /**
     * GET /api/research/projects
     *
     * Get researcher's projects.
     *
     * @param array $params Query parameters (status, type, limit, offset)
     * @return array Projects list
     */
    public function getProjects(array $params = []): array
    {
        if (!$this->authenticatedResearcher) {
            return ['error' => 'Not authenticated'];
        }

        $limit = min((int) ($params['limit'] ?? 50), 100);
        $offset = (int) ($params['offset'] ?? 0);

        $query = DB::table('research_project as p')
            ->join('research_project_collaborator as pc', function ($join) {
                $join->on('p.id', '=', 'pc.project_id')
                    ->where('pc.researcher_id', '=', $this->authenticatedResearcher->id)
                    ->where('pc.status', '=', 'accepted');
            })
            ->select('p.id', 'p.title', 'p.description', 'p.project_type', 'p.status', 'p.visibility', 'p.created_at', 'p.updated_at', 'pc.role as my_role');

        if (!empty($params['status'])) {
            $query->where('p.status', $params['status']);
        }

        if (!empty($params['type'])) {
            $query->where('p.project_type', $params['type']);
        }

        $total = (clone $query)->count();
        $projects = $query->orderBy('p.updated_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();

        return [
            'data' => $projects,
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ];
    }

    /**
     * POST /api/research/projects
     *
     * Create a new project.
     *
     * @param array $data Project data
     * @return array Created project
     */
    public function createProject(array $data): array
    {
        if (!$this->authenticatedResearcher) {
            return ['error' => 'Not authenticated'];
        }

        if (!$this->hasPermission('projects.create')) {
            return ['error' => 'Permission denied'];
        }

        if (empty($data['title'])) {
            return ['error' => 'Title is required'];
        }

        $projectId = DB::table('research_project')->insertGetId([
            'owner_id' => $this->authenticatedResearcher->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'project_type' => $data['project_type'] ?? 'personal',
            'institution' => $data['institution'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'expected_end_date' => $data['expected_end_date'] ?? null,
            'status' => 'planning',
            'visibility' => $data['visibility'] ?? 'private',
            'share_token' => bin2hex(random_bytes(32)),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Add owner as collaborator
        DB::table('research_project_collaborator')->insert([
            'project_id' => $projectId,
            'researcher_id' => $this->authenticatedResearcher->id,
            'role' => 'owner',
            'invited_by' => $this->authenticatedResearcher->id,
            'invited_at' => date('Y-m-d H:i:s'),
            'accepted_at' => date('Y-m-d H:i:s'),
            'status' => 'accepted',
        ]);

        return [
            'id' => $projectId,
            'message' => 'Project created successfully',
        ];
    }

    // =========================================================================
    // COLLECTION ENDPOINTS
    // =========================================================================

    /**
     * GET /api/research/collections
     *
     * Get researcher's collections.
     *
     * @param array $params Query parameters
     * @return array Collections list
     */
    public function getCollections(array $params = []): array
    {
        if (!$this->authenticatedResearcher) {
            return ['error' => 'Not authenticated'];
        }

        $limit = min((int) ($params['limit'] ?? 50), 100);
        $offset = (int) ($params['offset'] ?? 0);

        $query = DB::table('research_collection')
            ->where('researcher_id', $this->authenticatedResearcher->id)
            ->select('id', 'name', 'description', 'is_public', 'created_at', 'updated_at');

        $total = (clone $query)->count();
        $collections = $query->orderBy('name')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();

        // Add item counts
        foreach ($collections as &$collection) {
            $collection->item_count = DB::table('research_collection_item')
                ->where('collection_id', $collection->id)
                ->count();
        }

        return [
            'data' => $collections,
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ];
    }

    /**
     * POST /api/research/collections
     *
     * Create a new collection.
     *
     * @param array $data Collection data
     * @return array Created collection
     */
    public function createCollection(array $data): array
    {
        if (!$this->authenticatedResearcher) {
            return ['error' => 'Not authenticated'];
        }

        if (!$this->hasPermission('collections.create')) {
            return ['error' => 'Permission denied'];
        }

        if (empty($data['name'])) {
            return ['error' => 'Name is required'];
        }

        $collectionId = DB::table('research_collection')->insertGetId([
            'researcher_id' => $this->authenticatedResearcher->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_public' => $data['is_public'] ?? 0,
            'share_token' => bin2hex(random_bytes(32)),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'id' => $collectionId,
            'message' => 'Collection created successfully',
        ];
    }

    /**
     * GET /api/research/collections/:id
     *
     * Get a specific collection with items.
     *
     * @param int $collectionId The collection ID
     * @return array Collection data
     */
    public function getCollection(int $collectionId): array
    {
        if (!$this->authenticatedResearcher) {
            return ['error' => 'Not authenticated'];
        }

        $collection = DB::table('research_collection')
            ->where('id', $collectionId)
            ->where('researcher_id', $this->authenticatedResearcher->id)
            ->first();

        if (!$collection) {
            return ['error' => 'Collection not found'];
        }

        $items = DB::table('research_collection_item as ci')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('ci.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'ci.object_id', '=', 'slug.object_id')
            ->where('ci.collection_id', $collectionId)
            ->select('ci.id', 'ci.object_id', 'ioi.title', 'slug.slug', 'ci.notes', 'ci.created_at')
            ->orderBy('ci.sort_order')
            ->get()
            ->toArray();

        return [
            'id' => $collection->id,
            'name' => $collection->name,
            'description' => $collection->description,
            'is_public' => (bool) $collection->is_public,
            'created_at' => $collection->created_at,
            'items' => $items,
        ];
    }

    // =========================================================================
    // SAVED SEARCH ENDPOINTS
    // =========================================================================

    /**
     * GET /api/research/searches
     *
     * Get researcher's saved searches.
     *
     * @param array $params Query parameters
     * @return array Saved searches list
     */
    public function getSearches(array $params = []): array
    {
        if (!$this->authenticatedResearcher) {
            return ['error' => 'Not authenticated'];
        }

        $searches = DB::table('research_saved_search')
            ->where('researcher_id', $this->authenticatedResearcher->id)
            ->select('id', 'name', 'description', 'search_query', 'search_type', 'alert_enabled', 'alert_frequency', 'new_results_count', 'created_at', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->toArray();

        return ['data' => $searches];
    }

    // =========================================================================
    // BOOKING ENDPOINTS
    // =========================================================================

    /**
     * GET /api/research/bookings
     *
     * Get researcher's bookings.
     *
     * @param array $params Query parameters (status, date_from, date_to)
     * @return array Bookings list
     */
    public function getBookings(array $params = []): array
    {
        if (!$this->authenticatedResearcher) {
            return ['error' => 'Not authenticated'];
        }

        $query = DB::table('research_booking as b')
            ->join('research_reading_room as r', 'b.reading_room_id', '=', 'r.id')
            ->where('b.researcher_id', $this->authenticatedResearcher->id)
            ->select('b.id', 'b.booking_date', 'b.start_time', 'b.end_time', 'b.status', 'b.purpose', 'r.name as room_name', 'r.location as room_location', 'b.created_at');

        if (!empty($params['status'])) {
            $query->where('b.status', $params['status']);
        }

        if (!empty($params['date_from'])) {
            $query->where('b.booking_date', '>=', $params['date_from']);
        }

        if (!empty($params['date_to'])) {
            $query->where('b.booking_date', '<=', $params['date_to']);
        }

        $bookings = $query->orderBy('b.booking_date', 'desc')
            ->orderBy('b.start_time')
            ->get()
            ->toArray();

        return ['data' => $bookings];
    }

    /**
     * POST /api/research/bookings
     *
     * Create a new booking.
     *
     * @param array $data Booking data
     * @return array Created booking
     */
    public function createBooking(array $data): array
    {
        if (!$this->authenticatedResearcher) {
            return ['error' => 'Not authenticated'];
        }

        if (!$this->hasPermission('bookings.create')) {
            return ['error' => 'Permission denied'];
        }

        // Validate required fields
        $required = ['reading_room_id', 'booking_date', 'start_time', 'end_time'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['error' => "Field '{$field}' is required"];
            }
        }

        // Validate reading room exists
        $room = DB::table('research_reading_room')
            ->where('id', $data['reading_room_id'])
            ->where('is_active', 1)
            ->first();

        if (!$room) {
            return ['error' => 'Invalid reading room'];
        }

        // Check for conflicts
        $conflict = DB::table('research_booking')
            ->where('reading_room_id', $data['reading_room_id'])
            ->where('booking_date', $data['booking_date'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($data) {
                $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                    ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']]);
            })
            ->exists();

        if ($conflict) {
            return ['error' => 'Time slot is not available'];
        }

        $bookingId = DB::table('research_booking')->insertGetId([
            'researcher_id' => $this->authenticatedResearcher->id,
            'reading_room_id' => $data['reading_room_id'],
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'purpose' => $data['purpose'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'id' => $bookingId,
            'message' => 'Booking created successfully',
        ];
    }

    // =========================================================================
    // CITATION ENDPOINTS
    // =========================================================================

    /**
     * GET /api/research/citations/:id/:format
     *
     * Generate a citation for an object.
     *
     * @param int $objectId The object ID
     * @param string $format Citation format (chicago, mla, apa, harvard, unisa, turabian)
     * @return array Citation data
     */
    public function getCitation(int $objectId, string $format = 'chicago'): array
    {
        // Load the research service
        require_once dirname(__FILE__) . '/ResearchService.php';
        $researchService = new ResearchService();

        $result = $researchService->generateCitation($objectId, $format);

        if (isset($result['error'])) {
            return $result;
        }

        // Log the citation
        $researchService->logCitation(
            $this->authenticatedResearcher->id ?? null,
            $objectId,
            $format,
            $result['citation'] ?? ''
        );

        return $result;
    }

    // =========================================================================
    // BIBLIOGRAPHY ENDPOINTS
    // =========================================================================

    /**
     * GET /api/research/bibliographies
     *
     * Get researcher's bibliographies.
     *
     * @return array Bibliographies list
     */
    public function getBibliographies(): array
    {
        if (!$this->authenticatedResearcher) {
            return ['error' => 'Not authenticated'];
        }

        $bibliographies = DB::table('research_bibliography')
            ->where('researcher_id', $this->authenticatedResearcher->id)
            ->select('id', 'name', 'description', 'citation_style', 'is_public', 'created_at', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->toArray();

        foreach ($bibliographies as &$bib) {
            $bib->entry_count = DB::table('research_bibliography_entry')
                ->where('bibliography_id', $bib->id)
                ->count();
        }

        return ['data' => $bibliographies];
    }

    /**
     * GET /api/research/bibliographies/:id/export/:format
     *
     * Export a bibliography in a specific format.
     *
     * @param int $bibliographyId The bibliography ID
     * @param string $format Export format (ris, bibtex, csl-json, zotero, mendeley)
     * @return array Export result with content and mime type
     */
    public function exportBibliography(int $bibliographyId, string $format): array
    {
        if (!$this->authenticatedResearcher) {
            return ['error' => 'Not authenticated'];
        }

        // Verify ownership
        $bibliography = DB::table('research_bibliography')
            ->where('id', $bibliographyId)
            ->where('researcher_id', $this->authenticatedResearcher->id)
            ->first();

        if (!$bibliography) {
            return ['error' => 'Bibliography not found'];
        }

        require_once dirname(__FILE__) . '/BibliographyService.php';
        $bibliographyService = new BibliographyService();

        $content = match (strtolower($format)) {
            'ris' => $bibliographyService->exportRIS($bibliographyId),
            'bibtex' => $bibliographyService->exportBibTeX($bibliographyId),
            'csl-json', 'csl' => $bibliographyService->exportCSLJSON($bibliographyId),
            'zotero', 'rdf' => $bibliographyService->exportZoteroRDF($bibliographyId),
            'mendeley', 'json' => $bibliographyService->exportMendeleyJSON($bibliographyId),
            default => null,
        };

        if ($content === null) {
            return ['error' => 'Invalid export format'];
        }

        $mimeTypes = [
            'ris' => 'application/x-research-info-systems',
            'bibtex' => 'application/x-bibtex',
            'csl-json' => 'application/json',
            'csl' => 'application/json',
            'zotero' => 'application/rdf+xml',
            'rdf' => 'application/rdf+xml',
            'mendeley' => 'application/json',
            'json' => 'application/json',
        ];

        return [
            'content' => $content,
            'mime_type' => $mimeTypes[strtolower($format)] ?? 'text/plain',
            'filename' => $bibliography->name . '.' . strtolower($format),
        ];
    }

    // =========================================================================
    // ANNOTATION ENDPOINTS
    // =========================================================================

    /**
     * GET /api/research/annotations
     *
     * Get researcher's annotations.
     *
     * @param array $params Query parameters (object_id)
     * @return array Annotations list
     */
    public function getAnnotations(array $params = []): array
    {
        if (!$this->authenticatedResearcher) {
            return ['error' => 'Not authenticated'];
        }

        $query = DB::table('research_annotation as a')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('a.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'a.object_id', '=', 'slug.object_id')
            ->where('a.researcher_id', $this->authenticatedResearcher->id)
            ->select('a.id', 'a.object_id', 'ioi.title as object_title', 'slug.slug', 'a.annotation_type', 'a.title', 'a.content', 'a.tags', 'a.is_private', 'a.created_at');

        if (!empty($params['object_id'])) {
            $query->where('a.object_id', $params['object_id']);
        }

        $annotations = $query->orderBy('a.created_at', 'desc')
            ->get()
            ->toArray();

        return ['data' => $annotations];
    }

    // =========================================================================
    // STATISTICS ENDPOINT
    // =========================================================================

    /**
     * GET /api/research/stats
     *
     * Get researcher's statistics.
     *
     * @return array Statistics
     */
    public function getStats(): array
    {
        if (!$this->authenticatedResearcher) {
            return ['error' => 'Not authenticated'];
        }

        require_once dirname(__FILE__) . '/StatisticsService.php';
        $statisticsService = new StatisticsService();

        return $statisticsService->getResearcherStats($this->authenticatedResearcher->id);
    }
}
