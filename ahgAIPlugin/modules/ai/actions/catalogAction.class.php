<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

require_once dirname(__FILE__).'/../../../lib/Services/CatalogerService.php';

/**
 * GET  /ai/catalog/:id            — review page (shows latest draft if any).
 * POST /ai/catalog/:id  (generate) — generate a fresh full-record draft.
 *
 * AI Cataloguer (#149 strand): drafts a full ISAD(G) record from the existing
 * fields + OCR + #113 embedded metadata + NER entities, for archivist review.
 */
class aiCatalogAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
            return sfView::NONE;
        }

        $id = (int) $request->getParameter('id');
        $io = DB::table('information_object')->where('id', $id)->first();
        if (!$io) {
            $this->forward404('Record not found');
        }

        $this->objectId = $id;
        $this->recordTitle = (string) (DB::table('information_object_i18n')
            ->where('id', $id)->where('culture', 'en')->value('title') ?: ('Record #'.$id));
        $this->recordSlug = (string) (DB::table('slug')->where('object_id', $id)->value('slug') ?: '');
        $this->fields = CatalogerService::TEXT_FIELDS;
        $this->error = null;

        $svc = new CatalogerService();
        $wantGenerate = $request->isMethod('post') || $request->getParameter('generate');

        if ($wantGenerate) {
            $result = $svc->generateDraft($id, (int) $this->getUser()->getAttribute('user_id') ?: null);
            if ($request->isXmlHttpRequest()) {
                return $this->renderText(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }
            if (empty($result['success'])) {
                $this->error = $result['error'] ?? 'Generation failed.';
                $this->draft = null;
                $this->current = [];
                $this->entities = [];
            } else {
                $this->draft = $result['draft'];
                $this->current = $result['current'];
                $this->entities = $result['entities'];
                $this->model = $result['model'] ?? null;
            }
            return sfView::SUCCESS;
        }

        // GET: show the most recent draft, if one exists.
        $latest = $svc->getLatestDraft($id);
        if ($request->isXmlHttpRequest()) {
            return $this->renderText(json_encode(['success' => true, 'latest' => $latest], JSON_UNESCAPED_SLASHES));
        }
        $this->draft = $latest['draft'] ?? null;
        $this->current = $latest['current'] ?? [];
        $this->entities = $latest['entities'] ?? [];
        $this->model = $latest['model'] ?? null;
        $this->draftStatus = $latest['status'] ?? null;

        return sfView::SUCCESS;
    }
}
