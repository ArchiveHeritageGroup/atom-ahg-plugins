<?php

use AtomFramework\Http\Controllers\AhgApiController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * GET /api/v2/proposals?mine=1&status=&count=&start=
 *
 * Proposals made through POST /api/v2/descriptions/:slug/proposals and what
 * became of them: names, places and dates (AI > NER review, and the Authority
 * Resolution queue where it is on), field texts (AI > Suggest review) and
 * relationships (AI > Relationship review).
 * Newest first.
 *
 * Each item: kind (entity|field|relationship), type or field, value, record slug and title,
 * status (pending, approved, edited, linked, rejected, new_record_created,
 * parked), linked authority id, reviewer and dates.
 *
 * mine=0 lists everyone's proposals and needs an administrator or editor; the
 * default is the caller's own. status filters on the reported status.
 * Staff only (framework scope rule); proposals are made by staff.
 */
class apiv2ProposalsBrowseAction extends AhgApiController
{
    private const MAX = 200;

    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }
        $user = $this->getUser();
        $userId = (int) $user->getAttribute('user_id');
        $mine = '0' !== (string) $request->getParameter('mine', '1');
        if (!$mine && !$user->hasCredential(['administrator', 'editor'], false)) {
            return $this->error(403, 'Forbidden', 'Only administrators and editors may list everyone\'s proposals');
        }
        $status = strtolower(trim((string) $request->getParameter('status', '')));
        // Own parameter names: "limit" and "skip" are rewritten by the shared API paging.
        $count = max(1, min((int) $request->getParameter('count', 50), self::MAX));
        $start = max(0, (int) $request->getParameter('start', 0));
        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();

        // Entities: an API proposal's extraction is labelled "<source>:u<user id>".
        $entities = DB::table('ahg_ner_entity as n')
            ->join('ahg_ner_extraction as x', 'x.id', '=', 'n.extraction_id')
            ->where('x.backend_used', 'like', $mine ? '%:u'.$userId : '%:u%');
        $hasMention = DB::schema()->hasTable('ahg_mention');
        if ($hasMention) {
            $entities->leftJoin('ahg_mention as m', 'm.ner_entity_id', '=', 'n.id');
        }
        $entities = $entities->orderByDesc('n.id')->limit(self::MAX * 5)->get(array_merge(
            ['n.id', 'n.object_id', 'n.entity_type', 'n.entity_value', 'n.status', 'n.linked_actor_id', 'n.reviewed_by',
                'n.reviewed_at', 'n.created_at', 'x.backend_used'],
            $hasMention ? ['m.state as mention_state'] : []
        ));

        $fields = DB::table('ahg_description_suggestion')
            ->whereNotNull(DB::raw("JSON_EXTRACT(source_data, '$.proposed_via')"));
        if ($mine) {
            $fields->where('created_by', $userId);
        }
        $fields = $fields->orderByDesc('id')->limit(self::MAX * 5)
            ->get(['id', 'object_id', 'source_data', 'suggested_text', 'status', 'reviewed_by', 'reviewed_at', 'review_notes', 'created_by', 'created_at']);

        $relationships = [];
        if (DB::schema()->hasTable('ahg_relationship_proposal')) {
            $q = DB::table('ahg_relationship_proposal as r')->leftJoin('term_i18n as ti', function ($j) use ($culture) {
                $j->on('ti.id', '=', 'r.type_id')->where('ti.culture', '=', $culture);
            });
            if ($mine) {
                $q->where('r.created_by', $userId);
            }
            $relationships = $q->orderByDesc('r.id')->limit(self::MAX * 5)->get(['r.id', 'r.object_id', 'r.subject_name', 'ti.name as relation',
                'r.object_name', 'r.date_text', 'r.status', 'r.relation_id', 'r.review_notes', 'r.created_by', 'r.reviewed_by', 'r.reviewed_at', 'r.created_at']);
        }

        $items = [];
        foreach ($relationships as $r) {
            $items[] = [
                'kind' => 'relationship', 'id' => (int) $r->id, 'object_id' => (int) $r->object_id,
                'value' => trim($r->subject_name.' '.$r->relation.' '.$r->object_name.($r->date_text ? ' ('.$r->date_text.')' : '')),
                'status' => (string) $r->status, 'review_notes' => $r->review_notes,
                'proposed_by' => $r->created_by ? (int) $r->created_by : null,
                'reviewed_by' => $r->reviewed_by, 'reviewed_at' => $r->reviewed_at, 'proposed_at' => $r->created_at,
            ];
        }
        foreach ($entities as $e) {
            // The resolution queue's outcome is the more precise one once a person acted on it.
            $state = $hasMention && $e->mention_state && 'pending' !== $e->mention_state ? $e->mention_state : $e->status;
            $items[] = [
                'kind' => 'entity', 'id' => (int) $e->id, 'object_id' => (int) $e->object_id,
                'type' => $e->entity_type, 'value' => $e->entity_value, 'status' => (string) $state,
                'linked_authority_id' => $e->linked_actor_id ? (int) $e->linked_actor_id : null,
                'proposed_by' => (int) substr(strrchr((string) $e->backend_used, 'u'), 1) ?: null,
                'reviewed_by' => $e->reviewed_by, 'reviewed_at' => $e->reviewed_at, 'proposed_at' => $e->created_at,
            ];
        }
        foreach ($fields as $f) {
            $items[] = [
                'kind' => 'field', 'id' => (int) $f->id, 'object_id' => (int) $f->object_id,
                'field' => json_decode((string) $f->source_data, true)['field'] ?? null,
                'value' => mb_substr((string) $f->suggested_text, 0, 300), 'status' => (string) $f->status,
                'review_notes' => $f->review_notes,
                'proposed_by' => $f->created_by ? (int) $f->created_by : null,
                'reviewed_by' => $f->reviewed_by, 'reviewed_at' => $f->reviewed_at, 'proposed_at' => $f->created_at,
            ];
        }
        // ponytail: the newest 1,000 of each kind are read and filtered here; page
        // in SQL if anyone proposes more than that.
        if ('' !== $status) {
            $items = array_values(array_filter($items, fn ($i) => $i['status'] === $status));
        }
        usort($items, fn ($a, $b) => strcmp((string) $b['proposed_at'], (string) $a['proposed_at']));
        $total = count($items);
        $items = array_slice($items, $start, $count);

        // Record slug and title, and reviewer names, for the page only.
        $objectIds = array_unique(array_column($items, 'object_id'));
        $slugs = $objectIds ? DB::table('slug')->whereIn('object_id', $objectIds)->pluck('slug', 'object_id')->all() : [];
        $titles = $objectIds ? DB::table('information_object_i18n')->whereIn('id', $objectIds)->where('culture', $culture)->pluck('title', 'id')->all() : [];
        $userIds = array_filter(array_unique(array_merge(array_column($items, 'reviewed_by'), array_column($items, 'proposed_by'))));
        $names = $userIds ? DB::table('user')->whereIn('id', $userIds)->pluck('username', 'id')->all() : [];
        foreach ($items as &$i) {
            $i['slug'] = $slugs[$i['object_id']] ?? null;
            $i['title'] = $titles[$i['object_id']] ?? null;
            $i['proposed_by'] = $i['proposed_by'] ? ($names[$i['proposed_by']] ?? null) : null;
            $i['reviewed_by'] = $i['reviewed_by'] ? ($names[$i['reviewed_by']] ?? null) : null;
            unset($i['object_id']);
        }
        unset($i);

        return $this->success(['total' => $total, 'count' => $count, 'start' => $start, 'results' => $items]);
    }
}
