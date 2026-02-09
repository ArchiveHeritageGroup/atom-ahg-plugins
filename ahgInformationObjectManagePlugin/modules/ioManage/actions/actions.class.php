<?php

class ioManageActions extends sfActions
{
    // Note types taxonomy
    const TAXONOMY_NOTE_TYPES = 37;

    public function preExecute()
    {
        parent::preExecute();

        sfContext::getInstance()->getConfiguration()->loadHelpers(['I18N', 'Url', 'Qubit', 'Text', 'Date']);

        if (!class_exists('Illuminate\Database\Capsule\Manager')) {
            $frameworkBoot = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($frameworkBoot)) {
                require_once $frameworkBoot;
            }
        }
    }

    /**
     * Edit or create an information object (ISAD(G)).
     */
    public function executeEdit(sfWebRequest $request)
    {
        $culture = $this->context->user->getCulture();
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        // ACL — require editor/admin
        $user = $this->context->user;
        if (!$user->isAuthenticated()
            || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))
        ) {
            QubitAcl::forwardUnauthorized();
        }

        $slug = $request->getParameter('slug');
        $this->isNew = empty($slug);

        // Load dropdown options
        $this->levels = \AhgInformationObjectManage\Services\InformationObjectCrudService::getLevelsOfDescription($culture);
        $this->descriptionStatuses = \AhgInformationObjectManage\Services\InformationObjectCrudService::getDescriptionStatuses($culture);
        $this->descriptionDetails = \AhgInformationObjectManage\Services\InformationObjectCrudService::getDescriptionDetails($culture);
        $this->eventTypes = \AhgInformationObjectManage\Services\InformationObjectCrudService::getEventTypes($culture);
        $this->publicationStatuses = \AhgInformationObjectManage\Services\InformationObjectCrudService::getPublicationStatuses();
        $this->noteTypes = $this->getNoteTypes($culture);

        if (!$this->isNew) {
            $this->io = \AhgInformationObjectManage\Services\InformationObjectCrudService::getBySlug($slug, $culture);
            if (!$this->io) {
                $this->forward404();
            }

            $title = $this->io['title'] ?: $this->context->i18n->__('Untitled');
            $this->response->setTitle($this->context->i18n->__('Edit %1%', ['%1%' => $title]) . ' - ' . $this->response->getTitle());
        } else {
            // Defaults for new record
            $parentId = (int) $request->getParameter('parent', \AhgInformationObjectManage\Services\InformationObjectCrudService::ROOT_ID);
            $parentTitle = null;
            $parentSlug = null;

            if ($parentId && $parentId != \AhgInformationObjectManage\Services\InformationObjectCrudService::ROOT_ID) {
                $parentI18n = \AhgCore\Services\I18nService::getWithFallback('information_object_i18n', $parentId, $culture);
                $parentTitle = $parentI18n->title ?? null;
                $parentSlug = \AhgCore\Services\ObjectService::getSlug($parentId);
            }

            $this->io = [
                'id' => null,
                'slug' => null,
                'identifier' => '',
                'title' => '',
                'levelOfDescriptionId' => null,
                'repositoryId' => null,
                'repositoryName' => null,
                'parentId' => $parentId,
                'parentTitle' => $parentTitle,
                'parentSlug' => $parentSlug,
                'descriptionStatusId' => null,
                'descriptionDetailId' => null,
                'descriptionIdentifier' => '',
                'sourceStandard' => 'ISAD(G) 2nd edition',
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
                'nameAccessPoints' => [],
                'notes' => [],
                'publicationStatusId' => \AhgInformationObjectManage\Services\InformationObjectCrudService::STATUS_DRAFT,
            ];

            $this->response->setTitle($this->context->i18n->__('Add new archival description') . ' - ' . $this->response->getTitle());
        }

        // Handle POST
        if ($request->isMethod('post')) {
            $this->errors = [];

            $title = trim($request->getParameter('title', ''));
            $levelOfDescriptionId = $request->getParameter('levelOfDescriptionId', '');

            if (empty($title)) {
                $this->errors[] = __('Title is required.');
            }
            if (empty($levelOfDescriptionId)) {
                $this->errors[] = __('Level of description is required.');
            }

            if (empty($this->errors)) {
                $data = $this->extractFormData($request);

                if ($this->isNew) {
                    $newId = \AhgInformationObjectManage\Services\InformationObjectCrudService::create($data, $culture);
                    $newSlug = \AhgCore\Services\ObjectService::getSlug($newId);
                    $this->redirect('/' . $newSlug);
                } else {
                    \AhgInformationObjectManage\Services\InformationObjectCrudService::update($this->io['id'], $data, $culture);
                    $this->redirect('/' . $this->io['slug']);
                }
            }

            // If errors, re-populate from submitted data for re-display
            $this->repopulateFromRequest($request);
        }
    }

    /**
     * Delete an information object.
     */
    public function executeDelete(sfWebRequest $request)
    {
        $this->form = new sfForm();
        $culture = $this->context->user->getCulture();

        // ACL
        $user = $this->context->user;
        if (!$user->isAuthenticated()
            || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))
        ) {
            QubitAcl::forwardUnauthorized();
        }

        $slug = $request->getParameter('slug');
        $this->io = \AhgInformationObjectManage\Services\InformationObjectCrudService::getBySlug($slug, $culture);
        if (!$this->io) {
            $this->forward404();
        }

        // Check for children
        $this->hasChildren = \AhgInformationObjectManage\Services\NestedSetService::hasChildren($this->io['id']);

        if ($request->isMethod('delete')) {
            if ($this->hasChildren) {
                $this->errors = [__('Cannot delete: this description has child records. Delete or move children first.')];

                return;
            }

            $this->form->bind($request->getPostParameters());
            if ($this->form->isValid()) {
                $parentSlug = $this->io['parentSlug'];
                \AhgInformationObjectManage\Services\InformationObjectCrudService::delete($this->io['id']);

                if ($parentSlug) {
                    $this->redirect('/' . $parentSlug);
                } else {
                    $this->redirect('/');
                }
            }
        }
    }

    /**
     * Extract all form data from the request.
     */
    protected function extractFormData(sfWebRequest $request): array
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
            'sourceStandard' => trim($request->getParameter('sourceStandard', 'ISAD(G) 2nd edition')),
            'publicationStatusId' => $request->getParameter('publicationStatusId', ''),
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
        ];

        // Extract events
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

        // Extract access point term IDs
        $data['subjectAccessPointIds'] = array_filter((array) $request->getParameter('subjectAccessPointIds', []));
        $data['placeAccessPointIds'] = array_filter((array) $request->getParameter('placeAccessPointIds', []));
        $data['genreAccessPointIds'] = array_filter((array) $request->getParameter('genreAccessPointIds', []));

        // Extract name access points
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

        // Extract notes
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

        return $data;
    }

    /**
     * Re-populate $this->io from submitted request data after validation error.
     */
    protected function repopulateFromRequest(sfWebRequest $request): void
    {
        $this->io['title'] = $request->getParameter('title', '');
        $this->io['identifier'] = $request->getParameter('identifier', '');
        $this->io['levelOfDescriptionId'] = $request->getParameter('levelOfDescriptionId', '');
        $this->io['repositoryId'] = $request->getParameter('repositoryId', '');
        $this->io['repositoryName'] = $request->getParameter('repositoryName', '');
        $this->io['descriptionStatusId'] = $request->getParameter('descriptionStatusId', '');
        $this->io['descriptionDetailId'] = $request->getParameter('descriptionDetailId', '');
        $this->io['descriptionIdentifier'] = $request->getParameter('descriptionIdentifier', '');
        $this->io['sourceStandard'] = $request->getParameter('sourceStandard', '');
        $this->io['publicationStatusId'] = $request->getParameter('publicationStatusId', '');

        // i18n fields
        foreach ([
            'extentAndMedium', 'archivalHistory', 'acquisition', 'scopeAndContent',
            'appraisal', 'accruals', 'arrangement', 'accessConditions',
            'reproductionConditions', 'physicalCharacteristics', 'findingAids',
            'locationOfOriginals', 'locationOfCopies', 'relatedUnitsOfDescription',
            'institutionResponsibleIdentifier', 'rules', 'sources', 'revisionHistory',
        ] as $field) {
            $this->io[$field] = $request->getParameter($field, '');
        }

        // Re-populate events from POST
        $eventsRaw = $request->getParameter('events', []);
        $this->io['events'] = [];
        if (is_array($eventsRaw)) {
            foreach ($eventsRaw as $evt) {
                $this->io['events'][] = (object) [
                    'type_id' => $evt['typeId'] ?? null,
                    'date' => $evt['date'] ?? '',
                    'start_date' => $evt['startDate'] ?? null,
                    'end_date' => $evt['endDate'] ?? null,
                    'actor_id' => $evt['actorId'] ?? null,
                    'actor_name' => $evt['actorName'] ?? '',
                ];
            }
        }
    }

    /**
     * Get note type terms.
     */
    protected function getNoteTypes(string $culture): array
    {
        return \Illuminate\Database\Capsule\Manager::table('term')
            ->leftJoin('term_i18n', function ($join) use ($culture) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', self::TAXONOMY_NOTE_TYPES)
            ->select(['term.id', 'term_i18n.name'])
            ->orderBy('term_i18n.name')
            ->get()
            ->all();
    }
}
