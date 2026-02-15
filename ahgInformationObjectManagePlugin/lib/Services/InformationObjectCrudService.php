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

    // Event type IDs
    const EVENT_TYPE_CREATION = 111;

    // Note type IDs
    const NOTE_TYPE_PUBLICATION = 120;
    const NOTE_TYPE_ARCHIVIST = 124;
    const NOTE_TYPE_LANGUAGE = 174;

    // Taxonomy IDs
    const TAXONOMY_LEVELS_OF_DESCRIPTION = 34;
    const TAXONOMY_DESCRIPTION_STATUSES = 33;
    const TAXONOMY_DESCRIPTION_DETAIL_LEVELS = 31;
    const TAXONOMY_EVENT_TYPES = 40;
    const TAXONOMY_SUBJECT_ACCESS_POINTS = 35;
    const TAXONOMY_PLACE_ACCESS_POINTS = 42;
    const TAXONOMY_GENRE_ACCESS_POINTS = 78;
    const TAXONOMY_DC_TYPES = 54;
    const TAXONOMY_MODS_RESOURCE_TYPES = 53;
    const TAXONOMY_MATERIAL_TYPES = 50;
    const TAXONOMY_DISPLAY_STANDARD = 70;

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
        'alternateTitle' => 'alternate_title',
        'edition' => 'edition',
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
        $dcTypes = TermRelationService::getByObjectId($id, self::TAXONOMY_DC_TYPES, $culture);
        $modsResourceTypes = TermRelationService::getByObjectId($id, self::TAXONOMY_MODS_RESOURCE_TYPES, $culture);
        $materialTypes = TermRelationService::getByObjectId($id, self::TAXONOMY_MATERIAL_TYPES, $culture);

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

        // Notes (general — excludes typed notes handled separately)
        $notes = NoteService::getByObjectId($id, null, $culture);

        // Creators (events with type_id = Creation)
        $creators = self::getCreators($id, $culture);

        // Alternative identifiers
        $altIds = self::getAlternativeIdentifiers($id, $culture);

        // Properties: language, script, languageOfDescription, scriptOfDescription
        $languages = self::getPropertyArray($id, 'language', $culture);
        $scripts = self::getPropertyArray($id, 'script', $culture);
        $languagesOfDescription = self::getPropertyArray($id, 'languageOfDescription', $culture);
        $scriptsOfDescription = self::getPropertyArray($id, 'scriptOfDescription', $culture);

        // String properties (RAD/DACS)
        $stringPropertyNames = [
            'otherTitleInformation', 'titleStatementOfResponsibility',
            'editionStatementOfResponsibility', 'statementOfScaleCartographic',
            'statementOfProjection', 'statementOfCoordinates',
            'statementOfScaleArchitectural', 'issuingJurisdictionAndDenomination',
            'titleProperOfPublishersSeries', 'parallelTitleOfPublishersSeries',
            'otherTitleInformationOfPublishersSeries',
            'statementOfResponsibilityRelatingToPublishersSeries',
            'numberingWithinPublishersSeries', 'noteOnPublishersSeries',
            'standardNumber', 'technicalAccess',
        ];
        $stringProperties = self::getStringProperties($id, $stringPropertyNames, $culture);

        // Typed notes
        $languageNotes = self::getSingleNote($id, self::NOTE_TYPE_LANGUAGE, $culture);
        $publicationNotes = self::getTypedNotes($id, self::NOTE_TYPE_PUBLICATION, $culture);
        $archivistNotes = self::getTypedNotes($id, self::NOTE_TYPE_ARCHIVIST, $culture);

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
            'alternateTitle' => $i18n->alternate_title ?? '',
            'edition' => $i18n->edition ?? '',
            // Related data
            'events' => $events,
            'subjectAccessPoints' => $subjectAPs,
            'placeAccessPoints' => $placeAPs,
            'genreAccessPoints' => $genreAPs,
            'dcTypes' => $dcTypes,
            'modsResourceTypes' => $modsResourceTypes,
            'materialTypes' => $materialTypes,
            'stringProperties' => $stringProperties,
            'nameAccessPoints' => $nameAPs,
            'notes' => $notes,
            'creators' => $creators,
            'alternativeIdentifiers' => $altIds,
            'languages' => $languages,
            'scripts' => $scripts,
            'languageNotes' => $languageNotes,
            'publicationNotes' => $publicationNotes,
            'archivistNotes' => $archivistNotes,
            'languagesOfDescription' => $languagesOfDescription,
            'scriptsOfDescription' => $scriptsOfDescription,
            'displayStandardId' => $io->display_standard_id,
            'sourceCulture' => $io->source_culture ?? $culture,
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
                'display_standard_id' => !empty($data['displayStandardId']) ? (int) $data['displayStandardId'] : null,
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

            // 11. Save creators as Creation events
            if (!empty($data['creators'])) {
                foreach ($data['creators'] as $creator) {
                    $actorId = !empty($creator['actorId']) ? (int) $creator['actorId'] : null;
                    if ($actorId) {
                        EventService::save(
                            [
                                'type_id' => self::EVENT_TYPE_CREATION,
                                'object_id' => $id,
                                'actor_id' => $actorId,
                                'source_culture' => $culture,
                            ],
                            $culture,
                            []
                        );
                    }
                }
            }

            // 12. Save properties: language, script, languageOfDescription, scriptOfDescription
            self::savePropertyArray($id, 'language', $data['languages'] ?? [], $culture);
            self::savePropertyArray($id, 'script', $data['scripts'] ?? [], $culture);
            self::savePropertyArray($id, 'languageOfDescription', $data['languagesOfDescription'] ?? [], $culture);
            self::savePropertyArray($id, 'scriptOfDescription', $data['scriptsOfDescription'] ?? [], $culture);

            // 12. Save alternative identifiers
            if (!empty($data['alternativeIdentifiers'])) {
                self::saveAlternativeIdentifiers($id, $data['alternativeIdentifiers'], $culture);
            }

            // 13. Save language note (single)
            if (!empty($data['languageNotes'])) {
                NoteService::save($id, self::NOTE_TYPE_LANGUAGE, $data['languageNotes'], $culture);
            }

            // 14. Save publication notes (multi)
            if (!empty($data['publicationNotes'])) {
                foreach ($data['publicationNotes'] as $pn) {
                    $content = trim($pn['content'] ?? '');
                    if (!empty($content)) {
                        NoteService::save($id, self::NOTE_TYPE_PUBLICATION, $content, $culture);
                    }
                }
            }

            // 15. Save archivist notes (multi)
            if (!empty($data['archivistNotes'])) {
                foreach ($data['archivistNotes'] as $an) {
                    $content = trim($an['content'] ?? '');
                    if (!empty($content)) {
                        NoteService::save($id, self::NOTE_TYPE_ARCHIVIST, $content, $culture);
                    }
                }
            }

            // 16. Create child levels
            if (!empty($data['childLevels'])) {
                self::createChildLevels($id, $data['childLevels'], $culture);
            }

            // 17. Save RAD/DACS string properties
            if (!empty($data['stringProperties'])) {
                self::saveStringProperties($id, $data['stringProperties'], $culture);
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
            if (array_key_exists('displayStandardId', $data)) {
                $ioUpdate['display_standard_id'] = !empty($data['displayStandardId']) ? (int) $data['displayStandardId'] : null;
            }

            if (!empty($ioUpdate)) {
                DB::table('information_object')->where('id', $id)->update($ioUpdate);
            }

            // Optional: cascade display_standard_id to descendants
            if (!empty($data['updateDescendants']) && array_key_exists('displayStandardId', $data)) {
                $io = DB::table('information_object')->where('id', $id)->select('lft', 'rgt')->first();
                if ($io) {
                    DB::table('information_object')
                        ->where('lft', '>', $io->lft)
                        ->where('rgt', '<', $io->rgt)
                        ->update([
                            'display_standard_id' => !empty($data['displayStandardId']) ? (int) $data['displayStandardId'] : null,
                        ]);
                }
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

            // 4. Replace events (includes creators)
            if (array_key_exists('events', $data)) {
                // Preserve existing creators if not explicitly provided by the form
                $preservedCreators = [];
                if (!array_key_exists('creators', $data)) {
                    $preservedCreators = self::getCreators($id, $culture);
                }

                EventService::deleteByObjectId($id);
                if (!empty($data['events'])) {
                    self::saveEvents($id, $data['events'], $culture);
                }

                // Re-save creators
                if (array_key_exists('creators', $data)) {
                    // Explicitly provided by form — save what was submitted
                    foreach ($data['creators'] as $creator) {
                        $actorId = !empty($creator['actorId']) ? (int) $creator['actorId'] : null;
                        if ($actorId) {
                            EventService::save(
                                [
                                    'type_id' => self::EVENT_TYPE_CREATION,
                                    'object_id' => $id,
                                    'actor_id' => $actorId,
                                    'source_culture' => $culture,
                                ],
                                $culture,
                                []
                            );
                        }
                    }
                } else {
                    // Not provided (RAD/MODS) — preserve existing creators
                    foreach ($preservedCreators as $cr) {
                        if ($cr->actor_id) {
                            EventService::save(
                                [
                                    'type_id' => self::EVENT_TYPE_CREATION,
                                    'object_id' => $id,
                                    'actor_id' => (int) $cr->actor_id,
                                    'source_culture' => $culture,
                                ],
                                $culture,
                                []
                            );
                        }
                    }
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

            // 7. Replace notes (general)
            if (array_key_exists('notes', $data)) {
                NoteService::deleteByObjectId($id);
                if (!empty($data['notes'])) {
                    self::saveNotes($id, $data['notes'], $culture);
                }

                // Re-save typed notes (since deleteByObjectId removes all)
                if (!empty($data['languageNotes'])) {
                    NoteService::save($id, self::NOTE_TYPE_LANGUAGE, $data['languageNotes'], $culture);
                }
                if (!empty($data['publicationNotes'])) {
                    foreach ($data['publicationNotes'] as $pn) {
                        $content = trim($pn['content'] ?? '');
                        if (!empty($content)) {
                            NoteService::save($id, self::NOTE_TYPE_PUBLICATION, $content, $culture);
                        }
                    }
                }
                if (!empty($data['archivistNotes'])) {
                    foreach ($data['archivistNotes'] as $an) {
                        $content = trim($an['content'] ?? '');
                        if (!empty($content)) {
                            NoteService::save($id, self::NOTE_TYPE_ARCHIVIST, $content, $culture);
                        }
                    }
                }
            }

            // 8. Replace properties
            if (array_key_exists('languages', $data)) {
                self::savePropertyArray($id, 'language', $data['languages'], $culture);
            }
            if (array_key_exists('scripts', $data)) {
                self::savePropertyArray($id, 'script', $data['scripts'], $culture);
            }
            if (array_key_exists('languagesOfDescription', $data)) {
                self::savePropertyArray($id, 'languageOfDescription', $data['languagesOfDescription'], $culture);
            }
            if (array_key_exists('scriptsOfDescription', $data)) {
                self::savePropertyArray($id, 'scriptOfDescription', $data['scriptsOfDescription'], $culture);
            }

            // 9. Replace alternative identifiers
            if (array_key_exists('alternativeIdentifiers', $data)) {
                self::deleteProperties($id, 'alternativeIdentifiers');
                if (!empty($data['alternativeIdentifiers'])) {
                    self::saveAlternativeIdentifiers($id, $data['alternativeIdentifiers'], $culture);
                }
            }

            // 10. Create child levels (additive — does not delete existing children)
            if (!empty($data['childLevels'])) {
                self::createChildLevels($id, $data['childLevels'], $culture);
            }

            // 11. Save RAD/DACS string properties
            if (array_key_exists('stringProperties', $data) && !empty($data['stringProperties'])) {
                self::saveStringProperties($id, $data['stringProperties'], $culture);
            }

            // 12. Touch object
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

    /**
     * Get display standard terms (taxonomy 70).
     */
    public static function getDisplayStandards(string $culture = 'en'): array
    {
        return DB::table('term')
            ->leftJoin('term_i18n', function ($join) use ($culture) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', self::TAXONOMY_DISPLAY_STANDARD)
            ->select(['term.id', 'term_i18n.name'])
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }

    /**
     * Get Dublin Core type terms (taxonomy 54).
     */
    public static function getDcTypeTerms(string $culture = 'en'): array
    {
        return DB::table('term')
            ->leftJoin('term_i18n', function ($join) use ($culture) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', self::TAXONOMY_DC_TYPES)
            ->select(['term.id', 'term_i18n.name'])
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }

    /**
     * Get MODS resource type terms (taxonomy 53).
     */
    public static function getModsResourceTypes(string $culture = 'en'): array
    {
        return DB::table('term')
            ->leftJoin('term_i18n', function ($join) use ($culture) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', self::TAXONOMY_MODS_RESOURCE_TYPES)
            ->select(['term.id', 'term_i18n.name'])
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }

    /**
     * Get RAD material type terms (taxonomy 50).
     */
    public static function getMaterialTypes(string $culture = 'en'): array
    {
        return DB::table('term')
            ->leftJoin('term_i18n', function ($join) use ($culture) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', self::TAXONOMY_MATERIAL_TYPES)
            ->select(['term.id', 'term_i18n.name'])
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }

    /**
     * Get string properties for an object (RAD/DACS property-based fields).
     */
    public static function getStringProperties(int $objectId, array $names, string $culture): array
    {
        $result = array_fill_keys($names, '');

        $props = DB::table('property')
            ->where('object_id', $objectId)
            ->whereIn('name', $names)
            ->whereNull('scope')
            ->get();

        foreach ($props as $prop) {
            $pi = DB::table('property_i18n')
                ->where('id', $prop->id)
                ->where('culture', $culture)
                ->first();

            // Non-serialized string value
            $val = $pi->value ?? '';
            // Guard against serialized arrays (language/script use those)
            if ($val && $val[0] !== 'a' || strpos($val, ':{') === false) {
                $result[$prop->name] = $val;
            }
        }

        return $result;
    }

    /**
     * Save string properties (delete old, insert new).
     */
    public static function saveStringProperties(int $objectId, array $data, string $culture): void
    {
        foreach ($data as $name => $value) {
            // Delete existing
            $existing = DB::table('property')
                ->where('object_id', $objectId)
                ->where('name', $name)
                ->whereNull('scope')
                ->pluck('id')
                ->all();

            if (!empty($existing)) {
                DB::table('property_i18n')->whereIn('id', $existing)->delete();
                DB::table('property')->whereIn('id', $existing)->delete();
            }

            // Insert new if non-empty
            $value = trim($value);
            if (!empty($value)) {
                $propId = DB::table('property')->insertGetId([
                    'object_id' => $objectId,
                    'name' => $name,
                    'scope' => null,
                    'source_culture' => $culture,
                    'serial_number' => 0,
                ]);

                DB::table('property_i18n')->insert([
                    'id' => $propId,
                    'culture' => $culture,
                    'value' => $value,
                ]);
            }
        }
    }

    /**
     * Get ISO 639 language choices.
     */
    public static function getLanguageChoices(): array
    {
        return [
            'aa' => 'Afar', 'ab' => 'Abkhazian', 'af' => 'Afrikaans', 'am' => 'Amharic',
            'ar' => 'Arabic', 'as' => 'Assamese', 'ay' => 'Aymara', 'az' => 'Azerbaijani',
            'ba' => 'Bashkir', 'be' => 'Belarusian', 'bg' => 'Bulgarian', 'bh' => 'Bihari',
            'bi' => 'Bislama', 'bn' => 'Bengali', 'bo' => 'Tibetan', 'br' => 'Breton',
            'ca' => 'Catalan', 'co' => 'Corsican', 'cs' => 'Czech', 'cy' => 'Welsh',
            'da' => 'Danish', 'de' => 'German', 'dz' => 'Dzongkha', 'el' => 'Greek',
            'en' => 'English', 'eo' => 'Esperanto', 'es' => 'Spanish', 'et' => 'Estonian',
            'eu' => 'Basque', 'fa' => 'Persian', 'fi' => 'Finnish', 'fj' => 'Fijian',
            'fo' => 'Faroese', 'fr' => 'French', 'fy' => 'Western Frisian', 'ga' => 'Irish',
            'gd' => 'Scottish Gaelic', 'gl' => 'Galician', 'gn' => 'Guarani', 'gu' => 'Gujarati',
            'ha' => 'Hausa', 'he' => 'Hebrew', 'hi' => 'Hindi', 'hr' => 'Croatian',
            'hu' => 'Hungarian', 'hy' => 'Armenian', 'ia' => 'Interlingua', 'id' => 'Indonesian',
            'ie' => 'Interlingue', 'ik' => 'Inupiaq', 'is' => 'Icelandic', 'it' => 'Italian',
            'iu' => 'Inuktitut', 'ja' => 'Japanese', 'jv' => 'Javanese', 'ka' => 'Georgian',
            'kk' => 'Kazakh', 'kl' => 'Kalaallisut', 'km' => 'Khmer', 'kn' => 'Kannada',
            'ko' => 'Korean', 'ks' => 'Kashmiri', 'ku' => 'Kurdish', 'ky' => 'Kirghiz',
            'la' => 'Latin', 'ln' => 'Lingala', 'lo' => 'Lao', 'lt' => 'Lithuanian',
            'lv' => 'Latvian', 'mg' => 'Malagasy', 'mi' => 'Maori', 'mk' => 'Macedonian',
            'ml' => 'Malayalam', 'mn' => 'Mongolian', 'mr' => 'Marathi', 'ms' => 'Malay',
            'mt' => 'Maltese', 'my' => 'Burmese', 'na' => 'Nauru', 'ne' => 'Nepali',
            'nl' => 'Dutch', 'no' => 'Norwegian', 'oc' => 'Occitan', 'om' => 'Oromo',
            'or' => 'Oriya', 'pa' => 'Panjabi', 'pl' => 'Polish', 'ps' => 'Pashto',
            'pt' => 'Portuguese', 'qu' => 'Quechua', 'rm' => 'Romansh', 'rn' => 'Rundi',
            'ro' => 'Romanian', 'ru' => 'Russian', 'rw' => 'Kinyarwanda', 'sa' => 'Sanskrit',
            'sd' => 'Sindhi', 'sg' => 'Sango', 'si' => 'Sinhala', 'sk' => 'Slovak',
            'sl' => 'Slovenian', 'sm' => 'Samoan', 'sn' => 'Shona', 'so' => 'Somali',
            'sq' => 'Albanian', 'sr' => 'Serbian', 'ss' => 'Swati', 'st' => 'Southern Sotho',
            'su' => 'Sundanese', 'sv' => 'Swedish', 'sw' => 'Swahili', 'ta' => 'Tamil',
            'te' => 'Telugu', 'tg' => 'Tajik', 'th' => 'Thai', 'ti' => 'Tigrinya',
            'tk' => 'Turkmen', 'tl' => 'Tagalog', 'tn' => 'Tswana', 'to' => 'Tonga',
            'tr' => 'Turkish', 'ts' => 'Tsonga', 'tt' => 'Tatar', 'tw' => 'Twi',
            'ug' => 'Uighur', 'uk' => 'Ukrainian', 'ur' => 'Urdu', 'uz' => 'Uzbek',
            've' => 'Venda', 'vi' => 'Vietnamese', 'vo' => 'Volapük', 'wo' => 'Wolof',
            'xh' => 'Xhosa', 'yi' => 'Yiddish', 'yo' => 'Yoruba', 'za' => 'Zhuang',
            'zh' => 'Chinese', 'zu' => 'Zulu',
        ];
    }

    /**
     * Get ISO 15924 script choices.
     */
    public static function getScriptChoices(): array
    {
        return [
            'Arab' => 'Arabic', 'Armn' => 'Armenian', 'Beng' => 'Bengali',
            'Bopo' => 'Bopomofo', 'Brai' => 'Braille', 'Cans' => 'Unified Canadian Aboriginal Syllabics',
            'Cher' => 'Cherokee', 'Cyrl' => 'Cyrillic', 'Deva' => 'Devanagari',
            'Ethi' => 'Ethiopic', 'Geor' => 'Georgian', 'Grek' => 'Greek',
            'Gujr' => 'Gujarati', 'Guru' => 'Gurmukhi', 'Hang' => 'Hangul',
            'Hani' => 'Han (Hanzi, Kanji, Hanja)', 'Hans' => 'Han (Simplified)',
            'Hant' => 'Han (Traditional)', 'Hebr' => 'Hebrew', 'Hira' => 'Hiragana',
            'Jpan' => 'Japanese', 'Kana' => 'Katakana', 'Khmr' => 'Khmer',
            'Knda' => 'Kannada', 'Kore' => 'Korean', 'Laoo' => 'Lao',
            'Latn' => 'Latin', 'Mlym' => 'Malayalam', 'Mong' => 'Mongolian',
            'Mymr' => 'Myanmar', 'Orya' => 'Oriya', 'Sinh' => 'Sinhala',
            'Taml' => 'Tamil', 'Telu' => 'Telugu', 'Tfng' => 'Tifinagh',
            'Thai' => 'Thai', 'Tibt' => 'Tibetan', 'Vaii' => 'Vai',
            'Yiii' => 'Yi', 'Zyyy' => 'Common',
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
            'dcTypeIds' => self::TAXONOMY_DC_TYPES,
            'modsResourceTypeIds' => self::TAXONOMY_MODS_RESOURCE_TYPES,
            'materialTypeIds' => self::TAXONOMY_MATERIAL_TYPES,
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

    // ─── Property helpers ─────────────────────────────────────────────

    /**
     * Get a serialized array property (language, script, etc.).
     */
    protected static function getPropertyArray(int $objectId, string $name, string $culture): array
    {
        $prop = DB::table('property')
            ->where('object_id', $objectId)
            ->where('name', $name)
            ->whereNull('scope')
            ->first();

        if (!$prop) {
            return [];
        }

        $pi = DB::table('property_i18n')
            ->where('id', $prop->id)
            ->where('culture', $culture)
            ->value('value');

        if (!$pi) {
            return [];
        }

        $arr = @unserialize($pi);

        return is_array($arr) ? $arr : [];
    }

    /**
     * Save a serialized array property (delete old, insert new).
     */
    protected static function savePropertyArray(int $objectId, string $name, array $values, string $culture): void
    {
        // Delete existing
        $existing = DB::table('property')
            ->where('object_id', $objectId)
            ->where('name', $name)
            ->whereNull('scope')
            ->pluck('id')
            ->all();

        if (!empty($existing)) {
            DB::table('property_i18n')->whereIn('id', $existing)->delete();
            DB::table('property')->whereIn('id', $existing)->delete();
        }

        // Insert new
        $values = array_filter(array_values($values));
        $serialized = serialize($values);

        $propId = DB::table('property')->insertGetId([
            'object_id' => $objectId,
            'name' => $name,
            'scope' => null,
            'source_culture' => $culture,
            'serial_number' => 0,
        ]);

        DB::table('property_i18n')->insert([
            'id' => $propId,
            'culture' => $culture,
            'value' => $serialized,
        ]);
    }

    /**
     * Get alternative identifiers for an information object.
     */
    protected static function getAlternativeIdentifiers(int $objectId, string $culture): array
    {
        $props = DB::table('property')
            ->where('object_id', $objectId)
            ->where('scope', 'alternativeIdentifiers')
            ->get();

        $result = [];
        foreach ($props as $prop) {
            $pi = DB::table('property_i18n')
                ->where('id', $prop->id)
                ->where('culture', $culture)
                ->first();

            $result[] = (object) [
                'label' => $prop->name ?? '',
                'value' => $pi->value ?? '',
            ];
        }

        return $result;
    }

    /**
     * Save alternative identifiers (scope='alternativeIdentifiers').
     */
    protected static function saveAlternativeIdentifiers(int $objectId, array $altIds, string $culture): void
    {
        foreach ($altIds as $ai) {
            $label = trim($ai['label'] ?? '');
            $value = trim($ai['value'] ?? '');
            if (empty($label) && empty($value)) {
                continue;
            }

            $propId = DB::table('property')->insertGetId([
                'object_id' => $objectId,
                'scope' => 'alternativeIdentifiers',
                'name' => $label,
                'source_culture' => $culture,
                'serial_number' => 0,
            ]);

            DB::table('property_i18n')->insert([
                'id' => $propId,
                'culture' => $culture,
                'value' => $value,
            ]);
        }
    }

    /**
     * Delete properties by object ID and scope.
     */
    protected static function deleteProperties(int $objectId, string $scope): void
    {
        $ids = DB::table('property')
            ->where('object_id', $objectId)
            ->where('scope', $scope)
            ->pluck('id')
            ->all();

        if (!empty($ids)) {
            DB::table('property_i18n')->whereIn('id', $ids)->delete();
            DB::table('property')->whereIn('id', $ids)->delete();
        }
    }

    // ─── Creator helpers ──────────────────────────────────────────────

    /**
     * Get creators (events with type_id = Creation) for an IO.
     */
    protected static function getCreators(int $objectId, string $culture): array
    {
        return DB::table('event')
            ->leftJoin('event_i18n', function ($j) use ($culture) {
                $j->on('event.id', '=', 'event_i18n.id')
                    ->where('event_i18n.culture', '=', $culture);
            })
            ->leftJoin('actor_i18n', function ($j) use ($culture) {
                $j->on('event.actor_id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', $culture);
            })
            ->where('event.object_id', $objectId)
            ->where('event.type_id', self::EVENT_TYPE_CREATION)
            ->select(
                'event.id as event_id',
                'event.actor_id',
                'actor_i18n.authorized_form_of_name as actor_name',
                'event_i18n.date'
            )
            ->get()
            ->all();
    }

    // ─── Typed note helpers ───────────────────────────────────────────

    /**
     * Get a single note of a given type (returns content string).
     */
    protected static function getSingleNote(int $objectId, int $typeId, string $culture): string
    {
        $notes = NoteService::getByObjectId($objectId, $typeId, $culture);
        if (!empty($notes)) {
            return $notes[0]->content ?? '';
        }

        return '';
    }

    /**
     * Get multiple notes of a given type.
     */
    protected static function getTypedNotes(int $objectId, int $typeId, string $culture): array
    {
        return NoteService::getByObjectId($objectId, $typeId, $culture);
    }

    // ─── Child levels ─────────────────────────────────────────────────

    /**
     * Create child information objects under a parent.
     */
    protected static function createChildLevels(int $parentId, array $children, string $culture): void
    {
        foreach ($children as $child) {
            $title = trim($child['title'] ?? '');
            if (empty($title)) {
                continue;
            }

            self::create([
                'title' => $title,
                'identifier' => $child['identifier'] ?? null,
                'levelOfDescriptionId' => $child['levelOfDescriptionId'] ?? null,
                'parentId' => $parentId,
                'publicationStatusId' => self::STATUS_DRAFT,
            ], $culture);
        }
    }
}
