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

    // Aliases for legacy URLs
    public function executeAhgSettings(sfWebRequest $request)
    {
        return $this->executeIndex($request);
    }

    public function executeAhgDashboard(sfWebRequest $request)
    {
        return $this->executeIndex($request);
    }
}
