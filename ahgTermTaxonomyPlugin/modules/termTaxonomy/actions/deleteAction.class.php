<?php

use AtomFramework\Http\Controllers\AhgController;

/*
 * AHG Term Taxonomy Plugin - Term Delete Action
 *
 * Migrates base AtoM TermDeleteAction to ahgTermTaxonomyPlugin.
 * Follows the same pattern as other AHG manage plugin delete actions.
 */

class TermTaxonomyDeleteAction extends AhgController
{
    public function execute($request)
    {
        $this->form = new sfForm();

        $this->resource = $this->getRoute()->resource;

        // Check that this isn't the root
        if (!isset($this->resource->parent)) {
            $this->forward404();
        }

        // Don't delete protected terms
        if (QubitTerm::isProtected($this->resource->id)) {
            $this->forward('admin', 'termPermission');
        }

        // Check user authorization
        if (!\AtomExtensions\Services\AclService::check($this->resource, 'delete')) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }

        if ($request->isMethod('delete')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->resource->deleteFullHierarchy();

                if (isset($this->resource->taxonomy)) {
                    $this->redirect([$this->resource->taxonomy, 'module' => 'taxonomy']);
                }

                $this->redirect(['module' => 'taxonomy', 'action' => 'list']);
            }
        }

        // Count descendants using MPTT nested set tree
        $this->count = ($this->resource->rgt - $this->resource->lft - 1) / 2;
        $this->previewSize = (int) sfConfig::get('app_hits_per_page', 10);
        $this->previewIsLimited = $this->count > $this->previewSize;
    }
}
