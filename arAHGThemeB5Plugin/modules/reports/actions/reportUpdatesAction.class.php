<?php
use AtomExtensions\Services\AclService;

/**
 * Updates Report Action - Framework v2.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class reportsReportUpdatesAction extends BaseReportAction
{
    public function execute($request)
    {
        if (!$this->hasReportAccess()) {
            AclService::forwardUnauthorized();
        }

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
            $this->getReportLogger()->error('Updates report failed', [
                'error' => $e->getMessage(),
            ]);
            
            $this->getUser()->setFlash('error', 'Error: ' . $e->getMessage());
        }
    }

    private function getReportService(): \AtomExtensions\Reports\Services\UpdatesReportService
    {
        $logger = $this->getReportLogger();
        return new \AtomExtensions\Reports\Services\UpdatesReportService($logger);
    }

    private function createReportForm(): sfForm
    {
        $form = new sfForm([], [], false);
        $form->getValidatorSchema()->setOption('allow_extra_fields', true);

        \AtomExtensions\Forms\FormFieldFactory::addDateFields($form);
        \AtomExtensions\Forms\FormFieldFactory::addControlFields($form);
        
        $this->addClassNameField($form);

        return $form;
    }

    private function addClassNameField(sfForm $form): void
    {
        $choices = [
            'all' => 'All types',
            'QubitInformationObject' => 'Information Objects',
            'QubitActor' => 'Authority Records',
            'QubitRepository' => 'Repositories',
            'QubitAccession' => 'Accessions',
            'QubitPhysicalObject' => 'Physical Storage',
            'QubitDonor' => 'Donors',
        ];
        
        $form->setValidator('className', new sfValidatorChoice([
            'choices' => array_keys($choices),
            'required' => false
        ]));
        
        $form->setWidget('className', new sfWidgetFormSelect([
            'choices' => $choices
        ]));
    }

    private function getDefaultParameters(): array
    {
        return [
            'className' => 'all',
            'dateStart' => date('Y-m-d', strtotime('-1 month')),
            'dateEnd' => date('Y-m-d'),
            'dateOf' => 'UPDATED_AT',
            'limit' => '20',
            'page' => '1',
        ];
    }
}
