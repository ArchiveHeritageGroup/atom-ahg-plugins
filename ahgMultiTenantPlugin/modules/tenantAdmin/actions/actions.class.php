<?php

use AtomFramework\Http\Controllers\AhgController;
use AhgMultiTenant\Models\Tenant;
use AhgMultiTenant\Models\TenantUser;
use AhgMultiTenant\Services\TenantService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * tenantAdmin module actions
 *
 * Admin interface for managing tenants and super users.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class tenantAdminActions extends AhgController
{
    /**
     * Pre-execute check for admin access
     */
    public function boot(): void
    {
        $this->loadServices();

        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('user', 'login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        if (!\AhgMultiTenant\Services\TenantContext::isAdmin($userId)) {
            $this->getUser()->setFlash('error', 'Access denied. Administrator privileges required.');
            $this->redirect('@homepage');
        }
    }

    /**
     * Load required services
     */
    private function loadServices(): void
    {
        // Load framework bootstrap
        $frameworkPath = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }

        $pluginDir = $this->config('sf_plugins_dir') . '/ahgMultiTenantPlugin/lib';
        require_once $pluginDir . '/Services/TenantContext.php';
        require_once $pluginDir . '/Services/TenantAccess.php';
        require_once $pluginDir . '/Services/TenantBranding.php';
        require_once $pluginDir . '/Services/TenantService.php';
        require_once $pluginDir . '/Models/Tenant.php';
        require_once $pluginDir . '/Models/TenantUser.php';
    }

    /**
     * List all tenants and repositories
     */
    public function executeIndex($request)
    {
        // Get filter parameters
        $this->statusFilter = $request->getParameter('status', '');
        $this->searchFilter = $request->getParameter('search', '');

        // Get tenant statistics
        $this->statistics = TenantService::getStatistics();

        // Get tenants from new table
        $filters = [];
        if ($this->statusFilter) {
            $filters['status'] = $this->statusFilter;
        }
        if ($this->searchFilter) {
            $filters['search'] = $this->searchFilter;
        }
        $this->tenants = Tenant::all($filters);

        // Add user counts to tenants
        foreach ($this->tenants as $tenant) {
            $tenant->userCount = $tenant->getUserCount();
        }

        // Also get legacy repository data for backward compatibility
        $this->repositories = DB::table('repository as r')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'r.id')
            ->select('r.id', 'r.identifier', 'ai.authorized_form_of_name as name', 's.slug')
            ->orderBy('ai.authorized_form_of_name')
            ->get()
            ->toArray();

        // Add super user count and user count for each repo
        foreach ($this->repositories as &$repo) {
            $repo->super_user_count = count(\AhgMultiTenant\Services\TenantContext::getRepositorySuperUserIds($repo->id));
            $repo->user_count = count(\AhgMultiTenant\Services\TenantContext::getRepositoryUserIds($repo->id));
            $repo->has_branding = !empty(\AhgMultiTenant\Services\TenantBranding::getPrimaryColor($repo->id));
            // Check if tenant exists for this repository
            $repo->tenant = Tenant::findByRepository($repo->id);
        }
    }

    /**
     * Create new tenant form
     */
    public function executeCreate($request)
    {
        $this->repositories = DB::table('repository as r')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->leftJoin('heritage_tenant as t', 't.repository_id', '=', 'r.id')
            ->whereNull('t.id') // Only repositories without tenants
            ->select('r.id', 'r.identifier', 'ai.authorized_form_of_name as name')
            ->orderBy('ai.authorized_form_of_name')
            ->get()
            ->toArray();

        $this->statuses = Tenant::VALID_STATUSES;
        $this->roles = TenantUser::getRolesWithLabels();
    }

    /**
     * Store new tenant
     */
    public function executeStore($request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $currentUserId = $this->getUser()->getAttribute('user_id');

        $data = [
            'name' => $request->getParameter('name'),
            'code' => $request->getParameter('code') ?: null,
            'domain' => $request->getParameter('domain') ?: null,
            'subdomain' => $request->getParameter('subdomain') ?: null,
            'status' => $request->getParameter('status', Tenant::STATUS_TRIAL),
            'repository_id' => $request->getParameter('repository_id') ?: null,
            'contact_name' => $request->getParameter('contact_name') ?: null,
            'contact_email' => $request->getParameter('contact_email') ?: null,
            'trial_days' => (int) $request->getParameter('trial_days', 14),
        ];

        $result = TenantService::create($data, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);

            // If owner user specified, assign them
            $ownerUserId = $request->getParameter('owner_user_id');
            if ($ownerUserId && $result['tenant']) {
                TenantService::assignUser(
                    $result['tenant']->id,
                    (int) $ownerUserId,
                    TenantUser::ROLE_OWNER,
                    $currentUserId
                );
            }

            $this->redirect('tenant_admin');
        } else {
            $this->getUser()->setFlash('error', $result['message']);
            $this->redirect('tenant_admin_create');
        }
    }

    /**
     * Edit tenant form
     */
    public function executeEditTenant($request)
    {
        $tenantId = (int) $request->getParameter('id');
        $this->tenant = Tenant::find($tenantId);

        if (!$this->tenant) {
            $this->forward404('Tenant not found.');
        }

        $this->users = $this->tenant->getUsers();
        $this->availableUsers = TenantUser::getAvailableUsers($tenantId);
        $this->statuses = Tenant::VALID_STATUSES;
        $this->roles = TenantUser::getRolesWithLabels();

        // Get repositories not assigned to other tenants
        $this->repositories = DB::table('repository as r')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->leftJoin('heritage_tenant as t', function ($join) use ($tenantId) {
                $join->on('t.repository_id', '=', 'r.id')
                    ->where('t.id', '!=', $tenantId);
            })
            ->whereNull('t.id')
            ->select('r.id', 'r.identifier', 'ai.authorized_form_of_name as name')
            ->orderBy('ai.authorized_form_of_name')
            ->get()
            ->toArray();
    }

    /**
     * Update tenant
     */
    public function executeUpdateTenant($request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $tenantId = (int) $request->getParameter('id');
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $data = [
            'name' => $request->getParameter('name'),
            'code' => $request->getParameter('code') ?: null,
            'domain' => $request->getParameter('domain') ?: null,
            'subdomain' => $request->getParameter('subdomain') ?: null,
            'repository_id' => $request->getParameter('repository_id') ?: null,
            'contact_name' => $request->getParameter('contact_name') ?: null,
            'contact_email' => $request->getParameter('contact_email') ?: null,
        ];

        $result = TenantService::update($tenantId, $data, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_admin_edit_tenant', ['id' => $tenantId]);
    }

    /**
     * Activate tenant
     */
    public function executeActivate($request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $tenantId = (int) $request->getParameter('id');
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $result = TenantService::activate($tenantId, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_admin');
    }

    /**
     * Suspend tenant
     */
    public function executeSuspend($request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $tenantId = (int) $request->getParameter('id');
        $reason = $request->getParameter('reason');
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $result = TenantService::suspend($tenantId, $reason, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_admin');
    }

    /**
     * Extend trial
     */
    public function executeExtendTrial($request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $tenantId = (int) $request->getParameter('id');
        $days = (int) $request->getParameter('days', 14);
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $result = TenantService::extendTrial($tenantId, $days, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_admin');
    }

    /**
     * Delete tenant
     */
    public function executeDelete($request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $tenantId = (int) $request->getParameter('id');
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $result = TenantService::delete($tenantId, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_admin');
    }

    /**
     * Assign user to tenant
     */
    public function executeAssignTenantUser($request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $tenantId = (int) $request->getParameter('tenant_id');
        $userId = (int) $request->getParameter('user_id');
        $role = $request->getParameter('role', TenantUser::ROLE_VIEWER);
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $result = TenantService::assignUser($tenantId, $userId, $role, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_admin_edit_tenant', ['id' => $tenantId]);
    }

    /**
     * Remove user from tenant
     */
    public function executeRemoveTenantUser($request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $tenantId = (int) $request->getParameter('tenant_id');
        $userId = (int) $request->getParameter('user_id');
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $result = TenantService::removeUser($tenantId, $userId, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_admin_edit_tenant', ['id' => $tenantId]);
    }

    /**
     * Update user role in tenant
     */
    public function executeUpdateTenantUserRole($request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $tenantId = (int) $request->getParameter('tenant_id');
        $userId = (int) $request->getParameter('user_id');
        $role = $request->getParameter('role');
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $result = TenantService::updateUserRole($tenantId, $userId, $role, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_admin_edit_tenant', ['id' => $tenantId]);
    }

    /**
     * Edit tenant settings
     */
    public function executeEdit($request)
    {
        $repoId = (int) $request->getParameter('id');

        $this->repository = DB::table('repository as r')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'r.id')
            ->where('r.id', $repoId)
            ->select('r.id', 'r.identifier', 'ai.authorized_form_of_name as name', 's.slug')
            ->first();

        if (!$this->repository) {
            $this->forward404('Repository not found.');
        }

        $this->superUsers = \AhgMultiTenant\Services\TenantAccess::getRepositoryUsers($repoId)['super_users'];
        $this->users = \AhgMultiTenant\Services\TenantAccess::getRepositoryUsers($repoId)['users'];
        $this->branding = \AhgMultiTenant\Services\TenantBranding::getBranding($repoId);
    }

    /**
     * Manage super users for a repository
     */
    public function executeSuperUsers($request)
    {
        $repoId = (int) $request->getParameter('id');

        $this->repository = DB::table('repository as r')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->where('r.id', $repoId)
            ->select('r.id', 'r.identifier', 'ai.authorized_form_of_name as name')
            ->first();

        if (!$this->repository) {
            $this->forward404('Repository not found.');
        }

        $this->superUsers = \AhgMultiTenant\Services\TenantAccess::getRepositoryUsers($repoId)['super_users'];
        $this->availableUsers = \AhgMultiTenant\Services\TenantAccess::getAvailableUsers($repoId);
    }

    /**
     * Assign super user to repository
     */
    public function executeAssignSuperUser($request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $repoId = (int) $request->getParameter('repository_id');
        $userId = (int) $request->getParameter('user_id');
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $result = \AhgMultiTenant\Services\TenantAccess::assignSuperUser($userId, $repoId, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_admin_super_users', ['id' => $repoId]);
    }

    /**
     * Remove super user from repository
     */
    public function executeRemoveSuperUser($request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $repoId = (int) $request->getParameter('repository_id');
        $userId = (int) $request->getParameter('user_id');
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $result = \AhgMultiTenant\Services\TenantAccess::removeSuperUser($userId, $repoId, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_admin_super_users', ['id' => $repoId]);
    }
}
