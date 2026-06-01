<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * trainingActions - generic training-curriculum + LMS UI (#117).
 *
 * PSIS-parity port of the Heratio AhgResearch\Controllers\ResearchTrainingController.
 *
 * Admin/builder: courses, modules (lecture-linked), assessment, enrolment.
 * Learner: work modules, take the assessment, earn a certificate. Institution-
 * neutral - roles/cohort/languages/pass-mark are all data.
 *
 * @package ahgResearchPlugin
 * @version 1.0.0
 */
class trainingActions extends AhgController
{
    /** @var ResearchTrainingService */
    protected $service;

    public function boot(): void
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ResearchTrainingService.php';
        $this->service = new ResearchTrainingService();
        $this->sidebarActive = 'training';
        $this->unreadNotifications = 0;
    }

    protected function requireAuth(): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
    }

    protected function currentUserId(): ?int
    {
        if (!$this->getUser()->isAuthenticated()) {
            return null;
        }
        $uid = $this->getUser()->getAttribute('user_id');

        return $uid ? (int) $uid : null;
    }

    protected function setFlash(string $type, string $message): void
    {
        $this->getUser()->setFlash($type, $message);
    }

    // -- Courses (builder) ----------------------------------------------------

    public function executeIndex($request)
    {
        $this->requireAuth();
        $this->courses = $this->service->listCourses();
        $this->myEnrolments = ($uid = $this->currentUserId()) ? $this->service->enrolmentsForUser($uid) : [];
    }

    public function executeBuilder($request)
    {
        $this->requireAuth();
        $id = (int) $request->getParameter('id');
        $this->course = $id ? $this->service->getCourse($id) : null;
        if ($id && !$this->course) {
            $this->forward404('Course not found');
        }

        if ($request->isMethod('post')) {
            $data = $this->courseInput($request);
            if ($this->course) {
                $this->service->updateCourse((int) $this->course['id'], $data);
                $this->setFlash('success', 'Course updated.');
                $this->redirect('/training/' . (int) $this->course['id']);
            } else {
                $data['researcher_id'] = $this->researcherId();
                $newId = $this->service->createCourse($data);
                $this->setFlash('success', 'Course created.');
                $this->redirect('/training/' . $newId);
            }
        }
    }

    public function executeShow($request)
    {
        $this->requireAuth();
        $id = (int) $request->getParameter('id');
        $this->course = $this->service->getCourse($id);
        if (!$this->course) {
            $this->forward404('Course not found');
        }
        $this->modules = $this->service->listModules($id);
        $this->assessment = $this->service->getAssessment($id);
        $this->questions = $this->service->questions($id);
        $this->enrolments = $this->service->listEnrolments($id);
        $this->lectures = $this->service->curriculumLectures();
    }

    public function executeDestroy($request)
    {
        $this->requireAuth();
        $id = (int) $request->getParameter('id');
        if ($request->isMethod('post')) {
            $this->service->deleteCourse($id);
            $this->setFlash('success', 'Course deleted.');
        }
        $this->redirect('/training');
    }

    public function executeSetStatus($request)
    {
        $this->requireAuth();
        $id = (int) $request->getParameter('id');
        if ($request->isMethod('post')) {
            $this->service->setCourseStatus($id, (string) $request->getParameter('status', 'draft'));
            $this->setFlash('success', 'Status updated.');
        }
        $this->redirect('/training/' . $id);
    }

    // -- Modules --------------------------------------------------------------

    public function executeStoreModule($request)
    {
        $this->requireAuth();
        $courseId = (int) $request->getParameter('id');
        if (!$this->service->getCourse($courseId)) {
            $this->forward404('Course not found');
        }
        if ($request->isMethod('post')) {
            $this->service->createModule($courseId, $this->moduleInput($request));
            $this->setFlash('success', 'Module added.');
        }
        $this->redirect('/training/' . $courseId);
    }

    public function executeEditModule($request)
    {
        $this->requireAuth();
        $id = (int) $request->getParameter('id');
        $this->module = $this->service->getModule($id);
        if (!$this->module) {
            $this->forward404('Module not found');
        }
        $this->course = $this->service->getCourse((int) $this->module['course_id']);
        $this->lectures = $this->service->curriculumLectures();

        if ($request->isMethod('post')) {
            if ($request->getParameter('form_action') === 'delete') {
                $courseId = (int) $this->module['course_id'];
                $this->service->deleteModule($id);
                $this->setFlash('success', 'Module removed.');
                $this->redirect('/training/' . $courseId);
            }
            $this->service->updateModule($id, $this->moduleInput($request));
            $this->setFlash('success', 'Module saved.');
            $this->redirect('/training/' . (int) $this->module['course_id']);
        }
    }

    // -- Assessment -----------------------------------------------------------

    public function executeEditAssessment($request)
    {
        $this->requireAuth();
        $courseId = (int) $request->getParameter('id');
        $this->course = $this->service->getCourse($courseId);
        if (!$this->course) {
            $this->forward404('Course not found');
        }
        $this->assessment = $this->service->getAssessment($courseId);
        $this->questions = $this->service->questions($courseId);

        if ($request->isMethod('post')) {
            $questions = [];
            foreach ((array) $request->getParameter('q', []) as $i => $qtext) {
                $qtext = trim((string) $qtext);
                if ($qtext === '') {
                    continue;
                }
                $optionsParam = (array) $request->getParameter('options', []);
                $rawOptions = (array) ($optionsParam[$i] ?? []);
                $options = array_values(array_filter(array_map('trim', $rawOptions), fn ($o) => $o !== ''));
                if (count($options) < 2) {
                    continue;
                }
                $answerParam = (array) $request->getParameter('answer', []);
                $answer = (int) ($answerParam[$i] ?? 0);
                $questions[] = ['q' => $qtext, 'options' => $options, 'answer' => min($answer, count($options) - 1)];
            }
            $this->service->saveAssessment($courseId, [
                'title'     => $request->getParameter('title'),
                'pass_mark' => $request->getParameter('pass_mark'),
                'questions' => $questions,
            ]);
            $this->setFlash('success', 'Assessment saved (' . count($questions) . ' questions).');
            $this->redirect('/training/' . $courseId);
        }
    }

    // -- Enrolment ------------------------------------------------------------

    public function executeEnrol($request)
    {
        $this->requireAuth();
        $courseId = (int) $request->getParameter('id');
        if (!$this->service->getCourse($courseId)) {
            $this->forward404('Course not found');
        }
        if ($request->isMethod('post')) {
            $name = trim((string) $request->getParameter('learner_name'));
            if ($name === '') {
                $this->setFlash('error', 'Learner name is required.');
                $this->redirect('/training/' . $courseId);
            }
            $this->service->enrol($courseId, [
                'learner_name'  => $name,
                'learner_email' => $request->getParameter('learner_email') ?: null,
                'user_id'       => $request->getParameter('user_id') ?: $this->currentUserId(),
            ]);
            $this->setFlash('success', 'Learner enrolled.');
        }
        $this->redirect('/training/' . $courseId);
    }

    public function executeDestroyEnrolment($request)
    {
        $this->requireAuth();
        $id = (int) $request->getParameter('id');
        $enrol = $this->service->getEnrolment($id);
        if (!$enrol) {
            $this->forward404('Enrolment not found');
        }
        $courseId = (int) $enrol['course_id'];
        if ($request->isMethod('post')) {
            $this->service->deleteEnrolment($id);
            $this->setFlash('success', 'Enrolment removed.');
        }
        $this->redirect('/training/' . $courseId);
    }

    // -- Learner flow ---------------------------------------------------------

    public function executeLearn($request)
    {
        $this->requireAuth();
        $enrolmentId = (int) $request->getParameter('id');
        $this->enrol = $this->service->getEnrolment($enrolmentId);
        if (!$this->enrol) {
            $this->forward404('Enrolment not found');
        }
        $courseId = (int) $this->enrol['course_id'];
        $this->course = $this->service->getCourse($courseId);
        $this->modules = $this->service->listModules($courseId);
        // Attach reused-lecture HTML where a module references a curriculum lecture.
        foreach ($this->modules as &$m) {
            if (!empty($m['lecture_id'])) {
                $lec = $this->service->lectureBody((int) $m['lecture_id']);
                $m['lecture_html'] = $lec['body_html'] ?? null;
                $m['lecture_title'] = $lec['title'] ?? null;
            }
        }
        unset($m);
        $this->doneIds = $this->service->completedModuleIds($enrolmentId);
        $this->questions = $this->service->questions($courseId);
        $this->allDone = $this->service->allModulesComplete($enrolmentId, $courseId);
        $this->certificate = $this->service->getCertificate($enrolmentId);
    }

    public function executeCompleteModule($request)
    {
        $this->requireAuth();
        $enrolmentId = (int) $request->getParameter('id');
        $moduleId = (int) $request->getParameter('module_id');
        if (!$this->service->getEnrolment($enrolmentId)) {
            $this->forward404('Enrolment not found');
        }
        if ($request->isMethod('post')) {
            $completed = $request->getParameter('completed', '1') !== '0';
            $this->service->markModule($enrolmentId, $moduleId, $completed);
            $this->setFlash('success', 'Progress saved.');
        }
        $this->redirect('/training/learn/' . $enrolmentId);
    }

    public function executeTakeAssessment($request)
    {
        $this->requireAuth();
        $enrolmentId = (int) $request->getParameter('id');
        $this->enrol = $this->service->getEnrolment($enrolmentId);
        if (!$this->enrol) {
            $this->forward404('Enrolment not found');
        }
        $courseId = (int) $this->enrol['course_id'];
        $this->course = $this->service->getCourse($courseId);
        $this->questions = $this->service->questions($courseId);
        $this->allDone = $this->service->allModulesComplete($enrolmentId, $courseId);
    }

    public function executeSubmitAssessment($request)
    {
        $this->requireAuth();
        $enrolmentId = (int) $request->getParameter('id');
        if (!$this->service->getEnrolment($enrolmentId)) {
            $this->forward404('Enrolment not found');
        }
        $answers = array_map('intval', (array) $request->getParameter('answer', []));
        $result = $this->service->submitAssessment($enrolmentId, $answers);

        $msg = 'You scored ' . $result['score'] . '%.';
        if (!empty($result['passed']) && !empty($result['certificate_no'])) {
            $msg .= ' Passed - certificate ' . $result['certificate_no'] . ' issued.';
        } elseif (!empty($result['passed']) && empty($result['all_modules_done'])) {
            $msg .= ' Passed the assessment, but complete all modules to be certified.';
        } else {
            $msg .= ' Pass mark is ' . ($result['pass_mark'] ?? 0) . '%.';
        }
        $this->setFlash('success', $msg);
        $this->redirect('/training/learn/' . $enrolmentId);
    }

    public function executeCertificate($request)
    {
        $this->requireAuth();
        $enrolmentId = (int) $request->getParameter('id');
        $this->enrol = $this->service->getEnrolment($enrolmentId);
        if (!$this->enrol) {
            $this->forward404('Enrolment not found');
        }
        $this->cert = $this->service->getCertificate($enrolmentId);
        if (!$this->cert) {
            $this->forward404('Certificate not found');
        }
        $this->course = $this->service->getCourse((int) $this->enrol['course_id']);
    }

    // -- validation + helpers -------------------------------------------------

    private function courseInput($request): array
    {
        $status = (string) $request->getParameter('status', 'draft');
        if (!in_array($status, ResearchTrainingService::STATUSES, true)) {
            $status = 'draft';
        }

        return [
            'title'       => (string) $request->getParameter('title', 'Untitled course'),
            'description' => $request->getParameter('description'),
            'audience'    => $request->getParameter('audience'),
            'language'    => $request->getParameter('language'),
            'pass_mark'   => $request->getParameter('pass_mark'),
            'status'      => $status,
            'sort_order'  => $request->getParameter('sort_order'),
        ];
    }

    private function moduleInput($request): array
    {
        return [
            'title'         => (string) $request->getParameter('title', 'Untitled module'),
            'lecture_id'    => $request->getParameter('lecture_id') ?: null,
            'body_markdown' => $request->getParameter('body_markdown'),
            'sort_order'    => $request->getParameter('sort_order'),
        ];
    }

    private function researcherId(): ?int
    {
        $uid = $this->currentUserId();
        if (!$uid) {
            return null;
        }
        try {
            if (!DB::schema()->hasTable('researcher')) {
                return null;
            }
            $r = DB::table('researcher')->where('user_id', $uid)->first();

            return $r ? (int) $r->id : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
