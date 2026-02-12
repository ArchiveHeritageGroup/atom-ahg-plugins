<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Library item view action.
 */

// Load AhgAccessGate for embargo checks
require_once sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/Access/AhgAccessGate.php';

class libraryIndexAction extends AhgController
{
    public function execute($request)
    {
        // Initialize database
        \AhgCore\Core\AhgDb::init();

        $slug = $request->getParameter('slug');

        if (empty($slug)) {
            $this->forward404('No slug provided');
        }

        // Load the information object by slug
        $this->resource = QubitInformationObject::getBySlug($slug);

        if (!$this->resource) {
            // Try to find by ID if slug looks like a number
            if (is_numeric($slug)) {
                $this->resource = QubitInformationObject::getById((int)$slug);
            }
        }

        if (!$this->resource) {
            $this->forward404('Library item not found: ' . $slug);
        }

        // Check read permission
        if (!\AtomExtensions\Services\AclService::check($this->resource, 'read')) {
            \AtomExtensions\Services\AclService::forwardToSecureAction();
        }

        // Check embargo access
        if (!\AhgCore\Access\AhgAccessGate::canView($this->resource->id, $this)) {
            return sfView::NONE;
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
        $repoPath = sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/LibraryItemRepository.php';
        if (file_exists($repoPath)) {
            require_once $repoPath;
        }
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
        $locRepoPath = sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
        if (file_exists($locRepoPath)) {
            require_once $locRepoPath;
        }
        $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
        $this->itemLocation = $locRepo->getLocationWithContainer($informationObjectId) ?? [];
    }
}
