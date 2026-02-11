<?php

use AtomFramework\Http\Controllers\AhgController;
class userManageActions extends AhgController
{
    public function executeBrowse($request)
    {
        // Admin-only access
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');

            return;
        }

        $culture = $this->culture();

        $this->response->setTitle(__('List users') . ' - ' . $this->response->getTitle());

        // Sort options
        $this->sortOptions = [
            'username' => $this->context->i18n->__('Username'),
            'email' => $this->context->i18n->__('Email'),
            'lastUpdated' => $this->context->i18n->__('Date modified'),
        ];

        // Sort defaults
        $sort = $request->getParameter('sort', 'username');
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

        // Active/inactive filter
        $this->filter = $request->getParameter('filter', '');

        $service = new \AhgUserManage\Services\UserBrowseService($culture);

        $browseResult = $service->browse([
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort,
            'sortDir' => $sortDir,
            'subquery' => $subquery,
            'filter' => $this->filter,
        ]);

        $this->pager = new \AhgUserManage\SimplePager(
            $browseResult['hits'],
            $browseResult['total'],
            $browseResult['page'],
            $browseResult['limit']
        );
    }

    /**
     * View a user profile.
     */
    public function executeView($request)
    {
        // Admin-only
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');

            return;
        }

        $slug = $request->getParameter('slug');

        $this->userRecord = \AhgUserManage\Services\UserCrudService::getBySlug($slug);
        if (!$this->userRecord) {
            $this->forward404();
        }

        $title = $this->userRecord['username'] ?: $this->context->i18n->__('Untitled');
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        // Check if viewing own profile
        $this->isSelf = ($this->userRecord['id'] == $this->getUser()->getUserID());

        // Get API keys
        $this->restApiKey = \AhgUserManage\Services\UserCrudService::getApiKey($this->userRecord['id'], 'RestApiKey');
        $this->oaiApiKey = \AhgUserManage\Services\UserCrudService::getApiKey($this->userRecord['id'], 'OaiApiKey');

        // Get translate languages
        $this->translateLanguages = \AhgUserManage\Services\UserCrudService::getTranslateLanguages($this->userRecord['id']);

        // Get security clearance if plugin is active
        $this->clearance = null;
        if (class_exists('\\AhgSecurityClearance\\Services\\SecurityClearanceService')) {
            try {
                $this->clearance = \AhgSecurityClearance\Services\SecurityClearanceService::getUserClearance($this->userRecord['id']);
            } catch (\Exception $e) {
                // Plugin not fully installed
            }
        }
    }

    /**
     * Edit or create a user.
     */
    public function executeEdit($request)
    {
        // Admin-only
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');

            return;
        }

        $culture = $this->culture();
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $slug = $request->getParameter('slug');
        $this->isNew = empty($slug);

        // Get assignable groups
        $this->assignableGroups = \AhgUserManage\Services\UserCrudService::getAssignableGroups($culture);

        // Get available languages for translate permission
        $this->availableLanguages = \AhgUserManage\Services\UserCrudService::getAvailableLanguages();

        if (!$this->isNew) {
            $this->userRecord = \AhgUserManage\Services\UserCrudService::getBySlug($slug);
            if (!$this->userRecord) {
                $this->forward404();
            }

            $title = $this->userRecord['username'] ?: $this->context->i18n->__('Untitled');
            $this->response->setTitle($this->context->i18n->__('Edit %1%', ['%1%' => $title]) . ' - ' . $this->response->getTitle());

            $this->isSelf = ($this->userRecord['id'] == $this->getUser()->getUserID());

            // Get API keys
            $this->restApiKey = \AhgUserManage\Services\UserCrudService::getApiKey($this->userRecord['id'], 'RestApiKey');
            $this->oaiApiKey = \AhgUserManage\Services\UserCrudService::getApiKey($this->userRecord['id'], 'OaiApiKey');

            // Get current translate languages
            $this->translateLanguages = \AhgUserManage\Services\UserCrudService::getTranslateLanguages($this->userRecord['id']);
        } else {
            $this->userRecord = [
                'id' => null,
                'slug' => null,
                'username' => '',
                'email' => '',
                'active' => true,
                'groups' => [],
                'serialNumber' => 0,
            ];

            $this->isSelf = false;
            $this->restApiKey = null;
            $this->oaiApiKey = null;
            $this->translateLanguages = [];
            $this->response->setTitle($this->context->i18n->__('Add new user') . ' - ' . $this->response->getTitle());
        }

        // Handle POST
        if ($request->isMethod('post')) {
            $this->errors = [];
            $username = trim($request->getParameter('acct_name', ''));
            $email = trim($request->getParameter('acct_email', ''));
            $password = $request->getParameter('new_pw', '');
            $confirmPassword = $request->getParameter('confirm_pw', '');
            $active = $request->getParameter('active', '1');
            $groups = $request->getParameter('groups', []);

            // Validate
            if (empty($username)) {
                $this->errors[] = __('Username is required.');
            }
            if (empty($email)) {
                $this->errors[] = __('Email is required.');
            }
            if ($this->isNew && empty($password)) {
                $this->errors[] = __('Password is required for new users.');
            }
            if (!empty($password) && $password !== $confirmPassword) {
                $this->errors[] = __('Password confirmation does not match.');
            }

            // Check uniqueness (cast to int to prevent type issues)
            $excludeId = $this->isNew ? null : (int) $this->userRecord['id'];
            if (!empty($username) && \AhgUserManage\Services\UserCrudService::usernameExists($username, $excludeId)) {
                $this->errors[] = __('This username is already in use.');
            }
            if (!empty($email) && \AhgUserManage\Services\UserCrudService::emailExists($email, $excludeId)) {
                $this->errors[] = __('This email address is already in use.');
            }

            if (empty($this->errors)) {
                $data = [
                    'username' => $username,
                    'email' => $email,
                    'active' => (int) $active,
                    'groups' => is_array($groups) ? $groups : [],
                ];
                if (!empty($password)) {
                    $data['password'] = $password;
                }

                if ($this->isNew) {
                    $newId = \AhgUserManage\Services\UserCrudService::create($data);

                    // Save translate languages for new user
                    $translateLangs = $request->getParameter('translate', []);
                    if (!empty($translateLangs) && is_array($translateLangs)) {
                        \AhgUserManage\Services\UserCrudService::saveTranslateLanguages($newId, $translateLangs);
                    }

                    $newSlug = \AhgCore\Services\ObjectService::getSlug($newId);
                    $this->redirect('@user_view_override?slug=' . $newSlug);
                } else {
                    $userId = (int) $this->userRecord['id'];
                    \AhgUserManage\Services\UserCrudService::update($userId, $data);

                    // Handle API key actions
                    $restKeyAction = $request->getParameter('restApiKey', '');
                    if ('generate' === $restKeyAction) {
                        \AhgUserManage\Services\UserCrudService::generateApiKey($userId, 'RestApiKey');
                    } elseif ('delete' === $restKeyAction) {
                        \AhgUserManage\Services\UserCrudService::deleteApiKey($userId, 'RestApiKey');
                    }

                    $oaiKeyAction = $request->getParameter('oaiApiKey', '');
                    if ('generate' === $oaiKeyAction) {
                        \AhgUserManage\Services\UserCrudService::generateApiKey($userId, 'OaiApiKey');
                    } elseif ('delete' === $oaiKeyAction) {
                        \AhgUserManage\Services\UserCrudService::deleteApiKey($userId, 'OaiApiKey');
                    }

                    // Save translate languages
                    $translateLangs = $request->getParameter('translate', []);
                    \AhgUserManage\Services\UserCrudService::saveTranslateLanguages(
                        $userId,
                        is_array($translateLangs) ? $translateLangs : []
                    );

                    $this->redirect('@user_view_override?slug=' . $this->userRecord['slug']);
                }
            }

            // If errors, update the userRecord with submitted values for re-display
            $this->userRecord['username'] = $username;
            $this->userRecord['email'] = $email;
            $this->userRecord['active'] = (bool) $active;
        }
    }

    /**
     * Delete a user.
     */
    public function executeDelete($request)
    {
        // Admin-only
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');

            return;
        }

        $this->form = new sfForm();
        $slug = $request->getParameter('slug');

        $this->userRecord = \AhgUserManage\Services\UserCrudService::getBySlug($slug);
        if (!$this->userRecord) {
            $this->forward404();
        }

        // Cannot delete yourself
        if ($this->userRecord['id'] == $this->getUser()->getUserID()) {
            $this->getUser()->setFlash('error', $this->context->i18n->__('You cannot delete your own account.'));
            $this->redirect('@user_view_override?slug=' . $slug);
        }

        if ($request->isMethod('delete')) {
            $this->form->bind($request->getPostParameters());
            if ($this->form->isValid()) {
                \AhgUserManage\Services\UserCrudService::delete($this->userRecord['id']);
                $this->redirect('@user_list_override');
            }
        }
    }
}
