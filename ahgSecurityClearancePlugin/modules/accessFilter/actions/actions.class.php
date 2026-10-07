<?php

use AtomFramework\Http\Controllers\AhgController;
class accessFilterActions extends AhgController
{
    public function executeDenied($request)
    {
        $this->objectId = $request->getParameter('id');
        $this->slug = $request->getParameter('slug');
        
        $userId = $this->getUser()->isAuthenticated() 
            ? $this->getUser()->getAttribute('user_id') 
            : null;
        
        $service = \AtomExtensions\Services\Access\AccessFilterService::getInstance();
        $this->access = $service->checkAccess((int)$this->objectId, $userId);
        $this->userContext = $service->getUserContext($userId);
        
        // Get object title - withheld when the record itself is hidden, since
        // the title of classified material can be sensitive too.
        $this->objectTitle = $request->getAttribute('ahg_hide_title') ? 'This record' : \Illuminate\Database\Capsule\Manager::table('information_object_i18n')
            ->where('id', $this->objectId)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->value('title') ?? 'Unknown';

        $this->getResponse()->setStatusCode(403);
    }
}
