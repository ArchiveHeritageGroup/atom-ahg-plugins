<?php

namespace AhgCore\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Term Relation Service
 *
 * Manages the `object_term_relation` table.
 * Links objects to terms (access points, subject headings, etc.).
 */
class TermRelationService
{
    /**
     * Get all term relations for an object.
     */
    public static function getByObjectId(int $objectId, ?int $taxonomyId = null, string $culture = 'en'): array
    {
        $query = DB::table('object_term_relation')
            ->join('term', 'object_term_relation.term_id', '=', 'term.id')
            ->leftJoin('term_i18n', function ($j) use ($culture) {
                $j->on('term.id', '=', 'term_i18n.id')
                    ->where('term_i18n.culture', '=', $culture);
            })
            ->where('object_term_relation.object_id', $objectId);

        if ($taxonomyId !== null) {
            $query->where('term.taxonomy_id', $taxonomyId);
        }

        return $query->select(
            'object_term_relation.id',
            'object_term_relation.term_id',
            'term.taxonomy_id',
            'term_i18n.name as term_name'
        )
            ->get()
            ->all();
    }

    /**
     * Add a term relation.
     *
     * @return int The relation ID
     */
    public static function addRelation(int $objectId, int $termId): int
    {
        // Check if already exists
        $existing = DB::table('object_term_relation')
            ->where('object_id', $objectId)
            ->where('term_id', $termId)
            ->value('id');

        if ($existing) {
            return $existing;
        }

        return DB::table('object_term_relation')->insertGetId([
            'object_id' => $objectId,
            'term_id' => $termId,
        ]);
    }

    /**
     * Remove a specific term relation.
     */
    public static function removeRelation(int $objectId, int $termId): void
    {
        DB::table('object_term_relation')
            ->where('object_id', $objectId)
            ->where('term_id', $termId)
            ->delete();
    }

    /**
     * Replace all term relations for an object with a new set.
     */
    public static function replaceRelations(int $objectId, array $termIds, ?int $taxonomyId = null): void
    {
        if ($taxonomyId !== null) {
            // Only delete relations for terms in this taxonomy
            $existingTermIds = DB::table('object_term_relation')
                ->join('term', 'object_term_relation.term_id', '=', 'term.id')
                ->where('object_term_relation.object_id', $objectId)
                ->where('term.taxonomy_id', $taxonomyId)
                ->pluck('object_term_relation.term_id')
                ->all();

            foreach ($existingTermIds as $termId) {
                if (!in_array($termId, $termIds)) {
                    self::removeRelation($objectId, $termId);
                }
            }
        } else {
            // Delete all existing relations
            DB::table('object_term_relation')
                ->where('object_id', $objectId)
                ->delete();
        }

        // Add new relations
        foreach ($termIds as $termId) {
            self::addRelation($objectId, $termId);
        }
    }

    /**
     * Delete all term relations for an object.
     */
    public static function deleteByObjectId(int $objectId): void
    {
        DB::table('object_term_relation')
            ->where('object_id', $objectId)
            ->delete();
    }
}
