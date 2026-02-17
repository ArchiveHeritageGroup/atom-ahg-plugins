<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * MapService - Research Geospatial Mapping
 *
 * Manages map points for research projects, enabling researchers
 * to plot geospatial data, link locations to archival sources,
 * and perform bounding box searches across projects.
 *
 * Table: research_map_point
 *
 * @package ahgResearchPlugin
 * @version 2.0.0
 */
class MapService
{
    // =========================================================================
    // POINT MANAGEMENT
    // =========================================================================

    /**
     * Create a new map point.
     *
     * @param int $projectId The research project ID
     * @param int $researcherId The researcher creating the point
     * @param array $data Keys: label, description, latitude, longitude,
     *                     place_name, date_valid_from, date_valid_to,
     *                     source_type, source_id
     * @return int The new map point ID
     */
    public function createPoint(int $projectId, int $researcherId, array $data): int
    {
        $pointId = DB::table('research_map_point')->insertGetId([
            'project_id' => $projectId,
            'researcher_id' => $researcherId,
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'place_name' => $data['place_name'] ?? null,
            'date_valid_from' => $data['date_valid_from'] ?? null,
            'date_valid_to' => $data['date_valid_to'] ?? null,
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logEvent(
            $researcherId,
            $projectId,
            'map_point_created',
            'map_point',
            $pointId,
            mb_substr($data['label'], 0, 200)
        );

        return $pointId;
    }

    /**
     * Get all map points for a project.
     *
     * @param int $projectId The project ID
     * @return array List of map points with researcher names
     */
    public function getProjectPoints(int $projectId): array
    {
        return DB::table('research_map_point as mp')
            ->leftJoin('research_researcher as r', 'mp.researcher_id', '=', 'r.id')
            ->where('mp.project_id', $projectId)
            ->select(
                'mp.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name'
            )
            ->orderBy('mp.created_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Update a map point.
     *
     * @param int $id The map point ID
     * @param array $data Fields to update: label, description, latitude,
     *                     longitude, place_name, date_valid_from,
     *                     date_valid_to, source_type, source_id
     * @return bool Success status
     */
    public function updatePoint(int $id, array $data): bool
    {
        $allowed = [
            'label', 'description', 'latitude', 'longitude', 'place_name',
            'date_valid_from', 'date_valid_to', 'source_type', 'source_id',
        ];

        $updateData = array_intersect_key($data, array_flip($allowed));

        if (empty($updateData)) {
            return false;
        }

        return DB::table('research_map_point')
            ->where('id', $id)
            ->update($updateData) >= 0;
    }

    /**
     * Delete a map point.
     *
     * @param int $id The map point ID
     * @return bool Success status
     */
    public function deletePoint(int $id): bool
    {
        return DB::table('research_map_point')
            ->where('id', $id)
            ->delete() > 0;
    }

    /**
     * Find all map points within a geographic bounding box.
     *
     * Uses WHERE latitude BETWEEN and longitude BETWEEN to find
     * points within the specified rectangular area. Parameters
     * represent the south-west and north-east corners.
     *
     * @param float $lat1 South latitude (min)
     * @param float $lng1 West longitude (min)
     * @param float $lat2 North latitude (max)
     * @param float $lng2 East longitude (max)
     * @return array List of map points within the bounding box
     */
    public function boundingBoxSearch(float $lat1, float $lng1, float $lat2, float $lng2): array
    {
        // Normalize so lat1/lng1 is always the minimum
        $minLat = min($lat1, $lat2);
        $maxLat = max($lat1, $lat2);
        $minLng = min($lng1, $lng2);
        $maxLng = max($lng1, $lng2);

        return DB::table('research_map_point as mp')
            ->leftJoin('research_researcher as r', 'mp.researcher_id', '=', 'r.id')
            ->whereBetween('mp.latitude', [$minLat, $maxLat])
            ->whereBetween('mp.longitude', [$minLng, $maxLng])
            ->select(
                'mp.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name'
            )
            ->orderBy('mp.created_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Get a single map point by ID.
     *
     * @param int $id The map point ID
     * @return object|null The map point or null
     */
    public function getPoint(int $id): ?object
    {
        return DB::table('research_map_point as mp')
            ->leftJoin('research_researcher as r', 'mp.researcher_id', '=', 'r.id')
            ->where('mp.id', $id)
            ->select(
                'mp.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name',
                'r.email as researcher_email'
            )
            ->first();
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
