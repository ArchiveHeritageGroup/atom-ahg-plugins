<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * KnowledgeGraphService — unified cross-domain (G/L/A/M) entity graph (#150).
 *
 * Builds a relational knowledge graph centred on an information object, joining
 * across domains via the live AtoM relations: creators/agents (event → actor),
 * holding repository, subjects/places/genres (object_term_relation → term),
 * related records (relation), and donor/current-custody (provenance_record).
 *
 * This is the relational "graph surface" of #150; Fuseki/SPARQL (RiC) and the
 * CIDOC-CRM / KM joins remain in their own layers. Global namespace + Capsule,
 * matching ProvenanceGraphService.
 */
class KnowledgeGraphService
{
    private const CAP = 60; // max nodes per relation kind, keeps the graph legible

    /** Recent published-ish records for the picker. @return array<int,object> */
    public function listEntities(int $limit = 200): array
    {
        return DB::table('information_object as io')
            ->join('information_object_i18n as i', function ($j) {
                $j->on('i.id', '=', 'io.id')->where('i.culture', '=', 'en');
            })
            ->where('io.id', '>', 1)
            ->whereNotNull('i.title')->where('i.title', '<>', '')
            ->orderByDesc('io.id')
            ->limit($limit)
            ->get(['io.id as io_id', 'i.title'])
            ->all();
    }

    /** @return array{nodes:array,edges:array,summary:array} */
    public function build(int $ioId): array
    {
        $title = DB::table('information_object_i18n')->where('id', $ioId)->where('culture', 'en')->value('title')
            ?: ('Record #' . $ioId);

        $nodes = [['id' => 'io-' . $ioId, 'label' => (string) $title, 'type' => 'record', 'center' => 1]];
        $edges = [];
        $counts = ['creators' => 0, 'subjects' => 0, 'related' => 0, 'repository' => 0];

        // Holding repository.
        $repoId = (int) (DB::table('information_object')->where('id', $ioId)->value('repository_id') ?? 0);
        if ($repoId) {
            $rn = DB::table('actor_i18n')->where('id', $repoId)->where('culture', 'en')->value('authorized_form_of_name');
            if ($rn) {
                $nodes[] = ['id' => 'repo-' . $repoId, 'label' => (string) $rn, 'type' => 'repository'];
                $edges[] = ['source' => 'io-' . $ioId, 'target' => 'repo-' . $repoId, 'label' => 'held by'];
                $counts['repository'] = 1;
            }
        }

        // Creators / associated agents via the event table.
        $events = DB::table('event')->where('object_id', $ioId)->whereNotNull('actor_id')->limit(self::CAP)->get(['actor_id', 'type_id']);
        $seenActor = [];
        foreach ($events as $e) {
            $aid = (int) $e->actor_id;
            if (isset($seenActor[$aid])) {
                continue;
            }
            $seenActor[$aid] = true;
            $an = DB::table('actor_i18n')->where('id', $aid)->where('culture', 'en')->value('authorized_form_of_name');
            if (!$an) {
                continue;
            }
            $nodes[] = ['id' => 'actor-' . $aid, 'label' => (string) $an, 'type' => 'actor'];
            $edges[] = ['source' => 'actor-' . $aid, 'target' => 'io-' . $ioId, 'label' => $this->termLabel((int) $e->type_id) ?: 'associated'];
            $counts['creators']++;
        }

        // Subjects / places / genres via object_term_relation.
        $terms = DB::table('object_term_relation')->where('object_id', $ioId)->limit(self::CAP)->get(['term_id']);
        foreach ($terms as $t) {
            $tid = (int) $t->term_id;
            $tn = DB::table('term_i18n')->where('id', $tid)->where('culture', 'en')->value('name');
            if (!$tn) {
                continue;
            }
            $nodes[] = ['id' => 'term-' . $tid, 'label' => (string) $tn, 'type' => 'term'];
            $edges[] = ['source' => 'io-' . $ioId, 'target' => 'term-' . $tid, 'label' => 'subject'];
            $counts['subjects']++;
        }

        // Related records via the relation table (either direction).
        $rels = DB::table('relation')
            ->where(function ($q) use ($ioId) {
                $q->where('subject_id', $ioId)->orWhere('object_id', $ioId);
            })
            ->limit(self::CAP)->get(['subject_id', 'object_id', 'type_id']);
        $seenRel = [];
        foreach ($rels as $r) {
            $other = ((int) $r->subject_id === $ioId) ? (int) $r->object_id : (int) $r->subject_id;
            if ($other === $ioId || isset($seenRel[$other])) {
                continue;
            }
            $on = DB::table('information_object_i18n')->where('id', $other)->where('culture', 'en')->value('title');
            if (!$on) {
                continue; // not an information_object (skip non-record relations here)
            }
            $seenRel[$other] = true;
            $nodes[] = ['id' => 'io-' . $other, 'label' => (string) $on, 'type' => 'related'];
            $edges[] = ['source' => 'io-' . $ioId, 'target' => 'io-' . $other, 'label' => $this->termLabel((int) $r->type_id) ?: 'related'];
            $counts['related']++;
        }

        // Donor (from provenance_record, if present).
        $prov = DB::table('provenance_record')->where('information_object_id', $ioId)->first();
        if ($prov && !empty($prov->donor_id)) {
            $dn = DB::table('actor_i18n')->where('id', $prov->donor_id)->where('culture', 'en')->value('authorized_form_of_name');
            if ($dn) {
                $nodes[] = ['id' => 'donor-' . $prov->donor_id, 'label' => (string) $dn, 'type' => 'donor'];
                $edges[] = ['source' => 'donor-' . $prov->donor_id, 'target' => 'io-' . $ioId, 'label' => 'donated'];
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges, 'summary' => $counts];
    }

    /** Resolve a term id to its label (relation/event type), cached per-request. */
    private function termLabel(int $termId): ?string
    {
        static $cache = [];
        if ($termId <= 0) {
            return null;
        }
        if (!array_key_exists($termId, $cache)) {
            $cache[$termId] = DB::table('term_i18n')->where('id', $termId)->where('culture', 'en')->value('name') ?: null;
        }

        return $cache[$termId];
    }
}
