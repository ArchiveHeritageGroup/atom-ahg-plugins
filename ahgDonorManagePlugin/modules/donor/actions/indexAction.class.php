<?php

class DonorIndexAction extends sfAction
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

        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        if (QubitAcl::check($this->resource, 'update')) {
            $validatorSchema = new sfValidatorSchema();
            $values = [];

            $validatorSchema->authorizedFormOfName = new sfValidatorString(
                ['required' => true],
                ['required' => $this->context->i18n->__('Authorized form of name - This field is marked as mandatory in the relevant descriptive standard.').'"<span>*</span><span class="visually-hidden">'.$this->context->i18n->__('This field is marked as mandatory in the relevant descriptive standard.').'</span>']
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
