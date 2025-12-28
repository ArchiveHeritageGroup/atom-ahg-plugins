<?php

// Include parent actions
require_once sfConfig::get('sf_root_dir') . '/apps/qubit/modules/physicalobject/actions/editAction.class.php';
require_once sfConfig::get('sf_root_dir') . '/apps/qubit/modules/physicalobject/actions/indexAction.class.php';
require_once sfConfig::get('sf_root_dir') . '/apps/qubit/modules/physicalobject/actions/browseAction.class.php';
require_once sfConfig::get('sf_root_dir') . '/apps/qubit/modules/physicalobject/actions/deleteAction.class.php';

/**
 * Extended Physical Object Actions
 */
class physicalobjectActions extends sfActions
{
    public function executeIndex($request)
    {
        // Load framework for extended data
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/PhysicalObjectExtendedRepository.php';

        // Get resource from route
        $this->resource = $this->getRoute()->resource;

        // Load extended data
        $this->extendedData = [];
        if ($this->resource && $this->resource->id) {
            $repo = new \AtomFramework\Repositories\PhysicalObjectExtendedRepository();
            $this->extendedData = $repo->getExtendedData($this->resource->id) ?? [];
        }
    }

    public function executeEdit($request)
    {
        // Load framework
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/PhysicalObjectExtendedRepository.php';

        // Set up resource
        $this->resource = new QubitPhysicalObject();
        if (isset($this->getRoute()->resource)) {
            $this->resource = $this->getRoute()->resource;
        }

        // Set page title
        $title = $this->context->i18n->__('Add new physical storage');
        if (isset($this->getRoute()->resource)) {
            if (1 > strlen($title = $this->resource->__toString())) {
                $title = $this->context->i18n->__('Untitled');
            }
            $title = $this->context->i18n->__('Edit %1%', ['%1%' => $title]);
        }
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        // Load extended data
        $this->extendedData = [];
        if ($this->resource && $this->resource->id) {
            $repo = new \AtomFramework\Repositories\PhysicalObjectExtendedRepository();
            $this->extendedData = $repo->getExtendedData($this->resource->id) ?? [];
        }

        // Load type choices for dropdown
        $this->typeChoices = QubitTerm::getIndentedChildTree(QubitTerm::CONTAINER_ID, '&nbsp;', ['returnObjectInstances' => true]);

        // Handle POST
        if ($request->isMethod('post')) {
            // Save basic fields directly
            $this->resource->name = $request->getParameter('name');
            $this->resource->location = $request->getParameter('location');
            
            // Handle type
            $typeValue = $request->getParameter('type');
            if ($typeValue) {
                unset($this->resource->type);
                $params = $this->context->routing->parse(Qubit::pathInfo($typeValue));
                $this->resource->type = $params['_sf_route']->resource;
            }
            
            $this->resource->save();

            // Save extended data
            $repo = new \AtomFramework\Repositories\PhysicalObjectExtendedRepository();
            $extendedData = [
                'building' => $request->getParameter('building'),
                'floor' => $request->getParameter('floor'),
                'room' => $request->getParameter('room'),
                'aisle' => $request->getParameter('aisle'),
                'bay' => $request->getParameter('bay'),
                'rack' => $request->getParameter('rack'),
                'shelf' => $request->getParameter('shelf'),
                'position' => $request->getParameter('position'),
                'barcode' => $request->getParameter('barcode'),
                'reference_code' => $request->getParameter('reference_code'),
                'width' => $request->getParameter('width') ?: null,
                'height' => $request->getParameter('height') ?: null,
                'depth' => $request->getParameter('depth') ?: null,
                'total_capacity' => $request->getParameter('total_capacity') ?: null,
                'used_capacity' => $request->getParameter('used_capacity') ?: 0,
                'capacity_unit' => $request->getParameter('capacity_unit'),
                'total_linear_metres' => $request->getParameter('total_linear_metres') ?: null,
                'used_linear_metres' => $request->getParameter('used_linear_metres') ?: 0,
                'climate_controlled' => $request->getParameter('climate_controlled') ? 1 : 0,
                'temperature_min' => $request->getParameter('temperature_min') ?: null,
                'temperature_max' => $request->getParameter('temperature_max') ?: null,
                'humidity_min' => $request->getParameter('humidity_min') ?: null,
                'humidity_max' => $request->getParameter('humidity_max') ?: null,
                'security_level' => $request->getParameter('security_level'),
                'access_restrictions' => $request->getParameter('access_restrictions'),
                'status' => $request->getParameter('status') ?: 'active',
                'notes' => $request->getParameter('notes'),
            ];
            $repo->saveExtendedData($this->resource->id, $extendedData);

            $next = $request->getParameter('next');
            if ($next) {
                $this->redirect($next);
            }

            $this->redirect([$this->resource, 'module' => 'physicalobject']);
        }
    }

    public function executeBrowse($request)
    {
        $action = new PhysicalObjectBrowseAction($this->context, 'physicalobject', 'browse');
        $action->execute($request);
        
        // Copy variables from action to this controller
        foreach (get_object_vars($action) as $key => $value) {
            $this->$key = $value;
        }
        
        return sfView::SUCCESS;
    }

    public function executeDelete($request)
    {
        $action = new PhysicalObjectDeleteAction($this->context, 'physicalobject', 'delete');
        return $action->execute($request);
    }
}
