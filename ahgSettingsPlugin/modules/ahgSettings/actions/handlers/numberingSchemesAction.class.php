<?php

use AtomExtensions\Services\NumberingService;

/**
 * Numbering Schemes Management Action
 *
 * Full CRUD for numbering schemes with pattern builder.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class AhgSettingsNumberingSchemesAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->i18n = sfContext::getInstance()->i18n;
        $this->service = NumberingService::getInstance();

        // Get filter
        $this->sectorFilter = $request->getParameter('sector', '');

        // Get all schemes
        if ($this->sectorFilter) {
            $this->schemes = $this->service->getSchemesForSector($this->sectorFilter);
        } else {
            $this->schemes = $this->service->getAllSchemes();
        }

        // Available sectors
        $this->sectors = [
            'archive' => 'Archive',
            'library' => 'Library',
            'museum' => 'Museum',
            'gallery' => 'Gallery',
            'dam' => 'DAM',
        ];

        // Available tokens for reference
        $this->tokens = $this->service->getAvailableTokens();

        // Handle actions
        $action = $request->getParameter('do');

        if ($action === 'setDefault' && $request->getParameter('id')) {
            $this->service->setAsDefault((int) $request->getParameter('id'));
            $this->getUser()->setFlash('notice', $this->i18n->__('Default scheme updated.'));
            $this->redirect(['module' => 'ahgSettings', 'action' => 'numberingSchemes']);
        }

        if ($action === 'delete' && $request->getParameter('id')) {
            $this->service->deleteScheme((int) $request->getParameter('id'));
            $this->getUser()->setFlash('notice', $this->i18n->__('Scheme deleted.'));
            $this->redirect(['module' => 'ahgSettings', 'action' => 'numberingSchemes']);
        }

        if ($action === 'resetSequence' && $request->getParameter('id')) {
            $this->service->resetSequence((int) $request->getParameter('id'));
            $this->getUser()->setFlash('notice', $this->i18n->__('Sequence reset to 0.'));
            $this->redirect(['module' => 'ahgSettings', 'action' => 'numberingSchemes']);
        }
    }
}
