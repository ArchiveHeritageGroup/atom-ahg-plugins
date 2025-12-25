<?php

/**
 * Repository Report Action - Framework v2.
 *
 * Browse archival repositories with filters.
 * Uses Laravel Query Builder, no Qubit/Propel dependencies.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class reportsReportRepositoryAction extends BaseReportAction
{
    public function execute($request)
    {
        // Create and configure form
        $this->form = $this->createReportForm();
        
        // Bind request parameters with defaults
        $this->form->bind(
            $request->getRequestParameters() + 
            $request->getGetParameters() + 
            $this->getDefaultParameters()
        );

        // Execute search if form is valid
        if ($this->form->isValid()) {
            $this->executeSearch();
        }
    }

    /**
     * Execute the report search using the service layer.
     */
    private function executeSearch(): void
    {
        try {
            // Create filter from form
            $filter = \AtomExtensions\Reports\Filters\ReportFilter::fromForm($this->form);

            // Get report service
            $service = $this->getReportService();

            // Execute search
            $searchResults = $service->search($filter);

            // Create results for template
            $this->results = $searchResults['results'];
            $this->total = $searchResults['total'];
            
            // Get statistics for dashboard
            $this->statistics = $service->getStatistics();
            
        } catch (Exception $e) {
            $this->getReportLogger()->error('Repository report failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->getUser()->setFlash('error', 'Error: ' . $e->getMessage());
            error_log('Repository Report Error: ' . $e->getMessage());
        }
    }

    /**
     * Get the report service with dependencies.
     */
    private function getReportService(): \AtomExtensions\Reports\Services\RepositoryReportService
    {
        $repository = new \AtomExtensions\Repositories\RepositoryRepository();
        $termService = new \AtomExtensions\Services\TermService(sfContext::getInstance()->getUser()->getCulture());
        $logger = $this->getReportLogger();

        return new \AtomExtensions\Reports\Services\RepositoryReportService(
            $repository,
            $termService,
            $logger
        );
    }

    /**
     * Create the report form with all fields.
     */
    private function createReportForm(): sfForm
    {
        $form = new sfForm([], [], false);
        $form->getValidatorSchema()->setOption('allow_extra_fields', true);

        // Add standard report fields using FormFieldFactory
        \AtomExtensions\Forms\FormFieldFactory::addDateFields($form);
        \AtomExtensions\Forms\FormFieldFactory::addControlFields($form);

        return $form;
    }

    /**
     * Get default form parameters.
     */
    private function getDefaultParameters(): array
    {
        return [
            'className' => 'QubitRepository',
            'dateStart' => date('Y-m-d', strtotime('-1 month')),
            'dateEnd' => date('Y-m-d'),
            'dateOf' => 'CREATED_AT',
            'limit' => '20',
            'sort' => 'updatedDown',
            'page' => '1',
        ];
    }

}