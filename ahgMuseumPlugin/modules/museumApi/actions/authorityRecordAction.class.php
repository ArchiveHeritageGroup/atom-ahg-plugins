<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Authority Record API Action
 *
 * Retrieves detailed record from an authority source.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

class apiAuthorityRecordAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $source = $request->getParameter('source');
        $id = $request->getParameter('id');

        if (!$source || !$id) {
            $this->getResponse()->setStatusCode(400);
            echo json_encode(['error' => 'Missing source or id parameter']);
            return sfView::NONE;
        }

        try {
            $service = new ahgAuthorityLinkageService();
            $record = null;

            switch ($source) {
                case ahgAuthorityLinkageService::SOURCE_ULAN:
                    $record = $service->getULANRecord($id);
                    break;
                case ahgAuthorityLinkageService::SOURCE_WIKIDATA:
                    $record = $service->getWikidataRecord($id);
                    break;
                case ahgAuthorityLinkageService::SOURCE_VIAF:
                    $record = $service->getVIAFRecord($id);
                    break;
                default:
                    $this->getResponse()->setStatusCode(400);
                    echo json_encode(['error' => 'Unsupported source: ' . $source]);
                    return sfView::NONE;
            }

            if ($record) {
                echo json_encode(['record' => $record]);
            } else {
                $this->getResponse()->setStatusCode(404);
                echo json_encode(['error' => 'Record not found']);
            }

        } catch (Exception $e) {
            $this->getResponse()->setStatusCode(500);
            echo json_encode(['error' => $e->getMessage()]);
        }

        return sfView::NONE;
    }
}
