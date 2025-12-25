<?php
use AtomExtensions\Services\AclService;

/**
 * Donor Report Action - Framework v2 (No Symfony forms).
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class reportsReportDonorAction extends BaseReportAction
{
    public function execute($request)
    {
        if (!$this->hasReportAccess()) {
            AclService::forwardUnauthorized();
        }

        // Create form handler (no Symfony)
        $this->formHandler = $this->createFormHandler();
        
        // Bind request data
        $this->formHandler->bind(
            array_merge(
                $request->getParameter('_GET', []),
                $request->getParameter('_POST', [])
            )
        );

        // Pass form handler to template
        $this->form = $this->formHandler;

        if ($this->formHandler->isValid()) {
            $this->executeSearch();
        }
    }

    private function executeSearch(): void
    {
        try {
            // Create filter from form handler values
            $filter = new \AtomExtensions\Reports\Filters\ReportFilter(
                $this->formHandler->getValues()
            );

            $service = $this->getReportService();
            $searchResults = $service->search($filter);

            $this->results = $searchResults['results'];
            $this->total = $searchResults['total'];
            $this->statistics = $service->getStatistics();
            
        } catch (Exception $e) {
            $this->getReportLogger()->error('Donor report failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->getUser()->setFlash('error', 'Error: ' . $e->getMessage());
            error_log('Donor Report Error: ' . $e->getMessage());
        }
    }

    private function getReportService(): \AtomExtensions\Reports\Services\DonorReportService
    {
        $repository = new \AtomExtensions\Repositories\DonorRepository();
        $termService = new \AtomExtensions\Services\TermService(sfContext::getInstance()->getUser()->getCulture());
        $logger = $this->getReportLogger();

        return new \AtomExtensions\Reports\Services\DonorReportService(
            $repository,
            $termService,
            $logger
        );
    }

    private function createFormHandler(): \AtomExtensions\Forms\ReportFormHandler
    {
        $form = new \AtomExtensions\Forms\ReportFormHandler();

        // Get cultures from database
        $repository = new \AtomExtensions\Repositories\DonorRepository();
        $cultures = $repository->getAvailableCultures();
        
        $cultureChoices = [];
        foreach ($cultures as $culture) {
            $cultureChoices[$culture] = $this->getCultureLabel($culture);
        }

        // Add fields
        $form->addField('culture', 'choice', [
            'choices' => array_keys($cultureChoices),
            'labels' => $cultureChoices,
            'required' => false
        ]);

        $form->addField('dateStart', 'date', ['required' => false]);
        $form->addField('dateEnd', 'date', ['required' => false]);
        
        $form->addField('dateOf', 'choice', [
            'choices' => ['CREATED_AT', 'UPDATED_AT', 'both'],
            'labels' => [
                'CREATED_AT' => 'Created',
                'UPDATED_AT' => 'Updated',
                'both' => 'Both'
            ],
            'required' => false
        ]);

        $form->addField('limit', 'choice', [
            'choices' => ['10', '20', '50', '100'],
            'labels' => [
                '10' => '10',
                '20' => '20',
                '50' => '50',
                '100' => '100'
            ],
            'required' => false
        ]);

        $form->addField('page', 'hidden', ['required' => false]);
        $form->addField('sort', 'hidden', ['required' => false]);

        // Set defaults
        $form->setDefaults([
            'className' => 'QubitDonor',
            'dateStart' => date('Y-m-d', strtotime('-1 year')),
            'dateEnd' => date('Y-m-d'),
            'dateOf' => 'CREATED_AT',
            'culture' => sfContext::getInstance()->getUser()->getCulture(),
            'limit' => '20',
            'page' => '1',
        ]);

        return $form;
    }

    private function getCultureLabel(string $code): string
    {
        $labels = [
            'en' => 'English',
            'af' => 'Afrikaans',
            'zu' => 'Zulu',
        ];
        
        return $labels[$code] ?? strtoupper($code);
    }
}
