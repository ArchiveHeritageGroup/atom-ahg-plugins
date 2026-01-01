<?php
/**
 * Filter to redirect information objects to sector-specific modules
 */
class SectorRedirectFilter extends sfFilter
{
    public function execute($filterChain)
    {
        $moduleName = $this->context->getModuleName();
        $actionName = $this->context->getActionName();
        
        // Only intercept informationobject index (view) action
        if ($moduleName === 'informationobject' && $actionName === 'index') {
            $request = $this->context->getRequest();
            $slug = $request->getParameter('slug');
            
            if ($slug) {
                $sector = $this->getSectorForSlug($slug);
                
                if ($sector === 'library') {
                    $this->context->getController()->redirect('/library/' . $slug);
                    return;
                }
                // Add more sector redirects here as needed:
                // if ($sector === 'museum') { redirect to museum module }
                // if ($sector === 'gallery') { redirect to gallery module }
            }
        }
        
        $filterChain->execute();
    }
    
    protected function getSectorForSlug($slug)
    {
        try {
            $result = \Illuminate\Database\Capsule\Manager::table('slug')
                ->join('information_object', 'slug.object_id', '=', 'information_object.id')
                ->join('level_of_description_sector', 'information_object.level_of_description_id', '=', 'level_of_description_sector.term_id')
                ->where('slug.slug', $slug)
                ->select('level_of_description_sector.sector')
                ->first();
            
            return $result ? $result->sector : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
