<?php

/**
 * ahgSettings module actions.
 *
 * This is the main actions class that dispatches to individual action files.
 * Each action (index, email, levels, etc.) has its own *Action.class.php file.
 */
class settingsActions extends sfActions
{
    /**
     * Index action - Settings dashboard
     */
    public function executeIndex(sfWebRequest $request)
    {
        require_once __DIR__ . '/indexAction.class.php';
        $action = new AhgSettingsIndexAction($this->context, 'ahgSettings', 'index');
        return $action->execute($request);
    }

    /**
     * Section action - General section settings
     */
    public function executeSection(sfWebRequest $request)
    {
        require_once __DIR__ . '/sectionAction.class.php';
        $action = new AhgSettingsSectionAction($this->context, 'ahgSettings', 'section');
        return $action->execute($request);
    }

    /**
     * Email settings
     */
    public function executeEmail(sfWebRequest $request)
    {
        require_once __DIR__ . '/emailAction.class.php';
        $action = new AhgSettingsEmailAction($this->context, 'ahgSettings', 'email');
        return $action->execute($request);
    }

    /**
     * Email test
     */
    public function executeEmailTest(sfWebRequest $request)
    {
        require_once __DIR__ . '/emailTestAction.class.php';
        $action = new AhgSettingsEmailTestAction($this->context, 'ahgSettings', 'emailTest');
        return $action->execute($request);
    }

    /**
     * Levels of description
     */
    public function executeLevels(sfWebRequest $request)
    {
        require_once __DIR__ . '/levelsAction.class.php';
        $action = new AhgSettingsLevelsAction($this->context, 'ahgSettings', 'levels');
        return $action->execute($request);
    }

    /**
     * Plugins management
     */
    public function executePlugins(sfWebRequest $request)
    {
        require_once __DIR__ . '/pluginsAction.class.php';
        $action = new AhgSettingsPluginsAction($this->context, 'ahgSettings', 'plugins');
        return $action->execute($request);
    }

    /**
     * API Keys
     */
    public function executeApiKeys(sfWebRequest $request)
    {
        require_once __DIR__ . '/apiKeysAction.class.php';
        $action = new AhgSettingsApiKeysAction($this->context, 'ahgSettings', 'apiKeys');
        return $action->execute($request);
    }

    /**
     * AI Services
     */
    public function executeAiServices(sfWebRequest $request)
    {
        require_once __DIR__ . '/aiServicesAction.class.php';
        $action = new AhgSettingsAiServicesAction($this->context, 'ahgSettings', 'aiServices');
        return $action->execute($request);
    }

    /**
     * Global settings
     */
    public function executeGlobal(sfWebRequest $request)
    {
        require_once __DIR__ . '/globalAction.class.php';
        $action = new AhgSettingsGlobalAction($this->context, 'ahgSettings', 'global');
        return $action->execute($request);
    }

    /**
     * Language settings
     */
    public function executeLanguage(sfWebRequest $request)
    {
        require_once __DIR__ . '/languageAction.class.php';
        $action = new AhgSettingsLanguageAction($this->context, 'ahgSettings', 'language');
        return $action->execute($request);
    }

    /**
     * Site Information
     */
    public function executeSiteInformation(sfWebRequest $request)
    {
        require_once __DIR__ . '/siteInformationAction.class.php';
        $action = new AhgSettingsSiteInformationAction($this->context, 'ahgSettings', 'siteInformation');
        return $action->execute($request);
    }

    /**
     * Page Elements
     */
    public function executePageElements(sfWebRequest $request)
    {
        require_once __DIR__ . '/pageElementsAction.class.php';
        $action = new AhgSettingsPageElementsAction($this->context, 'ahgSettings', 'pageElements');
        return $action->execute($request);
    }

    /**
     * Permissions
     */
    public function executePermissions(sfWebRequest $request)
    {
        require_once __DIR__ . '/permissionsAction.class.php';
        $action = new AhgSettingsPermissionsAction($this->context, 'ahgSettings', 'permissions');
        return $action->execute($request);
    }

    /**
     * Security
     */
    public function executeSecurity(sfWebRequest $request)
    {
        require_once __DIR__ . '/securityAction.class.php';
        $action = new AhgSettingsSecurityAction($this->context, 'ahgSettings', 'security');
        return $action->execute($request);
    }

    /**
     * Treeview
     */
    public function executeTreeview(sfWebRequest $request)
    {
        require_once __DIR__ . '/treeviewAction.class.php';
        $action = new AhgSettingsTreeviewAction($this->context, 'ahgSettings', 'treeview');
        return $action->execute($request);
    }

    /**
     * Import settings
     */
    public function executeImport(sfWebRequest $request)
    {
        require_once __DIR__ . '/importAction.class.php';
        $action = new AhgSettingsImportAction($this->context, 'ahgSettings', 'import');
        return $action->execute($request);
    }

    /**
     * Export settings
     */
    public function executeExport(sfWebRequest $request)
    {
        require_once __DIR__ . '/exportAction.class.php';
        $action = new AhgSettingsExportAction($this->context, 'ahgSettings', 'export');
        return $action->execute($request);
    }

    /**
     * Uploads settings
     */
    public function executeUploads(sfWebRequest $request)
    {
        require_once __DIR__ . '/uploadsAction.class.php';
        $action = new AhgSettingsUploadsAction($this->context, 'ahgSettings', 'uploads');
        return $action->execute($request);
    }

    /**
     * Visible Elements
     */
    public function executeVisibleElements(sfWebRequest $request)
    {
        require_once __DIR__ . '/visibleElementsAction.class.php';
        $action = new AhgSettingsVisibleElementsAction($this->context, 'ahgSettings', 'visibleElements');
        return $action->execute($request);
    }

    /**
     * Template settings
     */
    public function executeTemplate(sfWebRequest $request)
    {
        require_once __DIR__ . '/templateAction.class.php';
        $action = new AhgSettingsTemplateAction($this->context, 'ahgSettings', 'template');
        return $action->execute($request);
    }

    /**
     * Finding Aid settings
     */
    public function executeFindingAid(sfWebRequest $request)
    {
        require_once __DIR__ . '/findingAidAction.class.php';
        $action = new AhgSettingsFindingAidAction($this->context, 'ahgSettings', 'findingAid');
        return $action->execute($request);
    }

    /**
     * OAI settings
     */
    public function executeOai(sfWebRequest $request)
    {
        require_once __DIR__ . '/oaiAction.class.php';
        $action = new AhgSettingsOaiAction($this->context, 'ahgSettings', 'oai');
        return $action->execute($request);
    }

    /**
     * Identifier settings
     */
    public function executeIdentifier(sfWebRequest $request)
    {
        require_once __DIR__ . '/identifierAction.class.php';
        $action = new AhgSettingsIdentifierAction($this->context, 'ahgSettings', 'identifier');
        return $action->execute($request);
    }

    /**
     * Diacritics settings
     */
    public function executeDiacritics(sfWebRequest $request)
    {
        require_once __DIR__ . '/diacriticsAction.class.php';
        $action = new AhgSettingsDiacriticsAction($this->context, 'ahgSettings', 'diacritics');
        return $action->execute($request);
    }

    /**
     * CSV Validator
     */
    public function executeCsvValidator(sfWebRequest $request)
    {
        require_once __DIR__ . '/csvValidatorAction.class.php';
        $action = new AhgSettingsCsvValidatorAction($this->context, 'ahgSettings', 'csvValidator');
        return $action->execute($request);
    }

    /**
     * Clipboard
     */
    public function executeClipboard(sfWebRequest $request)
    {
        require_once __DIR__ . '/clipboardAction.class.php';
        $action = new AhgSettingsClipboardAction($this->context, 'ahgSettings', 'clipboard');
        return $action->execute($request);
    }

    /**
     * DAM Tools
     */
    public function executeDamTools(sfWebRequest $request)
    {
        require_once __DIR__ . '/damToolsAction.class.php';
        $action = new AhgSettingsDamToolsAction($this->context, 'ahgSettings', 'damTools');
        return $action->execute($request);
    }

    /**
     * Delete action
     */
    public function executeDelete(sfWebRequest $request)
    {
        require_once __DIR__ . '/deleteAction.class.php';
        $action = new AhgSettingsDeleteAction($this->context, 'ahgSettings', 'delete');
        return $action->execute($request);
    }

    /**
     * DIP Upload
     */
    public function executeDipUpload(sfWebRequest $request)
    {
        require_once __DIR__ . '/dipUploadAction.class.php';
        $action = new AhgSettingsDipUploadAction($this->context, 'ahgSettings', 'dipUpload');
        return $action->execute($request);
    }

    /**
     * Digital Object Derivatives
     */
    public function executeDigitalObjectDerivatives(sfWebRequest $request)
    {
        require_once __DIR__ . '/digitalObjectDerivativesAction.class.php';
        $action = new AhgSettingsDigitalObjectDerivativesAction($this->context, 'ahgSettings', 'digitalObjectDerivatives');
        return $action->execute($request);
    }

    /**
     * Edit action
     */
    public function executeEdit(sfWebRequest $request)
    {
        require_once __DIR__ . '/editAction.class.php';
        $action = new AhgSettingsEditAction($this->context, 'ahgSettings', 'edit');
        return $action->execute($request);
    }

    /**
     * Fuseki Test
     */
    public function executeFusekiTest(sfWebRequest $request)
    {
        require_once __DIR__ . '/fusekiTestAction.class.php';
        $action = new AhgSettingsFusekiTestAction($this->context, 'ahgSettings', 'fusekiTest');
        return $action->execute($request);
    }

    /**
     * Inventory
     */
    public function executeInventory(sfWebRequest $request)
    {
        require_once __DIR__ . '/inventoryAction.class.php';
        $action = new AhgSettingsInventoryAction($this->context, 'ahgSettings', 'inventory');
        return $action->execute($request);
    }

    /**
     * Interface Label
     */
    public function executeInterfaceLabel(sfWebRequest $request)
    {
        require_once __DIR__ . '/interfaceLabelAction.class.php';
        $action = new AhgSettingsInterfaceLabelAction($this->context, 'ahgSettings', 'interfaceLabel');
        return $action->execute($request);
    }

    /**
     * LDAP settings
     */
    public function executeLdap(sfWebRequest $request)
    {
        require_once __DIR__ . '/ldapAction.class.php';
        $action = new AhgSettingsLdapAction($this->context, 'ahgSettings', 'ldap');
        return $action->execute($request);
    }

    /**
     * Markdown settings
     */
    public function executeMarkdown(sfWebRequest $request)
    {
        require_once __DIR__ . '/markdownAction.class.php';
        $action = new AhgSettingsMarkdownAction($this->context, 'ahgSettings', 'markdown');
        return $action->execute($request);
    }

    /**
     * Preservation settings
     */
    public function executePreservation(sfWebRequest $request)
    {
        require_once __DIR__ . '/preservationAction.class.php';
        $action = new AhgSettingsPreservationAction($this->context, 'ahgSettings', 'preservation');
        return $action->execute($request);
    }

    /**
     * Privacy Notification
     */
    public function executePrivacyNotification(sfWebRequest $request)
    {
        require_once __DIR__ . '/privacyNotificationAction.class.php';
        $action = new AhgSettingsPrivacyNotificationAction($this->context, 'ahgSettings', 'privacyNotification');
        return $action->execute($request);
    }

    /**
     * Reset settings
     */
    public function executeReset(sfWebRequest $request)
    {
        require_once __DIR__ . '/resetAction.class.php';
        $action = new AhgSettingsResetAction($this->context, 'ahgSettings', 'reset');
        return $action->execute($request);
    }

    /**
     * Save TIFF PDF Settings
     */
    public function executeSaveTiffPdfSettings(sfWebRequest $request)
    {
        require_once __DIR__ . '/saveTiffPdfSettingsAction.class.php';
        $action = new AhgSettingsSaveTiffPdfSettingsAction($this->context, 'ahgSettings', 'saveTiffPdfSettings');
        return $action->execute($request);
    }
}
