<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * tenantBranding module actions
 *
 * Branding management interface for super users and admins.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class tenantBrandingActions extends sfActions
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
        require_once sfConfig::get('sf_plugins_dir') . '/ahgMultiTenantPlugin/lib/Services/TenantBranding.php';
    }

    /**
     * Check user can manage branding for repository
     */
    private function checkAccess(int $repositoryId): void
    {
        $userId = $this->getUser()->getAttribute('user_id');
        if (!\AhgMultiTenant\Services\TenantAccess::canManageBranding($userId, $repositoryId)) {
            $this->getUser()->setFlash('error', 'You do not have permission to manage branding for this repository.');
            $this->redirect('@homepage');
        }
    }

    /**
     * Branding settings form
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

        $this->branding = \AhgMultiTenant\Services\TenantBranding::getBranding($repoId);
        $this->isAdmin = \AhgMultiTenant\Services\TenantContext::isAdmin($this->getUser()->getAttribute('user_id'));
    }

    /**
     * Save branding settings
     */
    public function executeSave(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $repoId = (int) $request->getParameter('repository_id');
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $this->checkAccess($repoId);

        $settings = [
            'primary_color' => $request->getParameter('primary_color'),
            'secondary_color' => $request->getParameter('secondary_color'),
            'header_bg_color' => $request->getParameter('header_bg_color'),
            'header_text_color' => $request->getParameter('header_text_color'),
            'link_color' => $request->getParameter('link_color'),
            'button_color' => $request->getParameter('button_color'),
            'custom_css' => $request->getParameter('custom_css'),
        ];

        $result = \AhgMultiTenant\Services\TenantBranding::saveBranding($repoId, $settings, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_branding', ['id' => $repoId]);
    }

    /**
     * Upload logo
     */
    public function executeUploadLogo(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $repoId = (int) $request->getParameter('repository_id');
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $this->checkAccess($repoId);

        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->getUser()->setFlash('error', 'No file uploaded.');
            $this->redirect('tenant_branding', ['id' => $repoId]);
        }

        $result = \AhgMultiTenant\Services\TenantBranding::saveLogo($repoId, $_FILES['logo'], $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_branding', ['id' => $repoId]);
    }

    /**
     * Delete logo
     */
    public function executeDeleteLogo(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('POST'));

        $repoId = (int) $request->getParameter('repository_id');
        $currentUserId = $this->getUser()->getAttribute('user_id');

        $this->checkAccess($repoId);

        $result = \AhgMultiTenant\Services\TenantBranding::deleteLogo($repoId, $currentUserId);

        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('tenant_branding', ['id' => $repoId]);
    }
}
