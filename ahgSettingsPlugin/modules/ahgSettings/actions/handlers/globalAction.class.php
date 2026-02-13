<?php

use AtomFramework\Http\Controllers\AhgController;
use AtomExtensions\Services\SettingService;
use AtomExtensions\Services\SlugService;
use AtomExtensions\Services\CacheService;

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Global settings.
 *
 * @author     Peter Van Garderen <peter@artefactual.com>
 * @author     Jack Bates <jack@nottheoilrig.com>
 * @author     David Juhasz <david@artefactual.com>
 */
class SettingsGlobalAction extends AhgController
{
    public function execute($request)
    {
        $this->globalForm = new SettingsGlobalForm();

        // Handle POST data (form submit)
        if ($request->isMethod('post')) {
            CacheService::getInstance()->removePattern('settings:i18n:*');

            // Global settings form submission
            if (null !== $request->global_settings) {
                // Hack to populate "version" field so it displays
                // if validation fails. By default, their values are not included in
                // $request->parameterHolder (and thus are not bound) because their
                // <input> field is disabled.
                $version = (null !== $setting = SettingService::getByName('version')) ? $setting->getValue(['sourceCulture' => true]) : null;
                $this->globalForm->bind(array_merge($request->global_settings, ['version' => $version]));
                if ($this->globalForm->isValid()) {
                    // Do update and redirect to avoid repeat submit wackiness
                    $this->updateGlobalSettings();

                    $notice = $this->getContext()->i18n->__('Global settings saved.');
                    $this->getUser()->setFlash('notice', $notice);

                    $this->redirect('settings/global');
                }
            }
        }

        $this->populateGlobalForm();

        // Build menu nodes for Blade sidebar
        $menuComponent = new \ahgSettingsMenuComponent($this->context, 'ahgSettings', 'global');
        $menuComponent->execute($this->getRequest());
        $this->menuNodes = $menuComponent->getVarHolder()->get('nodes', []);

        return [
            '_blade' => 'global',
            'globalForm' => $this->globalForm,
            'menuNodes' => $this->menuNodes,
        ];
    }

    /**
     * Populate the Global form with database values (non-localized).
     */
    protected function populateGlobalForm()
    {
        // Get global settings
        $version = qubitConfiguration::VERSION;
        if (null !== $setting = SettingService::getByName('version')) {
            $version .= ' - '.$setting->getValue(['sourceCulture' => true]);
        }

        $checkForUpdates = SettingService::getByName('check_for_updates');
        $hitsPerPage = SettingService::getByName('hits_per_page');
        $escapeQueries = SettingService::getByName('escape_queries');
        $sortBrowserUser = SettingService::getByName('sort_browser_user');
        $sortBrowserAnonymous = SettingService::getByName('sort_browser_anonymous');
        $defaultRepositoryView = SettingService::getByName('default_repository_browse_view');
        $defaultArchivalDescriptionView = SettingService::getByName('default_archival_description_browse_view');
        $multiRepository = SettingService::getByName('multi_repository');
        $auditLogEnabled = SettingService::getByName('audit_log_enabled');
        $showTooltips = SettingService::getByName('show_tooltips');
        $defaultPubStatus = SettingService::getByName('defaultPubStatus');
        $draftNotificationEnabled = SettingService::getByName('draft_notification_enabled');
        $swordDepositDir = SettingService::getByName('sword_deposit_dir');
        $googleMapsApiKey = SettingService::getByName('google_maps_api_key');
        $slugTypeInformationObject = SettingService::getByName('slug_basis_informationobject');
        $permissiveSlugCreation = SettingService::getByName('permissive_slug_creation');
        $generateReportsAsPubUser = SettingService::getByName('generate_reports_as_pub_user');
        $enableInstitutionalScoping = SettingService::getByName('enable_institutional_scoping');
        $cacheXmlOnSave = SettingService::getByName('cache_xml_on_save');

        // Set defaults for global form
        $this->globalForm->setDefaults([
            'version' => $version,
            'check_for_updates' => (isset($checkForUpdates)) ? intval($checkForUpdates->getValue(['sourceCulture' => true])) : 1,
            'hits_per_page' => (isset($hitsPerPage)) ? $hitsPerPage->getValue(['sourceCulture' => true]) : null,
            'escape_queries' => (isset($escapeQueries)) ? $escapeQueries->getValue(['sourceCulture' => true]) : null,
            'sort_browser_user' => (isset($sortBrowserUser)) ? $sortBrowserUser->getValue(['sourceCulture' => true]) : 0,
            'sort_browser_anonymous' => (isset($sortBrowserAnonymous)) ? $sortBrowserAnonymous->getValue(['sourceCulture' => true]) : 0,
            'default_repository_browse_view' => (isset($defaultRepositoryView)) ? $defaultRepositoryView->getValue(['sourceCulture' => true]) : 'card',
            'default_archival_description_browse_view' => (isset($defaultArchivalDescriptionView)) ? $defaultArchivalDescriptionView->getValue(['sourceCulture' => true]) : 'table',
            'multi_repository' => (isset($multiRepository)) ? intval($multiRepository->getValue(['sourceCulture' => true])) : 1,
            'audit_log_enabled' => (isset($auditLogEnabled)) ? intval($auditLogEnabled->getValue(['sourceCulture' => true])) : 0,
            'slug_basis_informationobject' => (isset($slugTypeInformationObject)) ? intval($slugTypeInformationObject->getValue(['sourceCulture' => true])) : SlugService::SLUG_BASIS_TITLE,
            'permissive_slug_creation' => (isset($permissiveSlugCreation)) ? intval($permissiveSlugCreation->getValue(['sourceCulture' => true])) : SlugService::SLUG_RESTRICTIVE,
            'show_tooltips' => (isset($showTooltips)) ? intval($showTooltips->getValue(['sourceCulture' => true])) : 1,
            'defaultPubStatus' => (isset($defaultPubStatus)) ? $defaultPubStatus->getValue(['sourceCulture' => true]) : QubitTerm::PUBLICATION_STATUS_DRAFT_ID,
            'draft_notification_enabled' => (isset($draftNotificationEnabled)) ? intval($draftNotificationEnabled->getValue(['sourceCulture' => true])) : 0,
            'sword_deposit_dir' => (isset($swordDepositDir)) ? $swordDepositDir->getValue(['sourceCulture' => true]) : null,
            'google_maps_api_key' => (isset($googleMapsApiKey)) ? $googleMapsApiKey->getValue(['sourceCulture' => true]) : null,
            'generate_reports_as_pub_user' => (isset($generateReportsAsPubUser)) ? $generateReportsAsPubUser->getValue(['sourceCulture' => true]) : 1,
            'enable_institutional_scoping' => (isset($enableInstitutionalScoping)) ? intval($enableInstitutionalScoping->getValue(['sourceCulture' => true])) : 0,
            'cache_xml_on_save' => (isset($cacheXmlOnSave)) ? intval($cacheXmlOnSave->getValue(['sourceCulture' => true])) : 0,
        ]);
    }

    /**
     * Update the global settings in database (non-localized).
     */
    protected function updateGlobalSettings()
    {
        $thisForm = $this->globalForm;
        $ws = \AtomFramework\Services\Write\WriteServiceFactory::settings();

        if (null !== $generateReportsAsPubUser = $thisForm->getValue('generate_reports_as_pub_user')) {
            $ws->save('generate_reports_as_pub_user', $generateReportsAsPubUser);
        }

        // Check for updates
        if (null !== $checkForUpdates = $thisForm->getValue('check_for_updates')) {
            $ws->save('check_for_updates', $checkForUpdates);
        }

        // Hits per page
        if (null !== $hitsPerPage = $thisForm->getValue('hits_per_page')) {
            if (intval($hitsPerPage) && $hitsPerPage > 0) {
                $ws->save('hits_per_page', $hitsPerPage);
            }
        }

        // Escape queries
        $ws->save('escape_queries', $thisForm->getValue('escape_queries'));

        // Sort Browser (for users)
        if (null !== $sortBrowserUser = $thisForm->getValue('sort_browser_user')) {
            $ws->save('sort_browser_user', $sortBrowserUser);
        }

        // Sort Browser (for anonymous)
        if (null !== $sortBrowserAnonymous = $thisForm->getValue('sort_browser_anonymous')) {
            $ws->save('sort_browser_anonymous', $sortBrowserAnonymous);
        }

        // Default repository browse page view
        if (null !== $defaultRepositoryView = $thisForm->getValue('default_repository_browse_view')) {
            $ws->save('default_repository_browse_view', $defaultRepositoryView);
        }

        // Default archival description browse page view
        if (null !== $defaultArchivalDescriptionView = $thisForm->getValue('default_archival_description_browse_view')) {
            $ws->save('default_archival_description_browse_view', $defaultArchivalDescriptionView);
        }

        // Multi-repository radio button
        if (null !== $multiRepositoryValue = $thisForm->getValue('multi_repository')) {
            $ws->save('multi_repository', $multiRepositoryValue);
        }

        // Audit log enabled
        if (null !== $auditLogEnabled = $thisForm->getValue('audit_log_enabled')) {
            $ws->save('audit_log_enabled', $auditLogEnabled);
        }

        if (null !== $slugTypeInformationObject = $thisForm->getValue('slug_basis_informationobject')) {
            $ws->save('slug_basis_informationobject', $slugTypeInformationObject);
        }

        if (null !== $permissiveSlugCreation = $thisForm->getValue('permissive_slug_creation')) {
            $ws->save('permissive_slug_creation', $permissiveSlugCreation);
        }

        // Show tooltips
        if (null !== $showTooltips = $thisForm->getValue('show_tooltips')) {
            $ws->save('show_tooltips', $showTooltips);
        }

        // Default publication status
        if (null !== $defaultPubStatus = $thisForm->getValue('defaultPubStatus')) {
            $ws->save('defaultPubStatus', $defaultPubStatus);
        }

        // Total drafts notification enabled
        if (null !== $draftNotificationEnabled = $thisForm->getValue('draft_notification_enabled')) {
            $ws->save('draft_notification_enabled', $draftNotificationEnabled);
        }

        // SWORD deposit directory
        if (null !== $swordDepositDir = $thisForm->getValue('sword_deposit_dir')) {
            $ws->save('sword_deposit_dir', $swordDepositDir);
        }

        // Google Maps Javascript API key
        $ws->save('google_maps_api_key', $thisForm->getValue('google_maps_api_key'));

        // Enable Institutional Scoping
        if (null !== $enableInstitutionalScoping = $thisForm->getValue('enable_institutional_scoping')) {
            $ws->save('enable_institutional_scoping', $enableInstitutionalScoping);
        }

        // Cache XML on save
        $ws->save('cache_xml_on_save', $thisForm->getValue('cache_xml_on_save'));

        return $this;
    }
}
