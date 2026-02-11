<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * tenantSwitcher module actions
 *
 * Provides tenant switching functionality via navbar dropdown.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class tenantSwitcherActions extends AhgController
{
    /**
     * Switch to a specific repository/tenant
     */
    public function executeSwitch($request)
    {
        $this->checkUserAuthenticated();

        $repositoryId = (int) $request->getParameter('id');

        // Use TenantContext to switch
        require_once $this->config('sf_plugins_dir') . '/ahgMultiTenantPlugin/lib/Services/TenantContext.php';
        require_once $this->config('sf_plugins_dir') . '/ahgMultiTenantPlugin/lib/Services/TenantAccess.php';

        $result = \AhgMultiTenant\Services\TenantContext::setCurrentRepository($repositoryId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode([
                'success' => $result,
                'repository_id' => $repositoryId,
                'message' => $result ? 'Switched to repository' : 'Access denied'
            ]));
        }

        if ($result) {
            $this->getUser()->setFlash('notice', 'Switched to repository successfully.');
        } else {
            $this->getUser()->setFlash('error', 'You do not have access to this repository.');
        }

        // Redirect to referer or home
        $referer = $request->getReferer();
        $this->redirect($referer ?: '@homepage');
    }

    /**
     * Switch to "View All" mode (admin only)
     */
    public function executeSwitchAll($request)
    {
        $this->checkUserAuthenticated();

        require_once $this->config('sf_plugins_dir') . '/ahgMultiTenantPlugin/lib/Services/TenantContext.php';

        $result = \AhgMultiTenant\Services\TenantContext::setCurrentRepository(null);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode([
                'success' => $result,
                'repository_id' => null,
                'view_all' => true,
                'message' => $result ? 'Viewing all repositories' : 'Access denied - admin only'
            ]));
        }

        if ($result) {
            $this->getUser()->setFlash('notice', 'Now viewing all repositories.');
        } else {
            $this->getUser()->setFlash('error', 'Only administrators can view all repositories.');
        }

        $referer = $request->getReferer();
        $this->redirect($referer ?: '@homepage');
    }

    /**
     * Get tenant switcher data (AJAX)
     */
    public function executeGetSwitcher($request)
    {
        $this->checkUserAuthenticated();

        require_once $this->config('sf_plugins_dir') . '/ahgMultiTenantPlugin/lib/Services/TenantContext.php';

        $userId = $this->getUser()->getAttribute('user_id');
        $repositories = \AhgMultiTenant\Services\TenantContext::getUserRepositories($userId);
        $currentRepoId = \AhgMultiTenant\Services\TenantContext::getCurrentRepositoryId();
        $isAdmin = \AhgMultiTenant\Services\TenantContext::isAdmin($userId);
        $viewAllMode = \AhgMultiTenant\Services\TenantContext::isViewAllMode();

        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode([
            'repositories' => $repositories,
            'current_repository_id' => $currentRepoId,
            'is_admin' => $isAdmin,
            'view_all_mode' => $viewAllMode
        ]));
    }

    /**
     * Check user is authenticated
     */
    private function checkUserAuthenticated(): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('user', 'login');
        }
    }
}
