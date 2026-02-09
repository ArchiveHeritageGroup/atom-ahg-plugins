<?php

namespace AhgCore\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Contact Information Service
 *
 * Manages contact_information and contact_information_i18n tables.
 * Used by actors, donors, repositories, and rights holders.
 */
class ContactInformationService
{
    /**
     * Get all contacts for an actor.
     */
    public static function getByActorId(int $actorId, string $culture = 'en'): array
    {
        return DB::table('contact_information')
            ->leftJoin('contact_information_i18n', function ($j) use ($culture) {
                $j->on('contact_information.id', '=', 'contact_information_i18n.id')
                    ->where('contact_information_i18n.culture', '=', $culture);
            })
            ->where('contact_information.actor_id', $actorId)
            ->select(
                'contact_information.*',
                'contact_information_i18n.contact_type as i18n_contact_type',
                'contact_information_i18n.city',
                'contact_information_i18n.region',
                'contact_information_i18n.note'
            )
            ->get()
            ->all();
    }

    /**
     * Get a single contact by ID.
     */
    public static function getById(int $id, string $culture = 'en'): ?object
    {
        return DB::table('contact_information')
            ->leftJoin('contact_information_i18n', function ($j) use ($culture) {
                $j->on('contact_information.id', '=', 'contact_information_i18n.id')
                    ->where('contact_information_i18n.culture', '=', $culture);
            })
            ->where('contact_information.id', $id)
            ->select(
                'contact_information.*',
                'contact_information_i18n.contact_type as i18n_contact_type',
                'contact_information_i18n.city',
                'contact_information_i18n.region',
                'contact_information_i18n.note'
            )
            ->first();
    }

    /**
     * Save (create or update) a contact information record.
     *
     * @param int    $actorId The parent actor ID
     * @param array  $data    Contact fields
     * @param string $culture Culture for i18n fields
     * @param int|null $id    Existing contact ID (null for create)
     *
     * @return int The contact information ID
     */
    public static function save(int $actorId, array $data, string $culture, ?int $id = null): int
    {
        $now = date('Y-m-d H:i:s');

        // Base table fields
        $baseFields = [
            'contact_person', 'street_address', 'website', 'email',
            'telephone', 'fax', 'postal_code', 'country_code',
            'longitude', 'latitude', 'primary_contact', 'contact_note',
        ];

        $baseData = array_intersect_key($data, array_flip($baseFields));

        // I18n fields
        $i18nFields = ['city', 'region', 'note', 'contact_type'];
        $i18nData = array_intersect_key($data, array_flip($i18nFields));

        if ($id) {
            // Update existing
            $baseData['updated_at'] = $now;
            DB::table('contact_information')
                ->where('id', $id)
                ->update($baseData);
        } else {
            // Create new
            $baseData['actor_id'] = $actorId;
            $baseData['created_at'] = $now;
            $baseData['updated_at'] = $now;
            $baseData['source_culture'] = $culture;
            $baseData['serial_number'] = 0;

            $id = DB::table('contact_information')->insertGetId($baseData);
        }

        // Save i18n data
        if (!empty($i18nData)) {
            I18nService::save('contact_information_i18n', $id, $culture, $i18nData);
        }

        return $id;
    }

    /**
     * Delete a contact and all its i18n records.
     */
    public static function delete(int $id): void
    {
        I18nService::delete('contact_information_i18n', $id);
        DB::table('contact_information')->where('id', $id)->delete();
    }

    /**
     * Delete all contacts for an actor.
     */
    public static function deleteByActorId(int $actorId): void
    {
        $ids = DB::table('contact_information')
            ->where('actor_id', $actorId)
            ->pluck('id')
            ->all();

        foreach ($ids as $id) {
            self::delete($id);
        }
    }
}
