<?php

class indexAction extends InformationObjectIndexAction
{
    public function execute($request)
    {
        // Check if this should redirect to a sector-specific module
        $slug = $request->getParameter('slug');
        
        if ($slug) {
            $sector = $this->getSectorForSlug($slug);
            
            if ($sector === 'library') {
                $this->redirect('/library/' . $slug);
                return sfView::NONE;
            }
            // Add more sector redirects as needed
        }
        
        // Call parent for normal informationobject display
        return parent::execute($request);
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
