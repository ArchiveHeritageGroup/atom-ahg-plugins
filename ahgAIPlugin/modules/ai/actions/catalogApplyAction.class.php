<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

require_once dirname(__FILE__).'/../../../lib/Services/CatalogerService.php';

/**
 * POST /ai/catalog/:id/apply — apply the archivist-selected draft fields to the record.
 */
class aiCatalogApplyAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
            return sfView::NONE;
        }
        // Cataloguing writes to the record — require edit credential.
        if (!$this->getUser()->hasCredential('editor') && !$this->getUser()->hasCredential('administrator')) {
            $this->forward404('Editor access required');
        }
        if (!$request->isMethod('post')) {
            $this->redirect('ai/catalog?id='.(int) $request->getParameter('id'));
            return sfView::NONE;
        }

        $id = (int) $request->getParameter('id');
        $accepted = (array) $request->getParameter('apply', []); // e.g. ['title'=>'1', ...]

        $svc = new CatalogerService();
        $result = $svc->applyDraft($id, $accepted, (int) $this->getUser()->getAttribute('user_id') ?: 0);

        if ($request->isXmlHttpRequest()) {
            return $this->renderText(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        if (!empty($result['success'])) {
            $n = count($result['applied'] ?? []);
            $this->getUser()->setFlash('notice', "Applied $n AI-drafted field(s) to the record.");
            $slug = DB::table('slug')->where('object_id', $id)->value('slug');
            if ($slug) {
                $this->redirect('/'.$slug);
                return sfView::NONE;
            }
        } else {
            $this->getUser()->setFlash('error', $result['error'] ?? 'Nothing was applied.');
        }
        $this->redirect('ai/catalog?id='.$id);
        return sfView::NONE;
    }
}
