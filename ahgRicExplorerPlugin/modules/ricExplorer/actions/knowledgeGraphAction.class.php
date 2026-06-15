<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

require_once dirname(__FILE__) . '/../../../lib/Services/KnowledgeGraphService.php';

/**
 * /ricExplorer/knowledge-graph[/:id] — unified cross-domain entity graph (#150).
 * With :id → that record's cross-entity graph; without → a record picker.
 */
class ricExplorerKnowledgeGraphAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');

            return sfView::NONE;
        }

        $svc = new KnowledgeGraphService();
        $id = (int) $request->getParameter('id', 0);

        if ($id > 0) {
            $io = DB::table('information_object')->where('id', $id)->first();
            if (!$io) {
                $this->forward404('Record not found');
            }
            $this->objectId = $id;
            $this->recordTitle = (string) (DB::table('information_object_i18n')
                ->where('id', $id)->where('culture', 'en')->value('title') ?: ('Record #' . $id));
            $this->recordSlug = (string) (DB::table('slug')->where('object_id', $id)->value('slug') ?: '');
            $this->graph = $svc->build($id);
            $this->records = [];
        } else {
            $this->objectId = 0;
            $this->records = $svc->listEntities();
        }

        return sfView::SUCCESS;
    }
}
