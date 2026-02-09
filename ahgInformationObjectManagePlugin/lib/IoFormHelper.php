<?php

/**
 * Shared helper for information object edit forms across all descriptive standards.
 *
 * Loads dropdowns, IO data, and handles POST extraction — called by each
 * standard-specific action (ioManage for ISAD, dcManage for DC, etc.).
 */
class IoFormHelper
{
    /**
     * Standard code → display_standard_id mapping.
     */
    public const STANDARD_IDS = [
        'isad' => 353,
        'dc'   => 354,
        'mods' => 355,
        'rad'  => 356,
        'dacs' => 357,
    ];

    /**
     * Reverse mapping: display_standard_id → standard code.
     */
    public const ID_TO_STANDARD = [
        353 => 'isad',
        354 => 'dc',
        355 => 'mods',
        356 => 'rad',
        357 => 'dacs',
    ];

    /**
     * Standard code → module name for forwarding.
     */
    public const MODULE_MAP = [
        'dc'   => 'dcManage',
        'rad'  => 'radManage',
        'mods' => 'modsManage',
        'dacs' => 'dacsManage',
        // 'isad' stays in ioManage — no forwarding
    ];

    /**
     * Detect the descriptive standard for an IO (or global default for new).
     *
     * @param int|null $displayStandardId The IO's display_standard_id (null for new)
     * @param string   $culture           Current culture
     *
     * @return string Standard code: 'isad', 'dc', 'rad', 'mods', 'dacs'
     */
    public static function detectStandard(?int $displayStandardId, string $culture = 'en'): string
    {
        // If the IO has a per-record standard, use it
        if ($displayStandardId && isset(self::ID_TO_STANDARD[$displayStandardId])) {
            return self::ID_TO_STANDARD[$displayStandardId];
        }

        // Fall back to global default_template setting
        $global = \Illuminate\Database\Capsule\Manager::table('setting as s')
            ->leftJoin('setting_i18n as si', function ($j) use ($culture) {
                $j->on('s.id', '=', 'si.id')->where('si.culture', '=', $culture);
            })
            ->where('s.name', 'informationobject')
            ->value('si.value');

        if ($global && in_array($global, array_keys(self::STANDARD_IDS), true)) {
            return $global;
        }

        return 'isad';
    }

    /**
     * Load all dropdown data and set on the action.
     */
    public static function loadDropdowns(sfActions $action, string $culture): void
    {
        $svc = '\\AhgInformationObjectManage\\Services\\InformationObjectCrudService';

        $action->levels = $svc::getLevelsOfDescription($culture);
        $action->descriptionStatuses = $svc::getDescriptionStatuses($culture);
        $action->descriptionDetails = $svc::getDescriptionDetails($culture);
        $action->eventTypes = $svc::getEventTypes($culture);
        $action->publicationStatuses = $svc::getPublicationStatuses();
        $action->noteTypes = self::getNoteTypes($culture);
        $action->displayStandards = $svc::getDisplayStandards($culture);
        $action->languageChoices = $svc::getLanguageChoices();
        $action->scriptChoices = $svc::getScriptChoices();
        $action->dcTypeTerms = $svc::getDcTypeTerms($culture);
        $action->modsResourceTypes = $svc::getModsResourceTypes($culture);
        $action->materialTypes = $svc::getMaterialTypes($culture);
    }

    /**
     * Load existing IO data or create defaults for a new record.
     *
     * @return string The detected standard code
     */
    public static function loadIoData(sfActions $action, sfWebRequest $request, string $culture): string
    {
        $svc = '\\AhgInformationObjectManage\\Services\\InformationObjectCrudService';
        $slug = $request->getParameter('slug');
        $action->isNew = empty($slug);

        if (!$action->isNew) {
            $action->io = $svc::getBySlug($slug, $culture);
            if (!$action->io) {
                $action->forward404();
            }

            $title = $action->io['title'] ?: $action->getContext()->getI18N()->__('Untitled');
            $action->getResponse()->setTitle(
                $action->getContext()->getI18N()->__('Edit %1%', ['%1%' => $title])
                . ' - ' . $action->getResponse()->getTitle()
            );

            $standard = self::detectStandard(
                $action->io['displayStandardId'] ? (int) $action->io['displayStandardId'] : null,
                $culture
            );
        } else {
            $parentId = (int) $request->getParameter('parent', $svc::ROOT_ID);
            $parentTitle = null;
            $parentSlug = null;

            if ($parentId && $parentId != $svc::ROOT_ID) {
                $parentI18n = \AhgCore\Services\I18nService::getWithFallback('information_object_i18n', $parentId, $culture);
                $parentTitle = $parentI18n->title ?? null;
                $parentSlug = \AhgCore\Services\ObjectService::getSlug($parentId);
            }

            $action->io = self::getNewDefaults($parentId, $parentTitle, $parentSlug, $culture);

            $action->getResponse()->setTitle(
                $action->getContext()->getI18N()->__('Add new archival description')
                . ' - ' . $action->getResponse()->getTitle()
            );

            // For new records, check URL param or global default
            $urlStandard = $request->getParameter('standard', null);
            if ($urlStandard && isset(self::STANDARD_IDS[$urlStandard])) {
                $standard = $urlStandard;
            } else {
                $standard = self::detectStandard(null, $culture);
            }
        }

        $action->standard = $standard;

        return $standard;
    }

    /**
     * Default field values for a new IO.
     */
    public static function getNewDefaults(int $parentId, ?string $parentTitle, ?string $parentSlug, string $culture): array
    {
        $svc = '\\AhgInformationObjectManage\\Services\\InformationObjectCrudService';

        return [
            'id' => null,
            'slug' => null,
            'identifier' => '',
            'title' => '',
            'alternateTitle' => '',
            'edition' => '',
            'levelOfDescriptionId' => null,
            'repositoryId' => null,
            'repositoryName' => null,
            'parentId' => $parentId,
            'parentTitle' => $parentTitle,
            'parentSlug' => $parentSlug,
            'descriptionStatusId' => null,
            'descriptionDetailId' => null,
            'descriptionIdentifier' => '',
            'sourceStandard' => '',
            'extentAndMedium' => '',
            'archivalHistory' => '',
            'acquisition' => '',
            'scopeAndContent' => '',
            'appraisal' => '',
            'accruals' => '',
            'arrangement' => '',
            'accessConditions' => '',
            'reproductionConditions' => '',
            'physicalCharacteristics' => '',
            'findingAids' => '',
            'locationOfOriginals' => '',
            'locationOfCopies' => '',
            'relatedUnitsOfDescription' => '',
            'institutionResponsibleIdentifier' => '',
            'rules' => '',
            'sources' => '',
            'revisionHistory' => '',
            'events' => [],
            'subjectAccessPoints' => [],
            'placeAccessPoints' => [],
            'genreAccessPoints' => [],
            'dcTypes' => [],
            'modsResourceTypes' => [],
            'materialTypes' => [],
            'stringProperties' => [],
            'nameAccessPoints' => [],
            'notes' => [],
            'creators' => [],
            'alternativeIdentifiers' => [],
            'languages' => [],
            'scripts' => [],
            'languageNotes' => '',
            'publicationNotes' => [],
            'archivistNotes' => [],
            'languagesOfDescription' => [],
            'scriptsOfDescription' => [],
            'displayStandardId' => null,
            'sourceCulture' => $culture,
            'updatedAt' => null,
            'publicationStatusId' => $svc::STATUS_DRAFT,
        ];
    }

    /**
     * Extract common form data from a POST request.
     */
    public static function extractFormData(sfWebRequest $request): array
    {
        $data = [
            'title' => trim($request->getParameter('title', '')),
            'identifier' => trim($request->getParameter('identifier', '')),
            'levelOfDescriptionId' => $request->getParameter('levelOfDescriptionId', ''),
            'repositoryId' => $request->getParameter('repositoryId', ''),
            'parentId' => $request->getParameter('parentId', ''),
            'descriptionStatusId' => $request->getParameter('descriptionStatusId', ''),
            'descriptionDetailId' => $request->getParameter('descriptionDetailId', ''),
            'descriptionIdentifier' => trim($request->getParameter('descriptionIdentifier', '')),
            'sourceStandard' => trim($request->getParameter('sourceStandard', '')),
            'publicationStatusId' => $request->getParameter('publicationStatusId', ''),
            'displayStandardId' => $request->getParameter('displayStandardId', ''),
            'updateDescendants' => (bool) $request->getParameter('updateDescendants', false),
            // i18n fields
            'extentAndMedium' => trim($request->getParameter('extentAndMedium', '')),
            'archivalHistory' => trim($request->getParameter('archivalHistory', '')),
            'acquisition' => trim($request->getParameter('acquisition', '')),
            'scopeAndContent' => trim($request->getParameter('scopeAndContent', '')),
            'appraisal' => trim($request->getParameter('appraisal', '')),
            'accruals' => trim($request->getParameter('accruals', '')),
            'arrangement' => trim($request->getParameter('arrangement', '')),
            'accessConditions' => trim($request->getParameter('accessConditions', '')),
            'reproductionConditions' => trim($request->getParameter('reproductionConditions', '')),
            'physicalCharacteristics' => trim($request->getParameter('physicalCharacteristics', '')),
            'findingAids' => trim($request->getParameter('findingAids', '')),
            'locationOfOriginals' => trim($request->getParameter('locationOfOriginals', '')),
            'locationOfCopies' => trim($request->getParameter('locationOfCopies', '')),
            'relatedUnitsOfDescription' => trim($request->getParameter('relatedUnitsOfDescription', '')),
            'institutionResponsibleIdentifier' => trim($request->getParameter('institutionResponsibleIdentifier', '')),
            'rules' => trim($request->getParameter('rules', '')),
            'sources' => trim($request->getParameter('sources', '')),
            'revisionHistory' => trim($request->getParameter('revisionHistory', '')),
            'alternateTitle' => trim($request->getParameter('alternateTitle', '')),
            'edition' => trim($request->getParameter('edition', '')),
        ];

        // Events
        $eventsRaw = $request->getParameter('events', []);
        $data['events'] = [];
        if (is_array($eventsRaw)) {
            foreach ($eventsRaw as $evt) {
                if (!empty($evt['typeId'])) {
                    $data['events'][] = [
                        'typeId' => $evt['typeId'],
                        'date' => $evt['date'] ?? '',
                        'startDate' => $evt['startDate'] ?? null,
                        'endDate' => $evt['endDate'] ?? null,
                        'actorId' => $evt['actorId'] ?? null,
                        'actorName' => $evt['actorName'] ?? '',
                    ];
                }
            }
        }

        // Term access points
        $data['subjectAccessPointIds'] = array_filter((array) $request->getParameter('subjectAccessPointIds', []));
        $data['placeAccessPointIds'] = array_filter((array) $request->getParameter('placeAccessPointIds', []));
        $data['genreAccessPointIds'] = array_filter((array) $request->getParameter('genreAccessPointIds', []));
        $data['dcTypeIds'] = array_filter((array) $request->getParameter('dcTypeIds', []));
        $data['modsResourceTypeIds'] = array_filter((array) $request->getParameter('modsResourceTypeIds', []));
        $data['materialTypeIds'] = array_filter((array) $request->getParameter('materialTypeIds', []));

        // RAD/DACS string properties
        $radPropertyNames = [
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
        $data['stringProperties'] = [];
        foreach ($radPropertyNames as $propName) {
            $val = trim($request->getParameter($propName, ''));
            if ($val !== '') {
                $data['stringProperties'][$propName] = $val;
            }
        }

        // Name access points
        $nameAPsRaw = $request->getParameter('nameAccessPoints', []);
        $data['nameAccessPoints'] = [];
        if (is_array($nameAPsRaw)) {
            foreach ($nameAPsRaw as $nap) {
                if (!empty($nap['actorId'])) {
                    $data['nameAccessPoints'][] = [
                        'actorId' => $nap['actorId'],
                        'actorName' => $nap['actorName'] ?? '',
                    ];
                }
            }
        }

        // Notes
        $notesRaw = $request->getParameter('notes', []);
        $data['notes'] = [];
        if (is_array($notesRaw)) {
            foreach ($notesRaw as $note) {
                if (!empty($note['typeId']) && !empty(trim($note['content'] ?? ''))) {
                    $data['notes'][] = [
                        'typeId' => $note['typeId'],
                        'content' => $note['content'],
                    ];
                }
            }
        }

        // Creators
        $creatorsRaw = $request->getParameter('creators', []);
        $data['creators'] = [];
        if (is_array($creatorsRaw)) {
            foreach ($creatorsRaw as $cr) {
                if (!empty($cr['actorId'])) {
                    $data['creators'][] = [
                        'actorId' => $cr['actorId'],
                        'actorName' => $cr['actorName'] ?? '',
                    ];
                }
            }
        }

        // Alternative identifiers
        $altIdsRaw = $request->getParameter('altIds', []);
        $data['alternativeIdentifiers'] = [];
        if (is_array($altIdsRaw)) {
            foreach ($altIdsRaw as $ai) {
                $label = trim($ai['label'] ?? '');
                $value = trim($ai['value'] ?? '');
                if (!empty($label) || !empty($value)) {
                    $data['alternativeIdentifiers'][] = ['label' => $label, 'value' => $value];
                }
            }
        }

        // Languages/scripts
        $data['languages'] = array_filter((array) $request->getParameter('languages', []));
        $data['scripts'] = array_filter((array) $request->getParameter('scripts', []));
        $data['languageNotes'] = trim($request->getParameter('languageNotes', ''));
        $data['languagesOfDescription'] = array_filter((array) $request->getParameter('languagesOfDescription', []));
        $data['scriptsOfDescription'] = array_filter((array) $request->getParameter('scriptsOfDescription', []));

        // Publication notes
        $pubNotesRaw = $request->getParameter('publicationNotes', []);
        $data['publicationNotes'] = [];
        if (is_array($pubNotesRaw)) {
            foreach ($pubNotesRaw as $pn) {
                $content = trim($pn['content'] ?? '');
                if (!empty($content)) {
                    $data['publicationNotes'][] = ['content' => $content];
                }
            }
        }

        // Archivist notes
        $archNotesRaw = $request->getParameter('archivistNotes', []);
        $data['archivistNotes'] = [];
        if (is_array($archNotesRaw)) {
            foreach ($archNotesRaw as $an) {
                $content = trim($an['content'] ?? '');
                if (!empty($content)) {
                    $data['archivistNotes'][] = ['content' => $content];
                }
            }
        }

        // Child levels
        $childRaw = $request->getParameter('childLevels', []);
        $data['childLevels'] = [];
        if (is_array($childRaw)) {
            foreach ($childRaw as $ch) {
                $title = trim($ch['title'] ?? '');
                if (!empty($title)) {
                    $data['childLevels'][] = [
                        'identifier' => $ch['identifier'] ?? '',
                        'levelOfDescriptionId' => $ch['levelOfDescriptionId'] ?? '',
                        'title' => $title,
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Re-populate IO array from POST data after validation error.
     */
    public static function repopulateFromRequest(sfActions $action, sfWebRequest $request): void
    {
        $action->io['title'] = $request->getParameter('title', '');
        $action->io['identifier'] = $request->getParameter('identifier', '');
        $action->io['levelOfDescriptionId'] = $request->getParameter('levelOfDescriptionId', '');
        $action->io['repositoryId'] = $request->getParameter('repositoryId', '');
        $action->io['repositoryName'] = $request->getParameter('repositoryName', '');
        $action->io['descriptionStatusId'] = $request->getParameter('descriptionStatusId', '');
        $action->io['descriptionDetailId'] = $request->getParameter('descriptionDetailId', '');
        $action->io['descriptionIdentifier'] = $request->getParameter('descriptionIdentifier', '');
        $action->io['sourceStandard'] = $request->getParameter('sourceStandard', '');
        $action->io['publicationStatusId'] = $request->getParameter('publicationStatusId', '');
        $action->io['displayStandardId'] = $request->getParameter('displayStandardId', '');
        $action->io['alternateTitle'] = $request->getParameter('alternateTitle', '');
        $action->io['edition'] = $request->getParameter('edition', '');

        foreach ([
            'extentAndMedium', 'archivalHistory', 'acquisition', 'scopeAndContent',
            'appraisal', 'accruals', 'arrangement', 'accessConditions',
            'reproductionConditions', 'physicalCharacteristics', 'findingAids',
            'locationOfOriginals', 'locationOfCopies', 'relatedUnitsOfDescription',
            'institutionResponsibleIdentifier', 'rules', 'sources', 'revisionHistory',
        ] as $field) {
            $action->io[$field] = $request->getParameter($field, '');
        }

        // Events
        $eventsRaw = $request->getParameter('events', []);
        $action->io['events'] = [];
        if (is_array($eventsRaw)) {
            foreach ($eventsRaw as $evt) {
                $action->io['events'][] = (object) [
                    'type_id' => $evt['typeId'] ?? null,
                    'date' => $evt['date'] ?? '',
                    'start_date' => $evt['startDate'] ?? null,
                    'end_date' => $evt['endDate'] ?? null,
                    'actor_id' => $evt['actorId'] ?? null,
                    'actor_name' => $evt['actorName'] ?? '',
                ];
            }
        }

        // Creators
        $creatorsRaw = $request->getParameter('creators', []);
        $action->io['creators'] = [];
        if (is_array($creatorsRaw)) {
            foreach ($creatorsRaw as $cr) {
                $action->io['creators'][] = (object) [
                    'actor_id' => $cr['actorId'] ?? null,
                    'actor_name' => $cr['actorName'] ?? '',
                ];
            }
        }

        // Alternative identifiers
        $altIdsRaw = $request->getParameter('altIds', []);
        $action->io['alternativeIdentifiers'] = [];
        if (is_array($altIdsRaw)) {
            foreach ($altIdsRaw as $ai) {
                $action->io['alternativeIdentifiers'][] = (object) [
                    'label' => $ai['label'] ?? '',
                    'value' => $ai['value'] ?? '',
                ];
            }
        }

        $action->io['languages'] = array_filter((array) $request->getParameter('languages', []));
        $action->io['scripts'] = array_filter((array) $request->getParameter('scripts', []));
        $action->io['languageNotes'] = $request->getParameter('languageNotes', '');
        $action->io['languagesOfDescription'] = array_filter((array) $request->getParameter('languagesOfDescription', []));
        $action->io['scriptsOfDescription'] = array_filter((array) $request->getParameter('scriptsOfDescription', []));

        $pubNotesRaw = $request->getParameter('publicationNotes', []);
        $action->io['publicationNotes'] = [];
        if (is_array($pubNotesRaw)) {
            foreach ($pubNotesRaw as $pn) {
                $action->io['publicationNotes'][] = (object) ['content' => $pn['content'] ?? ''];
            }
        }

        $archNotesRaw = $request->getParameter('archivistNotes', []);
        $action->io['archivistNotes'] = [];
        if (is_array($archNotesRaw)) {
            foreach ($archNotesRaw as $an) {
                $action->io['archivistNotes'][] = (object) ['content' => $an['content'] ?? ''];
            }
        }
    }

    /**
     * Handle POST for create/update. Returns true if redirect happened, false if validation errors.
     */
    public static function handlePost(sfActions $action, sfWebRequest $request, string $culture): bool
    {
        $svc = '\\AhgInformationObjectManage\\Services\\InformationObjectCrudService';

        $action->errors = [];
        $title = trim($request->getParameter('title', ''));
        $levelOfDescriptionId = $request->getParameter('levelOfDescriptionId', '');

        if (empty($title)) {
            $action->errors[] = $action->getContext()->getI18N()->__('Title is required.');
        }
        if (empty($levelOfDescriptionId)) {
            $action->errors[] = $action->getContext()->getI18N()->__('Level of description is required.');
        }

        if (empty($action->errors)) {
            $data = self::extractFormData($request);

            if ($action->isNew) {
                $newId = $svc::create($data, $culture);
                $newSlug = \AhgCore\Services\ObjectService::getSlug($newId);
                $action->redirect('/' . $newSlug);

                return true;
            }

            $svc::update($action->io['id'], $data, $culture);
            $action->redirect('/' . $action->io['slug']);

            return true;
        }

        self::repopulateFromRequest($action, $request);

        return false;
    }

    /**
     * Get note type terms.
     */
    protected static function getNoteTypes(string $culture): array
    {
        return \Illuminate\Database\Capsule\Manager::table('term')
            ->leftJoin('term_i18n', function ($join) use ($culture) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', 37)
            ->select(['term.id', 'term_i18n.name'])
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }
}
