<?php

use AtomFramework\Http\Controllers\AhgController;
class RightsHolderIndexAction extends AhgController
{
    public function execute($request)
    {
        $this->resource = $this->getRoute()->resource;

        // Guard the class: the catch-all route /:slug/:module/:action resolves any
        // object's slug with no class check, and the members read below exist only on
        // QubitRightsHolder. Any other class reaches BaseObject::__get / __isset and throws.
        // See CH-000066 and
        // docs/sessions/2026-09-18-informationobject-reports-slug-class-guard.md
        if (!$this->resource instanceof QubitRightsHolder) {
            $this->forward404();
        }

        // Check user authorization
        if (!\AtomExtensions\Services\AclService::check($this->resource, 'read')) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }

        if (1 > strlen($title = $this->resource->__toString())) {
            $title = $this->context->i18n->__('Untitled');
        }

        $this->getResponse()->setTitle("{$title} - {$this->getResponse()->getTitle()}");

        if (\AtomExtensions\Services\AclService::check($this->resource, 'update')) {
            $validatorSchema = new sfValidatorSchema();
            $values = [];

            $validatorSchema->authorizedFormOfName = new sfValidatorString(
                ['required' => true],
                ['required' => $this->context->i18n->__('Authorized form of name - This is a mandatory element.')]
            );
            $values['authorizedFormOfName'] = $this->resource->getAuthorizedFormOfName(['cultureFallback' => true]);

            try {
                $validatorSchema->clean($values);
            } catch (sfValidatorErrorSchema $e) {
                $this->errorSchema = $e;
            }
        }
    }
}
