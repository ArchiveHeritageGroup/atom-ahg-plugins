<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Storytelling engine (#155): staff write a story of the collection with the
 * AI gateway's help and publish it at /stories/<slug>.
 */
class storyActions extends AhgController
{
    public function executeIndex($request)
    {
        $this->staffOnly();
        $svc = $this->service();
        $this->result = null;
        $this->story = null;
        $this->form = ['theme' => '', 'notes' => '', 'urls' => ''];
        $this->picked = [];
        $this->problems = [];

        if ($request->isMethod('post')) {
            $do = (string) $request->getParameter('form_action');
            if ('save' === $do) {
                $sources = json_decode((string) $request->getParameter('sources_json', '[]'), true);
                $r = $svc->save([
                    'id' => (int) $request->getParameter('id'),
                    'title' => $request->getParameter('title'),
                    'theme' => $request->getParameter('theme'),
                    'body' => $request->getParameter('body'),
                    'status' => $request->getParameter('status'),
                    'object_ids' => (array) $request->getParameter('object_ids', []),
                    'sources' => is_array($sources) ? $sources : [],
                ]);
                if ($r['ok']) {
                    $this->getUser()->setFlash('notice', 'published' === $r['status'] ? __('Story published.') : __('Story saved as a draft.'));
                    $this->redirect('/admin/stories?id='.$r['id']);
                }
                $this->getUser()->setFlash('error', __($r['error']));
                $this->redirect('/admin/stories');
            }

            $this->form = ['theme' => (string) $request->getParameter('theme', ''), 'notes' => (string) $request->getParameter('notes', ''),
                'urls' => (string) $request->getParameter('urls', '')];
            $ids = array_values(array_filter(array_map('intval', (array) $request->getParameter('record_ids', []))));
            if ('on_this_day' === $do) {
                $this->result = $svc->onThisDay() + ['sources' => []];
            } else {
                [$context, $sources, $problems] = $this->gatherSources($request);
                $this->result = $svc->generate($this->form['theme'], $ids, $context) + ['sources' => $sources];
                foreach ($this->result['objects'] as $o) {
                    if (in_array($o['id'], $ids, true)) {
                        $this->result['sources'][] = ['type' => 'record', 'label' => $o['title']];
                    }
                }
                $this->problems = $problems;
            }
            $this->picked = array_values(array_filter($this->result['objects'], fn ($o) => in_array($o['id'], $ids, true)));
        } elseif ($id = (int) $request->getParameter('id')) {
            $this->story = $svc->find($id);
            if (!$this->story) {
                $this->forward404();
            }
            $this->result = ['ok' => true, 'theme' => $this->story->theme, 'story' => $this->story->body,
                'objects' => $svc->storyRecords($this->story), 'sources' => $svc->storySources($this->story), 'error' => ''];
        }
        $this->saved = $svc->listSaved();
    }

    /** Published records for the picker, as JSON. */
    public function executeSearch($request)
    {
        $this->staffOnly();
        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode($this->service()->search((string) $request->getParameter('q', ''))));
    }

    /** The public story page. A draft is shown to staff only. */
    public function executeShow($request)
    {
        $svc = $this->service();
        $this->story = $svc->findBySlug((string) $request->getParameter('slug'));
        $staff = $this->getUser()->isAuthenticated()
            && ($this->getUser()->hasCredential('editor') || $this->getUser()->hasCredential('administrator'));
        if (!$this->story || ('published' !== $this->story->status && !$staff)) {
            $this->forward404();
        }
        $this->records = $svc->storyRecords($this->story);
        $this->sources = $svc->storySources($this->story);
        $this->staff = $staff;
        $this->getResponse()->setTitle($this->story->title);
    }

    /** Notes, fetched pages and uploaded files as one background block, plus their attribution. */
    private function gatherSources($request): array
    {
        require_once dirname(__DIR__, 3).'/lib/Services/StorySourceService.php';
        $src = new StorySourceService();
        $pieces = [];
        $sources = [];
        $problems = [];
        if ('' !== trim($this->form['notes'])) {
            $pieces['Curator notes'] = mb_substr($this->form['notes'], 0, StorySourceService::MAX_SOURCE);
            $sources[] = ['type' => 'note', 'label' => __('Curator notes')];
        }
        foreach (array_slice(array_filter(array_map('trim', preg_split('/\r?\n/', $this->form['urls']))), 0, 5) as $url) {
            $r = $src->fetchUrlText($url);
            if ($r['ok']) {
                $pieces[$r['url']] = $r['text'];
                $sources[] = ['type' => 'url', 'label' => $r['title'] ?: $r['url'], 'url' => $r['url']];
            } else {
                $problems[] = $url.': '.$r['error'];
            }
        }
        $files = $_FILES['uploads'] ?? null;
        foreach (is_array($files['name'] ?? null) ? array_keys($files['name']) : [] as $k) {
            if (UPLOAD_ERR_OK !== ($files['error'][$k] ?? UPLOAD_ERR_NO_FILE) || !is_uploaded_file($files['tmp_name'][$k])) {
                continue;
            }
            $name = basename((string) $files['name'][$k]);
            $r = $src->extractUploadText($files['tmp_name'][$k], $name);
            if ($r['ok']) {
                $pieces[$name] = $r['text'];
                $sources[] = ['type' => 'upload', 'label' => $name];
            } else {
                $problems[] = $r['error'];
            }
        }

        return [StorySourceService::assembleContext($pieces), $sources, $problems];
    }

    private function staffOnly(): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
        if (!$this->getUser()->isAdministrator() && !$this->getUser()->hasCredential('editor')) {
            $this->forward('admin', 'secure');
        }
    }

    private function service(): StorytellingService
    {
        require_once dirname(__DIR__, 3).'/lib/Services/StorytellingService.php';

        return new StorytellingService();
    }
}
