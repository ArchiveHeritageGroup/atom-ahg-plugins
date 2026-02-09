<?php

/**
 * Label module - Barcode/label generation for all GLAM sectors.
 */
class labelActions extends AhgActions
{
    public function executeIndex(sfWebRequest $request)
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
