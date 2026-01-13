<?php

/**
 * View Feedback action.
 *
 * @author Johan Pieterse <johan@plainsailingisystems.co.za>
 */
class ahgFeedbackViewAction extends sfAction
{
    public function execute($request)
    {
        $this->resource = QubitFeedback::getById($request->getParameter('id'));

        if (!isset($this->resource)) {
            $this->forward404();
        }

        // Get linked information object if exists
        $this->informationObject = null;
        if ($this->resource->objectId) {
            $this->informationObject = QubitInformationObject::getById($this->resource->objectId);
        }

        // Feedback type labels
        $this->feedbackTypes = [
            0 => $this->context->i18n->__('General'),
            1 => $this->context->i18n->__('Error Report'),
            2 => $this->context->i18n->__('Suggestion'),
            3 => $this->context->i18n->__('Correction'),
            4 => $this->context->i18n->__('Need Assistance'),
        ];
    }
}
