<?php

/**
 * Grid entry (issue #208): type or paste many descriptions under one parent
 * in a spreadsheet-style grid. Saving validates the rows, stages them as an
 * ingest session and hands over to the ingest wizard's commit step, so the
 * records are created by the same job as a CSV or Excel ingest.
 */
class ingestGridActions extends sfActions
{
    protected function gridService(): \AhgIngestPlugin\Services\GridEntryService
    {
        $dir = sfConfig::get('sf_plugins_dir') . '/ahgIngestPlugin/lib/Services';
        require_once $dir . '/IngestService.php';
        require_once $dir . '/GridEntryService.php';

        return new \AhgIngestPlugin\Services\GridEntryService();
    }

    protected function isStaff(): bool
    {
        return $this->getUser()->isAuthenticated()
            && $this->getUser()->hasCredential(['administrator', 'editor'], false);
    }

    /**
     * The parent description, or null when it does not exist (or is the root).
     */
    protected function loadParent(int $id, string $culture): ?object
    {
        if ($id <= 1) {
            return null;
        }

        return \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->leftJoin('information_object_i18n as cur', function ($j) use ($culture) {
                $j->on('cur.id', '=', 'io.id')->where('cur.culture', '=', $culture);
            })
            ->leftJoin('information_object_i18n as src', function ($j) {
                $j->on('src.id', '=', 'io.id')->on('src.culture', '=', 'io.source_culture');
            })
            ->leftJoin('slug', 'slug.object_id', '=', 'io.id')
            ->where('io.id', $id)
            ->select('io.id', 'io.identifier', 'slug.slug', \Illuminate\Database\Capsule\Manager::raw('COALESCE(cur.title, src.title) AS title'))
            ->first();
    }

    public function executeIndex(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        if (!$this->isStaff()) {
            $this->forward('admin', 'secure');
        }

        $grid = $this->gridService();
        $culture = $this->getUser()->getCulture();

        $parentId = (int) $request->getParameter('parent');
        if (!$parentId && $request->getParameter('slug')) {
            $parentId = (int) \Illuminate\Database\Capsule\Manager::table('slug')
                ->where('slug', (string) $request->getParameter('slug'))
                ->value('object_id');
        }

        $this->parent = $parentId ? $this->loadParent($parentId, $culture) : null;
        $this->children = $this->parent ? $grid->children((int) $this->parent->id, $culture) : [];
        $this->levels = $grid->levelNames($culture);
        $this->columns = \AhgIngestPlugin\Services\GridEntryService::COLUMNS;
        $this->maxRows = \AhgIngestPlugin\Services\GridEntryService::MAX_ROWS;
    }

    public function executeSave(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $reply = function (array $data, int $status = 200) {
            $this->getResponse()->setStatusCode($status);

            return $this->renderText(json_encode($data, JSON_UNESCAPED_UNICODE));
        };

        if (!$this->isStaff()) {
            return $reply(['error' => 'You do not have permission to add descriptions.'], 403);
        }
        // JSON only: a cross-site form cannot send this content type without a
        // CORS preflight, which this endpoint never grants.
        if (!$request->isMethod('post') || false === stripos((string) $request->getHttpHeader('Content-Type'), 'application/json')) {
            return $reply(['error' => 'Bad request.'], 400);
        }

        $body = json_decode((string) $request->getContent(), true);
        if (!is_array($body) || !is_array($body['rows'] ?? null)) {
            return $reply(['error' => 'Bad request.'], 400);
        }

        $culture = $this->getUser()->getCulture();
        $parent = $this->loadParent((int) ($body['parent_id'] ?? 0), $culture);
        if (!$parent) {
            return $reply(['error' => 'Choose a parent description first.'], 422);
        }
        $parentObj = \QubitInformationObject::getById((int) $parent->id);
        if (!$parentObj || !\QubitAcl::check($parentObj, 'create')) {
            return $reply(['error' => 'You may not add descriptions under this parent.'], 403);
        }

        $grid = $this->gridService();
        $rows = $grid->normalise($body['rows']);
        if (empty($rows)) {
            return $reply(['error' => 'The grid is empty.'], 422);
        }

        $errors = $grid->validate($rows, $grid->levelNames($culture));
        if (!empty($errors)) {
            return $reply(['error' => 'Some rows need attention before they can be saved.', 'errors' => $errors], 422);
        }

        [$sessionId, $stats] = $grid->stage(
            (int) $this->getUser()->getAttribute('user_id'),
            (int) $parent->id,
            (string) ($parent->title ?: $parent->slug),
            $rows
        );

        if (($stats['errors'] ?? 0) > 0) {
            return $reply([
                'error' => 'The ingest validation found problems. Review them in the ingest wizard.',
                'review_url' => $this->getController()->genUrl(['module' => 'ingest', 'action' => 'validate', 'id' => $sessionId]),
            ], 422);
        }

        return $reply([
            'ok' => true,
            'session_id' => $sessionId,
            'rows' => count($rows),
            'commit_url' => $this->getController()->genUrl(['module' => 'ingest', 'action' => 'commit', 'id' => $sessionId]),
        ]);
    }
}
