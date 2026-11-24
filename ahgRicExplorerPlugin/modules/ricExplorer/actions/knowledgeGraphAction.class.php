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

        $svc = KnowledgeGraphService::forUser($this->getUser());
        $id = (int) $request->getParameter('id', 0);

        if ($id > 0) {
            $this->graph = $svc->build($id);
            if ([] === $this->graph['nodes']) {
                $this->forward404('Record not found'); // same answer for missing, hidden or unverifiable
            }
            $this->objectId = $id;
            $this->recordTitle = (string) (DB::table('information_object_i18n')
                ->where('id', $id)->where('culture', 'en')->value('title') ?: ('Record #' . $id));
            $this->recordSlug = (string) (DB::table('slug')->where('object_id', $id)->value('slug') ?: '');
            $this->records = [];
        } else {
            $this->objectId = 0;
            $this->records = $svc->listEntities();
        }

        return sfView::SUCCESS;
    }
}
