<?php

namespace AhgCore\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Other Name Service
 *
 * Manages the `other_name` and `other_name_i18n` tables.
 * Stores parallel/other/standardized forms of names for actors and other entities.
 */
class OtherNameService
{
    /**
     * Get all other names for an object, optionally filtered by type.
     */
    public static function getByObjectId(int $objectId, ?int $typeId = null, string $culture = 'en'): array
    {
        $query = DB::table('other_name')
            ->leftJoin('other_name_i18n', function ($j) use ($culture) {
                $j->on('other_name.id', '=', 'other_name_i18n.id')
                    ->where('other_name_i18n.culture', '=', $culture);
            })
            ->where('other_name.object_id', $objectId);

        if ($typeId !== null) {
            $query->where('other_name.type_id', $typeId);
        }

        return $query->select('other_name.*', 'other_name_i18n.name', 'other_name_i18n.note', 'other_name_i18n.dates')
            ->get()
            ->all();
    }

    /**
     * Save an other name.
     *
     * @return int The other_name ID
     */
    public static function save(int $objectId, int $typeId, string $name, string $culture, ?int $id = null, array $extra = []): int
    {
        if ($id) {
            DB::table('other_name')
                ->where('id', $id)
                ->update([
                    'type_id' => $typeId,
                    'start_date' => $extra['start_date'] ?? null,
                    'end_date' => $extra['end_date'] ?? null,
                ]);
        } else {
            $id = DB::table('other_name')->insertGetId([
                'object_id' => $objectId,
                'type_id' => $typeId,
                'start_date' => $extra['start_date'] ?? null,
                'end_date' => $extra['end_date'] ?? null,
                'source_culture' => $culture,
                'serial_number' => 0,
            ]);
        }

        // Save i18n
        $i18nData = ['name' => $name];
        if (isset($extra['note'])) {
            $i18nData['note'] = $extra['note'];
        }
        if (isset($extra['dates'])) {
            $i18nData['dates'] = $extra['dates'];
        }

        I18nService::save('other_name_i18n', $id, $culture, $i18nData);

        return $id;
    }

    /**
     * Delete an other name and its i18n records.
     */
    public static function delete(int $id): void
    {
        I18nService::delete('other_name_i18n', $id);
        DB::table('other_name')->where('id', $id)->delete();
    }

    /**
     * Delete all other names for an object, optionally by type.
     */
    public static function deleteByObjectId(int $objectId, ?int $typeId = null): void
    {
        $query = DB::table('other_name')
            ->where('object_id', $objectId);

        if ($typeId !== null) {
            $query->where('type_id', $typeId);
        }

        $ids = $query->pluck('id')->all();

        foreach ($ids as $id) {
            self::delete($id);
        }
    }
}
