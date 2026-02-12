<?php

use AtomFramework\Http\Controllers\AhgController;
class dacsManageActions extends AhgController
{
    public function executeEdit($request)
    {
        $culture = $this->culture();
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $user = $this->getUser();
        if (!$user->isAuthenticated()
            || !($user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID) || $user->hasGroup(\AtomExtensions\Constants\AclConstants::EDITOR_ID))
        ) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }

        \IoFormHelper::loadIoData($this, $request, $culture);
        \IoFormHelper::loadDropdowns($this, $culture);

        if ($request->isMethod('post')) {
            \IoFormHelper::handlePost($this, $request, $culture);
        }
    }
}
