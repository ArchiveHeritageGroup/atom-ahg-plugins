<?php

class exportActions extends sfActions
{
    public function preExecute()
    {
        parent::preExecute();
        
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }
    }
    
    /**
     * Export dashboard/index
     */
    public function executeIndex(sfWebRequest $request)
    {
        // Available export formats
        $this->exportFormats = [
            'ead' => ['name' => 'EAD 2002', 'description' => 'Encoded Archival Description'],
            'dc' => ['name' => 'Dublin Core', 'description' => 'Simple Dublin Core XML'],
            'mods' => ['name' => 'MODS', 'description' => 'Metadata Object Description Schema'],
            'csv' => ['name' => 'CSV', 'description' => 'Comma-separated values'],
            'json' => ['name' => 'JSON', 'description' => 'JavaScript Object Notation'],
        ];
    }
    
    /**
     * Archival descriptions export
     */
    public function executeArchival(sfWebRequest $request)
    {
        $this->format = $request->getParameter('format', 'ead');
    }
    
    /**
     * Authority records export
     */
    public function executeAuthority(sfWebRequest $request)
    {
        $this->format = $request->getParameter('format', 'eac');
    }
    
    /**
     * Repository export
     */
    public function executeRepository(sfWebRequest $request)
    {
        $this->format = $request->getParameter('format', 'csv');
    }
}
