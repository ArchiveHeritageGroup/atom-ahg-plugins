<?php

/*
 * AHG Core Plugin - contactinformation edit component stub.
 * Overrides base AtoM component for module decoupling (WP-S8).
 */

class ContactInformationEditComponent extends sfComponent
{
    public static $NAMES = [
        'city',
        'contactPerson',
        'contactType',
        'countryCode',
        'email',
        'fax',
        'latitude',
        'longitude',
        'note',
        'region',
        'postalCode',
        'primaryContact',
        'telephone',
        'streetAddress',
        'website',
    ];

    public function processForm()
    {
        $params = [$this->request[$this->actionName]];
        if (isset($this->request["{$this->actionName}s"])) {
            $params = $this->request["{$this->actionName}s"];
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
                    $this->contactInformation = QubitContactInformation::getById(preg_replace('/^.*\/(\d+)$/', '$1', $item['id']));
                } else {
                    $this->resource->contactInformations[] = $this->contactInformation = new QubitContactInformation();
                }

                foreach ($this->form as $field) {
                    if (isset($item[$field->getName()])) {
                        $this->processField($field);
                    }
                }

                if (isset($item['id'])) {
                    $this->contactInformation->save();

                    if ($this->contactInformation->primaryContact) {
                        $this->contactInformation->makePrimaryContact();
                    }
                }
            }
        }

        if (isset($this->request->deleteContactInformations)) {
            foreach ($this->request->deleteContactInformations as $item) {
                $contactInformation = QubitContactInformation::getById($item);

                if (isset($contactInformation)) {
                    $contactInformation->delete();
                }
            }
        }
    }

    public function execute($request)
    {
        $this->form = new sfForm([], [], false);
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);
        $this->form->getWidgetSchema()->setNameFormat('editContactInformation[%s]');

        foreach ($this::$NAMES as $name) {
            $this->addField($name);
        }
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'countryCode':
                $this->form->setValidator('countryCode', new sfValidatorI18nChoiceCountry());
                $this->form->setWidget('countryCode', new sfWidgetFormI18nChoiceCountry(['add_empty' => true, 'culture' => $this->context->user->getCulture()]));

                break;

            case 'primaryContact':
                $this->form->setDefault('primaryContact', false);
                $this->form->setValidator('primaryContact', new sfValidatorBoolean());
                $this->form->setWidget('primaryContact', new sfWidgetFormInputCheckbox());

                break;

            case 'latitude':
            case 'longitude':
                $this->form->setValidator($name, new sfValidatorNumber());
                $this->form->setWidget($name, new sfWidgetFormInput());

                break;

            case 'streetAddress':
            case 'note':
                $this->form->setValidator($name, new sfValidatorString());
                $this->form->setWidget($name, new sfWidgetFormTextArea([], ['rows' => 2]));

                break;

            default:
                $this->form->setValidator($name, new sfValidatorString());
                $this->form->setWidget($name, new sfWidgetFormInput());

                break;
        }
    }

    protected function processField($field)
    {
        $this->contactInformation[$field->getName()] = $this->form->getValue($field->getName());
    }
}
