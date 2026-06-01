<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * LectureBuilderActionsTrait - Lecture Builder action methods (#116).
 *
 * PSIS-parity port of Heratio AhgResearch\Controllers\ResearchLectureController.
 *
 * This trait is `use`-d by the research module's actions class
 * (researchActions). It supplies all execute* methods for the lecture
 * builder. The integrator wires it in by adding a single line to the
 * shared modules/research/actions/actions.class.php:
 *
 *     require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/LectureBuilderActionsTrait.php';
 *     // and `use LectureBuilderActionsTrait;` in the class body
 *
 * All routes are under the auth'd /research group. Templates live in
 * modules/research/templates/lecture*Success.php.
 *
 * @package ahgResearchPlugin
 * @version 1.0.0
 */
trait LectureBuilderActionsTrait
{
    private function lectureService(): LectureService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/LectureService.php';

        return new LectureService();
    }

    /**
     * Resolve the current researcher id, gracefully degrading to null.
     */
    private function lectureResearcherId(): ?int
    {
        try {
            if (!$this->getUser()->isAuthenticated()) {
                return null;
            }
            $r = $this->service->getResearcherByUserId($this->getUser()->getAttribute('user_id'));

            return $r ? (int) $r->id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ── Lectures ─────────────────────────────────────────────────────────

    public function executeLectures($request)
    {
        $this->requireLogin();
        $svc = $this->lectureService();

        $this->curriculum = $svc->listLectures('curriculum');
        $this->talks      = $svc->listLectures('talk');
        $this->standalone = $svc->listLectures('standalone');
    }

    public function executeLectureBuilder($request)
    {
        $this->requireLogin();
        $svc = $this->lectureService();

        $id = (int) $request->getParameter('id');
        $lecture = null;
        if ($id) {
            $lecture = $svc->getLecture($id);
            if (!$lecture) {
                $this->forward404('Lecture not found');
            }
        }

        $type = $request->getParameter('type');
        if (!in_array($type, LectureService::TYPES, true)) {
            $type = $lecture['type'] ?? 'standalone';
        }

        if ($request->isMethod('post')) {
            $data = $this->lectureFormData($request);
            if ($id) {
                $svc->updateLecture($id, $data);
                $this->getUser()->setFlash('success', 'Lecture updated.');
            } else {
                $data['researcher_id'] = $this->lectureResearcherId();
                $id = $svc->createLecture($data);
                $this->getUser()->setFlash('success', 'Lecture created.');
            }
            $this->redirect(url_for(['module' => 'research', 'action' => 'lectureShow', 'id' => $id]));
        }

        $this->lecture = $lecture;
        $this->type    = $type;
    }

    public function executeLectureShow($request)
    {
        $this->requireLogin();
        $svc = $this->lectureService();

        $id = (int) $request->getParameter('id');
        $lecture = $svc->getLecture($id);
        if (!$lecture) {
            $this->forward404('Lecture not found');
        }

        $this->lecture   = $lecture;
        $this->sections  = $svc->listSections($id);
        $this->resources = $svc->listResources($id);
    }

    public function executeLectureDelete($request)
    {
        $this->requireLogin();
        if (!$request->isMethod('post')) {
            $this->forward404('POST required');
        }
        $svc = $this->lectureService();
        $id = (int) $request->getParameter('id');
        if ($svc->getLecture($id)) {
            $svc->deleteLecture($id);
            $this->getUser()->setFlash('success', 'Lecture deleted.');
        }
        $this->redirect(url_for(['module' => 'research', 'action' => 'lectures']));
    }

    public function executeLectureStatus($request)
    {
        $this->requireLogin();
        if (!$request->isMethod('post')) {
            $this->forward404('POST required');
        }
        $svc = $this->lectureService();
        $id = (int) $request->getParameter('id');
        if (!$svc->getLecture($id)) {
            $this->forward404('Lecture not found');
        }
        $status = (string) $request->getParameter('status', 'draft');
        if ($svc->setStatus($id, $status)) {
            $this->getUser()->setFlash('success', 'Status updated.');
        } else {
            $this->getUser()->setFlash('error', 'Invalid status.');
        }
        $this->redirect(url_for(['module' => 'research', 'action' => 'lectureShow', 'id' => $id]));
    }

    public function executeLecturePublish($request)
    {
        $this->requireLogin();
        if (!$request->isMethod('post')) {
            $this->forward404('POST required');
        }
        $svc = $this->lectureService();
        $id = (int) $request->getParameter('id');
        if (!$svc->getLecture($id)) {
            $this->forward404('Lecture not found');
        }
        $publish = (bool) $request->getParameter('publish', 1);
        $svc->publish($id, $publish);
        $this->getUser()->setFlash('success', $publish ? 'Lecture published.' : 'Lecture unpublished.');
        $this->redirect(url_for(['module' => 'research', 'action' => 'lectureShow', 'id' => $id]));
    }

    // ── Sections ──────────────────────────────────────────────────────────

    public function executeLectureSectionStore($request)
    {
        $this->requireLogin();
        if (!$request->isMethod('post')) {
            $this->forward404('POST required');
        }
        $svc = $this->lectureService();
        $lectureId = (int) $request->getParameter('id');
        if (!$svc->getLecture($lectureId)) {
            $this->forward404('Lecture not found');
        }
        $svc->createSection($lectureId, $this->sectionFormData($request));
        $this->getUser()->setFlash('success', 'Section added.');
        $this->redirect(url_for(['module' => 'research', 'action' => 'lectureShow', 'id' => $lectureId]));
    }

    public function executeLectureSectionEdit($request)
    {
        $this->requireLogin();
        $svc = $this->lectureService();
        $id = (int) $request->getParameter('id');
        $section = $svc->getSection($id);
        if (!$section) {
            $this->forward404('Section not found');
        }

        if ($request->isMethod('post')) {
            $svc->updateSection($id, $this->sectionFormData($request));
            $this->getUser()->setFlash('success', 'Section saved.');
            $this->redirect(url_for(['module' => 'research', 'action' => 'lectureShow', 'id' => (int) $section['lecture_id']]));
        }

        $this->section = $section;
        $this->lecture = $svc->getLecture((int) $section['lecture_id']);
    }

    public function executeLectureSectionDelete($request)
    {
        $this->requireLogin();
        if (!$request->isMethod('post')) {
            $this->forward404('POST required');
        }
        $svc = $this->lectureService();
        $id = (int) $request->getParameter('id');
        $section = $svc->getSection($id);
        if (!$section) {
            $this->forward404('Section not found');
        }
        $lectureId = (int) $section['lecture_id'];
        $svc->deleteSection($id);
        $this->getUser()->setFlash('success', 'Section removed.');
        $this->redirect(url_for(['module' => 'research', 'action' => 'lectureShow', 'id' => $lectureId]));
    }

    // ── Resources ───────────────────────────────────────────────────────────

    public function executeLectureResourceStore($request)
    {
        $this->requireLogin();
        if (!$request->isMethod('post')) {
            $this->forward404('POST required');
        }
        $svc = $this->lectureService();
        $lectureId = (int) $request->getParameter('id');
        if (!$svc->getLecture($lectureId)) {
            $this->forward404('Lecture not found');
        }
        $svc->createResource($lectureId, [
            'label'         => trim((string) $request->getParameter('label')),
            'url'           => $request->getParameter('url') ?: null,
            'resource_type' => $request->getParameter('resource_type', 'link'),
            'sort_order'    => (int) $request->getParameter('sort_order', 0),
        ]);
        $this->getUser()->setFlash('success', 'Resource added.');
        $this->redirect(url_for(['module' => 'research', 'action' => 'lectureShow', 'id' => $lectureId]));
    }

    public function executeLectureResourceDelete($request)
    {
        $this->requireLogin();
        if (!$request->isMethod('post')) {
            $this->forward404('POST required');
        }
        $svc = $this->lectureService();
        $id = (int) $request->getParameter('id');
        $lectureId = (int) $request->getParameter('lecture_id');
        $svc->deleteResource($id);
        $this->getUser()->setFlash('success', 'Resource removed.');
        if ($lectureId) {
            $this->redirect(url_for(['module' => 'research', 'action' => 'lectureShow', 'id' => $lectureId]));
        }
        $this->redirect(url_for(['module' => 'research', 'action' => 'lectures']));
    }

    // ── form helpers ──────────────────────────────────────────────────────

    private function lectureFormData($request): array
    {
        return [
            'type'                => $request->getParameter('type'),
            'title'               => trim((string) $request->getParameter('title')),
            'subtitle'            => $request->getParameter('subtitle') ?: null,
            'summary'             => $request->getParameter('summary') ?: null,
            'speaker_name'        => $request->getParameter('speaker_name') ?: null,
            'speaker_affiliation' => $request->getParameter('speaker_affiliation') ?: null,
            'scheduled_at'        => $request->getParameter('scheduled_at') ?: null,
            'location'            => $request->getParameter('location') ?: null,
            'duration_minutes'    => $request->getParameter('duration_minutes'),
            'recording_url'       => $request->getParameter('recording_url') ?: null,
            'slides_url'          => $request->getParameter('slides_url') ?: null,
            'curriculum_ref'      => $request->getParameter('curriculum_ref') ?: null,
            'status'              => $request->getParameter('status'),
        ];
    }

    private function sectionFormData($request): array
    {
        return [
            'heading'       => $request->getParameter('heading') ?: null,
            'body_markdown' => (string) $request->getParameter('body_markdown'),
            'media_url'     => $request->getParameter('media_url') ?: null,
            'media_type'    => $request->getParameter('media_type') ?: null,
            'sort_order'    => $request->getParameter('sort_order'),
        ];
    }
}
