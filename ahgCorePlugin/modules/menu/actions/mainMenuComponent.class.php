<?php

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
 * Build main user navigation menu as simple xhtml lists, relying on css styling to
 * format the display of the menus.
 *
 * @author     David Juhasz <david@artefactual.com>
 */
class MenuMainMenuComponent extends sfComponent
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            return sfView::NONE;
        }

        // Only include menu for adding content if user is in an appropriate group
        // or has create permission for a relevant content type
        $this->addMenu = false;

        // Specify what groups can add content
        $groupsAllowedToAddContent = [
            QubitAclGroup::CONTRIBUTOR_ID,
            QubitAclGroup::EDITOR_ID,
            QubitAclGroup::ADMINISTRATOR_ID,
        ];

        // Add, if applicable, menu for adding content
        if ($this->context->user->hasGroup($groupsAllowedToAddContent) || $this->userCanCreate()) {
            $this->addMenu = QubitMenu::getById(QubitMenu::ADD_EDIT_ID);
        }

        $this->manageMenu = QubitMenu::getById(QubitMenu::MANAGE_ID);
        $this->importMenu = QubitMenu::getById(QubitMenu::IMPORT_ID);
        $this->adminMenu = QubitMenu::getById(QubitMenu::ADMIN_ID);

        $this->icons = [
            'add' => 'plus-circle',
            'manage' => 'pen-square',
            'import' => 'download',
            'admin' => 'cog',
        ];
    }

    private function userCanCreate()
    {
        // Guard against missing root objects. QubitXxx::getById(ROOT_ID) can return
        // null on instances where a root sentinel is absent, and QubitAcl::check(null)
        // then calls get_class(null) - a PHP 8 TypeError. Thrown here (during the
        // header/main-menu render) it halts output AFTER the top nav is already sent,
        // so the error page can't render and everything below the nav goes blank.
        // Admins short-circuit before this (hasGroup); anonymous users are denied
        // earlier in the ACL; only authenticated non-admins hit it. Skip null roots.
        foreach ([
            QubitInformationObject::getById(QubitInformationObject::ROOT_ID),
            QubitActor::getById(QubitActor::ROOT_ID),
            QubitRepository::getById(QubitRepository::ROOT_ID),
            QubitTerm::getById(QubitTerm::ROOT_ID),
        ] as $root) {
            if (null !== $root && QubitAcl::check($root, 'create')) {
                return true;
            }
        }

        return false;
    }
}
