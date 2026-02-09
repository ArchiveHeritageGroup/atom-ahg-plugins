<?php

namespace AhgRightsHolderManage\Services;

use Illuminate\Database\Capsule\Manager as DB;
use AhgCore\Services\ObjectService;
use AhgCore\Services\I18nService;
use AhgCore\Services\ContactInformationService;

class RightsHolderCrudService
{
    /**
     * Get a rights holder by ID with all related data.
     */
    public static function getById(int $id, string $culture = 'en'): ?array
    {
        $actor = DB::table('actor')
            ->join('object', 'actor.id', '=', 'object.id')
            ->join('slug', 'actor.id', '=', 'slug.object_id')
            ->where('actor.id', $id)
            ->where('object.class_name', 'QubitRightsHolder')
            ->select(['actor.id', 'slug.slug', 'object.serial_number'])
            ->first();

        if (!$actor) {
            return null;
        }

        $i18n = I18nService::getWithFallback('actor_i18n', $id, $culture);

        $contacts = ContactInformationService::getByActorId($id, $culture);

        return [
            'id' => $actor->id,
            'slug' => $actor->slug,
            'authorizedFormOfName' => $i18n->authorized_form_of_name ?? '',
            'contacts' => $contacts,
            'serialNumber' => $actor->serial_number ?? 0,
        ];
    }

    /**
     * Get a rights holder by slug.
     */
    public static function getBySlug(string $slug, string $culture = 'en'): ?array
    {
        $row = DB::table('slug')
            ->join('object', 'slug.object_id', '=', 'object.id')
            ->where('slug.slug', $slug)
            ->where('object.class_name', 'QubitRightsHolder')
            ->select(['slug.object_id'])
            ->first();

        if (!$row) {
            return null;
        }

        return self::getById($row->object_id, $culture);
    }

    /**
     * Create a new rights holder.
     */
    public static function create(array $data, string $culture = 'en'): int
    {
        return DB::transaction(function () use ($data, $culture) {
            $id = ObjectService::create('QubitRightsHolder');

            $slug = ObjectService::generateSlug($id, $data['authorizedFormOfName'] ?? null);

            // Insert actor record
            DB::table('actor')->insert([
                'id' => $id,
                'parent_id' => \QubitActor::ROOT_ID,
                'source_culture' => $culture,
            ]);

            // Insert rights_holder record
            DB::table('rights_holder')->insert([
                'id' => $id,
            ]);

            // Save i18n data
            I18nService::save('actor_i18n', $id, $culture, [
                'authorized_form_of_name' => $data['authorizedFormOfName'] ?? '',
            ]);

            // Save contact information if provided
            if (!empty($data['contacts'])) {
                foreach ($data['contacts'] as $contactData) {
                    ContactInformationService::save($id, $contactData, $culture);
                }
            }

            return $id;
        });
    }

    /**
     * Update a rights holder.
     */
    public static function update(int $id, array $data, string $culture = 'en'): void
    {
        I18nService::save('actor_i18n', $id, $culture, [
            'authorized_form_of_name' => $data['authorizedFormOfName'] ?? '',
        ]);

        ObjectService::touch($id);
    }

    /**
     * Delete a rights holder and all related data.
     */
    public static function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            // Delete contact information
            ContactInformationService::deleteByActorId($id);

            // Delete actor i18n
            I18nService::delete('actor_i18n', $id);

            // Delete rights_holder record
            DB::table('rights_holder')->where('id', $id)->delete();

            // Delete actor record
            DB::table('actor')->where('id', $id)->delete();

            // Delete slug and object
            ObjectService::deleteObject($id);
        });
    }
}
