<?php

class extendedRightsEmbargoBlockedAction extends sfAction
{
    public function execute($request)
    {
        $this->embargoInfo = $request->getAttribute('embargoInfo', [
            'type' => 'full',
            'type_label' => 'Full Access Restriction',
            'public_message' => null,
            'end_date' => null,
            'is_perpetual' => true,
        ]);
        
        $this->objectId = $request->getAttribute('objectId');
        
        // Set 403 status
        $this->getResponse()->setStatusCode(403);
    }
}
