<?php

namespace AhgArtworkRequest\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Artwork placement requests.
 *
 * The shape is deliberately small. A request is captured, the people
 * responsible are told, availability is reported, and whatever was decided is
 * written down. The decision itself is a conversation between people; this
 * records it rather than running it.
 */
class ArtworkRequestService
{
    /**
     * Availability for one work over a date range.
     *
     * Three sources have to agree before a work is free, and only the first was
     * already checked anywhere:
     *
     *   ahgLoanPlugin        the work is out on an institutional loan
     *   artwork_request      someone else has already asked for it
     *   exhibition checklist it is committed to a show
     *
     * ahgLoanPlugin's LoanCalendarService::getObjectAvailability() covers the
     * first and is used when the plugin is present. It cannot see the other two,
     * which is why this exists rather than calling straight through to it.
     *
     * Returns a list of clashes, empty when the work is free. Never throws on a
     * missing optional plugin: a site without loans still gets a useful answer.
     */
    public static function findConflicts(int $objectId, string $from, string $to, ?int $ignoreRequestId = null): array
    {
        $conflicts = [];

        // Other placement requests, live ones only. A declined or returned
        // request holds nothing.
        $query = DB::table('artwork_request_object as aro')
            ->join('artwork_request as ar', 'ar.id', '=', 'aro.request_id')
            ->where('aro.information_object_id', $objectId)
            ->whereIn('ar.status', ['submitted', 'approved', 'fulfilled'])
            ->whereIn('aro.status', ['requested', 'approved', 'issued'])
            // Overlap: starts before the other ends, and ends after it starts.
            ->where('ar.requested_from', '<=', $to)
            ->where('ar.requested_to', '>=', $from);

        if ($ignoreRequestId) {
            $query->where('ar.id', '!=', $ignoreRequestId);
        }

        foreach ($query->select('ar.id', 'ar.request_number', 'ar.requester_name', 'ar.requested_from', 'ar.requested_to', 'ar.status')->get() as $row) {
            $conflicts[] = [
                'source' => 'request',
                'reference' => $row->request_number,
                'who' => $row->requester_name,
                'from' => $row->requested_from,
                'to' => $row->requested_to,
                'detail' => sprintf('Requested by %s (%s)', $row->requester_name ?: 'unknown', $row->status),
            ];
        }

        // Institutional loans, when ahgLoanPlugin is installed.
        if (self::hasTable('ahg_loan_object') && self::hasTable('ahg_loan')) {
            $loans = DB::table('ahg_loan_object as lo')
                ->join('ahg_loan as l', 'l.id', '=', 'lo.loan_id')
                ->where('lo.information_object_id', $objectId)
                ->whereNotIn('l.status', ['cancelled', 'completed', 'returned'])
                ->where('l.start_date', '<=', $to)
                ->where('l.end_date', '>=', $from)
                ->select('l.loan_number', 'l.partner_institution', 'l.start_date', 'l.end_date')
                ->get();

            foreach ($loans as $row) {
                $conflicts[] = [
                    'source' => 'loan',
                    'reference' => $row->loan_number,
                    'who' => $row->partner_institution,
                    'from' => $row->start_date,
                    'to' => $row->end_date,
                    'detail' => 'On loan to '.$row->partner_institution,
                ];
            }
        }

        // Exhibition commitments, when ahgExhibitionPlugin is installed.
        if (self::hasTable('exhibition_object') && self::hasTable('exhibition')) {
            $shows = DB::table('exhibition_object as eo')
                ->join('exhibition as e', 'e.id', '=', 'eo.exhibition_id')
                ->where('eo.information_object_id', $objectId)
                ->where('e.start_date', '<=', $to)
                ->where('e.end_date', '>=', $from)
                ->select('e.title', 'e.start_date', 'e.end_date')
                ->get();

            foreach ($shows as $row) {
                $conflicts[] = [
                    'source' => 'exhibition',
                    'reference' => $row->title,
                    'who' => $row->title,
                    'from' => $row->start_date,
                    'to' => $row->end_date,
                    'detail' => 'Committed to exhibition: '.$row->title,
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Create a request and, if it is being submitted, tell the approvers.
     *
     * @param array $objectIds AtoM information object ids
     */
    public static function create(array $data, array $objectIds, bool $submit = true): int
    {
        $number = self::nextRequestNumber();

        $requestId = DB::table('artwork_request')->insertGetId([
            'request_number' => $number,
            'requester_user_id' => $data['requester_user_id'] ?? null,
            'requester_name' => $data['requester_name'] ?? null,
            'requester_email' => $data['requester_email'] ?? null,
            'department' => $data['department'] ?? null,
            'status' => $submit ? 'submitted' : 'draft',
            'purpose' => $data['purpose'] ?? null,
            'justification' => $data['justification'] ?? null,
            'requested_from' => $data['requested_from'] ?? null,
            'requested_to' => $data['requested_to'] ?? null,
            'placement_building' => $data['placement_building'] ?? null,
            'placement_floor' => $data['placement_floor'] ?? null,
            'placement_room' => $data['placement_room'] ?? null,
            'placement_occupant' => $data['placement_occupant'] ?? null,
            'placement_notes' => $data['placement_notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($objectIds as $objectId) {
            $objectId = (int) $objectId;

            if ($objectId < 1) {
                continue;
            }

            $conflicts = ($data['requested_from'] ?? null) && ($data['requested_to'] ?? null)
                ? self::findConflicts($objectId, $data['requested_from'], $data['requested_to'], $requestId)
                : [];

            DB::table('artwork_request_object')->insert([
                'request_id' => $requestId,
                'information_object_id' => $objectId,
                'object_title' => self::objectTitle($objectId),
                'object_identifier' => self::objectIdentifier($objectId),
                'status' => 'requested',
                'conflict_note' => $conflicts
                    ? implode('; ', array_column($conflicts, 'detail'))
                    : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        self::log($requestId, $submit ? 'submitted' : 'created', $data['requester_name'] ?? null, null, $data['requester_user_id'] ?? null);

        if ($submit) {
            self::notifyApprovers($requestId);
        }

        return $requestId;
    }

    /**
     * Record a decision.
     *
     * Per work rather than per request, because a curator routinely approves two
     * of the three works asked for. $decisions maps artwork_request_object id to
     * 'approved' or 'declined'.
     */
    public static function decide(int $requestId, array $decisions, ?string $notes, ?int $reviewerId, string $channel = 'system'): bool
    {
        $request = self::get($requestId);

        if (!$request) {
            return false;
        }

        foreach ($decisions as $objectRowId => $decision) {
            if (!in_array($decision, ['approved', 'declined'], true)) {
                continue;
            }

            DB::table('artwork_request_object')
                ->where('id', (int) $objectRowId)
                ->where('request_id', $requestId)
                ->update(['status' => $decision, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        // The request is approved if anything on it was.
        $anyApproved = DB::table('artwork_request_object')
            ->where('request_id', $requestId)
            ->where('status', 'approved')
            ->exists();

        DB::table('artwork_request')->where('id', $requestId)->update([
            'status' => $anyApproved ? 'approved' : 'declined',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'review_notes' => $notes,
            'decision_channel' => 'offline' === $channel ? 'offline' : 'system',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        self::log($requestId, $anyApproved ? 'approved' : 'declined', null, $notes, $reviewerId);
        self::notifyRequester($requestId, $anyApproved ? 'approved' : 'declined');

        return true;
    }

    /**
     * What is out, and what is late.
     *
     * The screen the programme lives or dies on. Works go out easily; they come
     * back when someone chases them, and nobody chases what they cannot see.
     */
    public static function placements(bool $overdueOnly = false): array
    {
        $query = DB::table('artwork_request_object as aro')
            ->join('artwork_request as ar', 'ar.id', '=', 'aro.request_id')
            ->whereIn('aro.status', ['approved', 'issued'])
            ->whereIn('ar.status', ['approved', 'fulfilled']);

        if ($overdueOnly) {
            $query->where('ar.requested_to', '<', date('Y-m-d'));
        }

        return $query->select(
            'aro.id', 'aro.object_title', 'aro.object_identifier', 'aro.status', 'aro.issued_at',
            'ar.request_number', 'ar.requester_name', 'ar.department',
            'ar.placement_building', 'ar.placement_room', 'ar.placement_occupant',
            'ar.requested_from', 'ar.requested_to'
        )->orderBy('ar.requested_to')->get()->all();
    }

    public static function get(int $id)
    {
        return DB::table('artwork_request')->where('id', $id)->first();
    }

    public static function objects(int $requestId): array
    {
        return DB::table('artwork_request_object')
            ->where('request_id', $requestId)
            ->orderBy('id')
            ->get()
            ->all();
    }

    public static function logEntries(int $requestId): array
    {
        return DB::table('artwork_request_log')
            ->where('request_id', $requestId)
            ->orderByDesc('created_at')
            ->get()
            ->all();
    }

    public static function log(int $requestId, string $event, ?string $actorName = null, ?string $detail = null, ?int $actorId = null): void
    {
        try {
            DB::table('artwork_request_log')->insert([
                'request_id' => $requestId,
                'event' => $event,
                'actor_user_id' => $actorId,
                'actor_name' => $actorName,
                'detail' => $detail,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // A log write must never take down the action it is recording.
        }
    }

    // ------------------------------------------------------------------ internals

    /**
     * Approvers for a request: the department queue plus the general one.
     *
     * A site that does not want departmental routing simply never sets a
     * department, and everyone sees everything.
     */
    protected static function approversFor(?string $department): array
    {
        $query = DB::table('artwork_request_approver as ara')
            ->join('user as u', 'ara.user_id', '=', 'u.id')
            ->where('ara.active', 1)
            ->where('ara.email_notifications', 1);

        if ($department) {
            $query->where(function ($q) use ($department) {
                $q->whereNull('ara.department')->orWhere('ara.department', $department);
            });
        }

        return $query->select('u.email', 'u.username')->get()->all();
    }

    protected static function notifyApprovers(int $requestId): void
    {
        $request = self::get($requestId);

        if (!$request) {
            return;
        }

        $works = self::objects($requestId);
        $titles = implode(', ', array_map(static fn ($w) => $w->object_title ?: ('#'.$w->information_object_id), $works));

        $body = sprintf(
            "%s has requested %d artwork(s) for placement.\n\n".
            "Request:  %s\nWorks:    %s\nPeriod:   %s to %s\nPlacement: %s %s %s\nPurpose:  %s\n\n%s\n",
            $request->requester_name ?: 'A member of staff',
            count($works),
            $request->request_number,
            $titles,
            $request->requested_from,
            $request->requested_to,
            $request->placement_building,
            $request->placement_room,
            $request->placement_occupant ? '('.$request->placement_occupant.')' : '',
            $request->purpose,
            $request->justification
        );

        foreach (self::approversFor($request->department) as $approver) {
            self::sendEmail($approver->email, 'Artwork request '.$request->request_number, $body);
        }
    }

    protected static function notifyRequester(int $requestId, string $outcome): void
    {
        $request = self::get($requestId);

        if (!$request || !$request->requester_email) {
            return;
        }

        $lines = [];

        foreach (self::objects($requestId) as $work) {
            $lines[] = sprintf('  %-50s %s', $work->object_title ?: '#'.$work->information_object_id, $work->status);
        }

        self::sendEmail(
            $request->requester_email,
            sprintf('Artwork request %s - %s', $request->request_number, $outcome),
            sprintf("Your request %s has been %s.\n\n%s\n\n%s\n",
                $request->request_number, $outcome, implode("\n", $lines), $request->review_notes ?: '')
        );
    }

    /**
     * Send through whatever the site already uses.
     *
     * ahgCorePlugin owns the SMTP settings and the sending itself, so this
     * defers to EmailService rather than inventing a second mail path that
     * would then need its own configuration screen. mail() is the fallback for
     * an install where SMTP was never set up: worth attempting, since a request
     * nobody is told about is the one failure this plugin cannot tolerate.
     */
    protected static function sendEmail(string $to, string $subject, string $body): void
    {
        if (!$to) {
            return;
        }

        try {
            if (class_exists('\AhgCore\Services\EmailService')
                && \AhgCore\Services\EmailService::isEnabled()) {
                \AhgCore\Services\EmailService::send($to, $subject, $body);

                return;
            }

            mail($to, $subject, $body);
        } catch (\Throwable $e) {
            // Notification failure must not roll back the request itself.
        }
    }

    protected static function nextRequestNumber(): string
    {
        $year = date('Y');
        $count = DB::table('artwork_request')->where('request_number', 'like', "AR-{$year}-%")->count();

        return sprintf('AR-%s-%04d', $year, $count + 1);
    }

    protected static function objectTitle(int $objectId): ?string
    {
        return DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->orderByRaw("culture = 'en' DESC")
            ->value('title');
    }

    protected static function objectIdentifier(int $objectId): ?string
    {
        return DB::table('information_object')->where('id', $objectId)->value('identifier');
    }

    protected static function hasTable(string $table): bool
    {
        try {
            return DB::schema()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
