<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * SubmissionService — Researcher Self-Description Submission workflow.
 *
 * Wires the (previously schema-only) researcher_submission* tables into a working
 * pipeline: Draft → Submitted → Under Review → Returned/Approved → Published.
 * Researchers describe an archaeological archive against ISAD(G); archivists
 * review and, on approval, publish it as an AtoM information_object (created as a
 * DRAFT for a final archivist check before going public).
 *
 * @package ahgResearchPlugin
 */
class SubmissionService
{
    /** Workflow states (mirrors researcher_submission.status). */
    public const STATES = ['draft', 'submitted', 'under_review', 'returned', 'approved', 'published', 'rejected'];

    /**
     * Guided archaeological submission types → researcher-facing label + the
     * default ISAD(G) level of description the type maps to.
     */
    public static function getTypes(): array
    {
        return [
            'excavation_archive' => ['label' => 'Archaeological Excavation Archive', 'level' => 'Fonds'],
            'survey_archive'     => ['label' => 'Archaeological Survey Archive', 'level' => 'Fonds'],
            'research_project'   => ['label' => 'Research Project Archive', 'level' => 'Fonds'],
            'personal_papers'    => ['label' => 'Researcher Personal Papers', 'level' => 'Fonds'],
            'field_notebook'     => ['label' => 'Field Notebook Collection', 'level' => 'Series'],
            'photographic'       => ['label' => 'Photographic Documentation', 'level' => 'Series'],
            'maps_plans'         => ['label' => 'Maps, Plans and Section Drawings', 'level' => 'Series'],
            'lab_records'        => ['label' => 'Laboratory and Analytical Records', 'level' => 'Series'],
            'reports'            => ['label' => 'Reports, Publications and Grey Literature', 'level' => 'Series'],
            'audiovisual'        => ['label' => 'Audio-visual Documentation', 'level' => 'Series'],
            'born_digital'       => ['label' => 'Born-digital Research Data', 'level' => 'Series'],
            'teaching'           => ['label' => 'Teaching and Field School Records', 'level' => 'Series'],
        ];
    }

    public static function typeLabel(string $key): string
    {
        return self::getTypes()[$key]['label'] ?? ucfirst(str_replace('_', ' ', $key));
    }

    public static function statusLabel(string $status): string
    {
        $map = [
            'draft' => 'Draft', 'submitted' => 'Submitted', 'under_review' => 'Under archival review',
            'returned' => 'Returned for revision', 'approved' => 'Approved', 'published' => 'Published',
            'rejected' => 'Rejected',
        ];

        return $map[$status] ?? ucfirst($status);
    }

    /** Archivist = AtoM administrator (100) or editor (101) ACL group. */
    public static function isArchivist(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        $groups = DB::table('acl_user_group')->where('user_id', $userId)->pluck('group_id')->all();

        return (bool) array_intersect([100, 101], array_map('intval', $groups));
    }

    // =====================================================================
    // Queries
    // =====================================================================

    public function listForUser(int $userId): array
    {
        return DB::table('researcher_submission')
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')->orderByDesc('created_at')
            ->get()->map(fn ($r) => (array) $r)->all();
    }

    /** Archivist review queue: everything past draft (and not yet closed out). */
    public function listForReview(): array
    {
        return DB::table('researcher_submission')
            ->whereIn('status', ['submitted', 'under_review', 'returned', 'approved'])
            ->orderByRaw("FIELD(status,'submitted','under_review','approved','returned')")
            ->orderBy('submitted_at')
            ->get()->map(fn ($r) => (array) $r)->all();
    }

    public function get(int $id): ?array
    {
        $sub = DB::table('researcher_submission')->where('id', $id)->first();
        if (!$sub) {
            return null;
        }
        $sub = (array) $sub;
        $sub['item'] = (array) (DB::table('researcher_submission_item')
            ->where('submission_id', $id)->orderBy('sort_order')->first() ?? []);
        $sub['reviews'] = DB::table('researcher_submission_review as r')
            ->where('submission_id', $id)->orderByDesc('created_at')
            ->get()->map(fn ($x) => (array) $x)->all();

        return $sub;
    }

    // =====================================================================
    // Researcher actions
    // =====================================================================

    /** Create a new draft submission + its primary ISAD(G) item. */
    public function createDraft(?int $userId, ?int $researcherId, string $type, string $title): int
    {
        $types = self::getTypes();
        $type = isset($types[$type]) ? $type : 'research_project';
        $now = date('Y-m-d H:i:s');

        $id = DB::table('researcher_submission')->insertGetId([
            'user_id' => $userId,
            'researcher_id' => $researcherId,
            'title' => $title ?: self::typeLabel($type),
            'status' => 'draft',
            'source_type' => $type,
            'total_items' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('researcher_submission_item')->insert([
            'submission_id' => $id,
            'item_type' => $type,
            'title' => $title ?: self::typeLabel($type),
            'level_of_description' => $types[$type]['level'],
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $id;
    }

    /** Save the ISAD(G) item fields of a draft/returned submission. */
    public function saveItem(int $submissionId, array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $fields = [
            'title', 'identifier', 'level_of_description', 'scope_and_content',
            'extent_and_medium', 'date_display', 'creators', 'subjects', 'places',
            'access_conditions', 'reproduction_conditions', 'notes',
        ];
        $update = ['updated_at' => $now];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $update[$f] = $data[$f];
            }
        }
        DB::table('researcher_submission_item')->where('submission_id', $submissionId)->update($update);
        DB::table('researcher_submission')->where('id', $submissionId)->update([
            'title' => $data['title'] ?? DB::table('researcher_submission')->where('id', $submissionId)->value('title'),
            'updated_at' => $now,
        ]);
    }

    public function submit(int $submissionId, int $userId): void
    {
        $this->transition($submissionId, 'submitted', $userId, ['submitted_at' => date('Y-m-d H:i:s')]);
        $this->log($submissionId, $userId, 'submitted', null);
    }

    // =====================================================================
    // Archivist actions
    // =====================================================================

    public function startReview(int $submissionId, int $reviewerId): void
    {
        $this->transition($submissionId, 'under_review', $reviewerId);
        $this->log($submissionId, $reviewerId, 'under_review', null);
    }

    public function returnForRevision(int $submissionId, int $reviewerId, string $comment): void
    {
        $this->transition($submissionId, 'returned', $reviewerId, ['return_comment' => $comment]);
        $this->log($submissionId, $reviewerId, 'returned', $comment);
    }

    public function approve(int $submissionId, int $reviewerId, string $comment = ''): void
    {
        $this->transition($submissionId, 'approved', $reviewerId);
        $this->log($submissionId, $reviewerId, 'approved', $comment);
    }

    /**
     * Publish an approved submission as a DRAFT AtoM information_object so an
     * archivist makes the final publish in AtoM. Returns the new object id.
     */
    public function publish(int $submissionId, int $reviewerId): int
    {
        $sub = $this->get($submissionId);
        if (!$sub || empty($sub['item'])) {
            throw new \RuntimeException('Submission or its description is missing.');
        }
        $item = $sub['item'];

        $io = new \QubitInformationObject();
        $io->parentId = !empty($sub['parent_object_id'])
            ? (int) $sub['parent_object_id'] : \QubitInformationObject::ROOT_ID;
        $io->title = $item['title'] ?: $sub['title'];
        if (!empty($item['scope_and_content'])) {
            $io->scopeAndContent = $item['scope_and_content'];
        }
        if (!empty($item['extent_and_medium'])) {
            $io->extentAndMedium = $item['extent_and_medium'];
        }
        if (!empty($item['access_conditions'])) {
            $io->accessConditions = $item['access_conditions'];
        }
        if (!empty($item['reproduction_conditions'])) {
            $io->reproductionConditions = $item['reproduction_conditions'];
        }
        $levelId = $this->levelTermId((string) ($item['level_of_description'] ?? ''));
        if ($levelId) {
            $io->levelOfDescriptionId = $levelId;
        }
        if (!empty($sub['repository_id'])) {
            $io->repositoryId = (int) $sub['repository_id'];
        }
        // New objects default to Draft publication status (final archivist check).
        // Skip per-save search indexing: in a programmatic context the on-save
        // OpenSearch update can fail, and a draft need not be indexed until the
        // archivist makes the final publish in AtoM (or search:populate runs).
        $io->indexOnSave = false;
        $io->save();

        $now = date('Y-m-d H:i:s');
        DB::table('researcher_submission_item')->where('id', $item['id'])
            ->update(['published_object_id' => $io->id, 'updated_at' => $now]);
        $this->transition($submissionId, 'published', $reviewerId, ['published_at' => $now]);
        $this->log($submissionId, $reviewerId, 'published', 'Created AtoM description #' . $io->id . ' (draft)');

        return (int) $io->id;
    }

    // =====================================================================
    // Helpers
    // =====================================================================

    private function transition(int $submissionId, string $status, ?int $actorId, array $extra = []): void
    {
        DB::table('researcher_submission')->where('id', $submissionId)
            ->update(array_merge(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], $extra));
    }

    private function log(int $submissionId, ?int $reviewerId, string $action, ?string $comment): void
    {
        DB::table('researcher_submission_review')->insert([
            'submission_id' => $submissionId,
            'reviewer_id' => $reviewerId,
            'action' => $action,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Map an ISAD level name (e.g. "Fonds") to its term id (levels taxonomy). */
    private function levelTermId(string $level): ?int
    {
        if ('' === $level) {
            return null;
        }
        $row = DB::table('term_i18n as ti')
            ->join('term as t', 't.id', '=', 'ti.id')
            ->where('ti.name', $level)
            ->where('t.taxonomy_id', \QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
            ->value('t.id');

        return $row ? (int) $row : null;
    }
}
