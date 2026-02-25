<?php

namespace AhgRegistry\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class TagRepository
{
    protected string $table = 'registry_tag';

    public function findByEntity(string $type, int $id): array
    {
        return DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->orderBy('tag', 'asc')
            ->pluck('tag')
            ->all();
    }

    public function setTags(string $type, int $id, array $tags): void
    {
        // Remove existing tags
        DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->delete();

        // Insert new tags
        if (!empty($tags)) {
            $rows = [];
            foreach (array_unique($tags) as $tag) {
                $tag = trim($tag);
                if ($tag !== '') {
                    $rows[] = [
                        'entity_type' => $type,
                        'entity_id' => $id,
                        'tag' => $tag,
                    ];
                }
            }

            if (!empty($rows)) {
                DB::table($this->table)->insert($rows);
            }
        }
    }

    public function addTag(string $type, int $id, string $tag): bool
    {
        $tag = trim($tag);
        if ($tag === '') {
            return false;
        }

        // Check for duplicate (unique constraint)
        $exists = DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->where('tag', $tag)
            ->exists();

        if ($exists) {
            return false;
        }

        DB::table($this->table)->insert([
            'entity_type' => $type,
            'entity_id' => $id,
            'tag' => $tag,
        ]);

        return true;
    }

    public function removeTag(string $type, int $id, string $tag): bool
    {
        return DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->where('tag', $tag)
            ->delete() > 0;
    }

    public function findEntitiesByTag(string $type, string $tag, array $params = []): array
    {
        $query = DB::table($this->table)
            ->where('entity_type', $type)
            ->where('tag', $tag);

        $total = $query->count();

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy('entity_id', 'asc')
                       ->limit($limit)
                       ->offset($offset)
                       ->pluck('entity_id');

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function getPopularTags(string $type, int $limit = 20): array
    {
        return DB::table($this->table)
            ->where('entity_type', $type)
            ->selectRaw('tag, COUNT(*) as cnt')
            ->groupBy('tag')
            ->orderBy('cnt', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }
}
