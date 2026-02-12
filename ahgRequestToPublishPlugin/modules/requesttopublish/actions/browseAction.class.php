<?php

use AtomFramework\Http\Controllers\AhgController;
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

    switch ($request->sort)
    {
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
  }
}
