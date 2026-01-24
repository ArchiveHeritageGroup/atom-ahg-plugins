<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * tenantUsers module actions
 *
 * User management interface for super users and admins.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class tenantUsersActions extends sfActions
{
    /**
     * Pre-execute check for access
     */
    public function preExecute()
    {
        $this->loadServices();

        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('user', 'login');
        }
    }

    /**
     * Load required services
     */
    private function loadServices(): void
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }

        require_once sfConfig::get('sf_plugins_dir') . '/ahgMultiTenantPlugin/lib/Services/TenantContext.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgMultiTenantPlugin/lib/Services/TenantAccess.php';
    }

    /**
     * Check user can manage users for repository
     */
    private function checkAccess(int $repositoryId): void
    {
        $userId = $this->getUser()->getAttribute('user_id');
        if (!\AhgMultiTenant\Services\TenantAccess::canAssignUsers($userId, $repositoryId)) {
            $this->getUser()->setFlash('error', 'You do not have permission to manage users for this repository.');
            $this->redirect('@homepage');
        }
    }

    /**
     * List users for a repository
     */
    public function executeIndex(sfWebRequest $request)
    {
        $repoId = (int) $request->getParameter('id');
        $this->checkAccess($repoId);

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

        $users = \AhgMultiTenant\Services\TenantAccess::getRepositoryUsers($repoId);
        $this->superUsers = $users['super_users'];
        $this->users = $users['users'];
        $this->availableUsers = \AhgMultiTenant\Services\TenantAccess::getAvailableUsers($repoId);
        $this->isAdmin = \AhgMultiTenant\Services\TenantContext::isAdmin($this->getUser()->getAttribute('user_id'));
    }

    /**
     * Assign user to repository
     */
    public function executeAssign(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $repoId = (int) $request->getParameter('repository_id');
        $userId = (int) $request->getParameter('user_id');
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $this->checkAccess($repoId);

        $result = \AhgMultiTenant\Services\TenantAccess::assignUserToRepository($userId, $repoId, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_users', ['id' => $repoId]);
    }

    /**
     * Remove user from repository
     */
    public function executeRemove(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $repoId = (int) $request->getParameter('repository_id');
        $userId = (int) $request->getParameter('user_id');
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $this->checkAccess($repoId);

        $result = \AhgMultiTenant\Services\TenantAccess::removeUserFromRepository($userId, $repoId, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_users', ['id' => $repoId]);
    }
}
