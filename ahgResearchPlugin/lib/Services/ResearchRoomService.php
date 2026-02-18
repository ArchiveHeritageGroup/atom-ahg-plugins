<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Research Room Service
 *
 * Manages collaborative IIIF viewing/annotation sessions within research projects.
 * Each room can have participants with different roles and share derivative manifests.
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class ResearchRoomService
{
    /**
     * Create a new research room.
     */
    public function createRoom(int $projectId, string $name, int $createdBy, ?string $description = null, int $maxParticipants = 10): int
    {
        $roomId = DB::table('research_room')->insertGetId([
            'project_id' => $projectId,
            'name' => $name,
            'description' => $description,
            'status' => 'draft',
            'created_by' => $createdBy,
            'max_participants' => $maxParticipants,
        ]);

        // Auto-add creator as owner
        DB::table('research_room_participant')->insert([
            'room_id' => $roomId,
            'user_id' => $createdBy,
            'role' => 'owner',
        ]);

        return (int) $roomId;
    }

    /**
     * List rooms for a project.
     */
    public function listRooms(int $projectId, ?string $status = null): array
    {
        $query = DB::table('research_room as r')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('r.created_by', '=', 'ai.id')
                    ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('r.project_id', $projectId);

        if ($status) {
            $query->where('r.status', $status);
        }

        return $query->select(
            'r.*',
            'ai.authorized_form_of_name as creator_name',
            DB::raw('(SELECT COUNT(*) FROM research_room_participant WHERE room_id = r.id) as participant_count'),
            DB::raw('(SELECT COUNT(*) FROM research_room_manifest WHERE room_id = r.id) as manifest_count')
        )
            ->orderBy('r.updated_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get a room by ID.
     */
    public function getRoom(int $roomId): ?object
    {
        return DB::table('research_room')->where('id', $roomId)->first();
    }

    /**
     * Update a room.
     */
    public function updateRoom(int $roomId, array $data): bool
    {
        $allowed = ['name', 'description', 'status', 'max_participants'];
        $update = array_intersect_key($data, array_flip($allowed));

        if (empty($update)) {
            return false;
        }

        return DB::table('research_room')
            ->where('id', $roomId)
            ->update($update) > 0;
    }

    /**
     * Archive a room.
     */
    public function archiveRoom(int $roomId): bool
    {
        return $this->updateRoom($roomId, ['status' => 'archived']);
    }

    // =========================================================================
    // PARTICIPANTS
    // =========================================================================

    /**
     * Add a participant to a room.
     */
    public function addParticipant(int $roomId, int $userId, string $role = 'viewer'): bool
    {
        // Check room capacity
        $room = $this->getRoom($roomId);
        if (!$room) {
            return false;
        }

        $currentCount = DB::table('research_room_participant')
            ->where('room_id', $roomId)
            ->count();

        if ($currentCount >= $room->max_participants) {
            return false;
        }

        DB::table('research_room_participant')->updateOrInsert(
            ['room_id' => $roomId, 'user_id' => $userId],
            ['role' => $role]
        );

        return true;
    }

    /**
     * Remove a participant from a room.
     */
    public function removeParticipant(int $roomId, int $userId): bool
    {
        return DB::table('research_room_participant')
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->where('role', '!=', 'owner') // Cannot remove owner
            ->delete() > 0;
    }

    /**
     * Update participant role.
     */
    public function updateParticipantRole(int $roomId, int $userId, string $role): bool
    {
        return DB::table('research_room_participant')
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->update(['role' => $role]) > 0;
    }

    /**
     * Get participants for a room.
     */
    public function getParticipants(int $roomId): array
    {
        return DB::table('research_room_participant as rp')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('rp.user_id', '=', 'ai.id')
                    ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('rp.room_id', $roomId)
            ->select('rp.*', 'ai.authorized_form_of_name as user_name')
            ->orderByRaw("FIELD(rp.role, 'owner', 'editor', 'viewer')")
            ->get()
            ->toArray();
    }

    /**
     * Check if user has access to a room.
     */
    public function hasAccess(int $roomId, int $userId): bool
    {
        return DB::table('research_room_participant')
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Check if user is room owner.
     */
    public function isOwner(int $roomId, int $userId): bool
    {
        return DB::table('research_room_participant')
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->where('role', 'owner')
            ->exists();
    }

    // =========================================================================
    // MANIFESTS
    // =========================================================================

    /**
     * Add an object manifest to a room.
     */
    public function addManifest(int $roomId, int $objectId, string $derivativeType = 'full', ?string $manifestJson = null): int
    {
        return (int) DB::table('research_room_manifest')->insertGetId([
            'room_id' => $roomId,
            'object_id' => $objectId,
            'manifest_json' => $manifestJson,
            'derivative_type' => $derivativeType,
        ]);
    }

    /**
     * Remove a manifest from a room.
     */
    public function removeManifest(int $manifestId): bool
    {
        return DB::table('research_room_manifest')
            ->where('id', $manifestId)
            ->delete() > 0;
    }

    /**
     * Get manifests for a room.
     */
    public function getManifests(int $roomId): array
    {
        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();

        return DB::table('research_room_manifest as rm')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('rm.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'rm.object_id', '=', 's.object_id')
            ->where('rm.room_id', $roomId)
            ->select('rm.*', 'ioi.title as object_title', 's.slug as object_slug')
            ->orderBy('rm.created_at')
            ->get()
            ->toArray();
    }

    /**
     * Generate a derivative IIIF collection manifest for a room.
     */
    public function generateRoomManifest(int $roomId): array
    {
        $room = $this->getRoom($roomId);
        if (!$room) {
            return [];
        }

        $manifests = $this->getManifests($roomId);

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = "{$protocol}://{$host}";

        $items = [];
        foreach ($manifests as $m) {
            $slug = $m->object_slug ?? $m->object_id;
            $items[] = [
                '@type' => 'sc:Manifest',
                '@id' => "{$baseUrl}/iiif/manifest/{$slug}",
                'label' => $m->object_title ?? "Object {$m->object_id}",
            ];
        }

        return [
            '@context' => 'http://iiif.io/api/presentation/2/context.json',
            '@type' => 'sc:Collection',
            '@id' => "{$baseUrl}/research/room/{$roomId}/manifest.json",
            'label' => $room->name,
            'description' => $room->description ?? '',
            'manifests' => $items,
        ];
    }

    /**
     * Export annotations for a room as W3C AnnotationCollection.
     */
    public function exportAnnotations(int $roomId): array
    {
        $room = $this->getRoom($roomId);
        if (!$room) {
            return [];
        }

        $manifests = $this->getManifests($roomId);
        $objectIds = array_map(fn($m) => $m->object_id, $manifests);

        if (empty($objectIds)) {
            return $this->emptyAnnotationCollection($room);
        }

        $annotations = DB::table('iiif_annotation as a')
            ->join('iiif_annotation_body as b', 'a.id', '=', 'b.annotation_id')
            ->whereIn('a.object_id', $objectIds)
            ->select('a.*', 'b.body_type', 'b.body_value', 'b.body_format', 'b.body_language')
            ->orderBy('a.created_at')
            ->get()
            ->toArray();

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = "{$protocol}://{$host}";

        $items = [];
        foreach ($annotations as $a) {
            $items[] = [
                '@context' => 'http://www.w3.org/ns/anno.jsonld',
                'id' => "{$baseUrl}/iiif/annotations/{$a->id}",
                'type' => 'Annotation',
                'motivation' => $a->motivation ?? 'commenting',
                'body' => [
                    'type' => $a->body_type ?? 'TextualBody',
                    'value' => $a->body_value ?? '',
                    'format' => $a->body_format ?? 'text/plain',
                    'language' => $a->body_language ?? 'en',
                ],
                'target' => $a->target_canvas,
                'created' => $a->created_at,
            ];
        }

        return [
            '@context' => 'http://www.w3.org/ns/anno.jsonld',
            'id' => "{$baseUrl}/research/room/{$roomId}/annotations.json",
            'type' => 'AnnotationCollection',
            'label' => "Annotations for room: {$room->name}",
            'total' => count($items),
            'first' => [
                'type' => 'AnnotationPage',
                'items' => $items,
            ],
        ];
    }

    private function emptyAnnotationCollection(object $room): array
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = "{$protocol}://{$host}";

        return [
            '@context' => 'http://www.w3.org/ns/anno.jsonld',
            'id' => "{$baseUrl}/research/room/{$room->id}/annotations.json",
            'type' => 'AnnotationCollection',
            'label' => "Annotations for room: {$room->name}",
            'total' => 0,
            'first' => [
                'type' => 'AnnotationPage',
                'items' => [],
            ],
        ];
    }
}
