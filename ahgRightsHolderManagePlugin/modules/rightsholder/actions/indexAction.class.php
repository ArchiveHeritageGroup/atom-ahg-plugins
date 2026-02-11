<?php

use AtomFramework\Http\Controllers\AhgController;
class RightsHolderIndexAction extends AhgController
{
    public function execute($request)
    {
        $this->resource = $this->getRoute()->resource;

        // Check user authorization
        if (!QubitAcl::check($this->resource, 'read')) {
            QubitAcl::forwardUnauthorized();
        }

        if (1 > strlen($title = $this->resource->__toString())) {
            $title = $this->context->i18n->__('Untitled');
        }

        $this->getResponse()->setTitle("{$title} - {$this->getResponse()->getTitle()}");

        if (QubitAcl::check($this->resource, 'update')) {
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
