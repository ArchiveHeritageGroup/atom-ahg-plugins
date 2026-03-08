<?php

declare(strict_types=1);

use AtomFramework\Http\Controllers\AhgController;

/**
 * OPAC (Online Public Access Catalog) Actions
 *
 * Public-facing library catalog: search, view, holds, patron account.
 *
 * @package    ahgLibraryPlugin
 * @subpackage opac
 */
class opacActions extends sfActions
{
    /**
     * Catalog search/browse page.
     */
    public function executeIndex($request)
    {
        $action = new opacIndexAction($this->context, 'opac', 'index');
        return $action->execute($request);
    }

    /**
     * View a single catalog item.
     */
    public function executeView($request)
    {
        $action = new opacViewAction($this->context, 'opac', 'view');
        return $action->execute($request);
    }

    /**
     * Place a hold (POST).
     */
    public function executeHold($request)
    {
        $action = new opacHoldAction($this->context, 'opac', 'hold');
        return $action->execute($request);
    }

    /**
     * Patron "My Account" page.
     */
    public function executeAccount($request)
    {
        $action = new opacAccountAction($this->context, 'opac', 'account');
        return $action->execute($request);
    }
}
