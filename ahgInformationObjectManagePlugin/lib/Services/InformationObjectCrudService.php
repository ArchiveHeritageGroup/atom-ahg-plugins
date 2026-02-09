<?php

namespace AhgInformationObjectManage\Services;

use AhgCore\Services\EventService;
use AhgCore\Services\I18nService;
use AhgCore\Services\NoteService;
use AhgCore\Services\ObjectService;
use AhgCore\Services\RelationService;
use AhgCore\Services\TermRelationService;
use Illuminate\Database\Capsule\Manager as DB;

class InformationObjectCrudService
{
    // Publication status type
    const STATUS_TYPE_PUBLICATION = 158;
    const STATUS_DRAFT = 159;
    const STATUS_PUBLISHED = 160;

    // Name access point relation type
    const RELATION_NAME_ACCESS_POINT = 161;

    // Taxonomy IDs
    const TAXONOMY_LEVELS_OF_DESCRIPTION = 32;
    const TAXONOMY_DESCRIPTION_STATUSES = 33;
    const TAXONOMY_DESCRIPTION_DETAIL_LEVELS = 31;
    const TAXONOMY_EVENT_TYPES = 40;
    const TAXONOMY_SUBJECT_ACCESS_POINTS = 35;
    const TAXONOMY_PLACE_ACCESS_POINTS = 42;
    const TAXONOMY_GENRE_ACCESS_POINTS = 78;

    // Root information object
    const ROOT_ID = 1;

    /**
     * camelCase -> snake_case field map for information_object_i18n.
     */
    protected static array $i18nFieldMap = [
        'title' => 'title',
        'extentAndMedium' => 'extent_and_medium',
        'archivalHistory' => 'archival_history',
        'acquisition' => 'acquisition',
        'scopeAndContent' => 'scope_and_content',
        'appraisal' => 'appraisal',
        'accruals' => 'accruals',
        'arrangement' => 'arrangement',
        'accessConditions' => 'access_conditions',
        'reproductionConditions' => 'reproduction_conditions',
        'physicalCharacteristics' => 'physical_characteristics',
        'findingAids' => 'finding_aids',
        'locationOfOriginals' => 'location_of_originals',
        'locationOfCopies' => 'location_of_copies',
        'relatedUnitsOfDescription' => 'related_units_of_description',
        'institutionResponsibleIdentifier' => 'institution_responsible_identifier',
        'rules' => 'rules',
        'sources' => 'sources',
        'revisionHistory' => 'revision_history',
    ];

    /**
     * Get an information object by ID with all related data.
     */
    public static function getById(int $id, string $culture = 'en'): ?array
    {
        $io = DB::table('information_object')
            ->join('object', 'information_object.id', '=', 'object.id')
            ->where('information_object.id', $id)
            ->select(
                'information_object.*',
                'object.created_at',
                'object.updated_at'
            )
            ->first();

        if (!$io) {
            return null;
        }

        $i18n = I18nService::getWithFallback('information_object_i18n', $id, $culture);
        $slug = ObjectService::getSlug($id);

        // Level of description name
        $levelName = null;
        if ($io->level_of_description_id) {
            $levelName = DB::table('term_i18n')
                ->where('id', $io->level_of_description_id)
                ->where('culture', $culture)
                ->value('name');
        }

        // Repository name
        $repoName = null;
        if ($io->repository_id) {
            $repoName = DB::table('actor_i18n')
                ->where('id', $io->repository_id)
                ->where('culture', $culture)
                ->value('authorized_form_of_name');
        }

        // Parent info
        $parentTitle = null;
        $parentSlug = null;
        if ($io->parent_id && $io->parent_id != self::ROOT_ID) {
            $parentI18n = I18nService::getWithFallback('information_object_i18n', $io->parent_id, $culture);
            $parentTitle = $parentI18n->title ?? null;
            $parentSlug = ObjectService::getSlug($io->parent_id);
        }

        // Publication status
        $pubStatus = DB::table('status')
            ->where('object_id', $id)
            ->where('type_id', self::STATUS_TYPE_PUBLICATION)
            ->first();

        // Events
        $events = EventService::getByObjectId($id, $culture);

        // Enrich events with actor names
        foreach ($events as &$event) {
            $event->actor_name = null;
            $event->actor_slug = null;
            if ($event->actor_id) {
                $actorI18n = DB::table('actor_i18n')
                    ->where('id', $event->actor_id)
                    ->where('culture', $culture)
                    ->first();
                $event->actor_name = $actorI18n->authorized_form_of_name ?? null;
                $event->actor_slug = ObjectService::getSlug($event->actor_id);
            }
        }
        unset($event);

        // Access points
        $subjectAPs = TermRelationService::getByObjectId($id, self::TAXONOMY_SUBJECT_ACCESS_POINTS, $culture);
        $placeAPs = TermRelationService::getByObjectId($id, self::TAXONOMY_PLACE_ACCESS_POINTS, $culture);
        $genreAPs = TermRelationService::getByObjectId($id, self::TAXONOMY_GENRE_ACCESS_POINTS, $culture);

        // Name access points (via relation table, type_id = 161)
        $nameAPs = DB::table('relation')
            ->leftJoin('actor_i18n', function ($j) use ($culture) {
                $j->on('relation.object_id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', $culture);
            })
            ->leftJoin('slug', 'relation.object_id', '=', 'slug.object_id')
            ->where('relation.subject_id', $id)
            ->where('relation.type_id', self::RELATION_NAME_ACCESS_POINT)
            ->select(
                'relation.id as relation_id',
                'relation.object_id as actor_id',
                'actor_i18n.authorized_form_of_name as actor_name',
                'slug.slug as actor_slug'
            )
            ->get()
            ->all();

        // Notes
        $notes = NoteService::getByObjectId($id, null, $culture);

        return [
            'id' => $id,
            'slug' => $slug,
            'identifier' => $io->identifier,
            'title' => $i18n->title ?? '',
            'levelOfDescriptionId' => $io->level_of_description_id,
            'levelOfDescriptionName' => $levelName,
            'repositoryId' => $io->repository_id,
            'repositoryName' => $repoName,
            'parentId' => $io->parent_id,
            'parentTitle' => $parentTitle,
            'parentSlug' => $parentSlug,
            'descriptionStatusId' => $io->description_status_id,
            'descriptionDetailId' => $io->description_detail_id,
            'descriptionIdentifier' => $io->description_identifier,
            'sourceStandard' => $io->source_standard,
            'displayStandardId' => $io->display_standard_id,
            // i18n fields
            'extentAndMedium' => $i18n->extent_and_medium ?? '',
            'archivalHistory' => $i18n->archival_history ?? '',
            'acquisition' => $i18n->acquisition ?? '',
            'scopeAndContent' => $i18n->scope_and_content ?? '',
            'appraisal' => $i18n->appraisal ?? '',
            'accruals' => $i18n->accruals ?? '',
            'arrangement' => $i18n->arrangement ?? '',
            'accessConditions' => $i18n->access_conditions ?? '',
            'reproductionConditions' => $i18n->reproduction_conditions ?? '',
            'physicalCharacteristics' => $i18n->physical_characteristics ?? '',
            'findingAids' => $i18n->finding_aids ?? '',
            'locationOfOriginals' => $i18n->location_of_originals ?? '',
            'locationOfCopies' => $i18n->location_of_copies ?? '',
            'relatedUnitsOfDescription' => $i18n->related_units_of_description ?? '',
            'institutionResponsibleIdentifier' => $i18n->institution_responsible_identifier ?? '',
            'rules' => $i18n->rules ?? '',
            'sources' => $i18n->sources ?? '',
            'revisionHistory' => $i18n->revision_history ?? '',
            // Related data
            'events' => $events,
            'subjectAccessPoints' => $subjectAPs,
            'placeAccessPoints' => $placeAPs,
            'genreAccessPoints' => $genreAPs,
            'nameAccessPoints' => $nameAPs,
            'notes' => $notes,
            'publicationStatusId' => $pubStatus->status_id ?? self::STATUS_DRAFT,
            'createdAt' => $io->created_at ?? null,
            'updatedAt' => $io->updated_at ?? null,
        ];
    }

    /**
     * Get an information object by slug.
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
     * Create a new information object.
     *
     * @return int The new IO ID
     */
    public static function create(array $data, string $culture = 'en'): int
    {
        return DB::transaction(function () use ($data, $culture) {
            // 1. Create object record
            $id = ObjectService::create('QubitInformationObject');

            // 2. Generate slug
            ObjectService::generateSlug($id, $data['title'] ?? null);

            $parentId = !empty($data['parentId']) ? (int) $data['parentId'] : self::ROOT_ID;

            // 3. Insert information_object record (without lft/rgt — NestedSetService handles those)
            DB::table('information_object')->insert([
                'id' => $id,
                'identifier' => $data['identifier'] ?? null,
                'level_of_description_id' => !empty($data['levelOfDescriptionId']) ? (int) $data['levelOfDescriptionId'] : null,
                'repository_id' => !empty($data['repositoryId']) ? (int) $data['repositoryId'] : null,
                'parent_id' => $parentId,
                'description_status_id' => !empty($data['descriptionStatusId']) ? (int) $data['descriptionStatusId'] : null,
                'description_detail_id' => !empty($data['descriptionDetailId']) ? (int) $data['descriptionDetailId'] : null,
                'description_identifier' => $data['descriptionIdentifier'] ?? null,
                'source_standard' => $data['sourceStandard'] ?? 'ISAD(G) 2nd edition',
                'source_culture' => $culture,
                'lft' => 0,
                'rgt' => 0,
            ]);

            // 4. Position in nested set
            NestedSetService::insertUnder($parentId, $id);

            // 5. Save i18n fields
            $i18nData = self::buildI18nData($data);
            if (!empty($i18nData)) {
                I18nService::save('information_object_i18n', $id, $culture, $i18nData);
            }

            // 6. Publication status
            $pubStatusId = !empty($data['publicationStatusId']) ? (int) $data['publicationStatusId'] : self::STATUS_DRAFT;
            DB::table('status')->insert([
                'object_id' => $id,
                'type_id' => self::STATUS_TYPE_PUBLICATION,
                'status_id' => $pubStatusId,
                'serial_number' => 0,
            ]);

            // 7. Save events
            if (!empty($data['events'])) {
                self::saveEvents($id, $data['events'], $culture);
            }

            // 8. Save term access points
            self::saveTermAccessPoints($id, $data);

            // 9. Save name access points
            if (!empty($data['nameAccessPoints'])) {
                self::saveNameAccessPoints($id, $data['nameAccessPoints'], $culture);
            }

            // 10. Save notes
            if (!empty($data['notes'])) {
                self::saveNotes($id, $data['notes'], $culture);
            }

            return $id;
        });
    }

    /**
     * Update an existing information object.
     */
    public static function update(int $id, array $data, string $culture = 'en'): void
    {
        DB::transaction(function () use ($id, $data, $culture) {
            // 1. Update structural fields
            $ioUpdate = [];
            if (array_key_exists('identifier', $data)) {
                $ioUpdate['identifier'] = $data['identifier'];
            }
            if (array_key_exists('levelOfDescriptionId', $data)) {
                $ioUpdate['level_of_description_id'] = !empty($data['levelOfDescriptionId']) ? (int) $data['levelOfDescriptionId'] : null;
            }
            if (array_key_exists('repositoryId', $data)) {
                $ioUpdate['repository_id'] = !empty($data['repositoryId']) ? (int) $data['repositoryId'] : null;
            }
            if (array_key_exists('descriptionStatusId', $data)) {
                $ioUpdate['description_status_id'] = !empty($data['descriptionStatusId']) ? (int) $data['descriptionStatusId'] : null;
            }
            if (array_key_exists('descriptionDetailId', $data)) {
                $ioUpdate['description_detail_id'] = !empty($data['descriptionDetailId']) ? (int) $data['descriptionDetailId'] : null;
            }
            if (array_key_exists('descriptionIdentifier', $data)) {
                $ioUpdate['description_identifier'] = $data['descriptionIdentifier'];
            }
            if (array_key_exists('sourceStandard', $data)) {
                $ioUpdate['source_standard'] = $data['sourceStandard'];
            }

            if (!empty($ioUpdate)) {
                DB::table('information_object')->where('id', $id)->update($ioUpdate);
            }

            // 2. Update i18n fields
            $i18nData = self::buildI18nData($data);
            if (!empty($i18nData)) {
                I18nService::save('information_object_i18n', $id, $culture, $i18nData);
            }

            // 3. Update publication status
            if (array_key_exists('publicationStatusId', $data)) {
                $pubStatusId = !empty($data['publicationStatusId']) ? (int) $data['publicationStatusId'] : self::STATUS_DRAFT;
                DB::table('status')
                    ->where('object_id', $id)
                    ->where('type_id', self::STATUS_TYPE_PUBLICATION)
                    ->delete();

                DB::table('status')->insert([
                    'object_id' => $id,
                    'type_id' => self::STATUS_TYPE_PUBLICATION,
                    'status_id' => $pubStatusId,
                    'serial_number' => 0,
                ]);
            }

            // 4. Replace events
            if (array_key_exists('events', $data)) {
                EventService::deleteByObjectId($id);
                if (!empty($data['events'])) {
                    self::saveEvents($id, $data['events'], $culture);
                }
            }

            // 5. Replace term access points
            self::saveTermAccessPoints($id, $data);

            // 6. Replace name access points
            if (array_key_exists('nameAccessPoints', $data)) {
                // Delete existing name access point relations
                $existingNAPs = DB::table('relation')
                    ->where('subject_id', $id)
                    ->where('type_id', self::RELATION_NAME_ACCESS_POINT)
                    ->pluck('id')
                    ->all();

                foreach ($existingNAPs as $relId) {
                    RelationService::delete($relId);
                }

                if (!empty($data['nameAccessPoints'])) {
                    self::saveNameAccessPoints($id, $data['nameAccessPoints'], $culture);
                }
            }

            // 7. Replace notes
            if (array_key_exists('notes', $data)) {
                NoteService::deleteByObjectId($id);
                if (!empty($data['notes'])) {
                    self::saveNotes($id, $data['notes'], $culture);
                }
            }

            // 8. Touch object
            ObjectService::touch($id);
            ObjectService::incrementSerialNumber($id);
        });
    }

    /**
     * Delete an information object and all related data.
     */
    public static function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            // 1. Delete events
            EventService::deleteByObjectId($id);

            // 2. Delete notes
            NoteService::deleteByObjectId($id);

            // 3. Delete term relations (access points)
            TermRelationService::deleteByObjectId($id);

            // 4. Delete relations (name access points, etc.)
            RelationService::deleteBySubjectOrObject($id);

            // 5. Delete status records
            DB::table('status')->where('object_id', $id)->delete();

            // 6. Delete property records
            $propertyIds = DB::table('property')
                ->where('object_id', $id)
                ->pluck('id')
                ->all();
            if (!empty($propertyIds)) {
                DB::table('property_i18n')->whereIn('id', $propertyIds)->delete();
                DB::table('property')->where('object_id', $id)->delete();
            }

            // 7. Delete i18n
            I18nService::delete('information_object_i18n', $id);

            // 8. Remove from nested set
            NestedSetService::removeNode($id);

            // 9. Delete information_object row
            DB::table('information_object')->where('id', $id)->delete();

            // 10. Delete slug + object
            ObjectService::deleteObject($id);
        });
    }

    // ─── Dropdown helpers ──────────────────────────────────────────────

    /**
     * Get levels of description terms.
     */
    public static function getLevelsOfDescription(string $culture = 'en'): array
    {
        return DB::table('term')
            ->leftJoin('term_i18n', function ($join) use ($culture) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', self::TAXONOMY_LEVELS_OF_DESCRIPTION)
            ->select(['term.id', 'term_i18n.name'])
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }

    /**
     * Get description status terms.
     */
    public static function getDescriptionStatuses(string $culture = 'en'): array
    {
        return DB::table('term')
            ->leftJoin('term_i18n', function ($join) use ($culture) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', self::TAXONOMY_DESCRIPTION_STATUSES)
            ->select(['term.id', 'term_i18n.name'])
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }

    /**
     * Get description detail level terms.
     */
    public static function getDescriptionDetails(string $culture = 'en'): array
    {
        return DB::table('term')
            ->leftJoin('term_i18n', function ($join) use ($culture) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', self::TAXONOMY_DESCRIPTION_DETAIL_LEVELS)
            ->select(['term.id', 'term_i18n.name'])
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }

    /**
     * Get event type terms.
     */
    public static function getEventTypes(string $culture = 'en'): array
    {
        return DB::table('term')
            ->leftJoin('term_i18n', function ($join) use ($culture) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', self::TAXONOMY_EVENT_TYPES)
            ->select(['term.id', 'term_i18n.name'])
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }

    /**
     * Get publication statuses.
     */
    public static function getPublicationStatuses(): array
    {
        return [
            (object) ['id' => self::STATUS_DRAFT, 'name' => 'Draft'],
            (object) ['id' => self::STATUS_PUBLISHED, 'name' => 'Published'],
        ];
    }

    // ─── Private helpers ───────────────────────────────────────────────

    /**
     * Build i18n data array from camelCase input to snake_case DB columns.
     */
    protected static function buildI18nData(array $data): array
    {
        $i18nData = [];
        foreach (self::$i18nFieldMap as $inputKey => $dbKey) {
            if (array_key_exists($inputKey, $data)) {
                $i18nData[$dbKey] = $data[$inputKey];
            }
        }

        return $i18nData;
    }

    /**
     * Save events for an information object.
     */
    protected static function saveEvents(int $objectId, array $events, string $culture): void
    {
        foreach ($events as $eventData) {
            $typeId = !empty($eventData['typeId']) ? (int) $eventData['typeId'] : null;
            if (!$typeId) {
                continue;
            }

            $actorId = !empty($eventData['actorId']) ? (int) $eventData['actorId'] : null;

            EventService::save(
                [
                    'type_id' => $typeId,
                    'object_id' => $objectId,
                    'actor_id' => $actorId,
                    'start_date' => !empty($eventData['startDate']) ? $eventData['startDate'] : null,
                    'end_date' => !empty($eventData['endDate']) ? $eventData['endDate'] : null,
                    'source_culture' => $culture,
                ],
                $culture,
                [
                    'date' => $eventData['date'] ?? null,
                ]
            );
        }
    }

    /**
     * Save term-based access points for an information object.
     */
    protected static function saveTermAccessPoints(int $objectId, array $data): void
    {
        $taxonomyMap = [
            'subjectAccessPointIds' => self::TAXONOMY_SUBJECT_ACCESS_POINTS,
            'placeAccessPointIds' => self::TAXONOMY_PLACE_ACCESS_POINTS,
            'genreAccessPointIds' => self::TAXONOMY_GENRE_ACCESS_POINTS,
        ];

        foreach ($taxonomyMap as $key => $taxonomyId) {
            if (array_key_exists($key, $data)) {
                $termIds = is_array($data[$key]) ? array_filter($data[$key]) : [];
                $termIds = array_map('intval', $termIds);
                TermRelationService::replaceRelations($objectId, $termIds, $taxonomyId);
            }
        }
    }

    /**
     * Save name access points for an information object.
     */
    protected static function saveNameAccessPoints(int $objectId, array $nameAccessPoints, string $culture): void
    {
        foreach ($nameAccessPoints as $nap) {
            $actorId = !empty($nap['actorId']) ? (int) $nap['actorId'] : null;
            if (!$actorId) {
                continue;
            }

            RelationService::save([
                'subject_id' => $objectId,
                'object_id' => $actorId,
                'type_id' => self::RELATION_NAME_ACCESS_POINT,
                'source_culture' => $culture,
            ], $culture);
        }
    }

    /**
     * Save notes for an information object.
     */
    protected static function saveNotes(int $objectId, array $notes, string $culture): void
    {
        foreach ($notes as $noteData) {
            $content = trim($noteData['content'] ?? '');
            if (empty($content)) {
                continue;
            }

            $typeId = !empty($noteData['typeId']) ? (int) $noteData['typeId'] : null;
            if (!$typeId) {
                continue;
            }

            NoteService::save($objectId, $typeId, $content, $culture);
        }
    }
}
