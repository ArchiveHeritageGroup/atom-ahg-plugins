<?php

use AtomExtensions\Services\SettingService;
use AtomExtensions\Services\CacheService;
use AtomFramework\Http\Controllers\AhgEditController;

/**
 * Global identifier settings (AHG settings module).
 *
 * Handles the base AtoM identifier settings:
 *  - accession_mask_enabled / accession_mask / accession_counter
 *  - identifier_mask_enabled / identifier_mask / identifier_counter
 *  - separator_character, inherit_code_*, prevent_duplicate_actor_identifiers
 *
 * For sector-specific numbering, see sectorNumberingAction.
 */
class AhgSettingsIdentifierAction extends AhgEditController
{
    public static $NAMES = [
        'accession_mask_enabled',
        'accession_mask',
        'accession_counter',
        'identifier_mask_enabled',
        'identifier_mask',
        'identifier_counter',
        'separator_character',
        'inherit_code_informationobject',
        'inherit_code_dc_xml',
        'prevent_duplicate_actor_identifiers',
    ];

    /** @var sfI18N */
    protected $i18n;

    public function execute($request)
    {
        parent::execute($request);

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->processForm();

                CacheService::getInstance()->removePattern('settings:i18n:*');

                $this->getUser()->setFlash('notice', $this->i18n->__('Identifier settings saved.'));
                $this->redirect(['module' => 'ahgSettings', 'action' => 'identifier']);
            }
        }
    }

    protected function earlyExecute()
    {
        $this->i18n = sfContext::getInstance()->i18n;
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'accession_mask':
            case 'accession_counter':
            case 'identifier_mask':
            case 'identifier_counter':
            case 'separator_character':
                $default = (null !== $this->{$name} = SettingService::getByName($name))
                    ? $this->{$name}->getValue(['sourceCulture' => true])
                    : '';

                $this->form->setDefault($name, $default);
                $this->form->setValidator($name, new sfValidatorString(['required' => true]));
                $this->form->setWidget($name, new sfWidgetFormInput());
                break;

            case 'accession_mask_enabled':
            case 'identifier_mask_enabled':
            case 'inherit_code_informationobject':
            case 'inherit_code_dc_xml':
            case 'prevent_duplicate_actor_identifiers':
                $defaults = [
                    'accession_mask_enabled' => 1,
                    'identifier_mask_enabled' => 0,
                    'inherit_code_informationobject' => 0,
                    'inherit_code_dc_xml' => 0,
                    'prevent_duplicate_actor_identifiers' => 0,
                ];

                $default = (null !== $this->{$name} = SettingService::getByName($name))
                    ? $this->{$name}->getValue(['sourceCulture' => true])
                    : ($defaults[$name] ?? 0);

                $options = [$this->i18n->__('No'), $this->i18n->__('Yes')];
                $this->form->setDefault($name, $default);
                $this->form->setValidator($name, new sfValidatorString(['required' => false]));
                $this->form->setWidget($name, new sfWidgetFormSelectRadio(['choices' => $options], ['class' => 'radio']));
                break;
        }
    }

    protected function processField($field)
    {
        $name = $field->getName();
        $value = $field->getValue();

        switch ($name) {
            case 'accession_mask_enabled':
            case 'accession_mask':
            case 'accession_counter':
            case 'identifier_mask_enabled':
            case 'identifier_mask':
            case 'identifier_counter':
            case 'separator_character':
            case 'inherit_code_informationobject':
            case 'inherit_code_dc_xml':
            case 'prevent_duplicate_actor_identifiers':
                \AtomFramework\Services\Write\WriteServiceFactory::settings()
                    ->save($name, (string) $value);
                break;
        }
    }
}
