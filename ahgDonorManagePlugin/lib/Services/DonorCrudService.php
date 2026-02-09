<?php

namespace AhgDonorManage\Services;

use AhgCore\Services\ContactInformationService;
use AhgCore\Services\I18nService;
use AhgCore\Services\ObjectService;
use AhgCore\Services\RelationService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Donor CRUD Service
 *
 * Pure Laravel Query Builder implementation for Donor entity operations.
 * Donors are actors with class_name = 'QubitDonor'.
 */
class DonorCrudService
{
    /**
     * Get a donor by ID with all related data.
     */
    public static function getById(int $id, string $culture = 'en'): ?array
    {
        $actor = DB::table('actor')
            ->join('object', 'actor.id', '=', 'object.id')
            ->where('actor.id', $id)
            ->where('object.class_name', 'QubitDonor')
            ->select('actor.*', 'object.created_at', 'object.updated_at', 'object.serial_number', 'object.class_name')
            ->first();

        if (!$actor) {
            return null;
        }

        $i18n = I18nService::getWithFallback('actor_i18n', $id, $culture);
        $slug = ObjectService::getSlug($id);
        $contacts = ContactInformationService::getByActorId($id, $culture);

        // Related accessions (where donor is object, type = DONOR_ID)
        $accessions = DB::table('relation')
            ->join('accession', 'relation.subject_id', '=', 'accession.id')
            ->leftJoin('accession_i18n', function ($j) use ($culture) {
                $j->on('accession.id', '=', 'accession_i18n.id')
                    ->where('accession_i18n.culture', '=', $culture);
            })
            ->leftJoin('slug', 'accession.id', '=', 'slug.object_id')
            ->where('relation.object_id', $id)
            ->where('relation.type_id', \QubitTerm::DONOR_ID)
            ->select(
                'accession.id',
                'accession.identifier',
                'accession_i18n.title',
                'slug.slug'
            )
            ->get()
            ->all();

        return [
            'id' => $id,
            'slug' => $slug,
            'authorizedFormOfName' => $i18n->authorized_form_of_name ?? '',
            'datesOfExistence' => $i18n->dates_of_existence ?? '',
            'history' => $i18n->history ?? '',
            'entityTypeId' => $actor->entity_type_id,
            'sourceCulture' => $actor->source_culture,
            'createdAt' => $actor->created_at,
            'updatedAt' => $actor->updated_at,
            'serialNumber' => $actor->serial_number,
            'contacts' => $contacts,
            'accessions' => $accessions,
        ];
    }

    /**
     * Get a donor by slug.
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
     * Create a new donor.
     *
     * @return int The new donor ID
     */
    public static function create(array $data, string $culture = 'en'): int
    {
        return DB::transaction(function () use ($data, $culture) {
            // 1. Create object record
            $id = ObjectService::create('QubitDonor');

            // 2. Generate slug
            ObjectService::generateSlug($id, $data['authorizedFormOfName'] ?? null);

            // 3. Create actor record
            DB::table('actor')->insert([
                'id' => $id,
                'entity_type_id' => $data['entityTypeId'] ?? null,
                'parent_id' => \QubitActor::ROOT_ID,
                'source_culture' => $culture,
            ]);

            // 3b. Create donor record (extends actor)
            DB::table('donor')->insert(['id' => $id]);

            // 4. Create actor_i18n record
            $i18nData = [];
            if (isset($data['authorizedFormOfName'])) {
                $i18nData['authorized_form_of_name'] = $data['authorizedFormOfName'];
            }

            if (!empty($i18nData)) {
                I18nService::save('actor_i18n', $id, $culture, $i18nData);
            }

            // 5. Save contact information if provided
            if (!empty($data['contacts'])) {
                foreach ($data['contacts'] as $contactData) {
                    ContactInformationService::save($id, $contactData, $culture);
                }
            }

            return $id;
        });
    }

    /**
     * Update an existing donor.
     */
    public static function update(int $id, array $data, string $culture = 'en'): void
    {
        DB::transaction(function () use ($id, $data, $culture) {
            // 1. Update actor record if needed
            $actorUpdate = [];
            if (array_key_exists('entityTypeId', $data)) {
                $actorUpdate['entity_type_id'] = $data['entityTypeId'];
            }
            if (!empty($actorUpdate)) {
                DB::table('actor')->where('id', $id)->update($actorUpdate);
            }

            // 2. Update actor_i18n
            $i18nData = [];
            if (array_key_exists('authorizedFormOfName', $data)) {
                $i18nData['authorized_form_of_name'] = $data['authorizedFormOfName'];
            }
            if (!empty($i18nData)) {
                I18nService::save('actor_i18n', $id, $culture, $i18nData);
            }

            // 3. Touch the object record
            ObjectService::touch($id);
            ObjectService::incrementSerialNumber($id);
        });
    }

    /**
     * Delete a donor and all related data.
     */
    public static function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            // 1. Delete relations
            RelationService::deleteBySubjectOrObject($id);

            // 2. Delete contact information
            ContactInformationService::deleteByActorId($id);

            // 3. Delete actor_i18n
            I18nService::delete('actor_i18n', $id);

            // 4. Delete donor record
            DB::table('donor')->where('id', $id)->delete();

            // 5. Delete actor record
            DB::table('actor')->where('id', $id)->delete();

            // 5. Delete slug + object
            ObjectService::deleteObject($id);
        });
    }
}
