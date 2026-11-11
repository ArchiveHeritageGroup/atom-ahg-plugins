<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Pages of a record's PDF: document type and privacy per page, rolled up to the
 * record, with per-page review (catalogue#320, twin of heratio#1584).
 */
class aiPagesActions extends AhgController
{
    private function classifier(): \AhgAIPlugin\Services\PageClassifier
    {
        require_once dirname(__DIR__, 3) . '/lib/Services/PageClassifier.php';

        return new \AhgAIPlugin\Services\PageClassifier();
    }

    private function requireStaff(): void
    {
        // __() for the messages; without it an action that sets a flash dies.
        $this->getContext()->getConfiguration()->loadHelpers(['I18N']);
        $u = $this->getUser();
        if (!$u->isAuthenticated() || !($u->isAdministrator() || $u->hasCredential('editor'))) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }
    }

    private function record(string $slug): object
    {
        $io = DB::table('slug')->join('information_object as io', 'io.id', '=', 'slug.object_id')
            ->leftJoin('information_object_i18n as i', function ($j) {
                $j->on('i.id', '=', 'io.id')->where('i.culture', '=', $this->culture());
            })->where('slug.slug', $slug)->first(['io.id', 'slug.slug', 'i.title']);
        if (!$io) {
            $this->forward404('Record not found.');
        }

        return $io;
    }

    /** GET: the page strip. POST: read the pages again, in the background. */
    public function executeIndex($request)
    {
        $this->requireStaff();
        $pc = $this->classifier();
        $this->io = $this->record((string) $request->getParameter('slug'));
        $id = (int) $this->io->id;

        if ($request->isMethod('post')) {
            if (!\AhgAIPlugin\Services\PageClassifier::masterPdf($id)) {
                $this->getUser()->setFlash('error', __('This record has no PDF master file.'));
            } else {
                $root = $this->config('sf_root_dir');
                $php = (PHP_SAPI === 'cli' && PHP_BINARY) ? PHP_BINARY : (is_executable(PHP_BINDIR . '/php') ? PHP_BINDIR . '/php' : 'php');
                exec(sprintf('cd %s && nohup %s symfony ai:classify-pages --id=%d --user=%d >> %s 2>&1 &',
                    escapeshellarg($root), escapeshellarg($php), $id, (int) $this->userId(), escapeshellarg($root . '/log/ai-pages.log')));
                $this->getUser()->setFlash('notice', \AhgAIPlugin\Services\PageClassifier::gpuHours()
                    ? __('Started: every page is screened and classified. Refresh this page in a minute or two.')
                    : __('Started: every page is screened for personal information now; document types wait for a daytime run (the GPUs are off 18:00-06:00).'));
            }
            $this->redirect('@ahg_ai_pages?slug=' . $this->io->slug);
        }

        $this->segments = $pc->segments($id);
        $this->rollup = \AhgAIPlugin\Services\PageClassifier::rollup($this->segments);
        $this->types = \AhgAIPlugin\Services\PageClassifier::types();
        $pdf = \AhgAIPlugin\Services\PageClassifier::masterPdf($id);
        $this->pdfUrl = $pdf ? $pdf->url : null;
        $this->isAdmin = $this->getUser()->isAdministrator();
        $this->settings = [
            'window' => \AhgAIPlugin\Services\PageClassifier::setting('ai_page_window_words', '0'),
            'on_ingest' => \AhgAIPlugin\Services\PageClassifier::setting('ai_page_classify_on_ingest', '0'),
            'collections' => \AhgAIPlugin\Services\PageClassifier::setting('ai_page_classify_collections', ''),
            'model' => \AhgAIPlugin\Services\PageClassifier::setting('ai_page_model', \AhgAIPlugin\Services\PageClassifier::DEFAULT_MODEL),
        ];
        $this->getResponse()->setTitle(__('Pages') . ' - ' . ($this->io->title ?? $this->io->slug));
    }

    /** POST: accept or correct one page result (recorded as an override). */
    public function executeReview($request)
    {
        $this->requireStaff();
        $io = $this->record((string) $request->getParameter('slug'));
        if (!$request->isMethod('post')) {
            $this->redirect('@ahg_ai_pages?slug=' . $io->slug);
        }
        $inference = (int) $request->getParameter('inference');
        $owner = (int) DB::table('ahg_ai_inference')->where('id', $inference)->value('target_entity_id');
        if ($owner !== (int) $io->id) {
            $this->forward404('Not a page result of this record.');
        }
        try {
            $this->classifier()->review($inference, (string) $request->getParameter('value'), (int) $this->userId(), trim((string) $request->getParameter('reason')) ?: null);
            $this->getUser()->setFlash('notice', __('Review saved.'));
        } catch (\InvalidArgumentException $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
        }
        $this->redirect('@ahg_ai_pages?slug=' . $io->slug);
    }

    /** POST (administrators): window size, run on ingest, collections. */
    public function executeSettings($request)
    {
        $this->requireStaff();
        $io = $this->record((string) $request->getParameter('slug'));
        if (!$request->isMethod('post') || !$this->getUser()->isAdministrator()) {
            $this->redirect('@ahg_ai_pages?slug=' . $io->slug);
        }
        $window = max(0, min(20000, (int) $request->getParameter('window_words')));
        $cols = preg_replace('/\s+/', '', (string) $request->getParameter('collections'));
        if ('' !== $cols && !preg_match('/^\d+(,\d+)*$/', $cols)) {
            $this->getUser()->setFlash('error', __('Collections must be record ids separated by commas.'));
            $this->redirect('@ahg_ai_pages?slug=' . $io->slug);
        }
        $model = trim((string) $request->getParameter('model'));
        if (!preg_match('/^[A-Za-z0-9._:\/-]{1,100}$/', $model)) {
            $model = \AhgAIPlugin\Services\PageClassifier::DEFAULT_MODEL;
        }
        foreach (['ai_page_model' => $model, 'ai_page_window_words' => (string) $window, 'ai_page_classify_on_ingest' => $request->getParameter('on_ingest') ? '1' : '0', 'ai_page_classify_collections' => $cols] as $k => $v) {
            DB::table('ahg_settings')->updateOrInsert(['setting_key' => $k], ['setting_value' => $v, 'setting_group' => 'ai', 'updated_at' => date('Y-m-d H:i:s')]);
        }
        $this->getUser()->setFlash('notice', __('Page settings saved.'));
        $this->redirect('@ahg_ai_pages?slug=' . $io->slug);
    }
}
