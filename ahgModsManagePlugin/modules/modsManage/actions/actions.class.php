<?php

class modsManageActions extends sfActions
{
    public function preExecute()
    {
        parent::preExecute();
        sfContext::getInstance()->getConfiguration()->loadHelpers(['I18N', 'Url', 'Qubit', 'Text', 'Date']);

        if (!class_exists('Illuminate\Database\Capsule\Manager')) {
            $frameworkBoot = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($frameworkBoot)) {
                require_once $frameworkBoot;
            }
        }
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
