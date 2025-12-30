<?php
use Illuminate\Database\Capsule\Manager as DB;

class ahgGalleryPluginAddAction extends ahgGalleryPluginEditAction
{
    public function execute($request)
    {
        // Check for parent parameter
        $parentSlug = $request->getParameter('parent');
        if ($parentSlug) {
            // Get parent ID from slug
            $parent = DB::table('slug')
                ->where('slug', $parentSlug)
                ->first();
            
            if ($parent) {
                $this->parentId = $parent->object_id;
            }
        }
        
        // Call parent execute
        parent::execute($request);
    }
}
