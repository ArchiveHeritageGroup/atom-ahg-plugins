<?php

namespace AhgRepositoryManage\Services;

use AhgCore\Services\ContactInformationService;
use AhgCore\Services\I18nService;
use AhgCore\Services\ObjectService;
use AhgCore\Services\RelationService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Repository CRUD Service
 *
 * Pure Laravel Query Builder implementation for Repository entity operations.
 * Repositories are actors with a sub-entity table: object -> actor -> repository.
 * Follows ISDIAH standard fields for repository_i18n.
 */
class RepositoryCrudService
{
    /**
     * Get a repository by ID with all related data.
     */
    public static function getById(int $id, string $culture = 'en'): ?array
    {
        $row = DB::table('repository')
            ->join('actor', 'repository.id', '=', 'actor.id')
            ->join('object', 'repository.id', '=', 'object.id')
            ->where('repository.id', $id)
            ->where('object.class_name', 'QubitRepository')
            ->select(
                'repository.*',
                'actor.entity_type_id',
                'actor.description_status_id',
                'actor.description_detail_id',
                'actor.description_identifier',
                'actor.source_standard',
                'actor.corporate_body_identifiers',
                'actor.parent_id',
                'object.created_at',
                'object.updated_at',
                'object.serial_number'
            )
            ->first();

        if (!$row) {
            return null;
        }

        $actorI18n = I18nService::getWithFallback('actor_i18n', $id, $culture);
        $repoI18n = I18nService::getWithFallback('repository_i18n', $id, $culture);
        $slug = ObjectService::getSlug($id);
        $contacts = ContactInformationService::getByActorId($id, $culture);

        return [
            'id' => $id,
            'slug' => $slug,
            // Repository fields
            'identifier' => $row->identifier,
            'descStatusId' => $row->desc_status_id,
            'descDetailId' => $row->desc_detail_id,
            'descIdentifier' => $row->desc_identifier,
            'uploadLimit' => $row->upload_limit,
            'sourceCulture' => $row->source_culture,
            // Actor fields
            'entityTypeId' => $row->entity_type_id,
            'descriptionStatusId' => $row->description_status_id,
            'descriptionDetailId' => $row->description_detail_id,
            'descriptionIdentifier' => $row->description_identifier,
            'sourceStandard' => $row->source_standard,
            'corporateBodyIdentifiers' => $row->corporate_body_identifiers,
            // Object timestamps
            'createdAt' => $row->created_at,
            'updatedAt' => $row->updated_at,
            'serialNumber' => $row->serial_number,
            // Actor i18n (ISAAR fields)
            'authorizedFormOfName' => $actorI18n->authorized_form_of_name ?? '',
            'datesOfExistence' => $actorI18n->dates_of_existence ?? '',
            'history' => $actorI18n->history ?? '',
            'places' => $actorI18n->places ?? '',
            'legalStatus' => $actorI18n->legal_status ?? '',
            'functions' => $actorI18n->functions ?? '',
            'mandates' => $actorI18n->mandates ?? '',
            'internalStructures' => $actorI18n->internal_structures ?? '',
            'generalContext' => $actorI18n->general_context ?? '',
            'institutionResponsibleIdentifier' => $actorI18n->institution_responsible_identifier ?? '',
            'rules' => $actorI18n->rules ?? '',
            'sources' => $actorI18n->sources ?? '',
            'revisionHistory' => $actorI18n->revision_history ?? '',
            // Repository i18n (ISDIAH fields)
            'geoculturalContext' => $repoI18n->geocultural_context ?? '',
            'collectingPolicies' => $repoI18n->collecting_policies ?? '',
            'buildings' => $repoI18n->buildings ?? '',
            'holdings' => $repoI18n->holdings ?? '',
            'findingAids' => $repoI18n->finding_aids ?? '',
            'openingTimes' => $repoI18n->opening_times ?? '',
            'accessConditions' => $repoI18n->access_conditions ?? '',
            'disabledAccess' => $repoI18n->disabled_access ?? '',
            'researchServices' => $repoI18n->research_services ?? '',
            'reproductionServices' => $repoI18n->reproduction_services ?? '',
            'publicFacilities' => $repoI18n->public_facilities ?? '',
            'descInstitutionIdentifier' => $repoI18n->desc_institution_identifier ?? '',
            'descRules' => $repoI18n->desc_rules ?? '',
            'descSources' => $repoI18n->desc_sources ?? '',
            'descRevisionHistory' => $repoI18n->desc_revision_history ?? '',
            // Related data
            'contacts' => $contacts,
        ];
    }

    /**
     * Get a repository by slug.
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
     * Create a new repository.
     *
     * @return int The new repository ID
     */
    public static function create(array $data, string $culture = 'en'): int
    {
        return DB::transaction(function () use ($data, $culture) {
            // 1. Create object record
            $id = ObjectService::create('QubitRepository');

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

            // 4. Create repository record
            DB::table('repository')->insert([
                'id' => $id,
                'identifier' => $data['identifier'] ?? null,
                'desc_status_id' => $data['descStatusId'] ?? null,
                'desc_detail_id' => $data['descDetailId'] ?? null,
                'desc_identifier' => $data['descIdentifier'] ?? null,
                'upload_limit' => $data['uploadLimit'] ?? null,
                'source_culture' => $culture,
            ]);

            // 5. Save actor_i18n (ISAAR fields)
            $actorI18n = self::extractActorI18nData($data);
            if (!empty($actorI18n)) {
                I18nService::save('actor_i18n', $id, $culture, $actorI18n);
            }

            // 6. Save repository_i18n (ISDIAH fields)
            $repoI18n = self::extractRepoI18nData($data);
            if (!empty($repoI18n)) {
                I18nService::save('repository_i18n', $id, $culture, $repoI18n);
            }

            // 7. Save contact information if provided
            if (!empty($data['contacts'])) {
                foreach ($data['contacts'] as $contactData) {
                    ContactInformationService::save($id, $contactData, $culture);
                }
            }

            return $id;
        });
    }

    /**
     * Update an existing repository.
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

            // 2. Update repository record if needed
            $repoUpdate = [];
            if (array_key_exists('identifier', $data)) {
                $repoUpdate['identifier'] = $data['identifier'];
            }
            if (array_key_exists('descStatusId', $data)) {
                $repoUpdate['desc_status_id'] = $data['descStatusId'];
            }
            if (array_key_exists('descDetailId', $data)) {
                $repoUpdate['desc_detail_id'] = $data['descDetailId'];
            }
            if (array_key_exists('descIdentifier', $data)) {
                $repoUpdate['desc_identifier'] = $data['descIdentifier'];
            }
            if (array_key_exists('uploadLimit', $data)) {
                $repoUpdate['upload_limit'] = $data['uploadLimit'];
            }
            if (!empty($repoUpdate)) {
                DB::table('repository')->where('id', $id)->update($repoUpdate);
            }

            // 3. Update actor_i18n (ISAAR fields)
            $actorI18n = self::extractActorI18nData($data);
            if (!empty($actorI18n)) {
                I18nService::save('actor_i18n', $id, $culture, $actorI18n);
            }

            // 4. Update repository_i18n (ISDIAH fields)
            $repoI18n = self::extractRepoI18nData($data);
            if (!empty($repoI18n)) {
                I18nService::save('repository_i18n', $id, $culture, $repoI18n);
            }

            // 5. Touch the object record
            ObjectService::touch($id);
            ObjectService::incrementSerialNumber($id);
        });
    }

    /**
     * Delete a repository and all related data.
     */
    public static function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            // 1. Delete contact information
            ContactInformationService::deleteByActorId($id);

            // 2. Delete relations
            RelationService::deleteBySubjectOrObject($id);

            // 3. Delete repository_i18n
            I18nService::delete('repository_i18n', $id);

            // 4. Delete actor_i18n
            I18nService::delete('actor_i18n', $id);

            // 5. Delete repository record
            DB::table('repository')->where('id', $id)->delete();

            // 6. Delete actor record
            DB::table('actor')->where('id', $id)->delete();

            // 7. Delete slug + object
            ObjectService::deleteObject($id);
        });
    }

    /**
     * Get form dropdown choices for repository forms.
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
        ];
    }

    /**
     * Get the count of holdings (information objects) for a repository.
     */
    public static function getHoldingsCount(int $id): int
    {
        return DB::table('information_object')
            ->where('repository_id', $id)
            ->count();
    }

    /**
     * Extract actor i18n fields (ISAAR) from input data.
     */
    protected static function extractActorI18nData(array $data): array
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

    /**
     * Extract repository i18n fields (ISDIAH) from input data.
     */
    protected static function extractRepoI18nData(array $data): array
    {
        $i18nData = [];
        $mapping = [
            'geoculturalContext' => 'geocultural_context',
            'collectingPolicies' => 'collecting_policies',
            'buildings' => 'buildings',
            'holdings' => 'holdings',
            'findingAids' => 'finding_aids',
            'openingTimes' => 'opening_times',
            'accessConditions' => 'access_conditions',
            'disabledAccess' => 'disabled_access',
            'researchServices' => 'research_services',
            'reproductionServices' => 'reproduction_services',
            'publicFacilities' => 'public_facilities',
            'descInstitutionIdentifier' => 'desc_institution_identifier',
            'descRules' => 'desc_rules',
            'descSources' => 'desc_sources',
            'descRevisionHistory' => 'desc_revision_history',
        ];

        foreach ($mapping as $camelKey => $snakeKey) {
            if (array_key_exists($camelKey, $data)) {
                $i18nData[$snakeKey] = $data[$camelKey];
            }
        }

        return $i18nData;
    }
}
