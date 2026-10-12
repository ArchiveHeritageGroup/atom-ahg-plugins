<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * researchjournal module - Journal Builder + Manuscript Workspace (#115).
 *
 * PSIS-parity port of the Heratio ResearchJournalController. Institutional
 * journal publication (journal -> issues -> articles -> TOC -> publish) plus a
 * manuscript workspace that formats an article toward an external target
 * journal (#114 directory, referenced when present; degrades gracefully).
 *
 * Distinct from the legacy researcher logbook (research module: journal /
 * journalNew / journalEntry actions over research_journal_entry).
 *
 * @package ahgResearchPlugin
 * @version 1.0.0
 */
class researchjournalActions extends AhgController
{
    /** @var ResearchService */
    protected $service;

    /** @var ResearchJournalService */
    protected $journal;

    public function boot(): void
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ResearchService.php';
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ResearchJournalService.php';
        $this->service = new ResearchService();
        $this->journal = new ResearchJournalService();
        $this->sidebarActive = 'journal';
        $this->unreadNotifications = 0;
    }

    /** A journal (and its articles) is its owner's; administrators may open any. Others get a 404. */
    protected function requireOwn(?array $journal, $researcher): void
    {
        if (!$journal || ((int) $journal['researcher_id'] !== (int) $researcher->id && !$this->getUser()->isAdministrator())) {
            $this->forward404('Journal not found');
        }
    }

    /** Resolve the current researcher row, or redirect to register / login. */
    protected function requireResearcher()
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            $this->redirect('research/register');
        }

        return $researcher;
    }

    // ── Journals ──────────────────────────────────────────────────────────

    public function executeIndex($request)
    {
        $researcher = $this->requireResearcher();
        // A researcher's journals and manuscripts are their own; administrators see all.
        $owner = $this->getUser()->isAdministrator() ? null : (int) $researcher->id;
        $this->publications = $this->journal->listJournals(ResearchJournalService::KIND_PUBLICATION, $owner);
        $this->manuscripts  = $this->journal->listJournals(ResearchJournalService::KIND_MANUSCRIPT, $owner);
        $this->setTemplate('index');
    }

    public function executeBuilder($request)
    {
        $researcher = $this->requireResearcher();
        $id = (int) $request->getParameter('id');

        if ($id) {
            $journal = $this->journal->getJournal($id);
            $this->requireOwn($journal, $researcher);
            $kind = $journal['kind'];
        } else {
            $journal = null;
            $kind = $request->getParameter('kind') === ResearchJournalService::KIND_MANUSCRIPT
                ? ResearchJournalService::KIND_MANUSCRIPT : ResearchJournalService::KIND_PUBLICATION;
        }

        if ($request->isMethod('post')) {
            $data = $this->collectJournal($request);
            if ($id) {
                $this->journal->updateJournal($id, $data);
                $this->getUser()->setFlash('success', 'Journal updated.');
                $this->redirect('researchjournal/show?id=' . $id);
            } else {
                $data['researcher_id'] = (int) $researcher->id;
                $newId = $this->journal->createJournal($data);
                $this->getUser()->setFlash('success', 'Journal created.');
                $this->redirect('researchjournal/show?id=' . $newId);
            }
        }

        $this->journal_record = $journal;
        $this->kind = $kind;
        $this->targetJournals = $this->journal->targetJournalOptions();
        $this->setTemplate('builder');
    }

    public function executeShow($request)
    {
        $researcher = $this->requireResearcher();
        $id = (int) $request->getParameter('id');
        $journal = $this->journal->getJournal($id);
        $this->requireOwn($journal, $researcher);

        if ($request->isMethod('post')) {
            $this->handleShowPost($request, $id);
        }

        $this->journal_record = $journal;
        $this->toc = $this->journal->tableOfContents($id);
        $this->setTemplate('show');
    }

    /** Handle issue add/update/delete and journal status changes posted to show. */
    protected function handleShowPost($request, int $journalId): void
    {
        $action = $request->getParameter('form_action');

        switch ($action) {
            case 'set_status':
                $status = (string) $request->getParameter('status', 'draft');
                if (in_array($status, ['draft', 'published', 'archived'], true)) {
                    $this->journal->setJournalStatus($journalId, $status);
                    $this->getUser()->setFlash('success', 'Journal status updated to ' . $status . '.');
                }
                break;

            case 'add_issue':
                $this->journal->createIssue($journalId, $this->collectIssue($request));
                $this->getUser()->setFlash('success', 'Issue added.');
                break;

            case 'update_issue':
                $issueId = (int) $request->getParameter('issue_id');
                if ($issueId && $this->journal->getIssue($issueId)) {
                    $this->journal->updateIssue($issueId, $this->collectIssue($request));
                    $this->getUser()->setFlash('success', 'Issue updated.');
                }
                break;

            case 'delete_issue':
                $issueId = (int) $request->getParameter('issue_id');
                if ($issueId && $this->journal->getIssue($issueId)) {
                    $this->journal->deleteIssue($issueId);
                    $this->getUser()->setFlash('success', 'Issue removed; its articles were unassigned.');
                }
                break;

            case 'delete':
                $this->journal->deleteJournal($journalId);
                $this->getUser()->setFlash('success', 'Journal deleted.');
                $this->redirect('researchjournal/index');
                break;
        }

        $this->redirect('researchjournal/show?id=' . $journalId);
    }

    // ── Articles / manuscript builder ───────────────────────────────────────

    /** Download an article as Word, ready to submit to its target journal. Owner (or administrator) only. */
    public function executeExportArticle($request)
    {
        $researcher = $this->requireResearcher();
        $article = $this->journal->getArticle((int) $request->getParameter('id'));
        if (!$article) {
            $this->forward404('Article not found');
        }
        $journal = $this->journal->getJournal((int) $article['journal_id']);
        $this->requireOwn($journal, $researcher);

        $target = null;
        $targetId = (int) ($article['target_journal_id'] ?: ($journal['target_journal_id'] ?? 0));
        if ($targetId) {
            $target = DB::table('research_target_journal')->where('id', $targetId)->value('title');
        }
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ReportExportService.php';
        $file = (new \ReportExportService())->exportManuscriptDocx($article, $target);

        $name = trim(preg_replace('/[^A-Za-z0-9]+/', '-', (string) $article['title']), '-') ?: 'manuscript';
        $response = $this->getResponse();
        $response->clearHttpHeaders();
        $response->setContentType('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . substr($name, 0, 80) . '.docx"');
        $response->setHttpHeader('Content-Length', (string) filesize($file));
        $response->sendHttpHeaders();
        readfile($file);
        @unlink($file);

        return sfView::NONE;
    }

    public function executeArticle($request)
    {
        $researcher = $this->requireResearcher();

        $articleId = (int) $request->getParameter('id');
        $journalId = (int) $request->getParameter('journal_id');

        if ($articleId) {
            $article = $this->journal->getArticle($articleId);
            if (!$article) {
                $this->forward404('Article not found');
            }
            $journalId = (int) $article['journal_id'];
        } else {
            $article = null;
        }

        $journal = $this->journal->getJournal($journalId);
        $this->requireOwn($journal, $researcher);

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');
            if ($action === 'delete' && $articleId) {
                $this->journal->deleteArticle($articleId);
                $this->getUser()->setFlash('success', 'Article deleted.');
                $this->redirect('researchjournal/show?id=' . $journalId);
            }

            $data = $this->collectArticle($request);
            if ($articleId) {
                $this->journal->updateArticle($articleId, $data);
                $newId = $articleId;
            } else {
                $newId = $this->journal->createArticle($journalId, $data);
            }
            $this->getUser()->setFlash('success', 'Article saved.');
            $this->redirect('researchjournal/article?id=' . $newId);
        }

        $this->journal_record = $journal;
        $this->article = $article;
        $this->issues = $this->journal->listIssues($journalId);
        $this->styles = ResearchJournalService::REFERENCE_STYLES;
        $this->targetJournals = $this->journal->targetJournalOptions();
        // An article with no target of its own is checked against its manuscript's target.
        $this->validation = ($article && $journal['kind'] === ResearchJournalService::KIND_MANUSCRIPT)
            ? $this->journal->validateManuscript(['target_journal_id' => $article['target_journal_id'] ?: ($journal['target_journal_id'] ?? null)] + $article)
            : [];
        $this->setTemplate('article');
    }

    // ── input collectors ────────────────────────────────────────────────────

    protected function collectJournal($request): array
    {
        return [
            'kind'              => $request->getParameter('kind'),
            'title'             => trim((string) $request->getParameter('title', '')),
            'subtitle'          => $request->getParameter('subtitle') ?: null,
            'issn'              => $request->getParameter('issn') ?: null,
            'eissn'             => $request->getParameter('eissn') ?: null,
            'publisher'         => $request->getParameter('publisher') ?: null,
            'description'       => $request->getParameter('description') ?: null,
            'aims_scope'        => $request->getParameter('aims_scope') ?: null,
            'editor_name'       => $request->getParameter('editor_name') ?: null,
            'editor_email'      => $request->getParameter('editor_email') ?: null,
            'target_journal_id' => $request->getParameter('target_journal_id') ?: null,
            'doi'               => $request->getParameter('doi') ?: null,
        ];
    }

    protected function collectIssue($request): array
    {
        return [
            'volume'      => $request->getParameter('volume') ?: null,
            'number'      => $request->getParameter('number') ?: null,
            'title'       => $request->getParameter('issue_title') ?: ($request->getParameter('title') ?: null),
            'issue_date'  => $request->getParameter('issue_date') ?: null,
            'description' => $request->getParameter('issue_description') ?: null,
            'status'      => in_array($request->getParameter('status'), ['draft', 'published'], true)
                ? $request->getParameter('status') : 'draft',
            'sort_order'  => (int) $request->getParameter('sort_order', 0),
        ];
    }

    protected function collectArticle($request): array
    {
        return [
            'issue_id'          => $request->getParameter('issue_id') ?: null,
            'title'             => trim((string) $request->getParameter('title', '')),
            'authors'           => $request->getParameter('authors') ?: null,
            'abstract'          => $request->getParameter('abstract') ?: null,
            'keywords'          => $request->getParameter('keywords') ?: null,
            'body_markdown'     => (string) $request->getParameter('body_markdown', ''),
            'reference_style'   => $request->getParameter('reference_style') ?: null,
            'target_journal_id' => $request->getParameter('target_journal_id') ?: null,
            'doi'               => $request->getParameter('doi') ?: null,
            'status'            => in_array($request->getParameter('status'), ['draft', 'submitted', 'published'], true)
                ? $request->getParameter('status') : 'draft',
            'sort_order'        => (int) $request->getParameter('sort_order', 0),
        ];
    }
}
