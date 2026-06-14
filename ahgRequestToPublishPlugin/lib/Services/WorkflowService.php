<?php

namespace ahgRequestToPublishPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Publication-request workflow: receipt tokens (anonymous tracking), curator
 * triage/assignment, and peer review. Companion to RequestToPublishService;
 * operates on rtp_workflow / rtp_review without touching the object-coupled
 * request_to_publish core table.
 */
class WorkflowService
{
    public const TRIAGE = ['new', 'triaged', 'in_review', 'decided'];
    public const PRIORITIES = ['low', 'normal', 'high'];
    public const VERDICTS = ['recommend_approve', 'recommend_reject', 'needs_changes', 'abstain'];

    // AtoM term ids used by request_to_publish_i18n.status_id.
    private const STATUS_LABELS = [220 => 'In review', 219 => 'Approved', 221 => 'Rejected'];

    /** Ensure a workflow row exists for a request; returns its receipt token. */
    public function ensureWorkflow(int $requestId, bool $isAnonymous = false): string
    {
        $existing = DB::table('rtp_workflow')->where('request_id', $requestId)->first();
        if ($existing) {
            return (string) $existing->receipt_token;
        }

        $token = $this->generateToken();
        DB::table('rtp_workflow')->insert([
            'request_id' => $requestId,
            'receipt_token' => $token,
            'is_anonymous' => $isAnonymous ? 1 : 0,
            'triage_status' => 'new',
            'priority' => 'normal',
        ]);

        return $token;
    }

    private function generateToken(): string
    {
        for ($i = 0; $i < 5; $i++) {
            $t = bin2hex(random_bytes(16));
            if (!DB::table('rtp_workflow')->where('receipt_token', $t)->exists()) {
                return $t;
            }
        }

        return bin2hex(random_bytes(16));
    }

    /** Public receipt lookup: workflow + request status by token. */
    public function getByToken(string $token): ?object
    {
        return DB::table('rtp_workflow as w')
            ->leftJoin('request_to_publish_i18n as r', function ($j) {
                $j->on('r.id', '=', 'w.request_id')->where('r.culture', '=', 'en');
            })
            ->where('w.receipt_token', $token)
            ->first([
                'w.request_id', 'w.receipt_token', 'w.triage_status', 'w.created_at',
                'r.rtp_name', 'r.rtp_surname', 'r.status_id', 'r.object_id', 'r.unique_identifier',
            ]);
    }

    public function getByRequest(int $requestId): ?object
    {
        return DB::table('rtp_workflow')->where('request_id', $requestId)->first();
    }

    /** Curator inbox: workflow rows joined to request + object title/slug. */
    public function inbox(array $filters = []): array
    {
        $q = DB::table('rtp_workflow as w')
            ->leftJoin('request_to_publish_i18n as r', function ($j) {
                $j->on('r.id', '=', 'w.request_id')->where('r.culture', '=', 'en');
            })
            ->leftJoin('slug as s', function ($j) {
                $j->on('s.object_id', '=', 'r.object_id');
            });

        if (!empty($filters['triage_status'])) {
            $q->where('w.triage_status', $filters['triage_status']);
        }
        if (!empty($filters['priority'])) {
            $q->where('w.priority', $filters['priority']);
        }

        $rows = $q->orderByRaw("FIELD(w.priority,'high','normal','low')")
            ->orderByDesc('w.created_at')
            ->limit(200)
            ->get([
                'w.request_id', 'w.receipt_token', 'w.triage_status', 'w.priority',
                'w.assigned_name', 'w.is_anonymous', 'w.created_at',
                'r.rtp_name', 'r.rtp_surname', 'r.rtp_email', 'r.status_id', 'r.object_id', 's.slug',
            ]);

        return array_map(fn ($x) => (array) $x, $rows->all());
    }

    public function counts(): array
    {
        $out = ['new' => 0, 'triaged' => 0, 'in_review' => 0, 'decided' => 0];
        foreach (DB::table('rtp_workflow')->select('triage_status', DB::raw('COUNT(*) as c'))->groupBy('triage_status')->get() as $r) {
            $out[(string) $r->triage_status] = (int) $r->c;
        }

        return $out;
    }

    public function assign(int $requestId, ?int $userId, ?string $name): void
    {
        DB::table('rtp_workflow')->where('request_id', $requestId)->update([
            'assigned_to' => $userId ?: null,
            'assigned_name' => $name ?: null,
            'triage_status' => 'triaged',
        ]);
    }

    public function setTriage(int $requestId, string $status): void
    {
        if (!in_array($status, self::TRIAGE, true)) {
            return;
        }
        DB::table('rtp_workflow')->where('request_id', $requestId)->update(['triage_status' => $status]);
    }

    public function setPriority(int $requestId, string $priority): void
    {
        if (!in_array($priority, self::PRIORITIES, true)) {
            return;
        }
        DB::table('rtp_workflow')->where('request_id', $requestId)->update(['priority' => $priority]);
    }

    public function setNotes(int $requestId, ?string $notes): void
    {
        DB::table('rtp_workflow')->where('request_id', $requestId)->update(['internal_notes' => $notes ?: null]);
    }

    // ---- peer review ------------------------------------------------------

    public function addReview(int $requestId, array $data): int
    {
        $verdict = in_array($data['verdict'] ?? '', self::VERDICTS, true) ? $data['verdict'] : 'abstain';

        return (int) DB::table('rtp_review')->insertGetId([
            'request_id' => $requestId,
            'reviewer_id' => !empty($data['reviewer_id']) ? (int) $data['reviewer_id'] : null,
            'reviewer_name' => $data['reviewer_name'] ?? null,
            'verdict' => $verdict,
            'comments' => $data['comments'] ?? null,
        ]);
    }

    public function getReviews(int $requestId): array
    {
        return DB::table('rtp_review')->where('request_id', $requestId)->orderByDesc('created_at')->get()->all();
    }

    public function statusLabel(?int $statusId): string
    {
        return self::STATUS_LABELS[$statusId] ?? 'Unknown';
    }
}
