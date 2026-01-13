<?php

/**
 * General Feedback (not linked to a specific record).
 *
 * @author Johan Pieterse <johan@plainsailingisystems.co.za>
 */
class ahgFeedbackGeneralAction extends sfAction
{
    public static $NAMES = [
        'feed_name',
        'feed_surname',
        'feed_phone',
        'feed_email',
        'feed_relationship',
        'feed_type_id',
        'remarks',
        'subject',
    ];

    public function execute($request)
    {
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        // Add form fields
        foreach (self::$NAMES as $name) {
            $this->addField($name);
        }

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->processForm();

                $this->getUser()->setFlash('notice', $this->context->i18n->__('Thank you for your feedback.'));
                $this->redirect('@homepage');
            }
        }
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'subject':
                $this->form->setValidator('subject', new sfValidatorString(['required' => true]));
                $this->form->setWidget('subject', new sfWidgetFormInput());
                break;

            case 'feed_name':
                $this->form->setValidator('feed_name', new sfValidatorString(['required' => true]));
                $this->form->setWidget('feed_name', new sfWidgetFormInput());
                break;

            case 'feed_surname':
                $this->form->setValidator('feed_surname', new sfValidatorString(['required' => true]));
                $this->form->setWidget('feed_surname', new sfWidgetFormInput());
                break;

            case 'feed_phone':
                $this->form->setValidator('feed_phone', new sfValidatorString(['required' => false]));
                $this->form->setWidget('feed_phone', new sfWidgetFormInput());
                break;

            case 'feed_email':
                $this->form->setValidator('feed_email', new sfValidatorEmail(['required' => true]));
                $this->form->setWidget('feed_email', new sfWidgetFormInput());
                break;

            case 'feed_relationship':
                $this->form->setValidator('feed_relationship', new sfValidatorString(['required' => false]));
                $this->form->setWidget('feed_relationship', new sfWidgetFormTextarea());
                break;

            case 'feed_type_id':
                $this->form->setValidator('feed_type_id', new sfValidatorInteger(['required' => true]));
                $this->form->setWidget('feed_type_id', new sfWidgetFormSelect([
                    'choices' => [
                        0 => $this->context->i18n->__('General Feedback'),
                        1 => $this->context->i18n->__('Error Report'),
                        2 => $this->context->i18n->__('Suggestion'),
                        3 => $this->context->i18n->__('Correction Request'),
                        4 => $this->context->i18n->__('Need Assistance'),
                    ],
                ]));
                break;

            case 'remarks':
                $this->form->setValidator('remarks', new sfValidatorString(['required' => true]));
                $this->form->setWidget('remarks', new sfWidgetFormTextarea());
                break;
        }
    }

    protected function processForm()
    {
        $culture = sfContext::getInstance()->user->getCulture();
        
        // Create new feedback record
        $feedback = new QubitFeedback();
        
        // Main table fields
        $feedback->feedName = $this->form->getValue('feed_name');
        $feedback->feedSurname = $this->form->getValue('feed_surname');
        $feedback->feedPhone = $this->form->getValue('feed_phone');
        $feedback->feedEmail = $this->form->getValue('feed_email');
        $feedback->feedRelationship = $this->form->getValue('feed_relationship');
        $feedback->feedTypeId = $this->form->getValue('feed_type_id');
        $feedback->sourceCulture = $culture;
        
        // i18n fields - these go to feedback_i18n
        $feedback->name = $this->form->getValue('subject');
        $feedback->remarks = $this->form->getValue('remarks');
        $feedback->objectId = null;
        $feedback->statusId = QubitTerm::PENDING_ID;
        $feedback->createdAt = date('Y-m-d H:i:s');

        $feedback->save();

        $this->feedback = $feedback;
    }
}
