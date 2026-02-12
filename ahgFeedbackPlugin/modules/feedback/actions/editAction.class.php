<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Edit Feedback action using Laravel Query Builder.
 *
 * @author Johan Pieterse <johan@plainsailingisystems.co.za>
 */
class feedbackEditAction extends AhgController
{
    public static $NAMES = [
        'name',
        'feed_name',
        'feed_surname',
        'feed_phone',
        'feed_email',
        'feed_relationship',
        'feed_type_id',
        'remarks',
        'status',
        'admin_notes',
    ];

    public function execute($request)
    {
        // Check authentication
        if (!$this->getUser()->isAuthenticated()) {
            QubitAcl::forwardUnauthorized();
        }

        // Initialize Laravel
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        $id = $request->getParameter('id');
        $culture = $this->getUser()->getCulture();

        // Load feedback from database
        $this->feedback = DB::table('feedback')
            ->join('feedback_i18n', 'feedback.id', '=', 'feedback_i18n.id')
            ->where('feedback.id', $id)
            ->where('feedback_i18n.culture', $culture)
            ->select(
                'feedback.*',
                'feedback_i18n.name',
                'feedback_i18n.remarks',
                'feedback_i18n.object_id',
                'feedback_i18n.status',
                'feedback_i18n.created_at',
                'feedback_i18n.completed_at'
            )
            ->first();

        if (!$this->feedback) {
            $this->forward404();
        }

        // For template compatibility - pass as $resource
        $this->resource = (object) [
            'id' => $this->feedback->id,
            'created_at' => $this->feedback->created_at,
            'completed_at' => $this->feedback->completed_at,
        ];

        // Get linked information object if exists
        $this->informationObject = null;
        if ($this->feedback->object_id) {
            $this->informationObject = QubitInformationObject::getById($this->feedback->object_id);
        }

        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        foreach (self::$NAMES as $name) {
            $this->addField($name);
        }

        // Set default values
        $this->form->setDefault('name', $this->feedback->name);
        $this->form->setDefault('feed_name', $this->feedback->feed_name);
        $this->form->setDefault('feed_surname', $this->feedback->feed_surname);
        $this->form->setDefault('feed_phone', $this->feedback->feed_phone);
        $this->form->setDefault('feed_email', $this->feedback->feed_email);
        $this->form->setDefault('feed_relationship', $this->feedback->feed_relationship);
        $this->form->setDefault('feed_type_id', $this->feedback->feed_type_id);
        $this->form->setDefault('remarks', $this->feedback->remarks);
        $this->form->setDefault('status', $this->feedback->status);
        $this->form->setDefault('admin_notes', '');

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->processForm();

                $this->getUser()->setFlash('notice', $this->context->i18n->__('Feedback updated successfully.'));
                $this->redirect(['module' => 'feedback', 'action' => 'browse']);
            }
        }
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'name':
                $this->form->setValidator('name', new sfValidatorString(['required' => false]));
                $this->form->setWidget('name', new sfWidgetFormInput());
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

            case 'status':
                $this->form->setValidator('status', new sfValidatorString(['required' => true]));
                $this->form->setWidget('status', new sfWidgetFormSelect([
                    'choices' => [
                        'pending' => $this->context->i18n->__('Pending'),
                        'completed' => $this->context->i18n->__('Completed'),
                    ],
                ]));
                break;

            case 'admin_notes':
                $this->form->setValidator('admin_notes', new sfValidatorString(['required' => false]));
                $this->form->setWidget('admin_notes', new sfWidgetFormTextarea());
                break;
        }
    }

    protected function processForm()
    {
        $now = date('Y-m-d H:i:s');
        $culture = $this->getUser()->getCulture();

        // Update feedback table
        DB::table('feedback')
            ->where('id', $this->feedback->id)
            ->update([
                'feed_name' => $this->form->getValue('feed_name'),
                'feed_surname' => $this->form->getValue('feed_surname'),
                'feed_phone' => $this->form->getValue('feed_phone'),
                'feed_email' => $this->form->getValue('feed_email'),
                'feed_relationship' => $this->form->getValue('feed_relationship'),
                'feed_type_id' => $this->form->getValue('feed_type_id'),
            ]);

        // Determine completed_at
        $completedAt = null;
        if ($this->form->getValue('status') === 'completed') {
            $completedAt = $this->feedback->completed_at ?: $now;
        }

        // Update feedback_i18n table
        DB::table('feedback_i18n')
            ->where('id', $this->feedback->id)
            ->where('culture', $culture)
            ->update([
                'remarks' => $this->form->getValue('remarks'),
                'status' => $this->form->getValue('status'),
                'completed_at' => $completedAt,
            ]);

        // Update object table
        DB::table('object')
            ->where('id', $this->feedback->id)
            ->update([
                'updated_at' => $now,
            ]);
    }
}
