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
        $culture = $this->culture();
        $slug = $request->getParameter('slug');

        $this->userRecord = \AhgUserManage\Services\UserCrudService::getBySlug($slug);
        if (!$this->userRecord) {
            $this->forward404();
        }

        // Admins may view any profile; other users may view only their own
        // (restores base AtoM user/index self-view behaviour).
        $this->isSelf = ($this->userRecord['id'] == $this->getUser()->getUserID());
        if (!$this->getUser()->isAdministrator() && !$this->isSelf) {
            $this->forward('admin', 'secure');

            return;
        }

        $title = $this->userRecord['username'] ?: $this->context->i18n->__('Untitled');
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        // Get API keys
        $this->restApiKey = \AhgUserManage\Services\UserCrudService::getApiKey($this->userRecord['id'], 'RestApiKey');
        $this->oaiApiKey = \AhgUserManage\Services\UserCrudService::getApiKey($this->userRecord['id'], 'OaiApiKey');

        // Get translate languages
        $this->translateLanguages = \AhgUserManage\Services\UserCrudService::getTranslateLanguages($this->userRecord['id']);

        // Get security clearance if framework service is available
        $this->clearance = null;
        if (class_exists('\\AtomExtensions\\Services\\SecurityClearanceService')) {
            try {
                $this->clearance = \AtomExtensions\Services\SecurityClearanceService::getUserClearance($this->userRecord['id']);
            } catch (\Exception $e) {
                // Service not fully installed
            }
        }
    }

    /**
     * Change a user's password. Admins may change any account's; other users
     * may change ONLY their own, and must confirm their current password.
     * (Restores the self-service password change base AtoM offered via
     * user/passwordEdit, which this plugin's /user/:slug override shadowed.)
     */
    public function executePassword($request)
    {
        $slug = $request->getParameter('slug');
        // No slug (e.g. the /user/passwordEdit menu link) → the current user's own account.
        if ($slug) {
            $this->userRecord = \AhgUserManage\Services\UserCrudService::getBySlug($slug);
        } else {
            $this->userRecord = \AhgUserManage\Services\UserCrudService::getById((int) $this->getUser()->getUserID());
        }
        if (!$this->userRecord) {
            $this->forward404();
        }

        $isAdmin = $this->getUser()->isAdministrator();
        $this->isSelf = ($this->userRecord['id'] == $this->getUser()->getUserID());
        if (!$isAdmin && !$this->isSelf) {
            $this->forward('admin', 'secure');

            return;
        }

        $title = $this->userRecord['username'] ?: $this->context->i18n->__('Untitled');
        $this->response->setTitle(__('Change password') . " - {$title} - " . $this->response->getTitle());
        $this->errors = [];

        if ($request->isMethod('post')) {
            $current = (string) $request->getParameter('current_pw', '');
            $new = (string) $request->getParameter('new_pw', '');
            $confirm = (string) $request->getParameter('confirm_pw', '');

            // Non-admins must confirm their current password.
            if (!$isAdmin && !\AhgUserManage\Services\UserCrudService::verifyPassword((int) $this->userRecord['id'], $current)) {
                $this->errors[] = __('Your current password is incorrect.');
            }
            if (strlen($new) < 8) {
                $this->errors[] = __('New password must be at least 8 characters long.');
            }
            if ($new !== $confirm) {
                $this->errors[] = __('Password confirmation does not match.');
            }

            if (empty($this->errors)) {
                // Password-only update — update() leaves groups/profile untouched.
                \AhgUserManage\Services\UserCrudService::update((int) $this->userRecord['id'], ['password' => $new]);
                $this->getUser()->setFlash('notice', __('Password updated successfully.'));
                $this->redirect('@user_view_override?slug=' . $this->userRecord['slug']);
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
                'authorizedFormOfName' => '',
                'entityTypeId' => null,
                'contact' => null,
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
                // Profile fields
                $authorizedFormOfName = trim($request->getParameter('authorized_form_of_name', ''));

                // Contact fields
                $contactFields = [
                    'telephone' => trim($request->getParameter('contact_telephone', '')),
                    'fax' => trim($request->getParameter('contact_fax', '')),
                    'street_address' => trim($request->getParameter('contact_street_address', '')),
                    'city' => trim($request->getParameter('contact_city', '')),
                    'region' => trim($request->getParameter('contact_region', '')),
                    'postal_code' => trim($request->getParameter('contact_postal_code', '')),
                    'country_code' => trim($request->getParameter('contact_country_code', '')),
                    'website' => trim($request->getParameter('contact_website', '')),
                    'note' => trim($request->getParameter('contact_note', '')),
                ];

                // Determine if any contact field has data
                $hasContactData = false;
                foreach ($contactFields as $v) {
                    if ('' !== $v) {
                        $hasContactData = true;

                        break;
                    }
                }

                $data = [
                    'username' => $username,
                    'email' => $email,
                    'active' => (int) $active,
                    'groups' => is_array($groups) ? $groups : [],
                    'authorizedFormOfName' => $authorizedFormOfName,
                    'entityTypeId' => \QubitTerm::PERSON_ID,
                    'contact' => $hasContactData ? $contactFields : null,
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
