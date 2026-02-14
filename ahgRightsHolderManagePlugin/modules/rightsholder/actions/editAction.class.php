<?php
use AtomFramework\Http\Controllers\AhgEditController;

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

class RightsHolderEditAction extends AhgEditController
{
    // Arrays not allowed in class constants
    public static $NAMES = [
        'authorizedFormOfName',
    ];

    public function execute($request)
    {
        parent::execute($request);

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->contactInformationEditComponent->processForm();

                $this->processForm();

                if (class_exists('\\AtomFramework\\Services\\Write\\WriteServiceFactory')) {
                    $this->resource->save(); // PropelBridge; Phase 4 replaces
                } else {
                    $this->resource->save();
                }

                $this->redirect([$this->resource, 'module' => 'rightsholder']);
            }
        }
    }

    protected function earlyExecute()
    {
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        if (class_exists('\\AtomFramework\\Services\\Write\\WriteServiceFactory')) {
            $this->resource = \AtomFramework\Services\Write\WriteServiceFactory::rightsHolder()->newRightsHolder();
        } else {
            $this->resource = new QubitRightsHolder();
        }

        if (isset($this->getRoute()->resource)) {
            $this->resource = $this->getRoute()->resource;

            // Check user authorization
            if (!\AtomExtensions\Services\AclService::check($this->resource, 'update')) {
                \AtomExtensions\Services\AclService::forwardUnauthorized();
            }

            // Add optimistic lock
            $this->form->setDefault('serialNumber', $this->resource->serialNumber);
            $this->form->setValidator('serialNumber', new sfValidatorInteger());
            $this->form->setWidget('serialNumber', new sfWidgetFormInputHidden());
        } else {
            // Check user authorization
            if (!\AtomExtensions\Services\AclService::check($this->resource, 'create')) {
                \AtomExtensions\Services\AclService::forwardUnauthorized();
            }
        }

        $title = $this->context->i18n->__('Add new rights holder');
        if (isset($this->getRoute()->resource)) {
            if (1 > strlen($title = $this->resource->__toString())) {
                $title = $this->context->i18n->__('Untitled');
            }

            $title = $this->context->i18n->__('Edit %1%', ['%1%' => $title]);
        }

        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        $this->contactInformationEditComponent = new ContactInformationEditComponent($this->context, 'contactinformation', 'editContactInformation');
        $this->contactInformationEditComponent->resource = $this->resource;
        $this->contactInformationEditComponent->execute($this->request);
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'authorizedFormOfName':
                $this->form->setDefault('authorizedFormOfName', $this->resource['authorizedFormOfName']);
                $this->form->setValidator('authorizedFormOfName', new sfValidatorString());
                $this->form->setWidget('authorizedFormOfName', new sfWidgetFormInput());

                break;

            default:
                return parent::addField($name);
        }
    }
}
