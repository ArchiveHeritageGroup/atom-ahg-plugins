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
        $this->resource = QubitInformationObject::getBySlug($slug);
        
        if (!$this->resource) {
            $this->forward404();
        }
        
        $this->labelType = $request->getParameter('type', 'full');
        $this->labelSize = $request->getParameter('size', 'medium');
    }
}
