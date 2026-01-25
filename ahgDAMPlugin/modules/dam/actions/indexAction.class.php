<?php
/*
 * DAM (IPTC/XMP) Index Action - View page
 */

// Load AhgAccessGate for embargo checks
require_once sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/Access/AhgAccessGate.php';

class damIndexAction extends InformationObjectIndexAction
{
    public function execute($request)
    {
        parent::execute($request);

        // Check if this is actually a DAM object - if not, redirect to the correct URL
        // This prevents /dam/slug from displaying non-DAM items
        $objectType = \Illuminate\Database\Capsule\Manager::table('display_object_config')
            ->where('object_id', $this->resource->id)
            ->value('object_type');

        // If not a DAM item, redirect to the standard slug route
        if ($objectType !== 'dam') {
            $this->redirect('@slug?slug=' . $this->resource->slug);
        }

        // Check embargo access
        if (!\AhgCore\Access\AhgAccessGate::canView($this->resource->id, $this)) {
            return sfView::NONE;
        }

        // Load digital object for display
        $this->digitalObject = $this->resource->getDigitalObject();

        // Load IPTC metadata
        $this->iptc = \Illuminate\Database\Capsule\Manager::table('dam_iptc_metadata')
            ->where('object_id', $this->resource->id)
            ->first();

        // Load item physical location
        \AhgCore\Core\AhgDb::init();
        $locRepoPath = sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
        if (file_exists($locRepoPath)) {
            require_once $locRepoPath;
        }
        $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
        $this->itemLocation = $locRepo->getLocationWithContainer($this->resource->id) ?? [];
    }
}
