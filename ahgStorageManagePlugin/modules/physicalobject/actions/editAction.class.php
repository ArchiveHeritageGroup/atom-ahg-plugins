<?php
use AtomFramework\Http\Controllers\AhgEditController;
use AtomFramework\Services\Write\WriteServiceFactory;

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
 * Physical Object edit.
 *
 * @author     David Juhasz <david@artefactual.com>
 */
class PhysicalObjectEditAction extends AhgEditController
{
    public static $NAMES = [
        'location',
        'name',
        'type',
    ];

    public function execute($request)
    {
        parent::execute($request);

        // heratio#145 follow-up — load strongroom data for the edit form.
        $this->loadStrongroomViewData();

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());
            if ($this->form->isValid()) {
                $this->processForm();

                if (class_exists('\\AtomFramework\\Services\\Write\\WriteServiceFactory')) {
                    $this->resource->save(); // PropelBridge; Phase 4 replaces
                } else {
                    $this->resource->save();
                }

                // heratio#145 follow-up — apply strongroom assignment if requested.
                $this->applyStrongroomAssignment($request);

                if (null !== $next = $this->form->getValue('next')) {
                    $this->redirect($next);
                }

                $this->redirect([$this->resource, 'module' => 'physicalobject']);
            }
        }
    }

    /**
     * Populate $this->strongroomChoices + $this->currentAssignment for the view.
     * No-op if the strongroom feature is not yet installed.
     */
    private function loadStrongroomViewData()
    {
        $this->strongroomChoices = [];
        $this->currentAssignment = null;

        try {
            if (!\Illuminate\Database\Capsule\Manager::schema()->hasTable('ahg_strongroom')) {
                return;
            }
        } catch (Exception $e) {
            return;
        }

        if (!class_exists('\\AhgStorageManage\\Services\\StrongroomService')) {
            return;
        }

        $svc = new \AhgStorageManage\Services\StrongroomService();
        $this->strongroomChoices = $svc->dropdownChoices();
        if (isset($this->resource->id) && $this->resource->id > 0) {
            $this->currentAssignment = $svc->getAssignment((int) $this->resource->id);
        }
    }

    /**
     * Handle strongroom_action POST values: 'assign' / 'unassign' / '' (no-op).
     */
    private function applyStrongroomAssignment($request)
    {
        if (!class_exists('\\AhgStorageManage\\Services\\StrongroomService')) {
            return;
        }
        if (!isset($this->resource->id) || $this->resource->id <= 0) {
            return;
        }

        $action = (string) $request->getParameter('strongroom_action', '');
        $svc = new \AhgStorageManage\Services\StrongroomService();
        $physicalObjectId = (int) $this->resource->id;

        if ('unassign' === $action) {
            $svc->unassign($physicalObjectId);
            return;
        }
        if ('assign' === $action) {
            $roomId = (int) $request->getParameter('strongroom_id', 0);
            $size = (float) $request->getParameter('size_units_used', 0);
            if ($roomId > 0) {
                $svc->assign($physicalObjectId, $roomId, $size);
            }
        }
    }

    protected function earlyExecute()
    {
        $this->resource = WriteServiceFactory::physicalObject()->newPhysicalObject();
        if (isset($this->getRoute()->resource)) {
            $this->resource = $this->getRoute()->resource;
        }

        $title = $this->context->i18n->__('Add new physical storage');
        if (isset($this->getRoute()->resource)) {
            if (1 > strlen($title = $this->resource->__toString())) {
                $title = $this->context->i18n->__('Untitled');
            }

            $title = $this->context->i18n->__('Edit %1%', ['%1%' => $title]);
        }

        $this->response->setTitle("{$title} - {$this->response->getTitle()}");
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'location':
            case 'name':
                $this->form->setDefault($name, $this->resource[$name]);
                $this->form->setValidator($name, new sfValidatorString());
                $this->form->setWidget($name, new sfWidgetFormInput());

                break;

            case 'type':
                $this->form->setDefault('type', $this->context->routing->generate(null, [$this->resource->type, 'module' => 'term']));
                $this->form->setValidator('type', new sfValidatorString());
                $this->form->setWidget('type', new sfWidgetFormSelect(['choices' => QubitTerm::getIndentedChildTree(QubitTerm::CONTAINER_ID, '&nbsp;', ['returnObjectInstances' => true])]));

                break;

            default:
                return parent::addField($name);
        }
    }

    protected function processField($field)
    {
        switch ($field->getName()) {
            case 'type':
                unset($this->resource->type);

                $params = $this->context->routing->parse(Qubit::pathInfo($this->form->getValue('type')));
                $this->resource->type = $params['_sf_route']->resource;

                break;

            default:
                return parent::processField($field);
        }
    }
}
