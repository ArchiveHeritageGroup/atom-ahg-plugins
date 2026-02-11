<?php

use AtomFramework\Http\Controllers\AhgController;
class DonorIndexAction extends AhgController
{
    public function execute($request)
    {
        // DEBUG: Verify this action is loading from the plugin
        error_log('DonorIndexAction loaded from ahgDonorManagePlugin');

        // Bootstrap Laravel QB
        if (!class_exists('Illuminate\Database\Capsule\Manager')) {
            require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        }

        $culture = $this->culture();

        // Resolve slug → donor data
        $slug = $request->getParameter('slug');
        $this->donor = \AhgDonorManage\Services\DonorCrudService::getBySlug($slug, $culture);

        if (!$this->donor) {
            $this->forward404();
        }

        // ACL check — donors require authenticated editor/admin
        $user = $this->getUser();
        $isAdmin = $user->isAuthenticated() && ($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID));

        // For read access, allow all authenticated users (donors are not public)
        if (!$user->isAuthenticated()) {
            QubitAcl::forwardUnauthorized();
        }

        $title = $this->donor['authorizedFormOfName'] ?: $this->context->i18n->__('Untitled');
        $this->getResponse()->setTitle("{$title} - {$this->getResponse()->getTitle()}");

        // Permission flags for template
        $this->canEdit = $isAdmin;
        $this->canDelete = $isAdmin;
        $this->canCreate = $isAdmin;

        // Validation check for edit permission holders
        if ($this->canEdit) {
            $this->errorSchema = null;
            if (empty($this->donor['authorizedFormOfName'])) {
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
    }
}
