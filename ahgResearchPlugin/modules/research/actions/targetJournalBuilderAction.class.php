<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Target-journal directory - create/edit form + persist (#114 / Heratio #1107).
 *
 * GET renders the builder (create when no :id, edit when :id present).
 * POST persists (create or update) and redirects to the show page.
 *
 * Mirrors Heratio ResearchTargetJournalController::create/edit/store/update.
 *
 * @package ahgResearchPlugin
 */
class researchTargetJournalBuilderAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/TargetJournalService.php';
        $service = new TargetJournalService();

        $id = $request->getParameter('id') ? (int) $request->getParameter('id') : null;
        $this->styles = TargetJournalService::REFERENCE_STYLES;
        $this->journal = $id ? $service->get($id) : null;
        if ($id && !$this->journal) {
            $this->forward404('Journal not found');
        }

        if ($request->isMethod('post')) {
            $data = $this->collect($request);

            if (trim((string) $data['title']) === '') {
                $this->getUser()->setFlash('error', 'Title is required.');
            } else {
                if ($id) {
                    $service->update($id, $data);
                    $this->getUser()->setFlash('success', 'Journal updated.');
                    $this->redirect('research/targetJournalShow?id=' . $id);

                    return;
                }
                $newId = $service->create($data);
                $this->getUser()->setFlash('success', 'Journal added to the directory.');
                $this->redirect('research/targetJournalShow?id=' . $newId);

                return;
            }
        }

        $this->sidebarActive = 'targetJournals';
        $this->unreadNotifications = $this->unreadNotifications ?? 0;
    }

    /**
     * Collect + lightly normalise the submitted form fields.
     */
    private function collect($request): array
    {
        $status = $request->getParameter('status');

        return [
            'title'                => $request->getParameter('title'),
            'subtitle'             => $request->getParameter('subtitle'),
            'issn'                 => $request->getParameter('issn'),
            'eissn'                => $request->getParameter('eissn'),
            'publisher'            => $request->getParameter('publisher'),
            'homepage_url'         => $request->getParameter('homepage_url'),
            'submission_url'       => $request->getParameter('submission_url'),
            'languages'            => $request->getParameter('languages'),
            'subject_scope'        => $request->getParameter('subject_scope'),
            'article_types'        => $request->getParameter('article_types'),
            'accreditation'        => $request->getParameter('accreditation'),
            'accreditation_market' => $request->getParameter('accreditation_market'),
            'reference_style'      => $request->getParameter('reference_style'),
            'structure_notes'      => $request->getParameter('structure_notes'),
            'max_words'            => $request->getParameter('max_words'),
            'abstract_max_words'   => $request->getParameter('abstract_max_words'),
            'peer_review'          => $request->getParameter('peer_review'),
            'open_access'          => $request->getParameter('open_access') ? 1 : 0,
            'apc_amount'           => $request->getParameter('apc_amount'),
            'turnaround'           => $request->getParameter('turnaround'),
            'notes'                => $request->getParameter('notes'),
            'status'               => in_array($status, ['active', 'discontinued'], true) ? $status : 'active',
        ];
    }
}
