<?php

/**
 * Library item view action.
 */
class ahgLibraryPluginIndexAction extends sfAction
{
    public function execute($request)
    {
        // Load framework
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        require_once $frameworkPath . '/bootstrap.php';

        $slug = $request->getParameter('slug');

        // Debug logging
        sfContext::getInstance()->getLogger()->info('ahgLibraryPlugin/index: slug = ' . var_export($slug, true));

        if (empty($slug)) {
            sfContext::getInstance()->getLogger()->err('ahgLibraryPlugin/index: Empty slug');
            $this->forward404('No slug provided');
        }

        // Load the information object by slug
        $this->resource = QubitInformationObject::getBySlug($slug);

        if (!$this->resource) {
            sfContext::getInstance()->getLogger()->err('ahgLibraryPlugin/index: Resource not found for slug: ' . $slug);
            
            // Try to find by ID if slug looks like a number
            if (is_numeric($slug)) {
                $this->resource = QubitInformationObject::getById((int)$slug);
            }
        }

        if (!$this->resource) {
            $this->forward404('Library item not found: ' . $slug);
        }

        // Load library extended data
        $this->loadLibraryData($this->resource->id);

        // Load digital object if exists
        $this->digitalObject = $this->resource->getDigitalObject();
    }

    /**
     * Load library extended data including creators and subjects.
     */
    protected function loadLibraryData(int $informationObjectId): void
    {
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/LibraryItemRepository.php';

        $repo = new \AtomFramework\Repositories\LibraryItemRepository();

        // Load library_item data
        $this->libraryData = $repo->getLibraryData($informationObjectId) ?? [];

        // Get library item ID
        $libraryItemId = $repo->getLibraryItemId($informationObjectId);

        if ($libraryItemId) {
            // Load creators
            $this->libraryData['creators'] = $repo->getCreators($libraryItemId);

            // Load subjects
            $this->libraryData['subjects'] = $repo->getSubjects($libraryItemId);
        } else {
            $this->libraryData['creators'] = [];
            $this->libraryData['subjects'] = [];
        }
        // Load item physical location
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
        $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
        $this->itemLocation = $locRepo->getLocationWithContainer($informationObjectId) ?? [];
	}
}

