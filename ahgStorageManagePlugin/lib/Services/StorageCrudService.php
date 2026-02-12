<?php

namespace AhgStorageManage\Services;

use AhgCore\Services\I18nService;
use AhgCore\Services\ObjectService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Storage CRUD Service
 *
 * Pure Laravel Query Builder implementation for Physical Object (storage location) operations.
 * Physical objects follow: object -> physical_object (NOT actor-based).
 */
class StorageCrudService
{
    /**
     * Get a physical object by ID with all related data.
     */
    public static function getById(int $id, string $culture = 'en'): ?array
    {
        $row = DB::table('physical_object')
            ->join('object', 'physical_object.id', '=', 'object.id')
            ->where('physical_object.id', $id)
            ->where('object.class_name', 'QubitPhysicalObject')
            ->select('physical_object.*', 'object.created_at', 'object.updated_at', 'object.serial_number')
            ->first();

        if (!$row) {
            return null;
        }

        $i18n = I18nService::getWithFallback('physical_object_i18n', $id, $culture);
        $slug = ObjectService::getSlug($id);

        // Extended data (AHG extension table — may not exist)
        $extended = null;
        try {
            $extended = DB::table('physical_object_extended')
                ->where('physical_object_id', $id)
                ->first();
        } catch (\Exception $e) {
            // Table does not exist on this installation
        }

        $result = [
            'id' => $id,
            'slug' => $slug,
            'name' => $i18n->name ?? '',
            'description' => $i18n->description ?? '',
            'location' => $i18n->location ?? '',
            'typeId' => $row->type_id,
            'sourceCulture' => $row->source_culture,
            'createdAt' => $row->created_at,
            'updatedAt' => $row->updated_at,
            'serialNumber' => $row->serial_number,
        ];

        if ($extended) {
            $result['extended'] = [
                'building' => $extended->building,
                'floor' => $extended->floor,
                'room' => $extended->room,
                'aisle' => $extended->aisle,
                'bay' => $extended->bay,
                'rack' => $extended->rack,
                'shelf' => $extended->shelf,
                'position' => $extended->position,
                'barcode' => $extended->barcode,
                'referenceCode' => $extended->reference_code,
                'width' => $extended->width,
                'height' => $extended->height,
                'depth' => $extended->depth,
                'totalCapacity' => $extended->total_capacity,
                'usedCapacity' => $extended->used_capacity,
                'availableCapacity' => $extended->available_capacity,
                'capacityUnit' => $extended->capacity_unit,
                'totalLinearMetres' => $extended->total_linear_metres,
                'usedLinearMetres' => $extended->used_linear_metres,
                'availableLinearMetres' => $extended->available_linear_metres,
                'climateControlled' => (bool) $extended->climate_controlled,
                'temperatureMin' => $extended->temperature_min,
                'temperatureMax' => $extended->temperature_max,
                'humidityMin' => $extended->humidity_min,
                'humidityMax' => $extended->humidity_max,
                'securityLevel' => $extended->security_level,
                'accessRestrictions' => $extended->access_restrictions,
                'status' => $extended->status,
                'notes' => $extended->notes,
            ];
        }

        return $result;
    }

    /**
     * Get a physical object by slug.
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
     * Create a new physical object.
     *
     * @return int The new physical object ID
     */
    public static function create(array $data, string $culture = 'en'): int
    {
        return DB::transaction(function () use ($data, $culture) {
            // 1. Create object record
            $id = ObjectService::create('QubitPhysicalObject');

            // 2. Generate slug
            ObjectService::generateSlug($id, $data['name'] ?? null);

            // 3. Create physical_object record
            DB::table('physical_object')->insert([
                'id' => $id,
                'type_id' => $data['typeId'] ?? null,
                'source_culture' => $culture,
            ]);

            // 4. Create physical_object_i18n record
            $i18nData = [];
            if (isset($data['name'])) {
                $i18nData['name'] = $data['name'];
            }
            if (isset($data['description'])) {
                $i18nData['description'] = $data['description'];
            }
            if (isset($data['location'])) {
                $i18nData['location'] = $data['location'];
            }
            if (!empty($i18nData)) {
                I18nService::save('physical_object_i18n', $id, $culture, $i18nData);
            }

            // 5. Create extended record if data provided
            if (!empty($data['extended'])) {
                self::saveExtended($id, $data['extended']);
            }

            return $id;
        });
    }

    /**
     * Update an existing physical object.
     */
    public static function update(int $id, array $data, string $culture = 'en'): void
    {
        DB::transaction(function () use ($id, $data, $culture) {
            // 1. Update physical_object record if needed
            $poUpdate = [];
            if (array_key_exists('typeId', $data)) {
                $poUpdate['type_id'] = $data['typeId'];
            }
            if (!empty($poUpdate)) {
                DB::table('physical_object')->where('id', $id)->update($poUpdate);
            }

            // 2. Update physical_object_i18n
            $i18nData = [];
            if (array_key_exists('name', $data)) {
                $i18nData['name'] = $data['name'];
            }
            if (array_key_exists('description', $data)) {
                $i18nData['description'] = $data['description'];
            }
            if (array_key_exists('location', $data)) {
                $i18nData['location'] = $data['location'];
            }
            if (!empty($i18nData)) {
                I18nService::save('physical_object_i18n', $id, $culture, $i18nData);
            }

            // 3. Update extended data if provided
            if (array_key_exists('extended', $data)) {
                self::saveExtended($id, $data['extended']);
            }

            // 4. Touch the object record
            ObjectService::touch($id);
            ObjectService::incrementSerialNumber($id);
        });
    }

    /**
     * Delete a physical object and all related data.
     */
    public static function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            // 1. Delete extended data
            try {
                DB::table('physical_object_extended')
                    ->where('physical_object_id', $id)
                    ->delete();
            } catch (\Exception $e) {
                // Table may not exist
            }

            // 2. Delete physical_object_i18n
            I18nService::delete('physical_object_i18n', $id);

            // 3. Delete physical_object record
            DB::table('physical_object')->where('id', $id)->delete();

            // 4. Delete slug + object
            ObjectService::deleteObject($id);
        });
    }

    /**
     * Get storage type terms.
     */
    public static function getTypes(string $culture = 'en'): array
    {
        return DB::table('term')
            ->leftJoin('term_i18n', function ($j) use ($culture) {
                $j->on('term.id', '=', 'term_i18n.id')
                    ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', \QubitTaxonomy::PHYSICAL_OBJECT_TYPE_ID)
            ->select('term.id', 'term_i18n.name')
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }

    /**
     * Get information objects linked to this physical object.
     */
    public static function getLinkedObjects(int $id, string $culture = 'en'): array
    {
        return DB::table('relation')
            ->join('information_object', 'relation.subject_id', '=', 'information_object.id')
            ->leftJoin('information_object_i18n', function ($j) use ($culture) {
                $j->on('information_object.id', '=', 'information_object_i18n.id')
                    ->where('information_object_i18n.culture', '=', $culture);
            })
            ->leftJoin('slug', 'information_object.id', '=', 'slug.object_id')
            ->where('relation.object_id', $id)
            ->where('relation.type_id', \QubitTerm::HAS_PHYSICAL_OBJECT_ID)
            ->select(
                'information_object.id',
                'information_object_i18n.title',
                'information_object.identifier',
                'slug.slug'
            )
            ->get()
            ->all();
    }

    /**
     * Save or update extended physical object data (AHG extension).
     * Note: available_capacity and available_linear_metres are STORED GENERATED — do not write them.
     */
    protected static function saveExtended(int $physicalObjectId, array $data): void
    {
        try {
            // Map camelCase keys to snake_case columns
            $fields = [
                'building' => $data['building'] ?? null,
                'floor' => $data['floor'] ?? null,
                'room' => $data['room'] ?? null,
                'aisle' => $data['aisle'] ?? null,
                'bay' => $data['bay'] ?? null,
                'rack' => $data['rack'] ?? null,
                'shelf' => $data['shelf'] ?? null,
                'position' => $data['position'] ?? null,
                'barcode' => $data['barcode'] ?? null,
                'reference_code' => $data['referenceCode'] ?? null,
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null,
                'depth' => $data['depth'] ?? null,
                'total_capacity' => $data['totalCapacity'] ?? null,
                'used_capacity' => $data['usedCapacity'] ?? null,
                'capacity_unit' => $data['capacityUnit'] ?? null,
                'total_linear_metres' => $data['totalLinearMetres'] ?? null,
                'used_linear_metres' => $data['usedLinearMetres'] ?? null,
                'climate_controlled' => $data['climateControlled'] ?? null,
                'temperature_min' => $data['temperatureMin'] ?? null,
                'temperature_max' => $data['temperatureMax'] ?? null,
                'humidity_min' => $data['humidityMin'] ?? null,
                'humidity_max' => $data['humidityMax'] ?? null,
                'security_level' => $data['securityLevel'] ?? null,
                'access_restrictions' => $data['accessRestrictions'] ?? null,
                'status' => $data['status'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            // Filter out null values for update (keep them for insert)
            $exists = DB::table('physical_object_extended')
                ->where('physical_object_id', $physicalObjectId)
                ->exists();

            if ($exists) {
                DB::table('physical_object_extended')
                    ->where('physical_object_id', $physicalObjectId)
                    ->update($fields);
            } else {
                $fields['physical_object_id'] = $physicalObjectId;
                DB::table('physical_object_extended')->insert($fields);
            }
        } catch (\Exception $e) {
            // Table may not exist on this installation
        }
    }
}
