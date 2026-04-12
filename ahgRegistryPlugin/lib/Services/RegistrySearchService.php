<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class RegistrySearchService
{
    protected string $culture;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    // =========================================================================
    // Unified Search
    // =========================================================================

    /**
     * Unified full-text search across all registry entity types.
     *
     * @param string $query   The search query
     * @param array  $params  Optional filters: type (entity type filter), limit, page
     * @return array ['items' => [...], 'total' => int, 'query' => string]
     */
    public function search(string $query, array $params = []): array
    {
        $query = trim($query);
        if (empty($query)) {
            return ['items' => [], 'total' => 0, 'query' => ''];
        }

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        // Determine which entity types to search
        $typeFilter = $params['type'] ?? null;
        $validTypes = ['institution', 'vendor', 'software', 'instance', 'user_group', 'discussion', 'blog_post'];

        if ($typeFilter && in_array($typeFilter, $validTypes)) {
            $searchTypes = [$typeFilter];
        } else {
            $searchTypes = $validTypes;
        }

        $allResults = [];

        // Search each entity type
        foreach ($searchTypes as $entityType) {
            $results = $this->searchEntityType($entityType, $query);
            $allResults = array_merge($allResults, $results);
        }

        // Bubble up parent institutions from matched instances so searching for
        // "wits", "rari.wits.ac.za" or a specific deployment surfaces the owning
        // institution too (e.g. RARI instance → University of the Witwatersrand).
        $allResults = $this->promoteInstanceParents($allResults);

        // Sort by relevance (score descending)
        usort($allResults, function ($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });

        $total = count($allResults);

        // Apply pagination
        $items = array_slice($allResults, $offset, $limit);

        return [
            'items' => $items,
            'total' => $total,
            'query' => $query,
        ];
    }

    // =========================================================================
    // Per-Entity Search
    // =========================================================================

    /**
     * Search a specific entity type and return normalized results.
     */
    private function searchEntityType(string $entityType, string $query): array
    {
        switch ($entityType) {
            case 'institution':
                return $this->searchInstitutions($query);

            case 'vendor':
                return $this->searchVendors($query);

            case 'software':
                return $this->searchSoftware($query);

            case 'instance':
                return $this->searchInstances($query);

            case 'user_group':
                return $this->searchUserGroups($query);

            case 'discussion':
                return $this->searchDiscussions($query);

            case 'blog_post':
                return $this->searchBlogPosts($query);

            default:
                return [];
        }
    }

    /**
     * Search institutions.
     *
     * Ranking prioritises (in order): exact name match, prefix name match,
     * substring name match, FULLTEXT name match, FULLTEXT body match.
     * This fixes the case where "northern bc archives" used to return 61
     * fuzzy-matched results before the target institution's page.
     */
    private function searchInstitutions(string $query): array
    {
        $likeTerm = '%' . $this->decodeEntities($query) . '%';

        $items = DB::table('registry_institution')
            ->where('is_active', 1)
            ->where(function ($q) use ($query, $likeTerm) {
                $q->whereRaw("MATCH(name, description, collection_summary) AGAINST(? IN BOOLEAN MODE)", [$query])
                  ->orWhere('name', 'LIKE', $likeTerm);
            })
            ->select('id', 'name', 'slug', 'short_description', 'institution_type', 'country', 'logo_path')
            ->limit(100)
            ->get()
            ->all();

        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'entity_type' => 'institution',
                'id' => $item->id,
                'title' => $this->cleanText($item->name),
                'excerpt' => $this->cleanText($item->short_description),
                'url' => '/registry/institutions/' . $item->slug,
                'meta' => [
                    'slug' => $item->slug,
                    'type' => $item->institution_type,
                    'country' => $item->country,
                    'logo' => $item->logo_path,
                ],
                'relevance' => $this->scoreName($item->name ?? '', $query),
            ];
        }

        return $results;
    }

    /**
     * For every matched instance, ensure its parent institution is also in the
     * result list (inserted just after the instance, deduped). Fixes the case
     * where searching "wits" finds the RARI instance but not its parent
     * University of the Witwatersrand.
     */
    private function promoteInstanceParents(array $results): array
    {
        $seenInstitutionIds = [];
        foreach ($results as $r) {
            if (($r['entity_type'] ?? '') === 'institution') {
                $seenInstitutionIds[(int) ($r['id'] ?? 0)] = true;
            }
        }

        $instanceInstitutionIds = [];
        foreach ($results as $r) {
            if (($r['entity_type'] ?? '') === 'instance' && !empty($r['meta']['institution_slug'])) {
                $instId = 0;
                $slug = $r['meta']['institution_slug'];
                $inst = DB::table('registry_institution')->where('slug', $slug)->first();
                if ($inst) {
                    $instId = (int) $inst->id;
                }
                if ($instId > 0 && empty($seenInstitutionIds[$instId])) {
                    $instanceInstitutionIds[$instId] = $inst;
                }
            }
        }

        if (empty($instanceInstitutionIds)) {
            return $results;
        }

        $promoted = [];
        foreach ($instanceInstitutionIds as $instId => $inst) {
            $promoted[] = [
                'entity_type' => 'institution',
                'id' => (int) $inst->id,
                'title' => $this->cleanText($inst->name),
                'excerpt' => $this->cleanText($inst->short_description ?? ''),
                'url' => '/registry/institutions/' . $inst->slug,
                'meta' => [
                    'slug' => $inst->slug,
                    'type' => $inst->institution_type ?? '',
                    'country' => $inst->country ?? '',
                    'logo' => $inst->logo_path ?? '',
                    'promoted_from_instance' => true,
                ],
                // Promoted institutions sit just above the highest-scoring instance
                // match so they rank as directly-relevant, not fall to the bottom.
                'relevance' => 600.0,
            ];
            $seenInstitutionIds[$instId] = true;
        }

        return array_merge($results, $promoted);
    }

    /**
     * Decode HTML entities and strip tags from user-visible search values.
     * Guards against double-encoded names ("Archives &amp; Special Collections")
     * leaking through from the data import stage.
     */
    private function cleanText(?string $text): string
    {
        if (null === $text || '' === $text) {
            return '';
        }
        $decoded = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return strip_tags($decoded);
    }

    private function decodeEntities(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Score a candidate name against a query. Higher is more relevant.
     *
     * Priority order:
     *   1. Exact match (case-insensitive)        → 1000
     *   2. Name starts with query                → 500
     *   3. Query is a whole-word match in name   → 300
     *   4. Query is a substring of name          → 150
     *   5. At least one query word matches name  → 30 per word
     *   6. Shorter names win ties                → +(100 - len(name))
     */
    private function scoreName(string $name, string $query): float
    {
        $cleanName = mb_strtolower($this->cleanText($name));
        $cleanQuery = mb_strtolower($this->decodeEntities(trim($query)));

        if ('' === $cleanName || '' === $cleanQuery) {
            return 0.0;
        }

        $score = 0.0;

        if ($cleanName === $cleanQuery) {
            $score += 1000;
        } elseif (0 === mb_strpos($cleanName, $cleanQuery)) {
            $score += 500;
        } elseif (preg_match('/\b' . preg_quote($cleanQuery, '/') . '\b/u', $cleanName)) {
            $score += 300;
        } elseif (false !== mb_strpos($cleanName, $cleanQuery)) {
            $score += 150;
        }

        // Per-word boost (helps multi-word queries where no single contiguous match exists)
        $words = preg_split('/\s+/u', $cleanQuery, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($words as $word) {
            if (mb_strlen($word) < 2) {
                continue;
            }
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $cleanName)) {
                $score += 30;
            }
        }

        // Tiebreaker: prefer shorter names so "Northern BC Archives" beats
        // "Museum of Anthropology, Audrey and Harry Hawthorn Library and Archives..."
        $score += max(0, 120 - mb_strlen($cleanName)) / 10;

        return $score;
    }

    /**
     * Search vendors.
     */
    private function searchVendors(string $query): array
    {
        $likeTerm = '%' . $this->decodeEntities($query) . '%';

        $items = DB::table('registry_vendor')
            ->where('is_active', 1)
            ->where(function ($q) use ($query, $likeTerm) {
                $q->whereRaw("MATCH(name, description) AGAINST(? IN BOOLEAN MODE)", [$query])
                  ->orWhere('name', 'LIKE', $likeTerm);
            })
            ->select('id', 'name', 'slug', 'short_description', 'vendor_type', 'country', 'logo_path')
            ->limit(100)
            ->get()
            ->all();

        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'entity_type' => 'vendor',
                'id' => $item->id,
                'title' => $this->cleanText($item->name),
                'excerpt' => $this->cleanText($item->short_description),
                'url' => '/registry/vendors/' . $item->slug,
                'meta' => [
                    'slug' => $item->slug,
                    'type' => $item->vendor_type,
                    'country' => $item->country,
                    'logo' => $item->logo_path,
                ],
                'relevance' => $this->scoreName($item->name ?? '', $query),
            ];
        }

        return $results;
    }

    /**
     * Search software.
     */
    private function searchSoftware(string $query): array
    {
        $likeTerm = '%' . $this->decodeEntities($query) . '%';

        $items = DB::table('registry_software')
            ->where('is_active', 1)
            ->where(function ($q) use ($query, $likeTerm) {
                $q->whereRaw("MATCH(name, description) AGAINST(? IN BOOLEAN MODE)", [$query])
                  ->orWhere('name', 'LIKE', $likeTerm);
            })
            ->select('id', 'name', 'slug', 'short_description', 'category', 'logo_path', 'latest_version', 'pricing_model')
            ->limit(100)
            ->get()
            ->all();

        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'entity_type' => 'software',
                'id' => $item->id,
                'title' => $this->cleanText($item->name),
                'excerpt' => $this->cleanText($item->short_description),
                'url' => '/registry/software/' . $item->slug,
                'meta' => [
                    'slug' => $item->slug,
                    'category' => $item->category,
                    'version' => $item->latest_version,
                    'pricing' => $item->pricing_model,
                    'logo' => $item->logo_path,
                ],
                'relevance' => $this->scoreName($item->name ?? '', $query),
            ];
        }

        return $results;
    }

    /**
     * Search system instances.
     *
     * Instances are hosted AtoM sites (e.g., "Rock Art Research Institute")
     * that belong to an institution. Search matches name, URL host, description,
     * and the software-in-use field so "RARI" and "rari.wits.ac.za" both hit.
     * registry_instance has no FULLTEXT index — LIKE-only is fine at this scale.
     */
    private function searchInstances(string $query): array
    {
        $cleanQuery = $this->decodeEntities($query);
        $likeTerm = '%' . $cleanQuery . '%';

        $items = DB::table('registry_instance as i')
            ->leftJoin('registry_institution as ri', 'ri.id', '=', 'i.institution_id')
            ->where('i.is_public', 1)
            ->where(function ($q) use ($likeTerm) {
                $q->where('i.name', 'LIKE', $likeTerm)
                  ->orWhere('i.url', 'LIKE', $likeTerm)
                  ->orWhere('i.description', 'LIKE', $likeTerm)
                  ->orWhere('i.software', 'LIKE', $likeTerm);
            })
            ->select(
                'i.id',
                'i.name',
                'i.url',
                'i.description',
                'i.instance_type',
                'i.software',
                'i.software_version',
                'i.institution_id',
                'i.status',
                'ri.name as institution_name',
                'ri.slug as institution_slug'
            )
            ->limit(100)
            ->get()
            ->all();

        $results = [];
        foreach ($items as $item) {
            $excerpt = $item->description
                ? $this->cleanText($item->description)
                : trim(($item->software ? $item->software : '') . ($item->software_version ? ' ' . $item->software_version : '')
                    . ($item->institution_name ? ' · ' . $this->cleanText($item->institution_name) : ''));

            $results[] = [
                'entity_type' => 'instance',
                'id' => $item->id,
                'title' => $this->cleanText($item->name),
                'excerpt' => $excerpt,
                'url' => '/registry/instances/' . (int) $item->id,
                'meta' => [
                    'url' => $item->url,
                    'type' => $item->instance_type,
                    'software' => $item->software,
                    'version' => $item->software_version,
                    'status' => $item->status,
                    'institution_name' => $item->institution_name,
                    'institution_slug' => $item->institution_slug,
                ],
                'relevance' => $this->scoreName($item->name ?? '', $query)
                    + ($item->url && false !== stripos($item->url, $cleanQuery) ? 200 : 0),
            ];
        }

        return $results;
    }

    /**
     * Search user groups.
     */
    private function searchUserGroups(string $query): array
    {
        $items = DB::table('registry_user_group')
            ->where('is_active', 1)
            ->whereRaw("MATCH(name, description) AGAINST(? IN BOOLEAN MODE)", [$query])
            ->selectRaw("id, name, slug, description, group_type, country, member_count, MATCH(name, description) AGAINST(? IN BOOLEAN MODE) as score", [$query])
            ->orderBy('score', 'desc')
            ->limit(50)
            ->get()
            ->all();

        // LIKE fallback when FULLTEXT returns no results
        $useLikeFallback = empty($items);
        if ($useLikeFallback) {
            $likeTerm = '%' . $query . '%';
            $items = DB::table('registry_user_group')
                ->where('is_active', 1)
                ->where(function ($q) use ($likeTerm) {
                    $q->where('name', 'LIKE', $likeTerm)
                      ->orWhere('description', 'LIKE', $likeTerm)
                      ->orWhere('country', 'LIKE', $likeTerm);
                })
                ->select('id', 'name', 'slug', 'description', 'group_type', 'country', 'member_count')
                ->orderBy('name', 'asc')
                ->limit(50)
                ->get()
                ->all();
        }

        $results = [];
        foreach ($items as $item) {
            $excerpt = $item->description;
            if ($excerpt && strlen($excerpt) > 200) {
                $excerpt = substr($excerpt, 0, 200) . '...';
            }

            $results[] = [
                'entity_type' => 'user_group',
                'id' => $item->id,
                'title' => $item->name,
                'excerpt' => $excerpt,
                'url' => '/registry/groups/' . $item->slug,
                'meta' => [
                    'slug' => $item->slug,
                    'type' => $item->group_type,
                    'country' => $item->country,
                    'members' => $item->member_count,
                ],
                'relevance' => $useLikeFallback ? 1.0 : (float) $item->score,
            ];
        }

        return $results;
    }

    /**
     * Search discussions.
     */
    private function searchDiscussions(string $query): array
    {
        $items = DB::table('registry_discussion as d')
            ->leftJoin('registry_user_group as g', 'g.id', '=', 'd.group_id')
            ->where('d.status', 'active')
            ->where('g.is_active', 1)
            ->whereRaw("MATCH(d.title, d.content) AGAINST(? IN BOOLEAN MODE)", [$query])
            ->selectRaw("d.id, d.title, d.content, d.topic_type, d.reply_count, d.author_name, g.slug as group_slug, g.name as group_name, MATCH(d.title, d.content) AGAINST(? IN BOOLEAN MODE) as score", [$query])
            ->orderBy('score', 'desc')
            ->limit(50)
            ->get()
            ->all();

        // LIKE fallback when FULLTEXT returns no results
        $useLikeFallback = empty($items);
        if ($useLikeFallback) {
            $likeTerm = '%' . $query . '%';
            $items = DB::table('registry_discussion as d')
                ->leftJoin('registry_user_group as g', 'g.id', '=', 'd.group_id')
                ->where('d.status', 'active')
                ->where('g.is_active', 1)
                ->where(function ($q) use ($likeTerm) {
                    $q->where('d.title', 'LIKE', $likeTerm)
                      ->orWhere('d.content', 'LIKE', $likeTerm)
                      ->orWhere('d.author_name', 'LIKE', $likeTerm);
                })
                ->select('d.id', 'd.title', 'd.content', 'd.topic_type', 'd.reply_count', 'd.author_name')
                ->addSelect('g.slug as group_slug', 'g.name as group_name')
                ->orderBy('d.title', 'asc')
                ->limit(50)
                ->get()
                ->all();
        }

        $results = [];
        foreach ($items as $item) {
            $excerpt = strip_tags($item->content);
            if (strlen($excerpt) > 200) {
                $excerpt = substr($excerpt, 0, 200) . '...';
            }

            $results[] = [
                'entity_type' => 'discussion',
                'id' => $item->id,
                'title' => $item->title,
                'excerpt' => $excerpt,
                'url' => '/registry/groups/' . $item->group_slug . '/discussions/' . $item->id,
                'meta' => [
                    'group_slug' => $item->group_slug,
                    'topic_type' => $item->topic_type,
                    'reply_count' => $item->reply_count,
                    'author' => $item->author_name,
                    'group' => $item->group_name,
                ],
                'relevance' => $useLikeFallback ? 1.0 : (float) $item->score,
            ];
        }

        return $results;
    }

    /**
     * Search blog posts.
     */
    private function searchBlogPosts(string $query): array
    {
        $items = DB::table('registry_blog_post')
            ->where('status', 'published')
            ->whereRaw("MATCH(title, content) AGAINST(? IN BOOLEAN MODE)", [$query])
            ->selectRaw("id, title, slug, excerpt, category, author_name, author_type, featured_image_path, published_at, MATCH(title, content) AGAINST(? IN BOOLEAN MODE) as score", [$query])
            ->orderBy('score', 'desc')
            ->limit(50)
            ->get()
            ->all();

        // LIKE fallback when FULLTEXT returns no results
        $useLikeFallback = empty($items);
        if ($useLikeFallback) {
            $likeTerm = '%' . $query . '%';
            $items = DB::table('registry_blog_post')
                ->where('status', 'published')
                ->where(function ($q) use ($likeTerm) {
                    $q->where('title', 'LIKE', $likeTerm)
                      ->orWhere('content', 'LIKE', $likeTerm)
                      ->orWhere('excerpt', 'LIKE', $likeTerm)
                      ->orWhere('category', 'LIKE', $likeTerm)
                      ->orWhere('author_name', 'LIKE', $likeTerm);
                })
                ->select('id', 'title', 'slug', 'excerpt', 'category', 'author_name', 'author_type', 'featured_image_path', 'published_at')
                ->orderBy('title', 'asc')
                ->limit(50)
                ->get()
                ->all();
        }

        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'entity_type' => 'blog_post',
                'id' => $item->id,
                'title' => $item->title,
                'excerpt' => $item->excerpt,
                'url' => '/registry/blog/' . $item->slug,
                'meta' => [
                    'slug' => $item->slug,
                    'category' => $item->category,
                    'author' => $item->author_name,
                    'author_type' => $item->author_type,
                    'image' => $item->featured_image_path,
                    'published_at' => $item->published_at,
                ],
                'relevance' => $useLikeFallback ? 1.0 : (float) $item->score,
            ];
        }

        return $results;
    }
}
