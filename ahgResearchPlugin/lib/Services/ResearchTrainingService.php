<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ResearchTrainingService - generic training-curriculum + LMS module (#117).
 *
 * PSIS-parity port of the Heratio AhgResearch\Services\ResearchTrainingService.
 *
 * Institution-neutral: a course defines roles/audience, language and pass mark;
 * its modules sequence content (each may reuse a curriculum lecture from the
 * research_lecture table, #116 twin, degrading gracefully if absent, or carry
 * its own Markdown); learners enrol, work through modules (progress tracked),
 * take an assessment, and on passing are issued a certificate. Nothing about any
 * customer is hard-coded — cohort, languages, pass mark and roles are all data.
 *
 * @package ahgResearchPlugin
 * @version 1.0.0
 */
class ResearchTrainingService
{
    public const STATUSES = ['draft', 'published', 'archived'];

    // -- Courses --------------------------------------------------------------

    public function listCourses(?string $status = null): array
    {
        $q = DB::table('training_course')->orderBy('sort_order')->orderBy('title');
        if ($status !== null) {
            $q->where('status', $status);
        }

        return array_map(fn ($c) => (array) $c, $q->get()->all());
    }

    public function getCourse(int $id): ?array
    {
        $row = DB::table('training_course')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    public function createCourse(array $d): int
    {
        return (int) DB::table('training_course')->insertGetId($this->coursePayload($d, true));
    }

    public function updateCourse(int $id, array $d): bool
    {
        return DB::table('training_course')->where('id', $id)->update($this->coursePayload($d, false)) >= 0;
    }

    public function deleteCourse(int $id): void
    {
        $enrolIds = DB::table('training_enrolment')->where('course_id', $id)->pluck('id')->all();
        DB::table('training_progress')->whereIn('enrolment_id', $enrolIds)->delete();
        DB::table('training_certificate')->whereIn('enrolment_id', $enrolIds)->delete();
        DB::table('training_enrolment')->where('course_id', $id)->delete();
        DB::table('training_assessment')->where('course_id', $id)->delete();
        DB::table('training_module')->where('course_id', $id)->delete();
        DB::table('training_course')->where('id', $id)->delete();
    }

    public function setCourseStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }

        return DB::table('training_course')->where('id', $id)->update(['status' => $status, 'updated_at' => $this->now()]) > 0;
    }

    private function coursePayload(array $d, bool $isNew): array
    {
        $p = [
            'title'       => trim((string) ($d['title'] ?? 'Untitled course')),
            'description' => $d['description'] ?? null,
            'audience'    => $d['audience'] ?? null,
            'language'    => $d['language'] ?? null,
            'pass_mark'   => isset($d['pass_mark']) && $d['pass_mark'] !== '' ? (int) $d['pass_mark'] : 80,
            'sort_order'  => (int) ($d['sort_order'] ?? 0),
            'updated_at'  => $this->now(),
        ];
        if ($isNew) {
            $p['researcher_id'] = $d['researcher_id'] ?? null;
            $p['status']        = $d['status'] ?? 'draft';
            $p['created_at']    = $this->now();
        } elseif (isset($d['status'])) {
            $p['status'] = $d['status'];
        }

        return $p;
    }

    // -- Modules --------------------------------------------------------------

    public function listModules(int $courseId): array
    {
        return array_map(
            fn ($m) => (array) $m,
            DB::table('training_module')->where('course_id', $courseId)
                ->orderBy('sort_order')->orderBy('id')->get()->all()
        );
    }

    public function getModule(int $id): ?array
    {
        $row = DB::table('training_module')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    public function createModule(int $courseId, array $d): int
    {
        $md = (string) ($d['body_markdown'] ?? '');

        return (int) DB::table('training_module')->insertGetId([
            'course_id'     => $courseId,
            'title'         => trim((string) ($d['title'] ?? 'Untitled module')),
            'lecture_id'    => !empty($d['lecture_id']) ? (int) $d['lecture_id'] : null,
            'body_markdown' => $md,
            'body_html'     => $this->render($md),
            'sort_order'    => isset($d['sort_order']) && $d['sort_order'] !== '' ? (int) $d['sort_order'] : $this->nextModuleOrder($courseId),
            'created_at'    => $this->now(),
            'updated_at'    => $this->now(),
        ]);
    }

    public function updateModule(int $id, array $d): bool
    {
        $md = (string) ($d['body_markdown'] ?? '');

        return DB::table('training_module')->where('id', $id)->update([
            'title'         => trim((string) ($d['title'] ?? 'Untitled module')),
            'lecture_id'    => !empty($d['lecture_id']) ? (int) $d['lecture_id'] : null,
            'body_markdown' => $md,
            'body_html'     => $this->render($md),
            'sort_order'    => (int) ($d['sort_order'] ?? 0),
            'updated_at'    => $this->now(),
        ]) >= 0;
    }

    public function deleteModule(int $id): void
    {
        DB::table('training_progress')->where('module_id', $id)->delete();
        DB::table('training_module')->where('id', $id)->delete();
    }

    private function nextModuleOrder(int $courseId): int
    {
        return (int) DB::table('training_module')->where('course_id', $courseId)->max('sort_order') + 1;
    }

    /**
     * Curriculum lectures (#116) available to attach as module content.
     * Degrades gracefully to an empty list when the research_lecture table
     * (from the #116 twin) is not present.
     */
    public function curriculumLectures(): array
    {
        if (!$this->tableExists('research_lecture')) {
            return [];
        }

        try {
            return array_map(
                fn ($r) => (array) $r,
                DB::table('research_lecture')->where('type', 'curriculum')
                    ->orderBy('title')->get(['id', 'title'])->all()
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    /** Resolve a reused curriculum lecture's rendered body, if available. */
    public function lectureBody(int $lectureId): ?array
    {
        if (!$this->tableExists('research_lecture')) {
            return null;
        }

        try {
            $row = DB::table('research_lecture')->where('id', $lectureId)->first();
        } catch (\Exception $e) {
            return null;
        }
        if (!$row) {
            return null;
        }
        $row = (array) $row;
        $html = $row['body_html'] ?? null;
        if (!$html && !empty($row['body_markdown'])) {
            $html = $this->render((string) $row['body_markdown']);
        }

        return ['id' => (int) $row['id'], 'title' => $row['title'] ?? '', 'body_html' => (string) $html];
    }

    // -- Assessment -----------------------------------------------------------

    public function getAssessment(int $courseId): ?array
    {
        $row = DB::table('training_assessment')->where('course_id', $courseId)->first();

        return $row ? (array) $row : null;
    }

    /** Decoded questions: [['q'=>..., 'options'=>[...], 'answer'=>idx], ...]. */
    public function questions(int $courseId): array
    {
        $a = $this->getAssessment($courseId);
        if (!$a || empty($a['questions_json'])) {
            return [];
        }
        $q = json_decode($a['questions_json'], true);

        return is_array($q) ? $q : [];
    }

    public function saveAssessment(int $courseId, array $d): void
    {
        $questions = $d['questions'] ?? [];
        $payload = [
            'title'          => $d['title'] ?? 'Assessment',
            'pass_mark'      => isset($d['pass_mark']) && $d['pass_mark'] !== '' ? (int) $d['pass_mark'] : null,
            'questions_json' => json_encode(array_values($questions), JSON_UNESCAPED_SLASHES),
            'updated_at'     => $this->now(),
        ];
        $existing = $this->getAssessment($courseId);
        if ($existing) {
            DB::table('training_assessment')->where('id', $existing['id'])->update($payload);
        } else {
            $payload['course_id'] = $courseId;
            $payload['created_at'] = $this->now();
            DB::table('training_assessment')->insert($payload);
        }
    }

    // -- Enrolment + progress -------------------------------------------------

    public function listEnrolments(int $courseId): array
    {
        return array_map(
            fn ($e) => (array) $e,
            DB::table('training_enrolment')->where('course_id', $courseId)
                ->orderByDesc('enrolled_at')->get()->all()
        );
    }

    public function getEnrolment(int $id): ?array
    {
        $row = DB::table('training_enrolment')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    /** Enrolments for a given user across courses (learner self-service list). */
    public function enrolmentsForUser(int $userId): array
    {
        return array_map(
            fn ($e) => (array) $e,
            DB::table('training_enrolment as e')
                ->leftJoin('training_course as c', 'e.course_id', '=', 'c.id')
                ->where('e.user_id', $userId)
                ->orderByDesc('e.enrolled_at')
                ->select('e.*', 'c.title as course_title')
                ->get()->all()
        );
    }

    public function enrol(int $courseId, array $d): int
    {
        return (int) DB::table('training_enrolment')->insertGetId([
            'course_id'     => $courseId,
            'user_id'       => !empty($d['user_id']) ? (int) $d['user_id'] : null,
            'learner_name'  => $d['learner_name'] ?? null,
            'learner_email' => $d['learner_email'] ?? null,
            'status'        => 'enrolled',
            'enrolled_at'   => $this->now(),
            'created_at'    => $this->now(),
            'updated_at'    => $this->now(),
        ]);
    }

    public function deleteEnrolment(int $id): void
    {
        DB::table('training_progress')->where('enrolment_id', $id)->delete();
        DB::table('training_certificate')->where('enrolment_id', $id)->delete();
        DB::table('training_enrolment')->where('id', $id)->delete();
    }

    public function completedModuleIds(int $enrolmentId): array
    {
        return array_map(
            fn ($i) => (int) $i,
            DB::table('training_progress')->where('enrolment_id', $enrolmentId)
                ->where('completed', 1)->pluck('module_id')->all()
        );
    }

    public function markModule(int $enrolmentId, int $moduleId, bool $completed = true): void
    {
        DB::table('training_progress')->updateOrInsert(
            ['enrolment_id' => $enrolmentId, 'module_id' => $moduleId],
            ['completed' => $completed ? 1 : 0, 'completed_at' => $completed ? $this->now() : null, 'updated_at' => $this->now()]
        );
        $enrol = $this->getEnrolment($enrolmentId);
        if ($enrol && $enrol['status'] === 'enrolled') {
            DB::table('training_enrolment')->where('id', $enrolmentId)->update(['status' => 'in_progress', 'updated_at' => $this->now()]);
        }
    }

    /**
     * Score an assessment attempt, record the best score, and — when all modules
     * are complete AND the score meets the pass mark — mark the enrolment
     * completed and issue a certificate.
     *
     * Returns [score, passed, all_modules_done, pass_mark, certificate_no?].
     */
    public function submitAssessment(int $enrolmentId, array $answers): array
    {
        $enrol = $this->getEnrolment($enrolmentId);
        if (!$enrol) {
            return ['score' => 0, 'passed' => false];
        }
        $courseId = (int) $enrol['course_id'];
        $questions = $this->questions($courseId);
        $total = count($questions);
        $correct = 0;
        foreach ($questions as $i => $q) {
            if (isset($answers[$i]) && (int) $answers[$i] === (int) ($q['answer'] ?? -1)) {
                $correct++;
            }
        }
        $score = $total > 0 ? (int) round($correct / $total * 100) : 0;

        $course = $this->getCourse($courseId);
        $assessment = $this->getAssessment($courseId);
        $passMark = $assessment && $assessment['pass_mark'] !== null ? (int) $assessment['pass_mark'] : (int) ($course['pass_mark'] ?? 80);

        $allModulesDone = $this->allModulesComplete($enrolmentId, $courseId);
        $passed = $total > 0 && $score >= $passMark;

        // Record best score.
        $best = max((int) ($enrol['score'] ?? 0), $score);
        $update = ['score' => $best, 'updated_at' => $this->now()];

        $certNo = null;
        if ($passed && $allModulesDone) {
            $update['status'] = 'completed';
            $update['completed_at'] = $this->now();
            $certNo = $this->issueCertificate($enrolmentId, $best);
        }
        DB::table('training_enrolment')->where('id', $enrolmentId)->update($update);

        return ['score' => $score, 'passed' => $passed, 'all_modules_done' => $allModulesDone, 'pass_mark' => $passMark, 'certificate_no' => $certNo];
    }

    public function allModulesComplete(int $enrolmentId, int $courseId): bool
    {
        $moduleIds = array_map(
            fn ($i) => (int) $i,
            DB::table('training_module')->where('course_id', $courseId)->pluck('id')->all()
        );
        if (!$moduleIds) {
            return true; // a course with no modules is trivially "covered"
        }
        $done = $this->completedModuleIds($enrolmentId);

        return count(array_diff($moduleIds, $done)) === 0;
    }

    public function getCertificate(int $enrolmentId): ?array
    {
        $row = DB::table('training_certificate')->where('enrolment_id', $enrolmentId)->first();

        return $row ? (array) $row : null;
    }

    private function issueCertificate(int $enrolmentId, int $score): string
    {
        $existing = $this->getCertificate($enrolmentId);
        if ($existing) {
            return $existing['certificate_no'];
        }
        $no = 'CERT-' . str_pad((string) $enrolmentId, 6, '0', STR_PAD_LEFT) . '-' . substr(md5($enrolmentId . '|' . $score), 0, 6);
        $no = strtoupper($no);
        DB::table('training_certificate')->insert([
            'enrolment_id'   => $enrolmentId,
            'certificate_no' => $no,
            'score'          => $score,
            'issued_at'      => $this->now(),
            'created_at'     => $this->now(),
        ]);

        return $no;
    }

    // -- helpers --------------------------------------------------------------

    public function render(string $md): string
    {
        if ($md === '') {
            return '';
        }
        $parsedownPath = sfConfig::get('sf_root_dir') . '/vendor/parsedown/Parsedown.php';
        if (is_file($parsedownPath)) {
            require_once $parsedownPath;
            if (class_exists('Parsedown')) {
                $pd = new Parsedown();
                if (method_exists($pd, 'setSafeMode')) {
                    $pd->setSafeMode(true);
                }

                return (string) $pd->text($md);
            }
        }

        // Fallback: escape and convert line breaks so content is never lost.
        return nl2br(htmlspecialchars($md, ENT_QUOTES, 'UTF-8'));
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::schema()->hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }
}
