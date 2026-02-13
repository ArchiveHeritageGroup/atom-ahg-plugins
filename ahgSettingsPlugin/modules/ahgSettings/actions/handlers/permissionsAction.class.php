<?php
use AtomExtensions\Services\SettingService;
use AtomExtensions\Services\HtmlPurifierService;
use AtomExtensions\Services\CacheService;
use AtomFramework\Http\Controllers\AhgController;

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
 * Permissions.
 *
 * @author     Peter Van Garderen <peter@artefactual.com>
 * @author     Jack Bates <jack@nottheoilrig.com>
 * @author     David Juhasz <david@artefactual.com>
 */
class SettingsPermissionsAction extends AhgController
{
    public function execute($request)
    {
        $this->permissionsForm = new SettingsPermissionsForm();
        $this->permissionsAccessStatementsForm = new SettingsPermissionsAccessStatementsForm();
        $this->permissionsCopyrightStatementForm = new SettingsPermissionsCopyrightStatementForm();
        $this->permissionsPreservationSystemAccessStatementForm = new SettingsPermissionsPreservationSystemAccessStatementForm();

        $this->basis = [];
        foreach (QubitTaxonomy::getTermsById(QubitTaxonomy::RIGHT_BASIS_ID) as $item) {
            $this->basis[$item->slug] = $item->getName(['cultureFallback' => true]);
        }

        $this->copyrightStatementSetting = SettingService::getByName('digitalobject_copyright_statement');
        $this->preservationSystemAccessStatementSetting = SettingService::getByName(
            'digitalobject_preservation_system_access_statement'
        );

        $this->response->addJavaScript('permissionsSettings', 'last');

        // Handle POST data (form submit)
        if ($request->isMethod('post')) {
            // Give the user the ability to preview the copyright statement before
            // we persist the changes. We are reusing the viewCopyrightStatement
            // template, populating the properties that are needed.
            if ($request->hasParameter('preview')) {
                $this->setTemplate('viewCopyrightStatement', 'digitalobject');

                $this->preview = true;
                $this->resource = new QubitInformationObject();

                $this->permissionsCopyrightStatementForm->bind($request->getPostParameters());
                $statementData = $this->permissionsCopyrightStatementForm['copyrightStatement']->getValue();
                $this->copyrightStatement = HtmlPurifierService::getInstance()->purify($statementData['copyrightStatement']);

                return sfView::SUCCESS;
            }

            CacheService::getInstance()->removePattern('settings:i18n:*');

            $values = $request->getPostParameters();
            $this->permissionsForm->bind($values['permissions']);
            $this->permissionsAccessStatementsForm->bind($values['accessStatements']);
            $this->permissionsCopyrightStatementForm->bind($values['copyrightStatement']);
            $this->permissionsPreservationSystemAccessStatementForm->bind($values['preservationSystemAccessStatement']);

            // Validate all forms at once and avoid redirection to show global errors
            if (
                !$this->permissionsForm->isValid()
                || !$this->permissionsAccessStatementsForm->isValid()
                || !$this->permissionsCopyrightStatementForm->isValid()
                || !$this->permissionsPreservationSystemAccessStatementForm->isValid()
            ) {
                return;
            }

            $acl = \AtomFramework\Services\Write\WriteServiceFactory::acl();

            // PREMIS access permissions
            $grantedRight = $this->permissionsForm->getValue('granted_right');
            $permissions = $this->permissionsForm->getValue('permissions');
            $acl->savePremisRights(
                is_array($grantedRight) ? $grantedRight : [$grantedRight],
                is_array($permissions) ? $permissions : []
            );

            // PREMIS access statements
            // Build the statements array: form values to save + orphaned DB entries to delete
            $accessValues = $this->permissionsAccessStatementsForm->getValues();
            $statements = [];

            foreach ($accessValues as $key => $value) {
                $statements[] = ['name' => $key, 'value' => $value];
            }

            // Mark orphaned settings (in DB but not in form) for deletion
            foreach (SettingService::getByScope('access_statement') as $setting) {
                if (!array_key_exists($setting->name, $accessValues)) {
                    $statements[] = ['name' => $setting->name, 'value' => null];
                }
            }

            $acl->saveAccessStatements($statements);

            // Copyright statement
            $copyrightEnabled = (bool) $this->permissionsCopyrightStatementForm->getValue('copyrightStatementEnabled');
            $copyrightText = $this->permissionsCopyrightStatementForm->getValue('copyrightStatement');
            $copyrightText = HtmlPurifierService::getInstance()->purify($copyrightText);
            $applyGlobally = (bool) $this->permissionsCopyrightStatementForm->getValue('copyrightStatementApplyGlobally');

            // Disable applying global copyright if the main setting is disabled too
            if (!$copyrightEnabled) {
                $applyGlobally = false;
            }

            $acl->saveCopyrightStatement(
                $copyrightEnabled,
                !empty($copyrightText) ? $copyrightText : null,
                $applyGlobally,
                $this->context->user->getCulture()
            );

            // Preservation system access statement
            $preservationEnabled = (bool) $this->permissionsPreservationSystemAccessStatementForm->getValue(
                'preservationSystemAccessStatementEnabled'
            );
            $preservationText = $this->permissionsPreservationSystemAccessStatementForm->getValue(
                'preservationSystemAccessStatement'
            );
            $preservationText = HtmlPurifierService::getInstance()->purify($preservationText);

            $acl->savePreservationStatement(
                $preservationEnabled,
                !empty($preservationText) ? $preservationText : null,
                $this->context->user->getCulture()
            );

            $notice = sfContext::getInstance()->i18n->__('Permissions saved.');
            $this->getUser()->setFlash('notice', $notice);

            $this->redirect('settings/permissions');
        }
    }
}
