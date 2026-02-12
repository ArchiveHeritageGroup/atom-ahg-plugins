<?php

namespace AhgActorManage\Services;

use AhgCore\Services\ContactInformationService;
use AhgCore\Services\EventService;
use AhgCore\Services\I18nService;
use AhgCore\Services\NoteService;
use AhgCore\Services\ObjectService;
use AhgCore\Services\OtherNameService;
use AhgCore\Services\RelationService;
use AhgCore\Services\TermRelationService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Actor CRUD Service
 *
 * Pure Laravel Query Builder implementation for Actor entity operations.
 * Actors (class_name = 'QubitActor') follow: object -> actor.
 * Related sub-tables: other_name, event, relation, contact_information,
 * note, object_term_relation.
 */
class ActorCrudService
{
    /**
     * Get an actor by ID with all related data.
     */
    public static function getById(int $id, string $culture = 'en'): ?array
    {
        $actor = DB::table('actor')
            ->join('object', 'actor.id', '=', 'object.id')
            ->where('actor.id', $id)
            ->where('object.class_name', 'QubitActor')
            ->select(
                'actor.*',
                'object.created_at',
                'object.updated_at',
                'object.serial_number'
            )
            ->first();

        if (!$actor) {
            return null;
        }

        $i18n = I18nService::getWithFallback('actor_i18n', $id, $culture);
        $slug = ObjectService::getSlug($id);
        $contacts = ContactInformationService::getByActorId($id, $culture);

        return [
            'id' => $id,
            'slug' => $slug,
            // Actor fields
            'entityTypeId' => $actor->entity_type_id,
            'descriptionStatusId' => $actor->description_status_id,
            'descriptionDetailId' => $actor->description_detail_id,
            'descriptionIdentifier' => $actor->description_identifier,
            'sourceStandard' => $actor->source_standard,
            'corporateBodyIdentifiers' => $actor->corporate_body_identifiers,
            'parentId' => $actor->parent_id,
            'sourceCulture' => $actor->source_culture,
            // Object timestamps
            'createdAt' => $actor->created_at,
            'updatedAt' => $actor->updated_at,
            'serialNumber' => $actor->serial_number,
            // Actor i18n (ISAAR fields)
            'authorizedFormOfName' => $i18n->authorized_form_of_name ?? '',
            'datesOfExistence' => $i18n->dates_of_existence ?? '',
            'history' => $i18n->history ?? '',
            'places' => $i18n->places ?? '',
            'legalStatus' => $i18n->legal_status ?? '',
            'functions' => $i18n->functions ?? '',
            'mandates' => $i18n->mandates ?? '',
            'internalStructures' => $i18n->internal_structures ?? '',
            'generalContext' => $i18n->general_context ?? '',
            'institutionResponsibleIdentifier' => $i18n->institution_responsible_identifier ?? '',
            'rules' => $i18n->rules ?? '',
            'sources' => $i18n->sources ?? '',
            'revisionHistory' => $i18n->revision_history ?? '',
            // Related data
            'contacts' => $contacts,
        ];
    }

    /**
     * Get an actor by slug.
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
     * Create a new actor.
     *
     * @return int The new actor ID
     */
    public static function create(array $data, string $culture = 'en'): int
    {
        return DB::transaction(function () use ($data, $culture) {
            // 1. Create object record
            $id = ObjectService::create('QubitActor');

            // 2. Generate slug
            ObjectService::generateSlug($id, $data['authorizedFormOfName'] ?? null);

            // 3. Create actor record
            DB::table('actor')->insert([
                'id' => $id,
                'entity_type_id' => $data['entityTypeId'] ?? null,
                'description_status_id' => $data['descriptionStatusId'] ?? null,
                'description_detail_id' => $data['descriptionDetailId'] ?? null,
                'description_identifier' => $data['descriptionIdentifier'] ?? null,
                'source_standard' => $data['sourceStandard'] ?? null,
                'corporate_body_identifiers' => $data['corporateBodyIdentifiers'] ?? null,
                'parent_id' => \QubitActor::ROOT_ID,
                'source_culture' => $culture,
            ]);

            // 4. Save actor_i18n (ISAAR fields)
            $i18nData = self::extractI18nData($data);
            if (!empty($i18nData)) {
                I18nService::save('actor_i18n', $id, $culture, $i18nData);
            }

            // 5. Save contact information if provided
            if (!empty($data['contacts'])) {
                foreach ($data['contacts'] as $contactData) {
                    ContactInformationService::save($id, $contactData, $culture);
                }
            }

            // 6. Save other names if provided
            if (!empty($data['otherNames'])) {
                foreach ($data['otherNames'] as $nameData) {
                    OtherNameService::save(
                        $id,
                        $nameData['typeId'],
                        $nameData['name'],
                        $culture,
                        null,
                        $nameData
                    );
                }
            }

            return $id;
        });
    }

    /**
     * Update an existing actor.
     */
    public static function update(int $id, array $data, string $culture = 'en'): void
    {
        DB::transaction(function () use ($id, $data, $culture) {
            // 1. Update actor record if needed
            $actorUpdate = [];
            if (array_key_exists('entityTypeId', $data)) {
                $actorUpdate['entity_type_id'] = $data['entityTypeId'];
            }
            if (array_key_exists('descriptionStatusId', $data)) {
                $actorUpdate['description_status_id'] = $data['descriptionStatusId'];
            }
            if (array_key_exists('descriptionDetailId', $data)) {
                $actorUpdate['description_detail_id'] = $data['descriptionDetailId'];
            }
            if (array_key_exists('descriptionIdentifier', $data)) {
                $actorUpdate['description_identifier'] = $data['descriptionIdentifier'];
            }
            if (array_key_exists('sourceStandard', $data)) {
                $actorUpdate['source_standard'] = $data['sourceStandard'];
            }
            if (array_key_exists('corporateBodyIdentifiers', $data)) {
                $actorUpdate['corporate_body_identifiers'] = $data['corporateBodyIdentifiers'];
            }
            if (!empty($actorUpdate)) {
                DB::table('actor')->where('id', $id)->update($actorUpdate);
            }

            // 2. Update actor_i18n (ISAAR fields)
            $i18nData = self::extractI18nData($data);
            if (!empty($i18nData)) {
                I18nService::save('actor_i18n', $id, $culture, $i18nData);
            }

            // 3. Touch the object record
            ObjectService::touch($id);
            ObjectService::incrementSerialNumber($id);
        });
    }

    /**
     * Delete an actor and all related data.
     */
    public static function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            // 1. Delete events (actor as creator/subject)
            EventService::deleteByActorId($id);

            // 2. Delete relations (actor-to-actor, actor-to-function, etc.)
            RelationService::deleteBySubjectOrObject($id);

            // 3. Delete contact information
            ContactInformationService::deleteByActorId($id);

            // 4. Delete other names
            OtherNameService::deleteByObjectId($id);

            // 5. Delete notes
            NoteService::deleteByObjectId($id);

            // 6. Delete term relations (access points)
            TermRelationService::deleteByObjectId($id);

            // 7. Delete actor_i18n
            I18nService::delete('actor_i18n', $id);

            // 8. Delete actor record
            DB::table('actor')->where('id', $id)->delete();

            // 9. Delete slug + object
            ObjectService::deleteObject($id);
        });
    }

    /**
     * Get other names (parallel, other, standardized forms) for an actor.
     */
    public static function getOtherNames(int $id, string $culture = 'en'): array
    {
        return OtherNameService::getByObjectId($id, null, $culture);
    }

    /**
     * Get actors related to this actor (via relation table).
     */
    public static function getRelatedActors(int $id, string $culture = 'en'): array
    {
        // Relations where this actor is subject — get the object actor
        $asSubject = DB::table('relation')
            ->join('actor', 'relation.object_id', '=', 'actor.id')
            ->join('object', 'actor.id', '=', 'object.id')
            ->leftJoin('actor_i18n', function ($j) use ($culture) {
                $j->on('actor.id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', $culture);
            })
            ->leftJoin('relation_i18n', function ($j) use ($culture) {
                $j->on('relation.id', '=', 'relation_i18n.id')
                    ->where('relation_i18n.culture', '=', $culture);
            })
            ->leftJoin('slug', 'actor.id', '=', 'slug.object_id')
            ->where('relation.subject_id', $id)
            ->where('object.class_name', 'QubitActor')
            ->select(
                'relation.id as relationId',
                'relation.type_id as typeId',
                'actor.id',
                'actor_i18n.authorized_form_of_name as name',
                'relation_i18n.description as relationDescription',
                'slug.slug'
            )
            ->get()
            ->all();

        // Relations where this actor is object — get the subject actor
        $asObject = DB::table('relation')
            ->join('actor', 'relation.subject_id', '=', 'actor.id')
            ->join('object', 'actor.id', '=', 'object.id')
            ->leftJoin('actor_i18n', function ($j) use ($culture) {
                $j->on('actor.id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', $culture);
            })
            ->leftJoin('relation_i18n', function ($j) use ($culture) {
                $j->on('relation.id', '=', 'relation_i18n.id')
                    ->where('relation_i18n.culture', '=', $culture);
            })
            ->leftJoin('slug', 'actor.id', '=', 'slug.object_id')
            ->where('relation.object_id', $id)
            ->where('object.class_name', 'QubitActor')
            ->select(
                'relation.id as relationId',
                'relation.type_id as typeId',
                'actor.id',
                'actor_i18n.authorized_form_of_name as name',
                'relation_i18n.description as relationDescription',
                'slug.slug'
            )
            ->get()
            ->all();

        return array_merge($asSubject, $asObject);
    }

    /**
     * Get information objects related to this actor (creator/subject events).
     */
    public static function getRelatedResources(int $id, string $culture = 'en'): array
    {
        return DB::table('event')
            ->join('information_object', 'event.object_id', '=', 'information_object.id')
            ->leftJoin('information_object_i18n', function ($j) use ($culture) {
                $j->on('information_object.id', '=', 'information_object_i18n.id')
                    ->where('information_object_i18n.culture', '=', $culture);
            })
            ->leftJoin('event_i18n', function ($j) use ($culture) {
                $j->on('event.id', '=', 'event_i18n.id')
                    ->where('event_i18n.culture', '=', $culture);
            })
            ->leftJoin('slug', 'information_object.id', '=', 'slug.object_id')
            ->where('event.actor_id', $id)
            ->select(
                'event.id as eventId',
                'event.type_id as eventTypeId',
                'information_object.id',
                'information_object_i18n.title',
                'information_object.identifier',
                'event_i18n.date',
                'slug.slug'
            )
            ->get()
            ->all();
    }

    /**
     * Get functions related to this actor (via relation table, ISDF).
     */
    public static function getRelatedFunctions(int $id, string $culture = 'en'): array
    {
        return DB::table('relation')
            ->join('term', 'relation.object_id', '=', 'term.id')
            ->join('object', 'term.id', '=', 'object.id')
            ->leftJoin('term_i18n', function ($j) use ($culture) {
                $j->on('term.id', '=', 'term_i18n.id')
                    ->where('term_i18n.culture', '=', $culture);
            })
            ->leftJoin('relation_i18n', function ($j) use ($culture) {
                $j->on('relation.id', '=', 'relation_i18n.id')
                    ->where('relation_i18n.culture', '=', $culture);
            })
            ->leftJoin('slug', 'term.id', '=', 'slug.object_id')
            ->where('relation.subject_id', $id)
            ->where('object.class_name', 'QubitFunction')
            ->select(
                'relation.id as relationId',
                'relation.type_id as typeId',
                'term.id',
                'term_i18n.name',
                'relation_i18n.description as relationDescription',
                'slug.slug'
            )
            ->get()
            ->all();
    }

    /**
     * Get form dropdown choices for actor forms.
     */
    public static function getFormChoices(string $culture = 'en'): array
    {
        $termLookup = function (int $taxonomyId) use ($culture) {
            return DB::table('term')
                ->leftJoin('term_i18n', function ($j) use ($culture) {
                    $j->on('term.id', '=', 'term_i18n.id')
                        ->where('term_i18n.culture', '=', $culture);
                })
                ->where('term.taxonomy_id', $taxonomyId)
                ->select('term.id', 'term_i18n.name')
                ->orderBy('term_i18n.name')
                ->get()
                ->all();
        };

        return [
            'entityTypes' => $termLookup(\QubitTaxonomy::ACTOR_ENTITY_TYPE_ID),
            'descriptionStatuses' => $termLookup(\QubitTaxonomy::DESCRIPTION_STATUS_ID),
            'descriptionDetails' => $termLookup(\QubitTaxonomy::DESCRIPTION_DETAIL_LEVEL_ID),
            'nameTypes' => $termLookup(\QubitTaxonomy::ACTOR_NAME_TYPE_ID),
            'relationTypes' => $termLookup(\QubitTaxonomy::ACTOR_RELATION_TYPE_ID),
        ];
    }

    /**
     * Extract actor i18n fields (ISAAR) from input data.
     */
    protected static function extractI18nData(array $data): array
    {
        $i18nData = [];
        $mapping = [
            'authorizedFormOfName' => 'authorized_form_of_name',
            'datesOfExistence' => 'dates_of_existence',
            'history' => 'history',
            'places' => 'places',
            'legalStatus' => 'legal_status',
            'functions' => 'functions',
            'mandates' => 'mandates',
            'internalStructures' => 'internal_structures',
            'generalContext' => 'general_context',
            'institutionResponsibleIdentifier' => 'institution_responsible_identifier',
            'rules' => 'rules',
            'sources' => 'sources',
            'revisionHistory' => 'revision_history',
        ];

        foreach ($mapping as $camelKey => $snakeKey) {
            if (array_key_exists($camelKey, $data)) {
                $i18nData[$snakeKey] = $data[$camelKey];
            }
        }

        return $i18nData;
    }
}
