<?php

class donorManageActions extends sfActions
{
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

    public function executeBrowse(sfWebRequest $request)
    {
        $culture = $this->context->user->getCulture();

        $this->response->setTitle(__('Browse donors') . ' - ' . $this->response->getTitle());

        // Sort options
        $this->sortOptions = [
            'alphabetic' => $this->context->i18n->__('Name'),
            'lastUpdated' => $this->context->i18n->__('Date modified'),
            'identifier' => $this->context->i18n->__('Identifier'),
        ];

        // Sort defaults
        if ($this->getUser()->isAuthenticated()) {
            $sortSetting = sfConfig::get('app_sort_browser_user', 'lastUpdated');
        } else {
            $sortSetting = sfConfig::get('app_sort_browser_anonymous', 'lastUpdated');
        }

        $sort = $request->getParameter('sort', $sortSetting);
        $sortDir = 'asc';
        if ('lastUpdated' == $sort) {
            $sortDir = 'desc';
        }
        if ($request->sortDir && in_array($request->sortDir, ['asc', 'desc'])) {
            $sortDir = $request->sortDir;
        }

        $limit = (int) ($request->limit ?: sfConfig::get('app_hits_per_page', 30));
        $page = (int) ($request->page ?: 1);

        // Handle global search redirect: ?query=X -> subquery=X
        $subquery = $request->getParameter('subquery', '');
        if (empty($subquery) && !empty($request->getParameter('query'))) {
            $subquery = $request->getParameter('query');
        }

        $service = new \AhgDonorManage\Services\DonorBrowseService($culture);

        $browseResult = $service->browse([
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort,
            'sortDir' => $sortDir,
            'subquery' => $subquery,
        ]);

        $this->pager = new \AhgDonorManage\SimplePager(
            $browseResult['hits'],
            $browseResult['total'],
            $browseResult['page'],
            $browseResult['limit']
        );
    }

    /**
     * View a donor record.
     */
    public function executeView(sfWebRequest $request)
    {
        $culture = $this->context->user->getCulture();
        $slug = $request->getParameter('slug');

        $this->donor = \AhgDonorManage\Services\DonorCrudService::getBySlug($slug, $culture);
        if (!$this->donor) {
            $this->forward404();
        }

        // ACL — donors require authenticated editor/admin
        $user = $this->context->user;
        $isAdmin = $user->isAuthenticated() && ($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID));

        if (!$user->isAuthenticated()) {
            QubitAcl::forwardUnauthorized();
        }

        $title = $this->donor['authorizedFormOfName'] ?: $this->context->i18n->__('Untitled');
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        $this->canEdit = $isAdmin;
        $this->canDelete = $isAdmin;
        $this->canCreate = $isAdmin;

        // Validation check
        if ($this->canEdit && empty($this->donor['authorizedFormOfName'])) {
            $validatorSchema = new sfValidatorSchema();
            $validatorSchema->authorizedFormOfName = new sfValidatorString(
                ['required' => true],
                ['required' => $this->context->i18n->__('Authorized form of name - This is a mandatory field.')]
            );
            try {
                $validatorSchema->clean(['authorizedFormOfName' => '']);
            } catch (sfValidatorErrorSchema $e) {
                $this->errorSchema = $e;
            }
        }
    }

    /**
     * Edit or create a donor record.
     */
    public function executeEdit(sfWebRequest $request)
    {
        $culture = $this->context->user->getCulture();
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        // ACL — donors require authenticated editor/admin
        $user = $this->context->user;
        if (!$user->isAuthenticated() || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))) {
            QubitAcl::forwardUnauthorized();
        }

        $slug = $request->getParameter('slug');
        $this->isNew = empty($slug);

        if (!$this->isNew) {
            $this->donor = \AhgDonorManage\Services\DonorCrudService::getBySlug($slug, $culture);
            if (!$this->donor) {
                $this->forward404();
            }

            $title = $this->donor['authorizedFormOfName'] ?: $this->context->i18n->__('Untitled');
            $this->response->setTitle($this->context->i18n->__('Edit %1%', ['%1%' => $title]) . ' - ' . $this->response->getTitle());
        } else {
            $this->donor = [
                'id' => null,
                'slug' => null,
                'authorizedFormOfName' => '',
                'contacts' => [],
                'accessions' => [],
                'serialNumber' => 0,
            ];

            $this->response->setTitle($this->context->i18n->__('Add new donor') . ' - ' . $this->response->getTitle());
        }

        $this->contacts = $this->donor['contacts'] ?? [];

        // Handle POST
        if ($request->isMethod('post')) {
            $authorizedFormOfName = trim($request->getParameter('authorizedFormOfName', ''));

            if ($this->isNew) {
                $newId = \AhgDonorManage\Services\DonorCrudService::create([
                    'authorizedFormOfName' => $authorizedFormOfName,
                    'contacts' => $this->parseContactsFromRequest($request),
                ], $culture);

                $newSlug = \AhgCore\Services\ObjectService::getSlug($newId);
                $this->redirect('@donor_view_override?slug=' . $newSlug);
            } else {
                \AhgDonorManage\Services\DonorCrudService::update($this->donor['id'], [
                    'authorizedFormOfName' => $authorizedFormOfName,
                ], $culture);

                $this->processContactUpdates($request, $this->donor['id'], $culture);
                $this->redirect('@donor_view_override?slug=' . $this->donor['slug']);
            }
        }
    }

    /**
     * Delete a donor record.
     */
    public function executeDelete(sfWebRequest $request)
    {
        $this->form = new sfForm();
        $culture = $this->context->user->getCulture();

        // ACL
        $user = $this->context->user;
        if (!$user->isAuthenticated() || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))) {
            QubitAcl::forwardUnauthorized();
        }

        $slug = $request->getParameter('slug');
        $this->donor = \AhgDonorManage\Services\DonorCrudService::getBySlug($slug, $culture);
        if (!$this->donor) {
            $this->forward404();
        }

        if ($request->isMethod('delete')) {
            $this->form->bind($request->getPostParameters());
            if ($this->form->isValid()) {
                \AhgDonorManage\Services\DonorCrudService::delete($this->donor['id']);

                try {
                    \AhgCore\Services\ElasticsearchService::deleteDocument('qubitactor', $this->donor['id']);
                } catch (\Exception $e) {
                    // ES failure should not block delete
                }

                $this->redirect('@donor_browse_override');
            }
        }
    }

    /**
     * Parse contact information from POST request.
     */
    protected function parseContactsFromRequest($request): array
    {
        $contacts = [];
        $contact = [];
        foreach (['contact_person', 'street_address', 'city', 'region', 'postal_code', 'country_code', 'telephone', 'fax', 'email', 'website', 'note'] as $field) {
            $value = $request->getParameter($field);
            if (!empty($value)) {
                $contact[$field] = $value;
            }
        }
        if (!empty($contact)) {
            $contacts[] = $contact;
        }

        return $contacts;
    }

    /**
     * Process contact information updates.
     */
    protected function processContactUpdates($request, int $donorId, string $culture): void
    {
        $contactData = [];
        foreach (['contact_person', 'street_address', 'city', 'region', 'postal_code', 'country_code', 'telephone', 'fax', 'email', 'website', 'note'] as $field) {
            $value = $request->getParameter($field);
            if ($value !== null) {
                $contactData[$field] = $value;
            }
        }

        if (!empty($contactData)) {
            $existingContacts = \AhgCore\Services\ContactInformationService::getByActorId($donorId, $culture);
            if (!empty($existingContacts)) {
                \AhgCore\Services\ContactInformationService::save(
                    $donorId,
                    $contactData,
                    $culture,
                    $existingContacts[0]->id
                );
            } else {
                \AhgCore\Services\ContactInformationService::save($donorId, $contactData, $culture);
            }
        }
    }
}
