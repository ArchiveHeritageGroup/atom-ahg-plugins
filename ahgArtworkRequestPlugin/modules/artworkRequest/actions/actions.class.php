<?php

use AhgArtworkRequest\Service\ArtworkRequestService as Requests;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Artwork placement requests.
 *
 * Five screens: ask for a work, see your own requests, review what has been
 * asked for, see what is currently out, and one JSON endpoint the request form
 * calls to report clashes as works are added.
 */
class artworkRequestActions extends sfActions
{
    /**
     * My requests.
     */
    public function executeIndex($request)
    {
        $userId = $this->getUser()->getAttribute('user_id');

        $this->requests = DB::table('artwork_request')
            ->where('requester_user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->all();

        $this->works = [];

        foreach ($this->requests as $r) {
            $this->works[$r->id] = Requests::objects($r->id);
        }
    }

    /**
     * Ask for one or more works.
     */
    public function executeRequest($request)
    {
        $this->conflicts = [];
        $this->errors = [];

        if ($request->isMethod('post')) {
            $objectIds = array_filter(array_map('intval', (array) $request->getParameter('object_ids', [])));
            $from = $request->getParameter('requested_from');
            $to = $request->getParameter('requested_to');

            if (!$objectIds) {
                $this->errors[] = 'Choose at least one work.';
            }

            if (!$from || !$to) {
                $this->errors[] = 'Give the dates the work is needed between.';
            } elseif ($to < $from) {
                $this->errors[] = 'The end date is before the start date.';
            }

            if (!$this->errors) {
                $user = $this->getUser();

                $requestId = Requests::create([
                    'requester_user_id' => $user->getAttribute('user_id'),
                    'requester_name' => $user->getAttribute('user_name'),
                    'requester_email' => $this->currentUserEmail(),
                    'department' => $request->getParameter('department'),
                    'purpose' => $request->getParameter('purpose'),
                    'justification' => $request->getParameter('justification'),
                    'requested_from' => $from,
                    'requested_to' => $to,
                    'placement_building' => $request->getParameter('placement_building'),
                    'placement_floor' => $request->getParameter('placement_floor'),
                    'placement_room' => $request->getParameter('placement_room'),
                    'placement_occupant' => $request->getParameter('placement_occupant'),
                    'placement_notes' => $request->getParameter('placement_notes'),
                ], $objectIds, true);

                $this->getUser()->setFlash('notice', 'Request submitted. The gallery has been notified.');

                $this->redirect(['module' => 'artworkRequest', 'action' => 'view', 'id' => $requestId]);

                return;
            }
        }
    }

    /**
     * Clashes for one work over a date range, as JSON.
     *
     * Called by the request form as works are added. A clash is reported, never
     * enforced: the curator may still say yes, and needs to see why they are
     * being asked twice.
     */
    public function executeAvailability($request)
    {
        $objectId = (int) $request->getParameter('object_id');
        $from = $request->getParameter('from');
        $to = $request->getParameter('to');

        $conflicts = ($objectId && $from && $to)
            ? Requests::findConflicts($objectId, $from, $to)
            : [];

        return $this->renderText(json_encode([
            'object_id' => $objectId,
            'free' => empty($conflicts),
            'conflicts' => $conflicts,
        ]));
    }

    /**
     * The review queue, and the decision.
     */
    public function executeReview($request)
    {
        if ($request->isMethod('post')) {
            $requestId = (int) $request->getParameter('request_id');
            $decisions = (array) $request->getParameter('decision', []);

            Requests::decide(
                $requestId,
                $decisions,
                $request->getParameter('review_notes'),
                $this->getUser()->getAttribute('user_id'),
                $request->getParameter('decision_channel', 'system')
            );

            $this->getUser()->setFlash('notice', 'Decision recorded and the requester notified.');

            $this->redirect(['module' => 'artworkRequest', 'action' => 'review']);

            return;
        }

        $this->pending = DB::table('artwork_request')
            ->where('status', 'submitted')
            ->orderBy('requested_from')
            ->get()
            ->all();

        $this->works = [];

        foreach ($this->pending as $r) {
            $this->works[$r->id] = Requests::objects($r->id);
        }
    }

    /**
     * Create the loan record for an approved request. #277
     *
     * A separate action behind a button rather than something approval does by
     * itself: approving decides whether a work may hang somewhere, creating the
     * loan is the moment it physically moves, and those are different events.
     */
    public function executeCreateLoan($request)
    {
        $id = (int) $request->getParameter('id');
        $loanId = Requests::createLoan($id, $this->getUser()->getAttribute('user_id'));

        if ($loanId) {
            $this->getUser()->setFlash('notice', 'Loan record created. Condition reports and movement are tracked there.');
        } else {
            // Distinguish "cannot" from "did not": a silent no-op here leaves a
            // request marked approved with no loan and nobody aware of the gap.
            $this->getUser()->setFlash('error', 'No loan record was created - ahgLoanPlugin may not be installed, nothing on this request is approved, or a loan already exists.');
        }

        $this->redirect(['module' => 'artworkRequest', 'action' => 'view', 'id' => $id]);
    }

    /**
     * What is out on campus, and what is late.
     */
    public function executePlacements($request)
    {
        $this->overdueOnly = (bool) $request->getParameter('overdue');
        $this->placements = Requests::placements($this->overdueOnly);
        $this->today = date('Y-m-d');
    }

    /**
     * One request, its works, its decision and its log.
     */
    public function executeView($request)
    {
        $id = (int) $request->getParameter('id');
        $this->request_row = Requests::get($id);

        if (!$this->request_row) {
            $this->forward404('No such request');
        }

        $this->works = Requests::objects($id);
        $this->log = Requests::logEntries($id);
        $this->canReview = $this->getUser()->hasCredential('editor')
            || $this->getUser()->hasCredential('administrator');
    }

    protected function currentUserEmail(): ?string
    {
        $userId = $this->getUser()->getAttribute('user_id');

        return $userId ? DB::table('user')->where('id', $userId)->value('email') : null;
    }
}
