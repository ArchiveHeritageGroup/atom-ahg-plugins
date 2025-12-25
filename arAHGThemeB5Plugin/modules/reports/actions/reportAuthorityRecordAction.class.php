<?php

/**
 * Authority Record Report Action - Framework v2.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class reportsReportAuthorityRecordAction extends BaseReportAction
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
            $resultObject = $service->search($filter);
            
            // Convert to arrays for template
            $this->results = $resultObject->getItems()->all();
            $this->total = $resultObject->getTotal();
            $this->currentPage = $resultObject->getCurrentPage();
            $this->lastPage = $resultObject->getLastPage();
            $this->hasNext = $resultObject->hasNextPage();
            $this->hasPrevious = $resultObject->hasPreviousPage();
            $this->statistics = $service->getStatistics();
            
        } catch (Exception $e) {
            $this->getReportLogger()->error('Authority report failed', [
                'error' => $e->getMessage(),
            ]);
            $this->getUser()->setFlash('error', 'Error: ' . $e->getMessage());
        }
    }

    private function getReportService(): \AtomExtensions\Reports\Services\AuthorityRecordReportService
    {
        return new \AtomExtensions\Reports\Services\AuthorityRecordReportService(
            new \AtomExtensions\Repositories\ActorRepository(),
            $this->getReportLogger()
        );
    }

    private function createReportForm(): sfForm
    {
        $form = new sfForm([], [], false);
        $form->getValidatorSchema()->setOption('allow_extra_fields', true);
        \AtomExtensions\Forms\FormFieldFactory::addDateFields($form);
        \AtomExtensions\Forms\FormFieldFactory::addControlFields($form);
        $this->addEntityTypeField($form);
        return $form;
    }

    private function addEntityTypeField(sfForm $form): void
    {
        try {
            $termService = new \AtomExtensions\Services\TermService(sfContext::getInstance()->getUser()->getCulture());
            $entityTypes = $termService->getActorEntityTypes();
            $choices = $termService->toChoices($entityTypes, true, 'All types');
        } catch (Exception $e) {
            $choices = ['' => 'All types'];
        }
        $form->setValidator('entityType', new sfValidatorString(['required' => false]));
        $form->setWidget('entityType', new sfWidgetFormSelect(['choices' => $choices]));
    }

    private function getDefaultParameters(): array
    {
        return [
            'className' => 'QubitActor',
            'dateStart' => date('Y-m-d', strtotime('-1 year')),
            'dateEnd' => date('Y-m-d'),
            'dateOf' => 'CREATED_AT',
            'limit' => '20',
            'sort' => 'updatedDown',
            'page' => '1',
        ];
    }
}
