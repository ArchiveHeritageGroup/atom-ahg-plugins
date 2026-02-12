<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * General Feedback (not linked to a specific record).
 *
 * @author Johan Pieterse <johan@plainsailingisystems.co.za>
 */
class feedbackGeneralAction extends AhgController
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
        // Initialize Laravel
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        $culture = $this->getUser()->getCulture();
        $now = date('Y-m-d H:i:s');

        // AtoM uses class_name in object table to identify type
        $className = 'QubitFeedback';

        // Insert into object table first (base table for all AtoM entities)
        $objectId = DB::table('object')->insertGetId([
            'class_name' => $className,
            'created_at' => $now,
            'updated_at' => $now,
            'serial_number' => 0,
        ]);

        // Get nested set values
        $maxRgt = DB::table('feedback')->max('rgt') ?? 0;

        // Insert into feedback table
        DB::table('feedback')->insert([
            'id' => $objectId,
            'feed_name' => $this->form->getValue('feed_name'),
            'feed_surname' => $this->form->getValue('feed_surname'),
            'feed_phone' => $this->form->getValue('feed_phone'),
            'feed_email' => $this->form->getValue('feed_email'),
            'feed_relationship' => $this->form->getValue('feed_relationship'),
            'feed_type_id' => $this->form->getValue('feed_type_id'),
            'parent_id' => null,
            'lft' => $maxRgt + 1,
            'rgt' => $maxRgt + 2,
            'source_culture' => $culture,
        ]);

        // Insert into feedback_i18n table
        DB::table('feedback_i18n')->insert([
            'id' => $objectId,
            'culture' => $culture,
            'name' => $this->form->getValue('subject'),
            'remarks' => $this->form->getValue('remarks'),
            'object_id' => null,
            'status' => 'pending',
            'created_at' => $now,
            'completed_at' => null,
        ]);

        $this->feedbackId = $objectId;
    }
}
