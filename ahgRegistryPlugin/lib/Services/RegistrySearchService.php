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
        $validTypes = ['institution', 'vendor', 'software', 'user_group', 'discussion', 'blog_post'];

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
     */
    private function searchInstitutions(string $query): array
    {
        $items = DB::table('registry_institution')
            ->where('is_active', 1)
            ->whereRaw("MATCH(name, description, collection_summary) AGAINST(? IN BOOLEAN MODE)", [$query])
            ->selectRaw("id, name, slug, short_description, institution_type, country, logo_path, MATCH(name, description, collection_summary) AGAINST(? IN BOOLEAN MODE) as score", [$query])
            ->orderBy('score', 'desc')
            ->limit(50)
            ->get()
            ->all();

        // LIKE fallback when FULLTEXT returns no results
        $useLikeFallback = empty($items);
        if ($useLikeFallback) {
            $likeTerm = '%' . $query . '%';
            $items = DB::table('registry_institution')
                ->where('is_active', 1)
                ->where(function ($q) use ($likeTerm) {
                    $q->where('name', 'LIKE', $likeTerm)
                      ->orWhere('description', 'LIKE', $likeTerm)
                      ->orWhere('collection_summary', 'LIKE', $likeTerm)
                      ->orWhere('city', 'LIKE', $likeTerm)
                      ->orWhere('country', 'LIKE', $likeTerm);
                })
                ->select('id', 'name', 'slug', 'short_description', 'institution_type', 'country', 'logo_path')
                ->orderBy('name', 'asc')
                ->limit(50)
                ->get()
                ->all();
        }

        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'entity_type' => 'institution',
                'id' => $item->id,
                'title' => $item->name,
                'excerpt' => $item->short_description,
                'url' => '/registry/institutions/' . $item->slug,
                'meta' => [
                    'slug' => $item->slug,
                    'type' => $item->institution_type,
                    'country' => $item->country,
                    'logo' => $item->logo_path,
                ],
                'relevance' => $useLikeFallback ? 1.0 : (float) $item->score,
            ];
        }

        return $results;
    }

    /**
     * Search vendors.
     */
    private function searchVendors(string $query): array
    {
        $items = DB::table('registry_vendor')
            ->where('is_active', 1)
            ->whereRaw("MATCH(name, description) AGAINST(? IN BOOLEAN MODE)", [$query])
            ->selectRaw("id, name, slug, short_description, vendor_type, country, logo_path, MATCH(name, description) AGAINST(? IN BOOLEAN MODE) as score", [$query])
            ->orderBy('score', 'desc')
            ->limit(50)
            ->get()
            ->all();

        // LIKE fallback when FULLTEXT returns no results
        $useLikeFallback = empty($items);
        if ($useLikeFallback) {
            $likeTerm = '%' . $query . '%';
            $items = DB::table('registry_vendor')
                ->where('is_active', 1)
                ->where(function ($q) use ($likeTerm) {
                    $q->where('name', 'LIKE', $likeTerm)
                      ->orWhere('description', 'LIKE', $likeTerm)
                      ->orWhere('short_description', 'LIKE', $likeTerm)
                      ->orWhere('country', 'LIKE', $likeTerm);
                })
                ->select('id', 'name', 'slug', 'short_description', 'vendor_type', 'country', 'logo_path')
                ->orderBy('name', 'asc')
                ->limit(50)
                ->get()
                ->all();
        }

        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'entity_type' => 'vendor',
                'id' => $item->id,
                'title' => $item->name,
                'excerpt' => $item->short_description,
                'url' => '/registry/vendors/' . $item->slug,
                'meta' => [
                    'slug' => $item->slug,
                    'type' => $item->vendor_type,
                    'country' => $item->country,
                    'logo' => $item->logo_path,
                ],
                'relevance' => $useLikeFallback ? 1.0 : (float) $item->score,
            ];
        }

        return $results;
    }

    /**
     * Search software.
     */
    private function searchSoftware(string $query): array
    {
        $items = DB::table('registry_software')
            ->where('is_active', 1)
            ->whereRaw("MATCH(name, description) AGAINST(? IN BOOLEAN MODE)", [$query])
            ->selectRaw("id, name, slug, short_description, category, logo_path, latest_version, pricing_model, MATCH(name, description) AGAINST(? IN BOOLEAN MODE) as score", [$query])
            ->orderBy('score', 'desc')
            ->limit(50)
            ->get()
            ->all();

        // LIKE fallback when FULLTEXT returns no results
        $useLikeFallback = empty($items);
        if ($useLikeFallback) {
            $likeTerm = '%' . $query . '%';
            $items = DB::table('registry_software')
                ->where('is_active', 1)
                ->where(function ($q) use ($likeTerm) {
                    $q->where('name', 'LIKE', $likeTerm)
                      ->orWhere('description', 'LIKE', $likeTerm)
                      ->orWhere('short_description', 'LIKE', $likeTerm)
                      ->orWhere('category', 'LIKE', $likeTerm);
                })
                ->select('id', 'name', 'slug', 'short_description', 'category', 'logo_path', 'latest_version', 'pricing_model')
                ->orderBy('name', 'asc')
                ->limit(50)
                ->get()
                ->all();
        }

        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'entity_type' => 'software',
                'id' => $item->id,
                'title' => $item->name,
                'excerpt' => $item->short_description,
                'url' => '/registry/software/' . $item->slug,
                'meta' => [
                    'slug' => $item->slug,
                    'category' => $item->category,
                    'version' => $item->latest_version,
                    'pricing' => $item->pricing_model,
                    'logo' => $item->logo_path,
                ],
                'relevance' => $useLikeFallback ? 1.0 : (float) $item->score,
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
