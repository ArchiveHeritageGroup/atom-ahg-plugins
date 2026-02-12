<?php

/*
 * ICIP Settings Action Handler
 *
 * Manages Indigenous Cultural and Intellectual Property settings
 * Uses icip_config table via ahgICIPService
 */

use Illuminate\Database\Capsule\Manager as DB;
use AtomFramework\Http\Controllers\AhgController;

class SettingsIcipSettingsAction extends AhgController
{
    public function execute($request)
    {
        // Check authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Check admin permission
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->form = new sfForm();
        $this->form->getWidgetSchema()->setNameFormat('icip[%s]');

        // Define fields
        $this->addFields();

        // Load current values
        $this->loadCurrentValues();

        // Handle form submission
        if ($request->isMethod('post')) {
            $this->form->bind($request->getParameter('icip'));

            if ($this->form->isValid()) {
                $this->saveSettings($this->form->getValues());
                $this->getUser()->setFlash('notice', $this->context->i18n->__('ICIP settings saved.'));
                $this->redirect(['module' => 'ahgSettings', 'action' => 'icipSettings']);
            }
        }
    }

    protected function addFields()
    {
        $i18n = $this->context->i18n;
        $yesNo = [
            '0' => $i18n->__('No'),
            '1' => $i18n->__('Yes'),
        ];

        // Display Settings
        $this->form->setWidget('enable_public_notices', new sfWidgetFormSelectRadio([
            'choices' => $yesNo,
        ], ['class' => 'radio']));
        $this->form->setValidator('enable_public_notices', new sfValidatorChoice([
            'choices' => array_keys($yesNo),
            'required' => false,
        ]));

        $this->form->setWidget('enable_staff_notices', new sfWidgetFormSelectRadio([
            'choices' => $yesNo,
        ], ['class' => 'radio']));
        $this->form->setValidator('enable_staff_notices', new sfValidatorChoice([
            'choices' => array_keys($yesNo),
            'required' => false,
        ]));

        // Acknowledgement Settings
        $this->form->setWidget('require_acknowledgement_default', new sfWidgetFormSelectRadio([
            'choices' => $yesNo,
        ], ['class' => 'radio']));
        $this->form->setValidator('require_acknowledgement_default', new sfValidatorChoice([
            'choices' => array_keys($yesNo),
            'required' => false,
        ]));

        // Consent Expiry Warning
        $this->form->setWidget('consent_expiry_warning_days', new sfWidgetFormInput([], [
            'class' => 'form-control',
            'style' => 'max-width: 100px;',
        ]));
        $this->form->setValidator('consent_expiry_warning_days', new sfValidatorInteger([
            'required' => false,
            'min' => 0,
            'max' => 365,
        ]));

        // Consultation Follow-up
        $this->form->setWidget('default_consultation_follow_up_days', new sfWidgetFormInput([], [
            'class' => 'form-control',
            'style' => 'max-width: 100px;',
        ]));
        $this->form->setValidator('default_consultation_follow_up_days', new sfValidatorInteger([
            'required' => false,
            'min' => 0,
            'max' => 365,
        ]));

        // Local Contexts Integration
        $this->form->setWidget('local_contexts_hub_enabled', new sfWidgetFormSelectRadio([
            'choices' => $yesNo,
        ], ['class' => 'radio']));
        $this->form->setValidator('local_contexts_hub_enabled', new sfValidatorChoice([
            'choices' => array_keys($yesNo),
            'required' => false,
        ]));

        $this->form->setWidget('local_contexts_api_key', new sfWidgetFormInput([], [
            'class' => 'form-control',
        ]));
        $this->form->setValidator('local_contexts_api_key', new sfValidatorString([
            'required' => false,
            'max_length' => 255,
        ]));

        // Audit Settings
        $this->form->setWidget('audit_all_icip_access', new sfWidgetFormSelectRadio([
            'choices' => $yesNo,
        ], ['class' => 'radio']));
        $this->form->setValidator('audit_all_icip_access', new sfValidatorChoice([
            'choices' => array_keys($yesNo),
            'required' => false,
        ]));
    }

    protected function loadCurrentValues()
    {
        $defaults = [
            'enable_public_notices' => $this->getConfig('enable_public_notices', '1'),
            'enable_staff_notices' => $this->getConfig('enable_staff_notices', '1'),
            'require_acknowledgement_default' => $this->getConfig('require_acknowledgement_default', '1'),
            'consent_expiry_warning_days' => $this->getConfig('consent_expiry_warning_days', '90'),
            'default_consultation_follow_up_days' => $this->getConfig('default_consultation_follow_up_days', '30'),
            'local_contexts_hub_enabled' => $this->getConfig('local_contexts_hub_enabled', '0'),
            'local_contexts_api_key' => $this->getConfig('local_contexts_api_key', ''),
            'audit_all_icip_access' => $this->getConfig('audit_all_icip_access', '1'),
        ];

        $this->form->setDefaults($defaults);
    }

    protected function saveSettings(array $values)
    {
        foreach ($values as $key => $value) {
            $this->setConfig($key, $value ?? '');
        }
    }

    /**
     * Get config value from icip_config table
     */
    protected function getConfig(string $key, $default = null)
    {
        $value = DB::table('icip_config')
            ->where('config_key', $key)
            ->value('config_value');

        return $value !== null ? $value : $default;
    }

    /**
     * Set config value in icip_config table
     */
    protected function setConfig(string $key, $value): void
    {
        DB::table('icip_config')->updateOrInsert(
            ['config_key' => $key],
            ['config_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
        );
    }
}
