<?php

namespace AhgCore\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Relation Service
 *
 * Manages the `relation` and `relation_i18n` tables.
 * Relations link two objects (subject + object) with a typed relationship.
 */
class RelationService
{
    /**
     * Get all relations where the given ID is subject or object.
     */
    public static function getBySubjectOrObject(int $id, ?int $typeId = null): array
    {
        $query = DB::table('relation')
            ->where(function ($q) use ($id) {
                $q->where('subject_id', $id)
                    ->orWhere('object_id', $id);
            });

        if ($typeId !== null) {
            $query->where('type_id', $typeId);
        }

        return $query->get()->all();
    }

    /**
     * Get relations where the given ID is the subject.
     */
    public static function getBySubjectId(int $subjectId, ?int $typeId = null): array
    {
        $query = DB::table('relation')
            ->where('subject_id', $subjectId);

        if ($typeId !== null) {
            $query->where('type_id', $typeId);
        }

        return $query->get()->all();
    }

    /**
     * Get relations where the given ID is the object.
     */
    public static function getByObjectId(int $objectId, ?int $typeId = null): array
    {
        $query = DB::table('relation')
            ->where('object_id', $objectId);

        if ($typeId !== null) {
            $query->where('type_id', $typeId);
        }

        return $query->get()->all();
    }

    /**
     * Create or update a relation.
     *
     * @param array $data Must include subject_id, object_id, type_id. May include start_date, end_date.
     * @param string|null $culture Culture for i18n data
     * @param array $i18nData Optional i18n fields (description, date)
     *
     * @return int The relation ID
     */
    public static function save(array $data, ?string $culture = null, array $i18nData = []): int
    {
        $id = $data['id'] ?? null;
        unset($data['id']);

        $baseFields = ['subject_id', 'object_id', 'type_id', 'start_date', 'end_date', 'source_culture'];
        $baseData = array_intersect_key($data, array_flip($baseFields));

        if ($id) {
            DB::table('relation')
                ->where('id', $id)
                ->update($baseData);
        } else {
            if (!isset($baseData['source_culture'])) {
                $baseData['source_culture'] = $culture ?? 'en';
            }

            $id = DB::table('relation')->insertGetId($baseData);
        }

        // Save i18n if provided
        if (!empty($i18nData) && $culture) {
            I18nService::save('relation_i18n', $id, $culture, $i18nData);
        }

        return $id;
    }

    /**
     * Delete a relation and its i18n records.
     */
    public static function delete(int $id): void
    {
        I18nService::delete('relation_i18n', $id);
        DB::table('relation')->where('id', $id)->delete();
    }

    /**
     * Delete all relations for an object (both as subject and object).
     */
    public static function deleteBySubjectOrObject(int $id): void
    {
        $relations = DB::table('relation')
            ->where('subject_id', $id)
            ->orWhere('object_id', $id)
            ->pluck('id')
            ->all();

        foreach ($relations as $relId) {
            self::delete($relId);
        }
    }
}
