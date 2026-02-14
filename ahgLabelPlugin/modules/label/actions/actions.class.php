<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Label module - Barcode/label generation for all GLAM sectors.
 */
class labelActions extends AhgController
{
    public function executeIndex($request)
    {
        $slug = $request->getParameter('slug');

        // Dual-mode: EntityQueryService (standalone) or Propel (legacy)
        if (class_exists('\\AtomFramework\\Services\\EntityQueryService')) {
            $entity = \AtomFramework\Services\EntityQueryService::findBySlug($slug);
            if ($entity) {
                $this->resource = new \AtomFramework\Services\LightweightResource($entity);
            }
        } else {
            $this->resource = QubitInformationObject::getBySlug($slug);
        }

        if (!$this->resource) {
            $this->forward404();
        }
        
        $this->labelType = $request->getParameter('type', 'full');
        $this->labelSize = $request->getParameter('size', 'medium');
    }
}
