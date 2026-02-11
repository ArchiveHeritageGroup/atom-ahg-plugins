<?php

use AtomFramework\Http\Controllers\AhgController;
class rightsHolderManageActions extends AhgController
{
    public function executeBrowse($request)
    {
        $culture = $this->culture();

        $this->response->setTitle(__('Browse rights holders') . ' - ' . $this->response->getTitle());

        // Sort options
        $this->sortOptions = [
            'alphabetic' => $this->context->i18n->__('Name'),
            'lastUpdated' => $this->context->i18n->__('Date modified'),
            'identifier' => $this->context->i18n->__('Identifier'),
        ];

        // Sort defaults
        if ($this->getUser()->isAuthenticated()) {
            $sortSetting = $this->config('app_sort_browser_user', 'lastUpdated');
        } else {
            $sortSetting = $this->config('app_sort_browser_anonymous', 'lastUpdated');
        }

        $sort = $request->getParameter('sort', $sortSetting);
        $sortDir = 'asc';
        if ('lastUpdated' == $sort) {
            $sortDir = 'desc';
        }
        if ($request->sortDir && in_array($request->sortDir, ['asc', 'desc'])) {
            $sortDir = $request->sortDir;
        }

        $limit = (int) ($request->limit ?: $this->config('app_hits_per_page', 30));
        $page = (int) ($request->page ?: 1);

        // Handle global search redirect: ?query=X -> subquery=X
        $subquery = $request->getParameter('subquery', '');
        if (empty($subquery) && !empty($request->getParameter('query'))) {
            $subquery = $request->getParameter('query');
        }

        $service = new \AhgRightsHolderManage\Services\RightsHolderBrowseService($culture);

        $browseResult = $service->browse([
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort,
            'sortDir' => $sortDir,
            'subquery' => $subquery,
        ]);

        $this->pager = new \AhgRightsHolderManage\SimplePager(
            $browseResult['hits'],
            $browseResult['total'],
            $browseResult['page'],
            $browseResult['limit']
        );
    }

    /**
     * View a rights holder record.
     */
    public function executeView($request)
    {
        $culture = $this->culture();
        $slug = $request->getParameter('slug');

        $this->rightsHolder = \AhgRightsHolderManage\Services\RightsHolderCrudService::getBySlug($slug, $culture);
        if (!$this->rightsHolder) {
            $this->forward404();
        }

        $user = $this->getUser();
        $isAdmin = $user->isAuthenticated() && ($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID));

        if (!$user->isAuthenticated()) {
            QubitAcl::forwardUnauthorized();
        }

        $title = $this->rightsHolder['authorizedFormOfName'] ?: $this->context->i18n->__('Untitled');
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        $this->canEdit = $isAdmin;
        $this->canDelete = $isAdmin;
        $this->canCreate = $isAdmin;
    }

    /**
     * Edit or create a rights holder record.
     */
    public function executeEdit($request)
    {
        $culture = $this->culture();
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $user = $this->getUser();
        if (!$user->isAuthenticated() || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))) {
            QubitAcl::forwardUnauthorized();
        }

        $slug = $request->getParameter('slug');
        $this->isNew = empty($slug);

        if (!$this->isNew) {
            $this->rightsHolder = \AhgRightsHolderManage\Services\RightsHolderCrudService::getBySlug($slug, $culture);
            if (!$this->rightsHolder) {
                $this->forward404();
            }

            $title = $this->rightsHolder['authorizedFormOfName'] ?: $this->context->i18n->__('Untitled');
            $this->response->setTitle($this->context->i18n->__('Edit %1%', ['%1%' => $title]) . ' - ' . $this->response->getTitle());
        } else {
            $this->rightsHolder = [
                'id' => null,
                'slug' => null,
                'authorizedFormOfName' => '',
                'contacts' => [],
                'serialNumber' => 0,
            ];

            $this->response->setTitle($this->context->i18n->__('Add new rights holder') . ' - ' . $this->response->getTitle());
        }

        $this->contacts = $this->rightsHolder['contacts'] ?? [];

        // Handle POST
        if ($request->isMethod('post')) {
            $authorizedFormOfName = trim($request->getParameter('authorizedFormOfName', ''));

            if ($this->isNew) {
                $newId = \AhgRightsHolderManage\Services\RightsHolderCrudService::create([
                    'authorizedFormOfName' => $authorizedFormOfName,
                    'contacts' => $this->parseContactsFromRequest($request),
                ], $culture);

                $newSlug = \AhgCore\Services\ObjectService::getSlug($newId);
                $this->redirect('@rightsholder_view_override?slug=' . $newSlug);
            } else {
                \AhgRightsHolderManage\Services\RightsHolderCrudService::update($this->rightsHolder['id'], [
                    'authorizedFormOfName' => $authorizedFormOfName,
                ], $culture);

                $this->processContactUpdates($request, $this->rightsHolder['id'], $culture);
                $this->redirect('@rightsholder_view_override?slug=' . $this->rightsHolder['slug']);
            }
        }
    }

    /**
     * Delete a rights holder record.
     */
    public function executeDelete($request)
    {
        $this->form = new sfForm();
        $culture = $this->culture();

        $user = $this->getUser();
        if (!$user->isAuthenticated() || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))) {
            QubitAcl::forwardUnauthorized();
        }

        $slug = $request->getParameter('slug');
        $this->rightsHolder = \AhgRightsHolderManage\Services\RightsHolderCrudService::getBySlug($slug, $culture);
        if (!$this->rightsHolder) {
            $this->forward404();
        }

        if ($request->isMethod('delete')) {
            $this->form->bind($request->getPostParameters());
            if ($this->form->isValid()) {
                \AhgRightsHolderManage\Services\RightsHolderCrudService::delete($this->rightsHolder['id']);

                try {
                    \AhgCore\Services\ElasticsearchService::deleteDocument('qubitactor', $this->rightsHolder['id']);
                } catch (\Exception $e) {
                    // ES failure should not block delete
                }

                $this->redirect('@rightsholder_browse_override');
            }
        }
    }

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

    protected function processContactUpdates($request, int $rightsHolderId, string $culture): void
    {
        $contactData = [];
        foreach (['contact_person', 'street_address', 'city', 'region', 'postal_code', 'country_code', 'telephone', 'fax', 'email', 'website', 'note'] as $field) {
            $value = $request->getParameter($field);
            if ($value !== null) {
                $contactData[$field] = $value;
            }
        }

        if (!empty($contactData)) {
            $existingContacts = \AhgCore\Services\ContactInformationService::getByActorId($rightsHolderId, $culture);
            if (!empty($existingContacts)) {
                \AhgCore\Services\ContactInformationService::save(
                    $rightsHolderId,
                    $contactData,
                    $culture,
                    $existingContacts[0]->id
                );
            } else {
                \AhgCore\Services\ContactInformationService::save($rightsHolderId, $contactData, $culture);
            }
        }
    }
}
