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
/**
 * Display a list of selectable reports.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class reportsreportSelectAction extends AhgEditController
{
    // Arrays not allowed in class constants
    public static $NAMES = ['collection'];

    public function execute($request)
    {
        parent::execute($request);

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->processForm();
                if ('audit_trail' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportAuditTrail',
                    ];
                } elseif ('access' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportAccess',
                    ];
                } elseif ('accession' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportAccession',
                    ];
                } elseif ('booked_in' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportBookIn',
                    ];
                } elseif ('booked_out' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportBookOut',
                        'reportType' => $request->getParameter('objectType'),
                    ];
                } elseif ('informationObject' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportInformationObject',
                    ];
                } elseif ('repository' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportRepository',
                    ];
                } elseif ('preservation' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportPreservation',
                    ];
                } elseif ('authorityRecord' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportAuthorityRecord',
                    ];
                } elseif ('registry' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportRegistry',
                    ];
                } elseif ('researcher' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportResearcher',
                    ];
                } elseif ('donor' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportDonor',
                    ];
                } elseif ('physical_storage' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportPhysicalStorage',
                    ];
                } elseif ('service_provider' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportServiceProvider',
                    ];
                } elseif ('user' == $request->getParameter('objectType')) {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportUser',
                    ];
                } else {
                    $reportSelectRoute = [
                        $this->getRoute()->resource,
                        'module' => 'reports',
                        'action' => 'reportUpdates',
                    ];
                }
                $this->redirect($reportSelectRoute);
            }
        }
    }

    protected function earlyExecute()
    {
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        if (isset($this->getRoute()->resource)) {
            $this->resource = $this->getRoute()->resource;

            $this->form->setDefault('parent', $this->context->routing->generate(null, [
                $this->resource,
            ]));
            $this->form->setValidator('parent', new sfValidatorString());
            $this->form->setWidget('parent', new sfWidgetFormInputHidden());
        }
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'collection':
                $this->form->setValidator($name, new sfValidatorString());
                $choices = [];

                if (isset($this->getParameters['collection']) && ctype_digit($this->getParameters['collection']) && null !== $collection = QubitInformationObject::getById($this->getParameters['collection'])) {
                    sfContext::getInstance()->getConfiguration()->loadHelpers([
                        'Url',
                    ]);
                    $collectionUrl = url_for($collection);
                    $this->form->setDefault($name, $collectionUrl);

                    $choices[$collectionUrl] = $collection;
                }
                $this->form->setWidget($name, new sfWidgetFormSelect([
                    'choices' => $choices,
                ]));

                break;

            default:
                return parent::addField($name);
        }
    }

    protected function processField($field)
    {
        switch ($field->getName()) {
            case 'collection':
                $url = $this->request->getPostParameter('collection');
                if (!empty($url)) {
                    $parts = explode('/', $url);
                    $this->collectionSlug = end($parts);
                }

                break;
        }
    }
}
