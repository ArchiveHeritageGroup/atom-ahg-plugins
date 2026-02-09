<?php

namespace AhgFunctionManage\Services;

use AhgCore\Services\I18nService;
use AhgCore\Services\ObjectService;
use AhgCore\Services\RelationService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Function CRUD Service
 *
 * Pure Laravel Query Builder implementation for ISDF Function entity operations.
 * Functions are stored in function_object + function_object_i18n tables.
 */
class FunctionCrudService
{
    /**
     * Get a function by ID with all related data.
     */
    public static function getById(int $id, string $culture = 'en'): ?array
    {
        $func = DB::table('function_object')
            ->join('object', 'function_object.id', '=', 'object.id')
            ->where('function_object.id', $id)
            ->where('object.class_name', 'QubitFunctionObject')
            ->select(
                'function_object.*',
                'object.created_at',
                'object.updated_at',
                'object.serial_number'
            )
            ->first();

        if (!$func) {
            return null;
        }

        $i18n = I18nService::getWithFallback('function_object_i18n', $id, $culture);
        $slug = ObjectService::getSlug($id);

        // Get type term name
        $typeName = null;
        if ($func->type_id) {
            $typeI18n = DB::table('term_i18n')
                ->where('id', $func->type_id)
                ->where('culture', $culture)
                ->first();
            $typeName = $typeI18n->name ?? null;
        }

        // Get description status term name
        $statusName = null;
        if ($func->description_status_id) {
            $statusI18n = DB::table('term_i18n')
                ->where('id', $func->description_status_id)
                ->where('culture', $culture)
                ->first();
            $statusName = $statusI18n->name ?? null;
        }

        // Get description detail term name
        $detailName = null;
        if ($func->description_detail_id) {
            $detailI18n = DB::table('term_i18n')
                ->where('id', $func->description_detail_id)
                ->where('culture', $culture)
                ->first();
            $detailName = $detailI18n->name ?? null;
        }

        // Related authority records (via relation table)
        $relatedActors = DB::table('relation')
            ->leftJoin('actor_i18n as subject_name', function ($j) use ($culture) {
                $j->on('relation.subject_id', '=', 'subject_name.id')
                    ->where('subject_name.culture', '=', $culture);
            })
            ->leftJoin('actor_i18n as object_name', function ($j) use ($culture) {
                $j->on('relation.object_id', '=', 'object_name.id')
                    ->where('object_name.culture', '=', $culture);
            })
            ->leftJoin('slug as subject_slug', 'relation.subject_id', '=', 'subject_slug.object_id')
            ->leftJoin('slug as object_slug', 'relation.object_id', '=', 'object_slug.object_id')
            ->where(function ($q) use ($id) {
                $q->where('relation.subject_id', $id)
                  ->orWhere('relation.object_id', $id);
            })
            ->whereIn('relation.type_id', function ($q) {
                $q->select('id')->from('term')->where('taxonomy_id', 61);
            })
            ->select(
                'relation.id as relation_id',
                'relation.subject_id',
                'relation.object_id',
                'relation.type_id',
                'subject_name.authorized_form_of_name as subject_name',
                'object_name.authorized_form_of_name as object_name',
                'subject_slug.slug as subject_slug',
                'object_slug.slug as object_slug'
            )
            ->get()
            ->all();

        return [
            'id' => $id,
            'slug' => $slug,
            'authorizedFormOfName' => $i18n->authorized_form_of_name ?? '',
            'classification' => $i18n->classification ?? '',
            'dates' => $i18n->dates ?? '',
            'description' => $i18n->description ?? '',
            'history' => $i18n->history ?? '',
            'legislation' => $i18n->legislation ?? '',
            'institutionIdentifier' => $i18n->institution_identifier ?? '',
            'revisionHistory' => $i18n->revision_history ?? '',
            'rules' => $i18n->rules ?? '',
            'sources' => $i18n->sources ?? '',
            'typeId' => $func->type_id,
            'typeName' => $typeName,
            'descriptionStatusId' => $func->description_status_id,
            'descriptionStatusName' => $statusName,
            'descriptionDetailId' => $func->description_detail_id,
            'descriptionDetailName' => $detailName,
            'descriptionIdentifier' => $func->description_identifier ?? '',
            'sourceStandard' => $func->source_standard ?? '',
            'sourceCulture' => $func->source_culture,
            'createdAt' => $func->created_at,
            'updatedAt' => $func->updated_at,
            'serialNumber' => $func->serial_number,
            'relatedActors' => $relatedActors,
        ];
    }

    /**
     * Get a function by slug.
     */
    public static function getBySlug(string $slug, string $culture = 'en'): ?array
    {
        $objectId = ObjectService::resolveSlug($slug);
        if (!$objectId) {
            return null;
        }

        return self::getById($objectId, $culture);
    }

    /**
     * Create a new function.
     *
     * @return int The new function ID
     */
    public static function create(array $data, string $culture = 'en'): int
    {
        return DB::transaction(function () use ($data, $culture) {
            // 1. Create object record
            $id = ObjectService::create('QubitFunctionObject');

            // 2. Generate slug
            ObjectService::generateSlug($id, $data['authorizedFormOfName'] ?? null);

            // 3. Create function_object record
            DB::table('function_object')->insert([
                'id' => $id,
                'type_id' => !empty($data['typeId']) ? (int) $data['typeId'] : null,
                'description_status_id' => !empty($data['descriptionStatusId']) ? (int) $data['descriptionStatusId'] : null,
                'description_detail_id' => !empty($data['descriptionDetailId']) ? (int) $data['descriptionDetailId'] : null,
                'description_identifier' => $data['descriptionIdentifier'] ?? null,
                'source_standard' => $data['sourceStandard'] ?? null,
                'source_culture' => $culture,
            ]);

            // 4. Create function_object_i18n record
            $i18nData = self::buildI18nData($data);
            if (!empty($i18nData)) {
                I18nService::save('function_object_i18n', $id, $culture, $i18nData);
            }

            return $id;
        });
    }

    /**
     * Update an existing function.
     */
    public static function update(int $id, array $data, string $culture = 'en'): void
    {
        DB::transaction(function () use ($id, $data, $culture) {
            // 1. Update function_object record
            $funcUpdate = [];
            if (array_key_exists('typeId', $data)) {
                $funcUpdate['type_id'] = !empty($data['typeId']) ? (int) $data['typeId'] : null;
            }
            if (array_key_exists('descriptionStatusId', $data)) {
                $funcUpdate['description_status_id'] = !empty($data['descriptionStatusId']) ? (int) $data['descriptionStatusId'] : null;
            }
            if (array_key_exists('descriptionDetailId', $data)) {
                $funcUpdate['description_detail_id'] = !empty($data['descriptionDetailId']) ? (int) $data['descriptionDetailId'] : null;
            }
            if (array_key_exists('descriptionIdentifier', $data)) {
                $funcUpdate['description_identifier'] = $data['descriptionIdentifier'];
            }
            if (array_key_exists('sourceStandard', $data)) {
                $funcUpdate['source_standard'] = $data['sourceStandard'];
            }
            if (!empty($funcUpdate)) {
                DB::table('function_object')->where('id', $id)->update($funcUpdate);
            }

            // 2. Update function_object_i18n
            $i18nData = self::buildI18nData($data);
            if (!empty($i18nData)) {
                I18nService::save('function_object_i18n', $id, $culture, $i18nData);
            }

            // 3. Touch the object record
            ObjectService::touch($id);
            ObjectService::incrementSerialNumber($id);
        });
    }

    /**
     * Delete a function and all related data.
     */
    public static function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            // 1. Delete relations
            RelationService::deleteBySubjectOrObject($id);

            // 2. Delete function_object_i18n
            I18nService::delete('function_object_i18n', $id);

            // 3. Delete function_object record
            DB::table('function_object')->where('id', $id)->delete();

            // 4. Delete slug + object
            ObjectService::deleteObject($id);
        });
    }

    /**
     * Get ISDF function type terms.
     */
    public static function getFunctionTypes(string $culture = 'en'): array
    {
        return DB::table('term')
            ->leftJoin('term_i18n', function ($join) use ($culture) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', 43) // ISDF Function Types taxonomy
            ->select(['term.id', 'term_i18n.name'])
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }

    /**
     * Get description status terms (Draft, Revised, Final).
     */
    public static function getDescriptionStatuses(string $culture = 'en'): array
    {
        return DB::table('term')
            ->leftJoin('term_i18n', function ($join) use ($culture) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', 33) // Description Statuses taxonomy
            ->select(['term.id', 'term_i18n.name'])
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }

    /**
     * Get description detail terms (Full, Partial, Minimal).
     */
    public static function getDescriptionDetails(string $culture = 'en'): array
    {
        return DB::table('term')
            ->leftJoin('term_i18n', function ($join) use ($culture) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', 31) // Description Detail Levels taxonomy
            ->select(['term.id', 'term_i18n.name'])
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }

    /**
     * Build i18n data array from input data.
     */
    protected static function buildI18nData(array $data): array
    {
        $i18nData = [];
        $fieldMap = [
            'authorizedFormOfName' => 'authorized_form_of_name',
            'classification' => 'classification',
            'dates' => 'dates',
            'description' => 'description',
            'history' => 'history',
            'legislation' => 'legislation',
            'institutionIdentifier' => 'institution_identifier',
            'revisionHistory' => 'revision_history',
            'rules' => 'rules',
            'sources' => 'sources',
        ];

        foreach ($fieldMap as $inputKey => $dbKey) {
            if (array_key_exists($inputKey, $data)) {
                $i18nData[$dbKey] = $data[$inputKey];
            }
        }

        return $i18nData;
    }
}
