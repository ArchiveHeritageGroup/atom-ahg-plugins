<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Relationship proposals (ahg_relationship_proposal), queued by
 * POST /api/v2/descriptions/:slug/proposals and reviewed at AI > Relationship review.
 *
 * Approval writes an AtoM actor relation (relation + relation_i18n) between the
 * two authority records, read subject - type - object, exactly as the authority
 * record edit screen stores one; it then shows on both records. A side proposed
 * as a name resolves once that name is linked (NER review or Authority
 * Resolution, including "create new").
 */
class RelationshipProposalService
{
    public function available(): bool
    {
        return DB::schema()->hasTable('ahg_relationship_proposal');
    }

    /**
     * Proposals with their record, relation label and both sides resolved.
     */
    public function listProposals(string $status, string $culture, int $limit = 100): array
    {
        $rows = DB::table('ahg_relationship_proposal as p')
            ->leftJoin('slug as s', 's.object_id', '=', 'p.object_id')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('ioi.id', '=', 'p.object_id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as ti', function ($j) use ($culture) {
                $j->on('ti.id', '=', 'p.type_id')->where('ti.culture', '=', $culture);
            })
            ->leftJoin('user as u', 'u.id', '=', 'p.created_by')
            ->where('p.status', $status)
            ->orderByDesc('p.id')
            ->limit($limit)
            ->get(['p.*', 's.slug', 'ioi.title', 'ti.name as relation', 'u.username as proposed_by']);

        foreach ($rows as $r) {
            $r->subject = $this->resolveSide($r->subject_actor_id, $r->subject_entity_id, $culture);
            $r->object = $this->resolveSide($r->object_actor_id, $r->object_entity_id, $culture);
        }

        return $rows->all();
    }

    public function counts(): array
    {
        return DB::table('ahg_relationship_proposal')->selectRaw('status, COUNT(*) AS n')->groupBy('status')->pluck('n', 'status')->all();
    }

    /**
     * The authority record one side stands for: given directly, or the record
     * its proposed name was linked to. Null id when not linked yet.
     */
    public function resolveSide($actorId, $entityId, string $culture): array
    {
        $id = $actorId ? (int) $actorId : null;
        if (!$id && $entityId) {
            $id = DB::table('ahg_ner_entity')->where('id', (int) $entityId)->value('linked_actor_id') ?: null;
            // Authority Resolution's "create new" records the new record on the decision only.
            if (!$id && DB::schema()->hasTable('ahg_mention_decision')) {
                $id = DB::table('ahg_mention_decision as d')->join('ahg_mention as m', 'm.id', '=', 'd.mention_id')
                    ->where('m.ner_entity_id', (int) $entityId)->whereIn('d.decision_type', ['link', 'link_different', 'create_new'])
                    ->whereNotNull('d.chosen_authority_id')->orderByDesc('d.id')->value('d.chosen_authority_id') ?: null;
            }
        }
        if (!$id || !DB::table('actor')->where('id', $id)->exists()) {
            return ['id' => null, 'name' => null, 'slug' => null];
        }

        return [
            'id' => (int) $id,
            'name' => DB::table('actor_i18n')->where('id', $id)->orderByRaw('culture = ? DESC', [$culture])->value('authorized_form_of_name'),
            'slug' => DB::table('slug')->where('object_id', $id)->value('slug'),
        ];
    }

    /**
     * @return array{success: bool, error?: string, relation_id?: int}
     */
    public function approve(int $id, int $userId, string $culture, ?string $notes = null): array
    {
        $p = DB::table('ahg_relationship_proposal')->where('id', $id)->first();
        if (!$p || 'pending' !== $p->status) {
            return ['success' => false, 'error' => 'This proposal is not pending'];
        }
        $subject = $this->resolveSide($p->subject_actor_id, $p->subject_entity_id, $culture);
        $object = $this->resolveSide($p->object_actor_id, $p->object_entity_id, $culture);
        foreach (['subject' => $subject, 'object' => $object] as $side => $r) {
            if (!$r['id']) {
                return ['success' => false, 'error' => 'Link "'.$p->{$side.'_name'}.'" to an authority record first (NER review or Authority Resolution)'];
            }
        }
        if ($subject['id'] === $object['id']) {
            return ['success' => false, 'error' => 'Both sides are the same authority record'];
        }

        return DB::transaction(function () use ($p, $subject, $object, $userId, $culture, $notes) {
            // An identical relation already on the records (either direction) is reused.
            $relationId = DB::table('relation')->where('type_id', $p->type_id)
                ->where(function ($q) use ($subject, $object) {
                    $q->where(fn ($w) => $w->where('subject_id', $subject['id'])->where('object_id', $object['id']))
                        ->orWhere(fn ($w) => $w->where('subject_id', $object['id'])->where('object_id', $subject['id']));
                })->value('id');
            if (!$relationId) {
                $now = date('Y-m-d H:i:s');
                $relationId = DB::table('object')->insertGetId(['class_name' => 'QubitRelation', 'created_at' => $now, 'updated_at' => $now]);
                DB::table('relation')->insert([
                    'id' => $relationId, 'subject_id' => $subject['id'], 'object_id' => $object['id'], 'type_id' => $p->type_id,
                    'start_date' => $p->start_date, 'end_date' => $p->end_date, 'source_culture' => $culture,
                ]);
                if ($p->date_text) {
                    DB::table('relation_i18n')->insert(['id' => $relationId, 'culture' => $culture, 'date' => $p->date_text]);
                }
            }
            DB::table('ahg_relationship_proposal')->where('id', $p->id)->update([
                'status' => 'approved', 'relation_id' => $relationId, 'reviewed_by' => $userId,
                'reviewed_at' => date('Y-m-d H:i:s'), 'review_notes' => $notes,
            ]);

            return ['success' => true, 'relation_id' => (int) $relationId];
        });
    }

    public function reject(int $id, int $userId, ?string $notes = null): array
    {
        $n = DB::table('ahg_relationship_proposal')->where('id', $id)->where('status', 'pending')->update([
            'status' => 'rejected', 'reviewed_by' => $userId, 'reviewed_at' => date('Y-m-d H:i:s'), 'review_notes' => $notes,
        ]);

        return $n ? ['success' => true] : ['success' => false, 'error' => 'This proposal is not pending'];
    }
}
