<?php

/**
 * ahgSettings actions - Handles AHG settings directly
 *
 * Includes and executes action classes from the settings module.
 * Variables set in the inner action are transferred to this action for template rendering.
 */
class ahgSettingsActions extends sfActions
{
    private function executeSettingsAction($actionClass, $request)
    {
        $actionFile = sfConfig::get('sf_plugins_dir') . '/ahgSettingsPlugin/modules/ahgSettings/actions/handlers/' . $actionClass . '.class.php';
        if (file_exists($actionFile)) {
            require_once $actionFile;

            // Try multiple class naming patterns due to inconsistent naming in codebase
            $baseName = str_replace('Action', '', $actionClass);
            $classPatterns = [
                'AhgSettings' . ucfirst($baseName) . 'Action',  // AhgSettingsIndexAction
                'Settings' . ucfirst($baseName) . 'Action',     // SettingsGlobalAction
                'settings' . ucfirst($baseName) . 'Action',     // settingsLevelsAction
                'settings' . $baseName . 'Action',              // settingsApiKeysAction (preserves camelCase)
            ];

            $className = null;
            foreach ($classPatterns as $pattern) {
                if (class_exists($pattern)) {
                    $className = $pattern;
                    break;
                }
            }

            if (!$className) {
                throw new sfError404Exception('Action class not found for: ' . $actionClass);
            }

            $action = new $className($this->context, 'ahgSettings', $this->getActionName());
            $result = $action->execute($request);

            // Transfer all public properties from inner action to this action
            // so they're available in the template
            $reflection = new ReflectionObject($action);
            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
                $name = $prop->getName();
                $this->$name = $action->$name;
            }

            // Also copy the varHolder contents
            foreach ($action->getVarHolder()->getAll() as $name => $value) {
                $this->$name = $value;
            }

            return $result;
        }
        throw new sfError404Exception('Action not found: ' . $actionClass);
    }

    public function executeIndex(sfWebRequest $request)
    {
        return $this->executeSettingsAction('indexAction', $request);
    }

    public function executeSection(sfWebRequest $request)
    {
        return $this->executeSettingsAction('sectionAction', $request);
    }

    public function executeExport(sfWebRequest $request)
    {
        return $this->executeSettingsAction('exportAction', $request);
    }

    public function executeImport(sfWebRequest $request)
    {
        return $this->executeSettingsAction('importAction', $request);
    }

    public function executeReset(sfWebRequest $request)
    {
        return $this->executeSettingsAction('resetAction', $request);
    }

    public function executeEmail(sfWebRequest $request)
    {
        return $this->executeSettingsAction('emailAction', $request);
    }

    public function executeEmailTest(sfWebRequest $request)
    {
        return $this->executeSettingsAction('emailTestAction', $request);
    }

    public function executeFusekiTest(sfWebRequest $request)
    {
        return $this->executeSettingsAction('fusekiTestAction', $request);
    }

    public function executePlugins(sfWebRequest $request)
    {
        return $this->executeSettingsAction('pluginsAction', $request);
    }

    public function executeSaveTiffPdfSettings(sfWebRequest $request)
    {
        return $this->executeSettingsAction('saveTiffPdfSettingsAction', $request);
    }

    public function executeDamTools(sfWebRequest $request)
    {
        return $this->executeSettingsAction('damToolsAction', $request);
    }

    public function executePreservation(sfWebRequest $request)
    {
        return $this->executeSettingsAction('preservationAction', $request);
    }

    public function executeLevels(sfWebRequest $request)
    {
        return $this->executeSettingsAction('levelsAction', $request);
    }

    public function executeAiServices(sfWebRequest $request)
    {
        return $this->executeSettingsAction('aiServicesAction', $request);
    }

    public function executeApiKeys(sfWebRequest $request)
    {
        return $this->executeSettingsAction('apiKeysAction', $request);
    }

    public function executeWebhooks(sfWebRequest $request)
    {
        return $this->executeSettingsAction('webhooksAction', $request);
    }

    public function executeGlobal(sfWebRequest $request)
    {
        return $this->executeSettingsAction('globalAction', $request);
    }

    public function executeLanguage(sfWebRequest $request)
    {
        return $this->executeSettingsAction('languageAction', $request);
    }

    public function executeSiteInformation(sfWebRequest $request)
    {
        return $this->executeSettingsAction('siteInformationAction', $request);
    }

    public function executePageElements(sfWebRequest $request)
    {
        return $this->executeSettingsAction('pageElementsAction', $request);
    }

    public function executePermissions(sfWebRequest $request)
    {
        return $this->executeSettingsAction('permissionsAction', $request);
    }

    public function executeSecurity(sfWebRequest $request)
    {
        return $this->executeSettingsAction('securityAction', $request);
    }

    public function executeTreeview(sfWebRequest $request)
    {
        return $this->executeSettingsAction('treeviewAction', $request);
    }

    public function executeUploads(sfWebRequest $request)
    {
        return $this->executeSettingsAction('uploadsAction', $request);
    }

    public function executeVisibleElements(sfWebRequest $request)
    {
        return $this->executeSettingsAction('visibleElementsAction', $request);
    }

    public function executeTemplate(sfWebRequest $request)
    {
        return $this->executeSettingsAction('templateAction', $request);
    }

    public function executeFindingAid(sfWebRequest $request)
    {
        return $this->executeSettingsAction('findingAidAction', $request);
    }

    public function executeOai(sfWebRequest $request)
    {
        return $this->executeSettingsAction('oaiAction', $request);
    }

    public function executeIdentifier(sfWebRequest $request)
    {
        return $this->executeSettingsAction('identifierAction', $request);
    }

    public function executeIcipSettings(sfWebRequest $request)
    {
        return $this->executeSettingsAction('icipSettingsAction', $request);
    }

    public function executeAhgIntegration(sfWebRequest $request)
    {
        return $this->executeSettingsAction('ahgIntegrationAction', $request);
    }

    public function executeSectorNumbering(sfWebRequest $request)
    {
        error_log('=== executeSectorNumbering CALLED ===');

        if (!$this->context->user->isAdministrator()) {
            error_log('=== NOT ADMIN, forwarding ===');
            $this->forward('admin', 'secure');
        }

        error_log('=== ADMIN CHECK PASSED ===');

        $this->i18n = sfContext::getInstance()->i18n;
        $this->form = new sfForm();

        // GLAM/DAM sectors - always show all 5
        $this->sectors = [
            'archive' => 'Archive',
            'museum' => 'Museum',
            'library' => 'Library',
            'gallery' => 'Gallery',
            'dam' => 'DAM',
        ];

        error_log('=== SECTORS SET: ' . print_r($this->sectors, true));

        // Sector field keys
        $sectorKeys = [
            'accession_mask_enabled',
            'accession_mask',
            'accession_counter',
            'identifier_mask_enabled',
            'identifier_mask',
            'identifier_counter',
        ];

        // Add form fields for each sector
        foreach (array_keys($this->sectors) as $sector) {
            foreach ($sectorKeys as $baseKey) {
                $fieldName = 'sector_' . $sector . '__' . $baseKey;
                $settingName = $fieldName;

                $existing = \AtomExtensions\Services\SettingService::getByName($settingName);
                $default = $existing ? $existing->getValue(['sourceCulture' => true]) : '';

                if (in_array($baseKey, ['accession_mask_enabled', 'identifier_mask_enabled'], true)) {
                    $choices = [
                        '' => $this->i18n->__('Inherit (global)'),
                        '0' => $this->i18n->__('No'),
                        '1' => $this->i18n->__('Yes'),
                    ];
                    $this->form->setDefault($fieldName, (string) $default);
                    $this->form->setValidator($fieldName, new sfValidatorChoice([
                        'required' => false,
                        'choices' => array_keys($choices),
                    ]));
                    $this->form->setWidget($fieldName, new sfWidgetFormChoice([
                        'choices' => $choices,
                        'expanded' => true,
                    ], ['class' => 'radio']));
                } else {
                    $this->form->setDefault($fieldName, (string) $default);
                    $this->form->setValidator($fieldName, new sfValidatorString(['required' => false]));
                    $this->form->setWidget($fieldName, new sfWidgetFormInput());
                }
            }
        }

        // Get global values for reference
        $this->globalValues = [];
        foreach ($sectorKeys as $key) {
            $setting = \AtomExtensions\Services\SettingService::getByName($key);
            $this->globalValues[$key] = $setting ? $setting->getValue(['sourceCulture' => true]) : '';
        }

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                // Process form
                foreach (array_keys($this->sectors) as $sector) {
                    foreach ($sectorKeys as $baseKey) {
                        $fieldName = 'sector_' . $sector . '__' . $baseKey;
                        $value = $this->form->getValue($fieldName);

                        if ($value === '' || $value === null) {
                            // Empty = inherit: delete sector setting if exists
                            if (null !== $existing = \AtomExtensions\Services\SettingService::getByName($fieldName)) {
                                if (method_exists($existing, 'delete')) {
                                    $existing->delete();
                                }
                            }
                        } else {
                            // Save sector override
                            if (null === $setting = \AtomExtensions\Services\SettingService::getByName($fieldName)) {
                                $setting = new QubitSetting();
                                $setting->name = $fieldName;
                            }
                            $setting->setValue((string) $value, ['sourceCulture' => true]);
                            $setting->save();
                        }
                    }
                }

                \AtomExtensions\Services\CacheService::getInstance()->removePattern('settings:i18n:*');
                $this->getUser()->setFlash('notice', $this->i18n->__('Sector numbering settings saved.'));
                $this->redirect(['module' => 'ahgSettings', 'action' => 'sectorNumbering']);
            }
        }
    }

    public function executeDiacritics(sfWebRequest $request)
    {
        return $this->executeSettingsAction('diacriticsAction', $request);
    }

    public function executeCsvValidator(sfWebRequest $request)
    {
        return $this->executeSettingsAction('csvValidatorAction', $request);
    }

    public function executeClipboard(sfWebRequest $request)
    {
        return $this->executeSettingsAction('clipboardAction', $request);
    }

    public function executeTts(sfWebRequest $request)
    {
        return $this->executeSettingsAction('ttsAction', $request);
    }

    public function executeServices(sfWebRequest $request)
    {
        return $this->executeSettingsAction('servicesAction', $request);
    }

    public function executeMarkdown(sfWebRequest $request)
    {
        return $this->executeSettingsAction('markdownAction', $request);
    }

    public function executePrivacyNotification(sfWebRequest $request)
    {
        return $this->executeSettingsAction('privacyNotificationAction', $request);
    }

    public function executeDigitalObjectDerivatives(sfWebRequest $request)
    {
        return $this->executeSettingsAction('digitalObjectDerivativesAction', $request);
    }

    public function executeDipUpload(sfWebRequest $request)
    {
        return $this->executeSettingsAction('dipUploadAction', $request);
    }

    public function executeInventory(sfWebRequest $request)
    {
        return $this->executeSettingsAction('inventoryAction', $request);
    }

    public function executeInterfaceLabel(sfWebRequest $request)
    {
        return $this->executeSettingsAction('interfaceLabelAction', $request);
    }

    public function executeLdap(sfWebRequest $request)
    {
        return $this->executeSettingsAction('ldapAction', $request);
    }

    public function executeNumberingSchemes(sfWebRequest $request)
    {
        return $this->executeSettingsAction('numberingSchemesAction', $request);
    }

    public function executeNumberingSchemeEdit(sfWebRequest $request)
    {
        return $this->executeSettingsAction('numberingSchemeEditAction', $request);
    }

    public function executeGenerateIdentifier(sfWebRequest $request)
    {
        return $this->executeSettingsAction('generateIdentifierAction', $request);
    }

    public function executeValidateIdentifier(sfWebRequest $request)
    {
        return $this->executeSettingsAction('validateIdentifierAction', $request);
    }

    // Aliases for legacy URLs
    public function executeAhgSettings(sfWebRequest $request)
    {
        return $this->executeIndex($request);
    }

    public function executeAhgDashboard(sfWebRequest $request)
    {
        return $this->executeIndex($request);
    }

    public function executeCronJobs(sfWebRequest $request)
    {
        return $this->executeSettingsAction('cronJobsAction', $request);
    }

    public function executeSystemInfo(sfWebRequest $request)
    {
        return $this->executeSettingsAction('systemInfoAction', $request);
    }
}
