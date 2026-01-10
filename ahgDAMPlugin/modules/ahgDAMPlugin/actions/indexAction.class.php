<?php
/*
 * DAM (IPTC/XMP) Index Action - View page
 */
class ahgDAMPluginIndexAction extends InformationObjectIndexAction
{
    public function execute($request)
    {
        parent::execute($request);
        
        // Embargo access filter
        require_once sfConfig::get('sf_plugins_dir') . '/ahgExtendedRightsPlugin/lib/EmbargoAccessFilter.php';
        if (!EmbargoAccessFilter::checkAccess($this->resource->id, $this)) {
            return sfView::NONE;
        }
        
        // Load digital object for display
        $this->digitalObject = $this->resource->getDigitalObject();
        
        // Load IPTC metadata
        $this->iptc = \Illuminate\Database\Capsule\Manager::table('dam_iptc_metadata')
            ->where('object_id', $this->resource->id)
            ->first();
        
        // Load item physical location
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
        $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
        $this->itemLocation = $locRepo->getLocationWithContainer($this->resource->id) ?? [];
    }
}
