<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * tenantAdmin module actions
 *
 * Admin interface for managing tenants and super users.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class tenantAdminActions extends sfActions
{
    /**
     * Pre-execute check for admin access
     */
    public function preExecute()
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
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }

        require_once sfConfig::get('sf_plugins_dir') . '/ahgMultiTenantPlugin/lib/Services/TenantContext.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgMultiTenantPlugin/lib/Services/TenantAccess.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgMultiTenantPlugin/lib/Services/TenantBranding.php';
    }

    /**
     * List all repositories with tenant info
     */
    public function executeIndex(sfWebRequest $request)
    {
        // Repository extends Actor, so name is in actor_i18n
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
        }
    }

    /**
     * Edit tenant settings
     */
    public function executeEdit(sfWebRequest $request)
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
    public function executeSuperUsers(sfWebRequest $request)
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
    public function executeAssignSuperUser(sfWebRequest $request)
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
    public function executeRemoveSuperUser(sfWebRequest $request)
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
