<?php
/*
 * DAM (IPTC/XMP) Index Action - View page
 */

// Load AhgAccessGate for embargo checks
require_once sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/Access/AhgAccessGate.php';

class ahgDAMPluginIndexAction extends InformationObjectIndexAction
{
    public function execute($request)
    {
        parent::execute($request);

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
