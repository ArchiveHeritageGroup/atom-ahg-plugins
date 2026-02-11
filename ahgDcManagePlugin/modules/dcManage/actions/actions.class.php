<?php

use AtomFramework\Http\Controllers\AhgController;
class dcManageActions extends AhgController
{
    /**
     * Edit or create a Dublin Core information object.
     *
     * Called via forward() from ioManage when DC standard is detected.
     * Must reload all data since forward() creates a fresh action instance.
     */
    public function executeEdit($request)
    {
        $culture = $this->culture();
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        // ACL — require editor/admin
        $user = $this->getUser();
        if (!$user->isAuthenticated()
            || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))
        ) {
            QubitAcl::forwardUnauthorized();
        }

        // Load IO data (forward() doesn't pass action variables)
        \IoFormHelper::loadIoData($this, $request, $culture);

        // Load dropdowns
        \IoFormHelper::loadDropdowns($this, $culture);

        // Handle POST
        if ($request->isMethod('post')) {
            \IoFormHelper::handlePost($this, $request, $culture);
        }
    }
}
