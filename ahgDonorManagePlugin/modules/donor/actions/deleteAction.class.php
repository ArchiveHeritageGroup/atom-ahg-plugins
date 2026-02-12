<?php

use AtomFramework\Http\Controllers\AhgController;

class DonorDeleteAction extends AhgController
{
    public function execute($request)
    {
        // Bootstrap Laravel QB
        if (!class_exists('Illuminate\Database\Capsule\Manager')) {
            require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        }

        $this->form = new sfForm();
        $culture = $this->context->user->getCulture();

        // ACL check — donors require authenticated editor/admin
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
                // Delete using Laravel QB service
                \AhgDonorManage\Services\DonorCrudService::delete($this->donor['id']);

                // Remove from Elasticsearch
                try {
                    \AhgCore\Services\ElasticsearchService::deleteDocument('qubitactor', $this->donor['id']);
                } catch (\Exception $e) {
                    // ES deletion failure should not block the operation
                }

                $this->redirect(['module' => 'donor', 'action' => 'browse']);
            }
        }
    }
}
