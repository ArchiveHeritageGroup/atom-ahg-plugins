<?php

/**
 * Feedback Browse/List action.
 *
 * @author Johan Pieterse <johan@plainsailingisystems.co.za>
 */
class ahgFeedbackBrowseAction extends sfAction
{
    public function execute($request)
    {
        // Check authentication
        if (!$this->getUser()->isAuthenticated()) {
            QubitAcl::forwardUnauthorized();
        }

        $title = $this->context->i18n->__('Feedback Management');
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        // Set defaults
        $this->limit = $request->getParameter('limit', sfConfig::get('app_hits_per_page', 25));
        $this->filter = $request->getParameter('filter', 'all');
        $this->sort = $request->getParameter('sort', 'dateDown');

        // Build criteria
        $criteria = new Criteria();
        $criteria->addJoin(QubitFeedback::ID, QubitFeedbackI18n::ID);

        // Apply filter
        switch ($this->filter) {
            case 'pending':
                $criteria->add(QubitFeedbackI18n::STATUS_ID, QubitTerm::PENDING_ID);
                break;
            case 'completed':
                $criteria->add(QubitFeedbackI18n::STATUS_ID, QubitTerm::COMPLETED_ID);
                break;
        }

        // Apply sorting
        switch ($this->sort) {
            case 'nameUp':
                $criteria->addAscendingOrderByColumn(QubitFeedbackI18n::NAME);
                break;
            case 'nameDown':
                $criteria->addDescendingOrderByColumn(QubitFeedbackI18n::NAME);
                break;
            case 'dateUp':
                $criteria->addAscendingOrderByColumn(QubitFeedbackI18n::CREATED_AT);
                break;
            case 'dateDown':
            default:
                $criteria->addDescendingOrderByColumn(QubitFeedbackI18n::CREATED_AT);
                break;
        }

        // Get counts for badges
        $this->totalCount = $this->getCount();
        $this->pendingCount = $this->getCount(QubitTerm::PENDING_ID);
        $this->completedCount = $this->getCount(QubitTerm::COMPLETED_ID);

        // Page results
        $this->pager = new QubitPager('QubitFeedback');
        $this->pager->setCriteria($criteria);
        $this->pager->setMaxPerPage($this->limit);
        $this->pager->setPage($request->getParameter('page', 1));
    }

    protected function getCount($statusId = null)
    {
        $criteria = new Criteria();
        $criteria->addJoin(QubitFeedback::ID, QubitFeedbackI18n::ID);
        
        if (null !== $statusId) {
            $criteria->add(QubitFeedbackI18n::STATUS_ID, $statusId);
        }
        
        return BasePeer::doCount($criteria)->fetchColumn(0);
    }
}
