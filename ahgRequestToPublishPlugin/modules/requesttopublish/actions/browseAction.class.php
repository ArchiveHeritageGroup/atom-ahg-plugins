<?php

use AtomFramework\Http\Controllers\AhgController;
use AtomFramework\Services\Pagination\PaginationService;
use AtomExtensions\Services\AclService;

/**
 * RequestToPublish List component.
 *
 * @package    qubit
 * @subpackage Request To Publish List Module
 * @author     Johan Pieterse <johan@plainsailingisystems.co.za>
 * @version    SVN: $Id 
 */
 
class RequestToPublishBrowseAction extends AhgController
{
  public function execute($request)
  {
	$title = $this->context->i18n->__('RequestToPublish');
	$this->getResponse()->setTitle("{$title} - {$this->getResponse()->getTitle()}");

	if (!isset($request->limit)) {
		$request->limit = $this->config('app_hits_per_page');
	}

	if ($this->config('app_enable_institutional_scoping')) {
		//remove search-realm
		$this->getUser()->removeAttribute('search-realm');
	}

	$this->filter = $request->filter;

    if (!$this->getUser()->isAuthenticated())
    {
      AclService::forwardUnauthorized();
    }

    if (!isset($request->limit))
    {
      $request->limit = $this->config('app_hits_per_page');
    }

	if (!isset($this->filter)) {
		$this->filter = 'all';
	}
	
    if (class_exists('QubitPager')) {
        // === Propel mode ===
        $criteria = new Criteria;
        if (!$this->getUser()->hasGroup(\AtomExtensions\Constants\AclConstants::EDITOR_ID) && !$this->getUser()->isAdministrator()) {
            $criteria->add(QubitRequestToPublishI18n::UNIQUE_IDENTIFIER, $this->getUser()->getAttribute('user_id'));
        }
        if ('pending' === $this->filter) {
            $criteria->add(QubitRequestToPublishI18n::STATUS_ID, QubitTerm::IN_REVIEW_ID);
        } elseif ('rejected' === $this->filter) {
            $criteria->add(QubitRequestToPublishI18n::STATUS_ID, QubitTerm::REJECTED_ID);
        } elseif ('approved' === $this->filter) {
            $criteria->add(QubitRequestToPublishI18n::STATUS_ID, QubitTerm::APPROVED_ID);
        }

        // Do source culture fallback
        $criteria->addJoin(QubitRequestToPublish::ID, QubitRequestToPublishI18n::ID);
        BaseRequestToPublish::addSelectColumns($criteria);

        switch ($request->sort) {
            case 'rtp_nameDown':
                $criteria->addDescendingOrderByColumn('rtp_name');

                break;

            case 'institutionDown':
                $criteria->addDescendingOrderByColumn('rtp_institution');

                break;

            case 'institutionUp':
                $criteria->addAscendingOrderByColumn('rtp_institution');

                break;

            default:
                $request->sort = 'rtp_nameUp';
                $criteria->addAscendingOrderByColumn('rtp_name');
        }

        // Page results
        $this->pager = new QubitPager('QubitRequestToPublish');
        $this->pager->setCriteria($criteria);
        $this->pager->setMaxPerPage($request->limit);
        $this->pager->setPage($request->page);
    } else {
        // === Standalone mode ===
        $options = [
            'join' => [
                'request_to_publish_i18n' => ['request_to_publish.id', '=', 'request_to_publish_i18n.id'],
            ],
            'where' => [],
            'orderBy' => [],
        ];

        // User restriction (non-editor, non-admin)
        if (!$this->getUser()->hasGroup(\AtomExtensions\Constants\AclConstants::EDITOR_ID) && !$this->getUser()->isAdministrator()) {
            $options['where'][] = ['request_to_publish_i18n.unique_identifier', '=', $this->getUser()->getAttribute('user_id')];
        }

        // Status filter
        if ('pending' === $this->filter) {
            $options['where'][] = ['request_to_publish_i18n.status_id', '=', QubitTerm::IN_REVIEW_ID];
        } elseif ('rejected' === $this->filter) {
            $options['where'][] = ['request_to_publish_i18n.status_id', '=', QubitTerm::REJECTED_ID];
        } elseif ('approved' === $this->filter) {
            $options['where'][] = ['request_to_publish_i18n.status_id', '=', QubitTerm::APPROVED_ID];
        }

        // Sort
        switch ($request->sort) {
            case 'rtp_nameDown':
                $options['orderBy'] = ['request_to_publish_i18n.rtp_name' => 'desc'];

                break;

            case 'institutionDown':
                $options['orderBy'] = ['request_to_publish_i18n.rtp_institution' => 'desc'];

                break;

            case 'institutionUp':
                $options['orderBy'] = ['request_to_publish_i18n.rtp_institution' => 'asc'];

                break;

            default:
                $request->sort = 'rtp_nameUp';
                $options['orderBy'] = ['request_to_publish_i18n.rtp_name' => 'asc'];
        }

        $this->pager = PaginationService::paginate('request_to_publish', $options, (int) ($request->page ?? 1), (int) $request->limit);
    }
  }
}
