<?php

use AtomFramework\Http\Controllers\AhgController;
class extendedRightsEmbargoStatusAction extends AhgController
{
    public function execute($request)
    {
        $this->objectId = $request->getParameter('objectId');
    }
}
