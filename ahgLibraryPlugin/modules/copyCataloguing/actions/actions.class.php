<?php

declare(strict_types=1);

/**
 * copyCataloguing actions — Z39.50 copy cataloguing workflow.
 *
 * Search a configured Z39.50 target, preview the returned MARC21 records,
 * and import a chosen record into the catalogue. Reuses the existing
 * AtomExtensions\Services\Z3950Service (search + importResults) and
 * MarcService::parseMarc21() for record preview — no duplicated engine.
 *
 * @package    ahgLibraryPlugin
 * @subpackage copyCataloguing
 */

use AtomFramework\Http\Controllers\AhgController;
use AtomExtensions\Services\Z3950Service;
use Illuminate\Database\Capsule\Manager as DB;

// Symfony 1.x dispatches the actions class in the global namespace and does not
// autoload namespaced plugin classes; load the services explicitly.
require_once sfConfig::get('sf_plugins_dir') . '/ahgLibraryPlugin/lib/Service/Z3950Service.class.php';
require_once sfConfig::get('sf_plugins_dir') . '/ahgLibraryPlugin/lib/Service/MarcService.php';

class copyCataloguingActions extends AhgController
{
    protected Z3950Service $z3950;
    protected bool $yazLoaded = false;

    public function preExecute(): void
    {
        $this->z3950     = Z3950Service::getInstance();
        $this->yazLoaded = $this->z3950->isYazLoaded();
    }

    /**
     * Show the target picker + search form; run the search when a query is
     * submitted (GET) and render the parsed result rows.
     */
    public function executeIndex($request)
    {
        $this->targets     = $this->z3950->listTargets();
        $this->records     = [];
        $this->recordCount = 0;
        $this->searchError = null;
        $this->query       = trim((string) $request->getParameter('query', ''));
        $this->targetId    = (int) $request->getParameter('target_id', 0);

        if ($this->query === '' || $this->targetId <= 0) {
            return sfView::SUCCESS;
        }

        if (!$this->yazLoaded) {
            $this->searchError = __('The YAZ PHP extension is not installed; Z39.50 search is unavailable. Install with: apt install php-yaz');

            return sfView::SUCCESS;
        }

        try {
            $result = $this->z3950->search($this->targetId, $this->query, 20, 0);
            if ($result->error) {
                $this->searchError = $result->error;

                return sfView::SUCCESS;
            }
            foreach ($result->records as $raw) {
                $this->records[] = $this->buildPreviewRow($raw);
            }
            $this->recordCount = count($this->records);
        } catch (\Throwable $e) {
            $this->searchError = $e->getMessage();
        }
    }

    /**
     * Import a single base64-encoded MARC21 record into the catalogue.
     */
    public function executeImport($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $raw = base64_decode((string) $request->getParameter('marc_content', ''), true);
        if ($raw === false || $raw === '') {
            $this->getUser()->setFlash('error', __('Invalid MARC data.'));
            $this->redirect(['module' => 'copyCataloguing', 'action' => 'index']);
        }

        try {
            $res = $this->z3950->importResults([$raw], $this->currentUserId());

            if (empty($res['ids'])) {
                $this->getUser()->setFlash('error', __('No record imported (possibly a duplicate ISBN/ISSN, or the record had no title).'));
                $this->redirect(['module' => 'copyCataloguing', 'action' => 'index']);
            }

            $ioId = (int) $res['ids'][0];
            $slug = DB::table('slug')->where('object_id', $ioId)->value('slug');

            $this->getUser()->setFlash('notice', __('Record imported via copy cataloguing.'));

            if ($slug) {
                $this->redirect(['module' => 'library', 'action' => 'index', 'slug' => $slug]);
            }
            $this->redirect(['module' => 'copyCataloguing', 'action' => 'index']);
        } catch (\Throwable $e) {
            $this->getUser()->setFlash('error', __('Import failed: ') . $e->getMessage());
            $this->redirect(['module' => 'copyCataloguing', 'action' => 'index']);
        }
    }

    /**
     * Parse a raw binary MARC21 record into a flat preview row.
     */
    private function buildPreviewRow(string $raw): array
    {
        $d = \MarcService::parseMarc21($raw);

        $author = '';
        if (!empty($d['creators'][0]['name'])) {
            $author = (string) $d['creators'][0]['name'];
        }

        return [
            'title'        => (string) ($d['title'] ?? ''),
            'author'       => $author,
            'isbn'         => $d['isbn'] ?? null,
            'issn'         => $d['issn'] ?? null,
            'publisher'    => $d['publisher'] ?? null,
            'pub_date'     => $d['publication_date'] ?? null,
            'marc_content' => base64_encode($raw),
        ];
    }

    private function currentUserId(): int
    {
        try {
            $u = \sfContext::getInstance()->getUser();
            if (method_exists($u, 'getUserID')) {
                return (int) $u->getUserID();
            }
            if (method_exists($u, 'getAttribute')) {
                return (int) $u->getAttribute('user_id', 0);
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return 0;
    }
}
