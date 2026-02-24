<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Services;

require_once dirname(__DIR__) . '/Repositories/ListingRepository.php';
require_once dirname(__DIR__) . '/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ListingRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;
use Illuminate\Database\Capsule\Manager as DB;

class MarketplaceSearchService
{
    private ListingRepository $listingRepo;
    private SettingsRepository $settingsRepo;

    public function __construct()
    {
        $this->listingRepo = new ListingRepository();
        $this->settingsRepo = new SettingsRepository();
    }

    // =========================================================================
    // Full-Text Search
    // =========================================================================

    public function search(string $query, array $filters = [], int $limit = 24, int $offset = 0): array
    {
        $query = trim($query);
        if (empty($query)) {
            return ['items' => [], 'total' => 0, 'query' => '', 'facets' => []];
        }

        // Merge search query into filters for ListingRepository::browse()
        $filters['search'] = $query;

        $results = $this->listingRepo->browse($filters, $limit, $offset, $filters['sort'] ?? 'newest');

        // Get facet counts for the current search
        $facets = $this->listingRepo->getFacetCounts(['search' => $query]);

        return [
            'items' => $results['items'],
            'total' => $results['total'],
            'query' => $query,
            'facets' => $facets,
        ];
    }

    // =========================================================================
    // Autocomplete
    // =========================================================================

    public function getAutocompleteSuggestions(string $query, int $limit = 10): array
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return [];
        }

        // Search listings by title/artist name
        $listings = DB::table('marketplace_listing')
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', '%' . $query . '%')
                  ->orWhere('artist_name', 'LIKE', '%' . $query . '%');
            })
            ->select('id', 'title', 'slug', 'artist_name', 'featured_image_path', 'price', 'currency', 'listing_type')
            ->orderByRaw("CASE WHEN title LIKE ? THEN 0 ELSE 1 END", [$query . '%'])
            ->limit($limit)
            ->get()
            ->all();

        $suggestions = [];
        foreach ($listings as $listing) {
            $suggestions[] = [
                'id' => $listing->id,
                'title' => $listing->title,
                'slug' => $listing->slug,
                'artist' => $listing->artist_name,
                'image' => $listing->featured_image_path,
                'price' => $listing->price,
                'currency' => $listing->currency,
                'type' => $listing->listing_type,
            ];
        }

        // Also search sellers
        $sellers = DB::table('marketplace_seller')
            ->where('is_active', 1)
            ->where('display_name', 'LIKE', '%' . $query . '%')
            ->select('id', 'display_name', 'slug', 'avatar_path', 'seller_type')
            ->limit(3)
            ->get()
            ->all();

        foreach ($sellers as $seller) {
            $suggestions[] = [
                'id' => $seller->id,
                'title' => $seller->display_name,
                'slug' => $seller->slug,
                'artist' => null,
                'image' => $seller->avatar_path,
                'price' => null,
                'currency' => null,
                'type' => 'seller',
                'seller_type' => $seller->seller_type,
            ];
        }

        return $suggestions;
    }

    // =========================================================================
    // Popular Searches
    // =========================================================================

    public function getPopularSearches(int $limit = 10): array
    {
        // Return popular categories/sectors as suggested searches
        $sectors = DB::table('marketplace_listing')
            ->where('status', 'active')
            ->selectRaw("sector, COUNT(*) as cnt")
            ->groupBy('sector')
            ->orderBy('cnt', 'DESC')
            ->limit($limit)
            ->get()
            ->all();

        $popular = [];
        foreach ($sectors as $sector) {
            $popular[] = [
                'term' => ucfirst($sector->sector),
                'count' => $sector->cnt,
                'type' => 'sector',
            ];
        }

        // Also include popular artists/mediums
        $artists = DB::table('marketplace_listing')
            ->where('status', 'active')
            ->whereNotNull('artist_name')
            ->where('artist_name', '!=', '')
            ->selectRaw("artist_name, COUNT(*) as cnt")
            ->groupBy('artist_name')
            ->orderBy('cnt', 'DESC')
            ->limit(5)
            ->get()
            ->all();

        foreach ($artists as $artist) {
            $popular[] = [
                'term' => $artist->artist_name,
                'count' => $artist->cnt,
                'type' => 'artist',
            ];
        }

        // Sort by count descending and limit
        usort($popular, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        return array_slice($popular, 0, $limit);
    }

    // =========================================================================
    // Filter Builder
    // =========================================================================

    public function buildSearchFilters(array $params): array
    {
        $filters = [];

        if (!empty($params['sector'])) {
            $filters['sector'] = $params['sector'];
        }
        if (!empty($params['category_id'])) {
            $filters['category_id'] = (int) $params['category_id'];
        }
        if (!empty($params['listing_type'])) {
            $filters['listing_type'] = $params['listing_type'];
        }
        if (!empty($params['seller_id'])) {
            $filters['seller_id'] = (int) $params['seller_id'];
        }
        if (isset($params['price_min']) && is_numeric($params['price_min'])) {
            $filters['price_min'] = (float) $params['price_min'];
        }
        if (isset($params['price_max']) && is_numeric($params['price_max'])) {
            $filters['price_max'] = (float) $params['price_max'];
        }
        if (!empty($params['condition_rating'])) {
            $filters['condition_rating'] = $params['condition_rating'];
        }
        if (!empty($params['medium'])) {
            $filters['medium'] = $params['medium'];
        }
        if (!empty($params['country'])) {
            $filters['country'] = $params['country'];
        }
        if (isset($params['is_digital'])) {
            $filters['is_digital'] = (int) $params['is_digital'];
        }
        if (!empty($params['sort'])) {
            $filters['sort'] = $params['sort'];
        }

        return $filters;
    }
}
