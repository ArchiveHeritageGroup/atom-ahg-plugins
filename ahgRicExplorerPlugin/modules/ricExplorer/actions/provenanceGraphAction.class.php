<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

require_once dirname(__FILE__).'/../../../lib/Services/ProvenanceGraphService.php';
require_once dirname(__FILE__).'/../../../lib/Services/KnowledgeGraphService.php';

/**
 * /ricExplorer/provenance[/:id] — provenance (chain-of-custody) graph (#149).
 * With :id → the record's graph; without → a picker of records that have provenance.
 */
class ricExplorerProvenanceGraphAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
            return sfView::NONE;
        }

        $svc = new ProvenanceGraphService();
        $id = (int) $request->getParameter('id', 0);
        $access = KnowledgeGraphService::forUser($this->getUser());

        if ($id > 0) {
            try {
                $visible = isset($access->visibleRecordIds([$id])[$id]);
            } catch (\Throwable $e) {
                error_log('ricExplorer.provenance_filter_failed: '.$e->getMessage());
                $visible = false;
            }
            if (!$visible) {
                $this->forward404('Record not found'); // same answer for missing, hidden or unverifiable
            }
            $this->objectId = $id;
            $this->recordTitle = (string) (DB::table('information_object_i18n')
                ->where('id', $id)->where('culture', 'en')->value('title') ?: ('Record #'.$id));
            $this->recordSlug = (string) (DB::table('slug')->where('object_id', $id)->value('slug') ?: '');
            $this->graph = $svc->build($id);
            $this->records = [];
        } else {
            $this->objectId = 0;
            try {
                $records = $svc->listRecords();
                $visible = $access->visibleRecordIds(array_map(static fn ($r) => (int) $r->io_id, $records));
                $this->records = array_values(array_filter($records, static fn ($r) => isset($visible[(int) $r->io_id])));
            } catch (\Throwable $e) {
                error_log('ricExplorer.provenance_filter_failed: '.$e->getMessage());
                $this->records = [];
            }
        }

        return sfView::SUCCESS;
    }
}
