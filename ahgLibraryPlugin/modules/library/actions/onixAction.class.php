<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * ONIX ingestion — upload a publisher ONIX feed, parse + validate into a review
 * queue. (Commit-to-catalogue is a follow-up phase.)
 *
 * GET  /library/onix            — upload form + recent ingests
 * GET  /library/onix?id=N       — review the parsed lines of ingest N
 * POST /library/onix            — ingest an uploaded file or pasted XML
 */
class libraryOnixAction extends AhgController
{
    public function execute($request)
    {
        $this->requireAuth();

        require_once \sfConfig::get('sf_plugins_dir') . '/ahgLibraryPlugin/lib/Service/OnixIngestService.php';
        $svc = new \OnixIngestService();

        $this->error = null;

        if ($request->isMethod('post')) {
            $xml = '';
            $filename = null;
            $files = $request->getFiles('onix_file');
            if (!empty($files['tmp_name']) && is_uploaded_file($files['tmp_name'])) {
                $xml = (string) file_get_contents($files['tmp_name']);
                $filename = $files['name'] ?? 'upload.xml';
            } else {
                $xml = (string) $request->getParameter('onix_xml', '');
                $filename = 'pasted.xml';
            }

            if (trim($xml) === '') {
                $this->error = __('Provide an ONIX file or paste ONIX XML.');
            } else {
                try {
                    $userId = (int) ($this->getUser()->getAttribute('user_id') ?? 0) ?: null;
                    $res = $svc->ingest($xml, $filename, 'file', $userId);
                    $this->getUser()->setFlash('notice', __('Parsed %1% records (%2% valid, %3% with issues).', [
                        '%1%' => $res['record_count'], '%2%' => $res['valid_count'], '%3%' => $res['error_count'],
                    ]));
                    $this->redirect('/index.php/library/onix?id=' . $res['ingest_id']);

                    return;
                } catch (\Throwable $e) {
                    $this->error = __('ONIX parse failed: %1%', ['%1%' => $e->getMessage()]);
                }
            }
        }

        $id = (int) $request->getParameter('id', 0);
        $this->ingestId = $id;
        $this->ingest = $id ? $svc->getIngest($id) : null;
        $this->lines = $id ? $svc->getLines($id) : [];
        $this->ingests = $svc->listIngests(['limit' => 50]);

        return sfView::SUCCESS;
    }
}
