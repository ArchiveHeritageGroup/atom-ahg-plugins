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

        // object_term_relation.id is a QubitObject id and is NOT auto_increment,
        // so a bare insert fails under MySQL STRICT mode ("Field 'id' doesn't have
        // a default value"). Create the base `object` row first (its id IS
        // auto_increment), then the relation with that id - the way base AtoM
        // persists a QubitObjectTermRelation. Without this, subject/place/genre
        // access points never saved from any AHG manage form.
        $newId = DB::table('object')->insertGetId([
            'class_name' => 'QubitObjectTermRelation',
            'created_at' => DB::raw('NOW()'),
            'updated_at' => DB::raw('NOW()'),
            'serial_number' => 0,
        ]);

        DB::table('object_term_relation')->insert([
            'id' => $newId,
            'object_id' => $objectId,
            'term_id' => $termId,
        ]);

        return $newId;
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
