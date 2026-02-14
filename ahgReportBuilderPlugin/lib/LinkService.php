<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Link Service for Report Builder.
 *
 * Manages external URLs and internal cross-references for reports.
 * Supports OpenGraph metadata fetching and AtoM entity resolution.
 */
class LinkService
{
    private string $culture;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    /**
     * Get all links for a report (optionally filtered by section).
     *
     * @param int      $reportId  The report ID
     * @param int|null $sectionId Optional section ID filter
     *
     * @return array The links
     */
    public function getLinks(int $reportId, ?int $sectionId = null): array
    {
        $query = DB::table('report_link')
            ->where('report_id', $reportId)
            ->orderBy('position');

        if ($sectionId !== null) {
            $query->where('section_id', $sectionId);
        }

        return $query->get()->toArray();
    }

    /**
     * Create a new link.
     *
     * @param array $data The link data
     *
     * @return int The new link ID
     */
    public function create(array $data): int
    {
        $maxPosition = DB::table('report_link')
            ->where('report_id', $data['report_id'])
            ->max('position') ?? -1;

        return DB::table('report_link')->insertGetId([
            'report_id' => $data['report_id'],
            'section_id' => $data['section_id'] ?? null,
            'link_type' => $data['link_type'] ?? 'external',
            'url' => $data['url'] ?? null,
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'target_id' => $data['target_id'] ?? null,
            'target_slug' => $data['target_slug'] ?? null,
            'link_category' => $data['link_category'] ?? 'reference',
            'og_image' => $data['og_image'] ?? null,
            'position' => $data['position'] ?? ($maxPosition + 1),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a link.
     *
     * @param int   $linkId The link ID
     * @param array $data   The data to update
     *
     * @return bool True if updated
     */
    public function update(int $linkId, array $data): bool
    {
        $updateData = [];

        $fields = ['title', 'url', 'description', 'link_type', 'target_id',
                   'target_slug', 'link_category', 'og_image', 'position', 'section_id'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        return DB::table('report_link')
            ->where('id', $linkId)
            ->update($updateData) > 0;
    }

    /**
     * Delete a link.
     *
     * @param int $linkId The link ID
     *
     * @return bool True if deleted
     */
    public function delete(int $linkId): bool
    {
        return DB::table('report_link')
            ->where('id', $linkId)
            ->delete() > 0;
    }

    /**
     * Fetch OpenGraph metadata from a URL.
     *
     * @param string $url The URL to fetch
     *
     * @return array The OG metadata (title, description, image)
     */
    public function fetchOpenGraph(string $url): array
    {
        $result = [
            'title' => '',
            'description' => '',
            'image' => '',
            'url' => $url,
        ];

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $result;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'AtoM Report Builder/1.0',
                'follow_location' => true,
                'max_redirects' => 3,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $html = @file_get_contents($url, false, $context);
        if (!$html) {
            return $result;
        }

        // Limit to first 50KB
        $html = substr($html, 0, 50000);

        // Parse OG tags
        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $m)) {
            $result['title'] = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        }
        if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $m)) {
            $result['description'] = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        }
        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $m)) {
            $result['image'] = $m[1];
        }

        // Fallback to <title> tag
        if (empty($result['title']) && preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $m)) {
            $result['title'] = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
        }

        // Fallback to meta description
        if (empty($result['description'])) {
            if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $m)) {
                $result['description'] = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
            }
        }

        return $result;
    }

    /**
     * Search AtoM entities for internal cross-references.
     *
     * @param string $query      The search query
     * @param string $entityType The entity type (information_object, actor, repository, accession)
     * @param int    $limit      Max results
     *
     * @return array The matching entities
     */
    public function searchEntities(string $query, string $entityType = 'information_object', int $limit = 10): array
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return [];
        }

        switch ($entityType) {
            case 'information_object':
                return $this->searchInformationObjects($query, $limit);
            case 'actor':
                return $this->searchActors($query, $limit);
            case 'repository':
                return $this->searchRepositories($query, $limit);
            case 'accession':
                return $this->searchAccessions($query, $limit);
            default:
                return [];
        }
    }

    private function searchInformationObjects(string $query, int $limit): array
    {
        return DB::table('information_object as io')
            ->join('information_object_i18n as i', function ($join) {
                $join->on('io.id', '=', 'i.id')
                     ->where('i.culture', $this->culture);
            })
            ->leftJoin('slug as s', function ($join) {
                $join->on('io.id', '=', 's.object_id');
            })
            ->where('i.title', 'LIKE', "%{$query}%")
            ->select('io.id', 'i.title as label', 's.slug', 'io.identifier')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => $item->label . ($item->identifier ? " ({$item->identifier})" : ''),
                    'slug' => $item->slug,
                    'type' => 'information_object',
                ];
            })
            ->toArray();
    }

    private function searchActors(string $query, int $limit): array
    {
        return DB::table('actor as a')
            ->join('actor_i18n as i', function ($join) {
                $join->on('a.id', '=', 'i.id')
                     ->where('i.culture', $this->culture);
            })
            ->leftJoin('slug as s', function ($join) {
                $join->on('a.id', '=', 's.object_id');
            })
            ->where('i.authorized_form_of_name', 'LIKE', "%{$query}%")
            ->select('a.id', 'i.authorized_form_of_name as label', 's.slug')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => $item->label,
                    'slug' => $item->slug,
                    'type' => 'actor',
                ];
            })
            ->toArray();
    }

    private function searchRepositories(string $query, int $limit): array
    {
        return DB::table('repository as r')
            ->join('actor_i18n as i', function ($join) {
                $join->on('r.id', '=', 'i.id')
                     ->where('i.culture', $this->culture);
            })
            ->leftJoin('slug as s', function ($join) {
                $join->on('r.id', '=', 's.object_id');
            })
            ->where('i.authorized_form_of_name', 'LIKE', "%{$query}%")
            ->select('r.id', 'i.authorized_form_of_name as label', 's.slug')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => $item->label,
                    'slug' => $item->slug,
                    'type' => 'repository',
                ];
            })
            ->toArray();
    }

    private function searchAccessions(string $query, int $limit): array
    {
        return DB::table('accession as a')
            ->join('accession_i18n as i', function ($join) {
                $join->on('a.id', '=', 'i.id')
                     ->where('i.culture', $this->culture);
            })
            ->leftJoin('slug as s', function ($join) {
                $join->on('a.id', '=', 's.object_id');
            })
            ->where(function ($q) use ($query) {
                $q->where('i.title', 'LIKE', "%{$query}%")
                  ->orWhere('a.identifier', 'LIKE', "%{$query}%");
            })
            ->select('a.id', 'i.title as label', 's.slug', 'a.identifier')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => $item->label . ($item->identifier ? " ({$item->identifier})" : ''),
                    'slug' => $item->slug,
                    'type' => 'accession',
                ];
            })
            ->toArray();
    }
}
