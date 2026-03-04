<?php

/**
 * Event edit component stub.
 * Handles event date/type form processing for accession and IO forms.
 */
class EventEditComponent extends sfComponent
{
    public function processForm()
    {
        $params = [$this->request->editEvent];
        if (isset($this->request->editEvents)) {
            $params = $this->request->editEvents;
        }

        $indexOnSave = false;
        if ($this->resource instanceof QubitActor) {
            $indexOnSave = true;
        }

        foreach ($params as $item) {
            foreach ($item as $value) {
                if (0 < strlen($value)) {
                    break;
                }
            }

            if (1 > strlen($value)) {
                continue;
            }

            $this->form->bind($item);
            if ($this->form->isValid()) {
                if (isset($item['id'])) {
                    $params = $this->context->routing->parse(Qubit::pathInfo($item['id']));
                    $this->event = $params['_sf_route']->resource;
                } elseif ($this->resource instanceof QubitActor) {
                    $this->resource->events[] = $this->event = new QubitEvent();
                } else {
                    $this->resource->eventsRelatedByobjectId[] = $this->event = new QubitEvent();
                }

                foreach ($this->form as $field) {
                    if (isset($item[$field->getName()])) {
                        $this->processField($field);
                    }
                }

                if (isset($this->event->id)) {
                    $this->event->indexOnSave = $indexOnSave;
                    $this->event->save();
                }
            }
        }

        if (isset($this->request->sourceId)) {
            return;
        }

        if (isset($this->request->deleteEvents)) {
            foreach ($this->request->deleteEvents as $item) {
                $params = $this->context->routing->parse(Qubit::pathInfo($item));
                $event = $params['_sf_route']->resource;
                $event->indexOnSave = $indexOnSave;
                $event->delete();
            }
        }
    }

    public function execute($request)
    {
        $this->form = new sfForm([], [], false);
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);
        $this->form->getWidgetSchema()->setNameFormat('editEvent[%s]');

        foreach ($this::$NAMES as $name) {
            $this->addField($name);
        }
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'date':
                $this->form->setValidator('date', new sfValidatorString());
                $this->form->setWidget('date', new sfWidgetFormInput());
                $this->form->getWidgetSchema()->date->setHelp($this->context->i18n->__('Enter free-text information, including qualifiers or typographical symbols to express uncertainty, to change the way the date displays. If this field is not used, the default will be the start and end years only.'));
                break;

            case 'endDate':
                $this->form->setValidator('endDate', new sfValidatorString());
                $this->form->setWidget('endDate', new sfWidgetFormInput());
                $this->form->getWidgetSchema()->endDate->setHelp($this->context->i18n->__('Enter the end year. Do not use any qualifiers or typographical symbols to express uncertainty. Acceptable date formats: YYYYMMDD, YYYY-MM-DD, YYYY-MM, YYYY.'));
                $this->form->getWidgetSchema()->endDate->setLabel($this->context->i18n->__('End'));
                break;

            case 'startDate':
                $this->form->setValidator('startDate', new sfValidatorString());
                $this->form->setWidget('startDate', new sfWidgetFormInput());
                $this->form->getWidgetSchema()->startDate->setHelp($this->context->i18n->__('Enter the start year. Do not use any qualifiers or typographical symbols to express uncertainty. Acceptable date formats: YYYYMMDD, YYYY-MM-DD, YYYY-MM, YYYY.'));
                $this->form->getWidgetSchema()->startDate->setLabel($this->context->i18n->__('Start'));
                break;

            case 'type':
                $eventTypes = QubitTaxonomy::getTermsById(QubitTaxonomy::EVENT_TYPE_ID);
                if ('sfDcPlugin' == $this->request->module) {
                    $eventTypes = sfDcPlugin::eventTypes();
                }

                $choices = [];
                foreach ($eventTypes as $item) {
                    if (QubitTerm::CREATION_ID == $item->id) {
                        $this->form->setDefault('type', $this->context->routing->generate(null, [$item, 'module' => 'term']));
                    }
                    $choices[$this->context->routing->generate(null, [$item, 'module' => 'term'])] = $item->__toString();
                }

                $this->form->setValidator('type', new sfValidatorString());
                $this->form->setWidget('type', new sfWidgetFormSelect(['choices' => $choices]));
                break;
        }
    }

    protected function processField($field)
    {
        switch ($field->getName()) {
            case 'type':
            case 'resourceType':
                unset($this->event[$field->getName()]);
                $value = $this->form->getValue($field->getName());
                if (isset($value)) {
                    $params = $this->context->routing->parse(Qubit::pathInfo($value));
                    $this->event[$field->getName()] = $params['_sf_route']->resource;
                }
                break;

            case 'startDate':
            case 'endDate':
                $value = $this->form->getValue($field->getName());
                if (isset($value) && preg_match('/^\d{8}\z/', trim($value), $matches)) {
                    $value = substr($matches[0], 0, 4).'-'.substr($matches[0], 4, 2).'-'.substr($matches[0], 6, 2);
                } elseif (isset($value) && preg_match('/^\d{6}\z/', trim($value), $matches)) {
                    $value = substr($matches[0], 0, 4).'-'.substr($matches[0], 4, 2);
                }
                $this->event[$field->getName()] = $value;
                break;

            default:
                $this->event[$field->getName()] = $this->form->getValue($field->getName());
        }
    }
}
