<?php

class damAddAction extends damEditAction
{
    public function execute($request)
    {
        // Set DAM template before parent execute
        $this->request->setParameter('template', 'dam');

        parent::execute($request);
    }
}
