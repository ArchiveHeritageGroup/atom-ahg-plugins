<?php

/**
 * Physical Storage Report Action - Framework v2.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class reportsReportPhysicalStorageAction extends BaseReportAction
{
    public function execute($request)
    {
        $this->form = $this->createReportForm();
        
        $this->form->bind(
            $request->getRequestParameters() + 
            $request->getGetParameters() + 
            $this->getDefaultParameters()
        );

        if ($this->form->isValid()) {
            $this->executeSearch();
        }
    }

    private function executeSearch(): void
    {
        try {
            $filter = \AtomExtensions\Reports\Filters\ReportFilter::fromForm($this->form);
            $service = $this->getReportService();
            $searchResults = $service->search($filter);

            $this->results = $searchResults['results'];
            $this->total = $searchResults['total'];
            $this->statistics = $service->getStatistics();
            
        } catch (Exception $e) {
            $this->getReportLogger()->error('Physical storage report failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->getUser()->setFlash('error', 'Error: ' . $e->getMessage());
            error_log('Physical Storage Report Error: ' . $e->getMessage());
        }
    }

    private function getReportService(): \AtomExtensions\Reports\Services\PhysicalObjectReportService
    {
        $repository = new \AtomExtensions\Repositories\PhysicalObjectRepository();
        $termService = new \AtomExtensions\Services\TermService('en');
        $logger = $this->getReportLogger();

        return new \AtomExtensions\Reports\Services\PhysicalObjectReportService(
            $repository,
            $termService,
            $logger
        );
    }

    private function createReportForm(): sfForm
    {
        $form = new sfForm([], [], false);
        $form->getValidatorSchema()->setOption('allow_extra_fields', true);

        \AtomExtensions\Forms\FormFieldFactory::addDateFields($form);
        \AtomExtensions\Forms\FormFieldFactory::addControlFields($form);
        
        $this->addCultureField($form);
        $this->addRepositoryField($form);
        $this->addShowLinkedIOField($form);

        return $form;
    }

    private function addCultureField(sfForm $form): void
    {
        $repository = new \AtomExtensions\Repositories\PhysicalObjectRepository();
        $cultures = $repository->getAvailableCultures();
        
        $choices = [];
        foreach ($cultures as $culture) {
            $choices[$culture] = $this->getCultureLabel($culture);
        }
        
        $form->setValidator('culture', new sfValidatorChoice([
            'choices' => array_keys($choices),
            'required' => false
        ]));
        
        $form->setWidget('culture', new sfWidgetFormSelect([
            'choices' => $choices
        ]));
    }

    private function addRepositoryField(sfForm $form): void
    {
        $repository = new \AtomExtensions\Repositories\PhysicalObjectRepository();
        $repositories = $repository->getAvailableRepositories('en');
        
        $choices = ['' => 'All repositories'];
        foreach ($repositories as $repo) {
            $choices[$repo->id] = $repo->name;
        }
        
        $form->setValidator('repositoryId', new sfValidatorChoice([
            'choices' => array_keys($choices),
            'required' => false
        ]));
        
        $form->setWidget('repositoryId', new sfWidgetFormSelect([
            'choices' => $choices
        ]));
    }

    private function addShowLinkedIOField(sfForm $form): void
    {
        $form->setValidator('showLinkedIO', new sfValidatorBoolean([
            'required' => false
        ]));
        
        $form->setWidget('showLinkedIO', new sfWidgetFormInputCheckbox());
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

    private function getDefaultParameters(): array
    {
        return [
            'className' => 'QubitPhysicalObject',
            'dateStart' => date('Y-m-d', strtotime('-1 year')),
            'dateEnd' => date('Y-m-d'),
            'dateOf' => 'CREATED_AT',
            'culture' => 'en',
            'repositoryId' => '',
            'showLinkedIO' => false,
            'limit' => '20',
            'page' => '1',
        ];
    }
}
