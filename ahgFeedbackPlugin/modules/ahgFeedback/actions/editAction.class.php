<?php

/**
 * Feedback Edit action.
 *
 * @author Johan Pieterse <johan@plainsailingisystems.co.za>
 */
class ahgFeedbackEditAction extends DefaultEditAction
{
    public static $NAMES = [
        'feed_name',
        'feed_surname',
        'feed_phone',
        'feed_email',
        'feed_relationship',
        'feed_type_id',
        'remarks',
        'name',
        'status_id',
        'admin_notes',
    ];

    public function execute($request)
    {
        // Get feedback by ID
        $this->resource = QubitFeedback::getById($request->getParameter('id'));
        
        if (!isset($this->resource)) {
            $this->forward404();
        }

        if (!$this->getUser()->isAuthenticated()) {
            QubitAcl::forwardUnauthorized();
        }

        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        // Add fields
        foreach (self::$NAMES as $name) {
            $this->addField($name);
        }

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->processForm();
                $this->resource->save();

                $this->getUser()->setFlash('notice', $this->context->i18n->__('Feedback updated.'));
                $this->redirect(['module' => 'ahgFeedback', 'action' => 'browse']);
            }
        }

        // Get linked information object if exists
        $this->informationObject = null;
        if ($this->resource->objectId) {
            $this->informationObject = QubitInformationObject::getById($this->resource->objectId);
        }
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'feed_name':
                $this->form->setDefault('feed_name', $this->resource->feedName);
                $this->form->setValidator('feed_name', new sfValidatorString(['required' => false]));
                $this->form->setWidget('feed_name', new sfWidgetFormInput());
                break;

            case 'feed_surname':
                $this->form->setDefault('feed_surname', $this->resource->feedSurname);
                $this->form->setValidator('feed_surname', new sfValidatorString(['required' => false]));
                $this->form->setWidget('feed_surname', new sfWidgetFormInput());
                break;

            case 'feed_phone':
                $this->form->setDefault('feed_phone', $this->resource->feedPhone);
                $this->form->setValidator('feed_phone', new sfValidatorString(['required' => false]));
                $this->form->setWidget('feed_phone', new sfWidgetFormInput());
                break;

            case 'feed_email':
                $this->form->setDefault('feed_email', $this->resource->feedEmail);
                $this->form->setValidator('feed_email', new sfValidatorEmail(['required' => false]));
                $this->form->setWidget('feed_email', new sfWidgetFormInput());
                break;

            case 'feed_relationship':
                $this->form->setDefault('feed_relationship', $this->resource->feedRelationship);
                $this->form->setValidator('feed_relationship', new sfValidatorString(['required' => false]));
                $this->form->setWidget('feed_relationship', new sfWidgetFormTextarea());
                break;

            case 'feed_type_id':
                $this->form->setDefault('feed_type_id', $this->resource->feedTypeId);
                $this->form->setValidator('feed_type_id', new sfValidatorInteger(['required' => false]));
                $this->form->setWidget('feed_type_id', new sfWidgetFormSelect([
                    'choices' => [
                        0 => $this->context->i18n->__('General'),
                        1 => $this->context->i18n->__('Error Report'),
                        2 => $this->context->i18n->__('Suggestion'),
                        3 => $this->context->i18n->__('Correction'),
                        4 => $this->context->i18n->__('Need Assistance'),
                    ],
                ]));
                break;

            case 'remarks':
                $this->form->setDefault('remarks', $this->resource->remarks);
                $this->form->setValidator('remarks', new sfValidatorString(['required' => false]));
                $this->form->setWidget('remarks', new sfWidgetFormTextarea());
                break;

            case 'name':
                $this->form->setDefault('name', $this->resource->name);
                $this->form->setValidator('name', new sfValidatorString(['required' => false]));
                $this->form->setWidget('name', new sfWidgetFormInput());
                break;

            case 'status_id':
                $this->form->setDefault('status_id', $this->resource->statusId);
                $this->form->setValidator('status_id', new sfValidatorInteger(['required' => true]));
                $this->form->setWidget('status_id', new sfWidgetFormSelect([
                    'choices' => [
                        QubitTerm::PENDING_ID => $this->context->i18n->__('Pending'),
                        QubitTerm::COMPLETED_ID => $this->context->i18n->__('Completed'),
                    ],
                ]));
                break;

            case 'admin_notes':
                $this->form->setDefault('admin_notes', $this->resource->uniqueIdentifier);
                $this->form->setValidator('admin_notes', new sfValidatorString(['required' => false]));
                $this->form->setWidget('admin_notes', new sfWidgetFormTextarea());
                break;
        }
    }

    protected function processForm()
    {
        $this->resource->feedName = $this->form->getValue('feed_name');
        $this->resource->feedSurname = $this->form->getValue('feed_surname');
        $this->resource->feedPhone = $this->form->getValue('feed_phone');
        $this->resource->feedEmail = $this->form->getValue('feed_email');
        $this->resource->feedRelationship = $this->form->getValue('feed_relationship');
        $this->resource->feedTypeId = $this->form->getValue('feed_type_id');
        $this->resource->remarks = $this->form->getValue('remarks');
        $this->resource->name = $this->form->getValue('name');
        $this->resource->uniqueIdentifier = $this->form->getValue('admin_notes');
        
        // Handle status change
        $newStatus = $this->form->getValue('status_id');
        if ($newStatus != $this->resource->statusId) {
            $this->resource->statusId = $newStatus;
            if (QubitTerm::COMPLETED_ID == $newStatus && !$this->resource->completedAt) {
                $this->resource->completedAt = date('Y-m-d H:i:s');
            }
        }
    }
}
