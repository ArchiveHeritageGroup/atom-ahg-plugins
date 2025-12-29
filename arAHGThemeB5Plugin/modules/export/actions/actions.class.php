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
        $this->exportFormats = [
            'ead' => ['name' => 'EAD 2002', 'description' => 'Encoded Archival Description'],
            'dc' => ['name' => 'Dublin Core', 'description' => 'Simple Dublin Core XML'],
            'mods' => ['name' => 'MODS', 'description' => 'Metadata Object Description Schema'],
            'csv' => ['name' => 'CSV', 'description' => 'Comma-separated values'],
            'json' => ['name' => 'JSON', 'description' => 'JavaScript Object Notation'],
        ];
    }

    /**
     * CSV Export
     */
    public function executeCsv(sfWebRequest $request)
    {
        $this->format = 'csv';
        $this->formatName = 'CSV Export (ISAD-G)';
    }

    /**
     * EAD Export
     */
    public function executeEad(sfWebRequest $request)
    {
        $this->format = 'ead';
        $this->formatName = 'EAD 2002 Export';
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
