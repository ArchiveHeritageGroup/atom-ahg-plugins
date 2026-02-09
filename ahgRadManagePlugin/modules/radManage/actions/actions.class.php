<?php

class radManageActions extends AhgActions
{
    public function preExecute()
    {
        parent::preExecute();
        sfContext::getInstance()->getConfiguration()->loadHelpers(['I18N', 'Url', 'Qubit', 'Text', 'Date']);
    }

    public function executeEdit(sfWebRequest $request)
    {
        $culture = $this->context->user->getCulture();
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $user = $this->context->user;
        if (!$user->isAuthenticated()
            || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))
        ) {
            QubitAcl::forwardUnauthorized();
        }

        \IoFormHelper::loadIoData($this, $request, $culture);
        \IoFormHelper::loadDropdowns($this, $culture);

        if ($request->isMethod('post')) {
            \IoFormHelper::handlePost($this, $request, $culture);
        }
    }
}
