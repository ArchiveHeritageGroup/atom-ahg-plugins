<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * ahgSettings actions - Handles AHG settings directly
 *
 * Includes and executes action classes from the settings module.
 * Variables set in the inner action are transferred to this action for template rendering.
 */
class ahgSettingsActions extends AhgController
{
    private function executeSettingsAction($actionClass, $request)
    {
        $actionFile = $this->config('sf_plugins_dir') . '/ahgSettingsPlugin/modules/ahgSettings/actions/handlers/' . $actionClass . '.class.php';
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

            // If the handler returned blade template data (array), render via Blade
            if (is_array($result) && isset($result['_blade'])) {
                $template = $result['_blade'];
                unset($result['_blade']);

                return $this->renderBlade($template, $result);
            }

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

            // Check if a Blade template exists for this action
            $actionName = $this->getActionName();
            $bladeFile = $this->config('sf_plugins_dir') . '/ahgSettingsPlugin/modules/ahgSettings/templates/' . $actionName . '.blade.php';
            if (file_exists($bladeFile) && !is_string($result)) {
                return $this->renderSettingsBlade($actionName, $request);
            }

            return $result;
        }
        throw new sfError404Exception('Action not found: ' . $actionClass);
    }

    /**
     * Build menu nodes and render a Blade template for legacy form handlers.
     *
     * Collects menu nodes from the settings menu component and merges all
     * public properties from this action into the template data array.
     */
    private function renderSettingsBlade($template, $request, $additionalData = [])
    {
        $menuComponent = new \ahgSettingsMenuComponent($this->context, 'ahgSettings', $this->getActionName());
        $menuComponent->execute($request);
        $menuNodes = $menuComponent->getVarHolder()->get('nodes', []);

        $data = array_merge(['menuNodes' => $menuNodes], $additionalData);

        // Transfer varHolder contents (where sfComponent stores template vars via __set)
        foreach ($this->getVarHolder()->getAll() as $name => $value) {
            if (!isset($data[$name])) {
                $data[$name] = $value;
            }
        }

        return $this->renderBlade($template, $data);
    }

    public function executeIndex($request)
    {
        return $this->executeSettingsAction('indexAction', $request);
    }

    public function executeSection($request)
    {
        return $this->executeSettingsAction('sectionAction', $request);
    }

    public function executeExport($request)
    {
        return $this->executeSettingsAction('exportAction', $request);
    }

    public function executeImport($request)
    {
        return $this->executeSettingsAction('importAction', $request);
    }

    public function executeReset($request)
    {
        return $this->executeSettingsAction('resetAction', $request);
    }

    public function executeEmail($request)
    {
        return $this->executeSettingsAction('emailAction', $request);
    }

    public function executeEmailTest($request)
    {
        return $this->executeSettingsAction('emailTestAction', $request);
    }

    public function executeFusekiTest($request)
    {
        return $this->executeSettingsAction('fusekiTestAction', $request);
    }

    public function executePlugins($request)
    {
        return $this->executeSettingsAction('pluginsAction', $request);
    }

    public function executeSaveTiffPdfSettings($request)
    {
        return $this->executeSettingsAction('saveTiffPdfSettingsAction', $request);
    }

    public function executeDamTools($request)
    {
        return $this->executeSettingsAction('damToolsAction', $request);
    }

    public function executePreservation($request)
    {
        return $this->executeSettingsAction('preservationAction', $request);
    }

    public function executeLevels($request)
    {
        return $this->executeSettingsAction('levelsAction', $request);
    }

    public function executeAiServices($request)
    {
        return $this->executeSettingsAction('aiServicesAction', $request);
    }

    public function executeApiKeys($request)
    {
        return $this->executeSettingsAction('apiKeysAction', $request);
    }

    public function executeWebhooks($request)
    {
        return $this->executeSettingsAction('webhooksAction', $request);
    }

    public function executeGlobal($request)
    {
        return $this->executeSettingsAction('globalAction', $request);
    }

    public function executeLanguage($request)
    {
        return $this->executeSettingsAction('languageAction', $request);
    }

    public function executeSiteInformation($request)
    {
        return $this->executeSettingsAction('siteInformationAction', $request);
    }

    public function executePageElements($request)
    {
        return $this->executeSettingsAction('pageElementsAction', $request);
    }

    public function executePermissions($request)
    {
        return $this->executeSettingsAction('permissionsAction', $request);
    }

    public function executeSecurity($request)
    {
        return $this->executeSettingsAction('securityAction', $request);
    }

    public function executeTreeview($request)
    {
        return $this->executeSettingsAction('treeviewAction', $request);
    }

    public function executeUploads($request)
    {
        return $this->executeSettingsAction('uploadsAction', $request);
    }

    public function executeVisibleElements($request)
    {
        return $this->executeSettingsAction('visibleElementsAction', $request);
    }

    public function executeTemplate($request)
    {
        return $this->executeSettingsAction('templateAction', $request);
    }

    public function executeFindingAid($request)
    {
        return $this->executeSettingsAction('findingAidAction', $request);
    }

    public function executeOai($request)
    {
        return $this->executeSettingsAction('oaiAction', $request);
    }

    public function executeIdentifier($request)
    {
        return $this->executeSettingsAction('identifierAction', $request);
    }

    public function executeIcipSettings($request)
    {
        return $this->executeSettingsAction('icipSettingsAction', $request);
    }

    public function executeAhgIntegration($request)
    {
        return $this->executeSettingsAction('ahgIntegrationAction', $request);
    }

    public function executeSectorNumbering($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->i18n = $this->getContext()->i18n;
        $this->form = new sfForm();

        // GLAM/DAM sectors - always show all 5
        $this->sectors = [
            'archive' => 'Archive',
            'museum' => 'Museum',
            'library' => 'Library',
            'gallery' => 'Gallery',
            'dam' => 'DAM',
        ];

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
                            $propelSetting = QubitSetting::getByName($fieldName);
                            if ($propelSetting) {
                                if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                                    \Illuminate\Database\Capsule\Manager::table('setting_i18n')->where('id', $propelSetting->id)->delete();
                                    \Illuminate\Database\Capsule\Manager::table('setting')->where('id', $propelSetting->id)->delete();
                                } else {
                                    $propelSetting->delete();
                                }
                            }
                        } else {
                            // Save sector override via WriteServiceFactory
                            \AtomFramework\Services\Write\WriteServiceFactory::settings()->save($fieldName, (string) $value);

                            // Sync counter changes to numbering_scheme table
                            if ($baseKey === 'identifier_counter') {
                                \Illuminate\Database\Capsule\Manager::table('numbering_scheme')
                                    ->where('sector', $sector)
                                    ->where('is_default', 1)
                                    ->update(['current_sequence' => (int) $value, 'updated_at' => date('Y-m-d H:i:s')]);
                            }
                        }
                    }
                }

                \AtomExtensions\Services\CacheService::getInstance()->removePattern('settings:i18n:*');
                $this->getUser()->setFlash('notice', $this->i18n->__('Sector numbering settings saved.'));
                $this->redirect(['module' => 'ahgSettings', 'action' => 'sectorNumbering']);
            }
        }
    }

    public function executeDiacritics($request)
    {
        return $this->executeSettingsAction('diacriticsAction', $request);
    }

    public function executeCsvValidator($request)
    {
        return $this->executeSettingsAction('csvValidatorAction', $request);
    }

    public function executeClipboard($request)
    {
        return $this->executeSettingsAction('clipboardAction', $request);
    }

    public function executeTts($request)
    {
        return $this->executeSettingsAction('ttsAction', $request);
    }

    public function executeServices($request)
    {
        return $this->executeSettingsAction('servicesAction', $request);
    }

    public function executeMarkdown($request)
    {
        return $this->executeSettingsAction('markdownAction', $request);
    }

    public function executePrivacyNotification($request)
    {
        return $this->executeSettingsAction('privacyNotificationAction', $request);
    }

    public function executeDigitalObjectDerivatives($request)
    {
        return $this->executeSettingsAction('digitalObjectDerivativesAction', $request);
    }

    public function executeDipUpload($request)
    {
        return $this->executeSettingsAction('dipUploadAction', $request);
    }

    public function executeInventory($request)
    {
        return $this->executeSettingsAction('inventoryAction', $request);
    }

    public function executeInterfaceLabel($request)
    {
        return $this->executeSettingsAction('interfaceLabelAction', $request);
    }

    public function executeLdap($request)
    {
        return $this->executeSettingsAction('ldapAction', $request);
    }

    public function executeNumberingSchemes($request)
    {
        return $this->executeSettingsAction('numberingSchemesAction', $request);
    }

    public function executeNumberingSchemeEdit($request)
    {
        return $this->executeSettingsAction('numberingSchemeEditAction', $request);
    }

    public function executeGenerateIdentifier($request)
    {
        return $this->executeSettingsAction('generateIdentifierAction', $request);
    }

    public function executeValidateIdentifier($request)
    {
        return $this->executeSettingsAction('validateIdentifierAction', $request);
    }

    // Aliases for legacy URLs
    public function executeAhgSettings($request)
    {
        return $this->executeIndex($request);
    }

    public function executeAhgDashboard($request)
    {
        return $this->executeIndex($request);
    }

    public function executeCronJobs($request)
    {
        return $this->executeSettingsAction('cronJobsAction', $request);
    }

    public function executeSystemInfo($request)
    {
        return $this->executeSettingsAction('systemInfoAction', $request);
    }

    public function executeEncryption($request)
    {
        // Redirect to AHG Settings section page with encryption section
        $this->redirect(['module' => 'ahgSettings', 'action' => 'section', 'section' => 'encryption']);
    }
}
