<?php

declare(strict_types=1);

/**
 * Rights module actions
 *
 * @package    ahgRightsPlugin
 * @subpackage actions
 */
class rightsActions extends sfActions
{
    protected RightsService $service;

    public function preExecute(): void
    {
        parent::preExecute();

        if (!class_exists('RightsService')) {
            require_once sfConfig::get('sf_plugins_dir') . '/ahgRightsPlugin/lib/Service/RightsService.php';
        }

        $this->service = RightsService::getInstance();
    }

    /**
     * View rights for an object
     */
    public function executeIndex(sfWebRequest $request): void
    {
        $this->resource = $this->getRoute()->resource;

        if (!isset($this->resource)) {
            $this->forward404();
        }

        $this->rights = $this->service->getRightsForObject($this->resource->id);
        $this->embargo = $this->service->getEmbargo($this->resource->id);
        $this->tkLabels = $this->service->getTkLabelsForObject($this->resource->id);
        $this->orphanWork = $this->service->getOrphanWork($this->resource->id);
        $this->accessCheck = $this->service->checkAccess(
            $this->resource->id,
            'information_object',
            $this->getUser()->getAttribute('user_id')
        );
        $this->canEdit = ($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'));
    }

    /**
     * Add new rights record
     */
    public function executeAdd(sfWebRequest $request): void
    {
        $this->resource = $this->getRoute()->resource;

        if (!isset($this->resource)) {
            $this->forward404();
        }

        if (!($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'))) {
            $this->forward("admin", "secure");
        }

        $this->formOptions = $this->service->getFormOptions();
        $this->isNew = true;
        $this->right = null;

        if ($request->isMethod('post')) {
            $this->processForm($request);
        }

        $this->setTemplate('edit');
    }

    /**
     * Edit existing rights record
     */
    public function executeEdit(sfWebRequest $request): void
    {
        $this->resource = $this->getRoute()->resource;
        $rightId = (int) $request->getParameter('id');

        if (!isset($this->resource) || !$rightId) {
            $this->forward404();
        }

        if (!($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'))) {
            $this->forward("admin", "secure");
        }

        $this->right = $this->service->getRightsRecord($rightId);

        if (!$this->right || $this->right['object_id'] !== $this->resource->id) {
            $this->forward404();
        }

        $this->formOptions = $this->service->getFormOptions();
        $this->isNew = false;

        if ($request->isMethod('post')) {
            $this->processForm($request, $rightId);
        }
    }

    /**
     * Process rights form submission
     */
    protected function processForm(sfWebRequest $request, ?int $rightId = null): void
    {
        // CSRF check
        if (!$this->getUser()->getAttribute('_csrf_token') === $request->getParameter('_csrf_token')) {
            throw new sfException('CSRF token mismatch');
        }

        $data = [
            'id' => $rightId,
            'object_id' => $this->resource->id,
            'object_type' => 'information_object',
            'basis' => $request->getParameter('basis'),
            'basis_note' => $request->getParameter('basis_note'),
            'rights_statement_id' => $request->getParameter('rights_statement_id') ?: null,
            'copyright_status' => $request->getParameter('copyright_status') ?: null,
            'copyright_jurisdiction' => $request->getParameter('copyright_jurisdiction') ?: null,
            'copyright_status_date' => $request->getParameter('copyright_status_date') ?: null,
            'copyright_holder' => $request->getParameter('copyright_holder') ?: null,
            'copyright_expiry_date' => $request->getParameter('copyright_expiry_date') ?: null,
            'copyright_note' => $request->getParameter('copyright_note') ?: null,
            'license_type' => $request->getParameter('license_type') ?: null,
            'cc_license_id' => $request->getParameter('cc_license_id') ?: null,
            'license_identifier' => $request->getParameter('license_identifier') ?: null,
            'license_terms' => $request->getParameter('license_terms') ?: null,
            'license_url' => $request->getParameter('license_url') ?: null,
            'license_note' => $request->getParameter('license_note') ?: null,
            'statute_jurisdiction' => $request->getParameter('statute_jurisdiction') ?: null,
            'statute_citation' => $request->getParameter('statute_citation') ?: null,
            'statute_determination_date' => $request->getParameter('statute_determination_date') ?: null,
            'statute_note' => $request->getParameter('statute_note') ?: null,
            'donor_name' => $request->getParameter('donor_name') ?: null,
            'policy_identifier' => $request->getParameter('policy_identifier') ?: null,
            'start_date' => $request->getParameter('start_date') ?: null,
            'end_date' => $request->getParameter('end_date') ?: null,
            'end_date_open' => $request->getParameter('end_date_open') ? 1 : 0,
            'rights_holder_name' => $request->getParameter('rights_holder_name') ?: null,
            'rights_note' => $request->getParameter('rights_note') ?: null,
        ];

        // Process granted rights
        $grantedRights = [];
        $acts = $request->getParameter('acts', []);
        $restrictions = $request->getParameter('restrictions', []);
        $restrictionReasons = $request->getParameter('restriction_reasons', []);

        if (is_array($acts)) {
            foreach ($acts as $i => $act) {
                if (!empty($act)) {
                    $grantedRights[] = [
                        'act' => $act,
                        'restriction' => $restrictions[$i] ?? 'allow',
                        'restriction_reason' => $restrictionReasons[$i] ?? null,
                    ];
                }
            }
        }

        $data['granted_rights'] = $grantedRights;

        try {
            $this->service->saveRightsRecord($data);
            $this->getUser()->setFlash('notice', $this->context->i18n->__('Rights record saved.'));
            $this->redirect([$this->resource, 'module' => 'informationobject']);
        } catch (Exception $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
        }
    }

    /**
     * Delete rights record
     */
    public function executeDelete(sfWebRequest $request): void
    {
        $this->resource = $this->getRoute()->resource;
        $rightId = (int) $request->getParameter('id');

        if (!isset($this->resource) || !$rightId) {
            $this->forward404();
        }

        if (!$this->getUser()->isAdministrator()) {
            $this->forward("admin", "secure");
        }

        if ($request->isMethod('post')) {
            $this->service->deleteRightsRecord($rightId);
            $this->getUser()->setFlash('notice', $this->context->i18n->__('Rights record deleted.'));
        }

        $this->redirect([$this->resource, 'module' => 'informationobject']);
    }

    /**
     * Edit embargo
     */
    public function executeEditEmbargo(sfWebRequest $request): void
    {
        $this->resource = $this->getRoute()->resource;

        if (!isset($this->resource)) {
            $this->forward404();
        }

        if (!($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'))) {
            $this->forward("admin", "secure");
        }

        $this->embargo = $this->service->getEmbargo($this->resource->id);
        $this->formOptions = $this->service->getFormOptions();

        if ($request->isMethod('post')) {
            $data = [
                'id' => $this->embargo['id'] ?? null,
                'object_id' => $this->resource->id,
                'object_type' => 'information_object',
                'embargo_type' => $request->getParameter('embargo_type'),
                'reason' => $request->getParameter('reason'),
                'reason_detail' => $request->getParameter('reason_detail'),
                'start_date' => $request->getParameter('start_date'),
                'end_date' => $request->getParameter('end_date') ?: null,
                'auto_release' => $request->getParameter('auto_release') ? 1 : 0,
                'release_notification_days' => (int) $request->getParameter('release_notification_days', 30),
                'allow_staff' => $request->getParameter('allow_staff') ? 1 : 0,
                'allow_researchers' => $request->getParameter('allow_researchers') ? 1 : 0,
                'access_note' => $request->getParameter('access_note'),
            ];

            try {
                $this->service->setEmbargo($data);
                $this->getUser()->setFlash('notice', $this->context->i18n->__('Embargo saved.'));
                $this->redirect([$this->resource, 'module' => 'informationobject']);
            } catch (Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
    }

    /**
     * Release embargo
     */
    public function executeReleaseEmbargo(sfWebRequest $request): void
    {
        $this->resource = $this->getRoute()->resource;
        $embargoId = (int) $request->getParameter('id');

        if (!isset($this->resource) || !$embargoId) {
            $this->forward404();
        }

        if (!($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'))) {
            $this->forward("admin", "secure");
        }

        if ($request->isMethod('post')) {
            $this->service->releaseEmbargo($embargoId);
            $this->getUser()->setFlash('notice', $this->context->i18n->__('Embargo released.'));
        }

        $this->redirect([$this->resource, 'module' => 'informationobject']);
    }

    /**
     * Manage TK Labels
     */
    public function executeTkLabels(sfWebRequest $request): void
    {
        $this->resource = $this->getRoute()->resource;

        if (!isset($this->resource)) {
            $this->forward404();
        }

        if (!($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'))) {
            $this->forward("admin", "secure");
        }

        $this->availableLabels = $this->service->getTkLabels();
        $this->assignedLabels = $this->service->getTkLabelsForObject($this->resource->id);

        if ($request->isMethod('post')) {
            $labelId = (int) $request->getParameter('tk_label_id');
            $data = [
                'object_type' => 'information_object',
                'community_name' => $request->getParameter('community_name'),
                'community_contact' => $request->getParameter('community_contact'),
                'provenance_statement' => $request->getParameter('provenance_statement'),
                'cultural_note' => $request->getParameter('cultural_note'),
            ];

            try {
                $this->service->assignTkLabel($this->resource->id, $labelId, $data);
                $this->getUser()->setFlash('notice', $this->context->i18n->__('TK Label assigned.'));
                $this->redirect(['module' => 'rights', 'action' => 'tkLabels', 'slug' => $this->resource->slug]);
            } catch (Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
    }

    /**
     * Orphan work management
     */
    public function executeOrphanWork(sfWebRequest $request): void
    {
        $this->resource = $this->getRoute()->resource;

        if (!isset($this->resource)) {
            $this->forward404();
        }

        if (!($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'))) {
            $this->forward("admin", "secure");
        }

        $this->orphanWork = $this->service->getOrphanWork($this->resource->id);

        if ($request->isMethod('post')) {
            $data = [
                'id' => $this->orphanWork['id'] ?? null,
                'object_id' => $this->resource->id,
                'status' => $request->getParameter('status'),
                'work_type' => $request->getParameter('work_type'),
                'work_title' => $request->getParameter('work_title'),
                'creator_name' => $request->getParameter('creator_name'),
                'search_started_date' => $request->getParameter('search_started_date'),
                'search_completed_date' => $request->getParameter('search_completed_date'),
                'search_conducted_by' => $request->getParameter('search_conducted_by'),
                'search_methodology' => $request->getParameter('search_methodology'),
                'sources_searched' => $request->getParameter('sources_searched'),
                'evidence_summary' => $request->getParameter('evidence_summary'),
                'jurisdiction' => $request->getParameter('jurisdiction'),
            ];

            try {
                $this->service->saveOrphanWork($data);
                $this->getUser()->setFlash('notice', $this->context->i18n->__('Orphan work record saved.'));
                $this->redirect([$this->resource, 'module' => 'informationobject']);
            } catch (Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
    }

    /**
     * API: Check access rights
     */
    public function executeApiCheck(sfWebRequest $request): sfView
    {
        $objectId = (int) $request->getParameter('id');

        $result = $this->service->checkAccess(
            $objectId,
            'information_object',
            $this->getUser()->getAttribute('user_id')
        );

        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode($result));
    }

    /**
     * API: Get embargo status
     */
    public function executeApiEmbargo(sfWebRequest $request): sfView
    {
        $objectId = (int) $request->getParameter('id');

        $embargo = $this->service->getEmbargo($objectId);

        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode([
            'embargoed' => $embargo !== null,
            'embargo' => $embargo,
        ]));
    }
}
