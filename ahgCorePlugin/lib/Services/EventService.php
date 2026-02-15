<?php

namespace AhgCore\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Event Service
 *
 * Manages the `event` and `event_i18n` tables.
 * Events link actors to objects with typed relationships and date ranges.
 */
class EventService
{
    /**
     * Get all events for an object.
     */
    public static function getByObjectId(int $objectId, string $culture = 'en'): array
    {
        return DB::table('event')
            ->leftJoin('event_i18n', function ($j) use ($culture) {
                $j->on('event.id', '=', 'event_i18n.id')
                    ->where('event_i18n.culture', '=', $culture);
            })
            ->where('event.object_id', $objectId)
            ->select('event.*', 'event_i18n.name', 'event_i18n.description', 'event_i18n.date')
            ->get()
            ->all();
    }

    /**
     * Get all events for an actor.
     */
    public static function getByActorId(int $actorId, string $culture = 'en'): array
    {
        return DB::table('event')
            ->leftJoin('event_i18n', function ($j) use ($culture) {
                $j->on('event.id', '=', 'event_i18n.id')
                    ->where('event_i18n.culture', '=', $culture);
            })
            ->where('event.actor_id', $actorId)
            ->select('event.*', 'event_i18n.name', 'event_i18n.description', 'event_i18n.date')
            ->get()
            ->all();
    }

    /**
     * Save an event.
     *
     * @return int The event ID
     */
    public static function save(array $data, string $culture, array $i18nData = [], ?int $id = null): int
    {
        $baseFields = ['type_id', 'object_id', 'actor_id', 'start_date', 'start_time', 'end_date', 'end_time', 'source_culture'];
        $baseData = array_intersect_key($data, array_flip($baseFields));

        if ($id) {
            DB::table('event')
                ->where('id', $id)
                ->update($baseData);
        } else {
            if (!isset($baseData['source_culture'])) {
                $baseData['source_culture'] = $culture;
            }

            // event.id is a FK to object.id (entity inheritance) — create object first
            $id = ObjectService::create('QubitEvent');
            $baseData['id'] = $id;
            DB::table('event')->insert($baseData);
        }

        // Save i18n data
        if (!empty($i18nData)) {
            I18nService::save('event_i18n', $id, $culture, $i18nData);
        }

        return $id;
    }

    /**
     * Delete an event and its i18n records.
     */
    public static function delete(int $id): void
    {
        I18nService::delete('event_i18n', $id);
        DB::table('event')->where('id', $id)->delete();
        // Clean up the parent object row (entity inheritance)
        DB::table('object')->where('id', $id)->delete();
    }

    /**
     * Delete all events for an object.
     */
    public static function deleteByObjectId(int $objectId): void
    {
        $ids = DB::table('event')
            ->where('object_id', $objectId)
            ->pluck('id')
            ->all();

        foreach ($ids as $id) {
            self::delete($id);
        }
    }

    /**
     * Delete all events for an actor.
     */
    public static function deleteByActorId(int $actorId): void
    {
        $ids = DB::table('event')
            ->where('actor_id', $actorId)
            ->pluck('id')
            ->all();

        foreach ($ids as $id) {
            self::delete($id);
        }
    }
}
