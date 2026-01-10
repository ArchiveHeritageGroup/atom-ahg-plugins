<?php

namespace ahgNerPlugin\Repository;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * NER Repository - Database operations for extracted entities
 */
class NerRepository
{
    /**
     * Save extracted entities
     */
    public function saveExtraction(int $objectId, array $entities, string $backend = 'local'): int
    {
        $extractionId = DB::table('ahg_ner_extraction')->insertGetId([
            'object_id' => $objectId,
            'backend_used' => $backend,
            'status' => 'pending',
            'extracted_at' => now()
        ]);

        foreach ($entities as $type => $values) {
            foreach ($values as $value) {
                DB::table('ahg_ner_entity')->insert([
                    'extraction_id' => $extractionId,
                    'object_id' => $objectId,
                    'entity_type' => $type,
                    'entity_value' => $value,
                    'status' => 'pending',
                    'created_at' => now()
                ]);
            }
        }

        return $extractionId;
    }

    /**
     * Get pending entities for an object
     */
    public function getPendingEntities(int $objectId): array
    {
        return DB::table('ahg_ner_entity')
            ->where('object_id', $objectId)
            ->where('status', 'pending')
            ->orderBy('entity_type')
            ->orderBy('entity_value')
            ->get()
            ->toArray();
    }

    /**
     * Get all entities for an object
     */
    public function getEntities(int $objectId): array
    {
        return DB::table('ahg_ner_entity')
            ->where('object_id', $objectId)
            ->orderBy('entity_type')
            ->orderBy('entity_value')
            ->get()
            ->toArray();
    }

    /**
     * Update entity status
     */
    public function updateEntityStatus(int $entityId, string $status, ?int $linkedActorId = null, ?int $reviewedBy = null): bool
    {
        return DB::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->update([
                'status' => $status,
                'linked_actor_id' => $linkedActorId,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now()
            ]) > 0;
    }

    /**
     * Find matching actors for entity
     */
    public function findMatchingActors(string $entityValue, string $entityType): array
    {
        $query = DB::table('actor_i18n')
            ->join('actor', 'actor.id', '=', 'actor_i18n.id')
            ->select('actor.id', 'actor_i18n.authorized_form_of_name');

        // Exact match
        $exact = (clone $query)
            ->where('actor_i18n.authorized_form_of_name', $entityValue)
            ->get()
            ->toArray();

        // Partial match
        $partial = (clone $query)
            ->where('actor_i18n.authorized_form_of_name', 'LIKE', '%' . $entityValue . '%')
            ->whereNotIn('actor.id', array_column($exact, 'id'))
            ->limit(5)
            ->get()
            ->toArray();

        return [
            'exact' => $exact,
            'partial' => $partial
        ];
    }

    /**
     * Get extraction history for object
     */
    public function getExtractionHistory(int $objectId): array
    {
        return DB::table('ahg_ner_extraction')
            ->where('object_id', $objectId)
            ->orderBy('extracted_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get pending review count
     */
    public function getPendingCount(): int
    {
        return DB::table('ahg_ner_entity')
            ->where('status', 'pending')
            ->count();
    }
}
