<?php

class extendedRightsEmbargoStatusAction extends sfAction
{
    public function execute($request)
    {
        $this->objectId = $request->getParameter('objectId');
    }
}
