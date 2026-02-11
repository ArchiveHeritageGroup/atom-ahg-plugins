<?php

use AtomFramework\Http\Controllers\AhgController;
class AccessionIndexAction extends AhgController
{
    public function execute($request)
    {
        $this->resource = $this->getRoute()->resource;

        if (!isset($this->resource)) {
            $this->forward404();
        }

        // Check user authorization
        if (!QubitAcl::check($this->resource, 'read')) {
            QubitAcl::forwardToSecureAction();
        }

        if (1 > strlen($title = $this->resource->__toString())) {
            $title = $this->context->i18n->__('Untitled');
        }

        $this->getResponse()->setTitle("{$title} - {$this->getResponse()->getTitle()}");

        if (QubitAcl::check($this->resource, 'update')) {
            $validatorSchema = new sfValidatorSchema();
            $values = [];

            $validatorSchema->date = new sfValidatorString(
                ['required' => true],
                ['required' => $this->context->i18n->__('Acquisition date - This field is marked as mandatory in the relevant descriptive standard.').'"<span>*</span><span class="visually-hidden">'.$this->context->i18n->__('This field is marked as mandatory in the relevant descriptive standard.').'</span>']
            );
            $values['date'] = $this->resource->date;

            $validatorSchema->sourceOfAcquisition = new sfValidatorString(
                ['required' => true],
                ['required' => $this->context->i18n->__('Source of acquisition - This field is marked as mandatory in the relevant descriptive standard.').'"<span>*</span><span class="visually-hidden">'.$this->context->i18n->__('This field is marked as mandatory in the relevant descriptive standard.').'</span>']
            );
            $values['sourceOfAcquisition'] = $this->resource->getSourceOfAcquisition(['culltureFallback' => true]);

            // Only require location information if there are no linked physical objects
            $locationRequired = 0 == count($this->resource->getPhysicalObjects());
            $validatorSchema->locationInformation = new sfValidatorString(
                ['required' => $locationRequired],
                ['required' => $this->context->i18n->__('Location information - This field is marked as mandatory in the relevant descriptive standard.').'"<span>*</span><span class="visually-hidden">'.$this->context->i18n->__('This field is marked as mandatory in the relevant descriptive standard.').'</span>']
            );
            $values['locationInformation'] = $this->resource->getLocationInformation(['culltureFallback' => true]);

            try {
                $validatorSchema->clean($values);
            } catch (sfValidatorErrorSchema $e) {
                $this->errorSchema = $e;
            }
        }
    }
}
