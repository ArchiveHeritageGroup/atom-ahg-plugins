<?php
/**
 * Homepage Redirect Filter
 * 
 * Redirects unauthenticated users to /heritage landing page
 * Authenticated users go to standard AtoM homepage
 */
class ahgHomepageFilter extends sfFilter
{
    public function execute($filterChain)
    {
        // Only run on first call (not on forwards)
        if ($this->isFirstCall()) {
            $request = $this->getContext()->getRequest();
            $user = $this->getContext()->getUser();
            
            // Get the current module and action
            $module = $request->getParameter('module');
            $action = $request->getParameter('action');
            
            // Check if this is the homepage request
            $isHomepage = ($module === 'staticpage' && $action === 'index') 
                       || ($request->getPathInfo() === '/' || $request->getPathInfo() === '/index.php');
            
            // If homepage and NOT authenticated, redirect to heritage
            if ($isHomepage && !$user->isAuthenticated()) {
                $this->getContext()->getController()->redirect('/heritage');
                
                return sfView::NONE;
            }
        }
        
        // Continue filter chain
        $filterChain->execute();
    }
}
