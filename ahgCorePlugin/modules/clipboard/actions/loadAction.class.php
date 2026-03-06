<?php

/**
 * AHG stub for clipboard/load action.
 * Replaces apps/qubit/modules/clipboard/actions/loadAction.class.php.
 *
 * Form processing to load a saved clipboard by password.
 */
class ClipboardLoadAction extends DefaultEditAction
{
    // Arrays not allowed in class constants
    public static $NAMES = [
        'clipboardPassword',
        'mode',
    ];

    public function execute($request)
    {
        $title = $this->context->i18n->__('Load clipboard');
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        parent::execute($request);

        if (!$request->isMethod('post')) {
            return;
        }

        $this->response->setHttpHeader('Content-Type', 'application/json; charset=utf-8');

        $this->form->bind($request->getPostParameters());

        if (!$this->form->isValid()) {
            $this->response->setStatusCode(400);
            $message = $this->context->i18n->__('Incorrect clipboard ID and/or action.');

            return $this->renderText(json_encode(['error' => $message]));
        }

        $this->processForm();

        $criteria = new Criteria();
        $criteria->add(QubitClipboardSave::PASSWORD, $this->password);
        $save = QubitClipboardSave::getOne($criteria);

        if (!isset($save)) {
            $this->response->setStatusCode(404);
            $message = $this->context->i18n->__('Clipboard ID not found.');

            return $this->renderText(json_encode(['error' => $message]));
        }

        $criteria = new Criteria();
        $criteria->add(QubitClipboardSaveItem::SAVE_ID, $save->id);
        $items = QubitClipboardSaveItem::get($criteria);

        $clipboard = [
            'informationObject' => [],
            'actor' => [],
            'repository' => [],
        ];
        $addedCount = 0;

        foreach ($items as $item) {
            // Add slug to clipboard if the object exists and the user can read it
            $object = QubitObject::getBySlug($item->slug);

            if (isset($object) && QubitAcl::check($object, 'read')) {
                $type = lcfirst(str_replace('Qubit', '', $item->itemClassName));
                array_push($clipboard[$type], $item->slug);
                ++$addedCount;
            }
        }

        if ('replace' == $this->mode) {
            $actionDescription = $this->context->i18n->__('added');
        } else {
            $actionDescription = $this->context->i18n->__('merged with current clipboard');
        }

        $message = $this->context->i18n->__(
            'Clipboard %1% loaded, %2% records %3%.',
            ['%1%' => $this->password, '%2%' => $addedCount, '%3%' => $actionDescription]
        );

        $this->response->setStatusCode(200);

        return $this->renderText(json_encode(['success' => $message, 'clipboard' => $clipboard]));
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'clipboardPassword':
                $this->form->setValidator('clipboardPassword', new sfValidatorString(['required' => true]));
                $this->form->setWidget('clipboardPassword', new sfWidgetFormInput());

                break;

            case 'mode':
                $this->form->setDefault('mode', 'merge');
                $this->form->setValidator('mode', new sfValidatorString());
                $choices = [
                    'merge' => $this->context->i18n->__('Merge saved clipboard with existing clipboard results'),
                    'replace' => $this->context->i18n->__('Replace existing clipboard results with saved clipboard'),
                ];
                $this->form->setWidget('mode', new sfWidgetFormSelect(['choices' => $choices]));

                break;
        }
    }

    protected function processField($field)
    {
        switch ($field->getName()) {
            case 'clipboardPassword':
                $this->password = $this->form->getValue($field->getName());

                break;

            case 'mode':
                $this->mode = $this->form->getValue($field->getName());

                break;
        }
    }
}
