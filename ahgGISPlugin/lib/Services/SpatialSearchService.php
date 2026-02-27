<?php

namespace AhgGIS\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Spatial search service for heritage records.
 *
 * Aggregates coordinates from multiple AtoM tables:
 *   1. contact_information (repositories, actors)
 *   2. research_map_point (research projects)
 *   3. nmmz_archaeological_site (Zimbabwe monuments)
 *   4. dam_iptc_metadata (photo GPS)
 *
 * All queries use Laravel Query Builder with standard SQL
 * (no MySQL spatial extensions required).
 */
class SpatialSearchService
{
    /**
     * Earth radius in kilometres (mean radius).
     */
    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * Bounding box search — find records within a lat/lng rectangle.
     *
     * @param float $latMin Southern boundary
     * @param float $latMax Northern boundary
     * @param float $lngMin Western boundary
     * @param float $lngMax Eastern boundary
     * @param array $sources Which coordinate sources to query (default: all available)
     * @param int   $limit   Max results
     * @return array [{id, source, latitude, longitude, title, slug, ...}, ...]
     */
    public function boundingBox(
        float $latMin,
        float $latMax,
        float $lngMin,
        float $lngMax,
        array $sources = [],
        int $limit = 200
    ): array {
        if (empty($sources)) {
            $sources = $this->availableSources();
        }

        $results = [];

        if (in_array('contact', $sources)) {
            $results = array_merge($results, $this->bboxContact($latMin, $latMax, $lngMin, $lngMax, $limit));
        }

        if (in_array('research', $sources) && $this->tableExists('research_map_point')) {
            $results = array_merge($results, $this->bboxResearch($latMin, $latMax, $lngMin, $lngMax, $limit));
        }

        if (in_array('nmmz', $sources) && $this->tableExists('nmmz_archaeological_site')) {
            $results = array_merge($results, $this->bboxNmmz($latMin, $latMax, $lngMin, $lngMax, $limit));
        }

        if (in_array('iptc', $sources) && $this->tableExists('dam_iptc_metadata')) {
            $results = array_merge($results, $this->bboxIptc($latMin, $latMax, $lngMin, $lngMax, $limit));
        }

        // Sort by latitude (north to south)
        usort($results, fn($a, $b) => $b['latitude'] <=> $a['latitude']);

        return array_slice($results, 0, $limit);
    }

    /**
     * Radius search — find records within N km of a centre point.
     * Uses the Haversine formula.
     *
     * @param float $lat    Centre latitude
     * @param float $lng    Centre longitude
     * @param float $radius Radius in kilometres
     * @param array $sources Which coordinate sources
     * @param int   $limit   Max results
     * @return array [{id, source, latitude, longitude, distance_km, title, slug, ...}, ...]
     */
    public function radius(
        float $lat,
        float $lng,
        float $radius,
        array $sources = [],
        int $limit = 200
    ): array {
        // Calculate bounding box for pre-filtering (faster than full Haversine on all rows)
        $latDelta = $radius / 111.0; // ~111 km per degree latitude
        $lngDelta = $radius / (111.0 * cos(deg2rad($lat)));

        $latMin = $lat - $latDelta;
        $latMax = $lat + $latDelta;
        $lngMin = $lng - $lngDelta;
        $lngMax = $lng + $lngDelta;

        // Get bounding box candidates
        $candidates = $this->boundingBox($latMin, $latMax, $lngMin, $lngMax, $sources, $limit * 3);

        // Apply exact Haversine filter
        $results = [];
        foreach ($candidates as $row) {
            $dist = $this->haversine($lat, $lng, $row['latitude'], $row['longitude']);
            if ($dist <= $radius) {
                $row['distance_km'] = round($dist, 2);
                $results[] = $row;
            }
        }

        // Sort by distance
        usort($results, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);

        return array_slice($results, 0, $limit);
    }

    /**
     * Export records with coordinates as GeoJSON FeatureCollection.
     *
     * @param array $sources Which coordinate sources
     * @param int   $limit   Max features
     * @return array GeoJSON FeatureCollection
     */
    public function toGeoJSON(array $sources = [], int $limit = 1000): array
    {
        // Use worldwide bounding box
        $records = $this->boundingBox(-90, 90, -180, 180, $sources, $limit);

        $features = [];
        foreach ($records as $r) {
            $properties = [
                'title'  => $r['title'] ?? '',
                'source' => $r['source'],
            ];
            if (!empty($r['slug'])) {
                $properties['slug'] = $r['slug'];
            }
            if (!empty($r['object_id'])) {
                $properties['object_id'] = $r['object_id'];
            }
            if (!empty($r['actor_id'])) {
                $properties['actor_id'] = $r['actor_id'];
            }

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float)$r['longitude'], (float)$r['latitude']],
                ],
                'properties' => $properties,
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    /**
     * Get list of available coordinate sources based on installed tables.
     */
    public function availableSources(): array
    {
        $sources = ['contact']; // Always available
        if ($this->tableExists('research_map_point')) {
            $sources[] = 'research';
        }
        if ($this->tableExists('nmmz_archaeological_site')) {
            $sources[] = 'nmmz';
        }
        if ($this->tableExists('dam_iptc_metadata')) {
            $sources[] = 'iptc';
        }
        return $sources;
    }

    // ── Private: Source-specific bounding box queries ──

    private function bboxContact(float $latMin, float $latMax, float $lngMin, float $lngMax, int $limit): array
    {
        $rows = DB::table('contact_information as ci')
            ->leftJoin('contact_information_i18n as ci_i18n', function ($j) {
                $j->on('ci.id', '=', 'ci_i18n.id')
                  ->where('ci_i18n.culture', '=', 'en');
            })
            ->leftJoin('actor_i18n as ai', function ($j) {
                $j->on('ci.actor_id', '=', 'ai.id')
                  ->where('ai.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'ci.actor_id')
            ->whereBetween('ci.latitude', [$latMin, $latMax])
            ->whereBetween('ci.longitude', [$lngMin, $lngMax])
            ->whereNotNull('ci.latitude')
            ->whereNotNull('ci.longitude')
            ->select(
                'ci.id',
                'ci.actor_id',
                'ci.latitude',
                'ci.longitude',
                'ai.authorized_form_of_name as title',
                's.slug',
                'ci_i18n.city',
                'ci.country_code'
            )
            ->limit($limit)
            ->get();

        return $rows->map(fn($r) => [
            'id' => $r->id,
            'source' => 'contact',
            'actor_id' => $r->actor_id,
            'latitude' => (float)$r->latitude,
            'longitude' => (float)$r->longitude,
            'title' => $r->title ?? '',
            'slug' => $r->slug ?? '',
            'city' => $r->city ?? '',
            'country_code' => $r->country_code ?? '',
        ])->toArray();
    }

    private function bboxResearch(float $latMin, float $latMax, float $lngMin, float $lngMax, int $limit): array
    {
        $rows = DB::table('research_map_point as rmp')
            ->whereBetween('rmp.latitude', [$latMin, $latMax])
            ->whereBetween('rmp.longitude', [$lngMin, $lngMax])
            ->whereNotNull('rmp.latitude')
            ->whereNotNull('rmp.longitude')
            ->select('rmp.id', 'rmp.latitude', 'rmp.longitude', 'rmp.label as title', 'rmp.place_name')
            ->limit($limit)
            ->get();

        return $rows->map(fn($r) => [
            'id' => $r->id,
            'source' => 'research',
            'latitude' => (float)$r->latitude,
            'longitude' => (float)$r->longitude,
            'title' => $r->title ?? $r->place_name ?? '',
            'slug' => '',
        ])->toArray();
    }

    private function bboxNmmz(float $latMin, float $latMax, float $lngMin, float $lngMax, int $limit): array
    {
        $rows = DB::table('nmmz_archaeological_site as nas')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('nas.information_object_id', '=', 'ioi.id')
                  ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'nas.information_object_id')
            ->whereBetween('nas.gps_latitude', [$latMin, $latMax])
            ->whereBetween('nas.gps_longitude', [$lngMin, $lngMax])
            ->whereNotNull('nas.gps_latitude')
            ->whereNotNull('nas.gps_longitude')
            ->select(
                'nas.id',
                'nas.information_object_id as object_id',
                'nas.gps_latitude as latitude',
                'nas.gps_longitude as longitude',
                'ioi.title',
                's.slug',
                'nas.site_type'
            )
            ->limit($limit)
            ->get();

        return $rows->map(fn($r) => [
            'id' => $r->id,
            'source' => 'nmmz',
            'object_id' => $r->object_id,
            'latitude' => (float)$r->latitude,
            'longitude' => (float)$r->longitude,
            'title' => $r->title ?? '',
            'slug' => $r->slug ?? '',
            'site_type' => $r->site_type ?? '',
        ])->toArray();
    }

    private function bboxIptc(float $latMin, float $latMax, float $lngMin, float $lngMax, int $limit): array
    {
        $rows = DB::table('dam_iptc_metadata as dim')
            ->leftJoin('digital_object as do_tbl', 'dim.digital_object_id', '=', 'do_tbl.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('do_tbl.object_id', '=', 'ioi.id')
                  ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'do_tbl.object_id')
            ->whereBetween('dim.gps_latitude', [$latMin, $latMax])
            ->whereBetween('dim.gps_longitude', [$lngMin, $lngMax])
            ->whereNotNull('dim.gps_latitude')
            ->whereNotNull('dim.gps_longitude')
            ->select(
                'dim.id',
                'do_tbl.object_id',
                'dim.gps_latitude as latitude',
                'dim.gps_longitude as longitude',
                'ioi.title',
                's.slug'
            )
            ->limit($limit)
            ->get();

        return $rows->map(fn($r) => [
            'id' => $r->id,
            'source' => 'iptc',
            'object_id' => $r->object_id,
            'latitude' => (float)$r->latitude,
            'longitude' => (float)$r->longitude,
            'title' => $r->title ?? '',
            'slug' => $r->slug ?? '',
        ])->toArray();
    }

    // ── Utility ──

    /**
     * Haversine distance between two points in kilometres.
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }

    /**
     * Check if a table exists in the database.
     */
    private function tableExists(string $table): bool
    {
        static $cache = [];
        if (!isset($cache[$table])) {
            try {
                DB::table($table)->limit(1)->first();
                $cache[$table] = true;
            } catch (\Exception $e) {
                $cache[$table] = false;
            }
        }
        return $cache[$table];
    }
}
