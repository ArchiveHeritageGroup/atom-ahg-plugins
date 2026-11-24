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
    private const PUBLICATION_STATUS_TYPE_ID = 158;
    private const PUBLISHED_ID = 160;
    // ponytail: the picker scans at most 5 batches, so a long run of hidden records can shorten it; raise this if that shows.
    private const PICKER_BATCHES = 5;

    /** @var int[]|null ids SearchAccessFilterService hides from this user */
    private ?array $hidden = null;

    /**
     * @param \Closure|null $mayViewDraft fn(int $id): bool for staff (contributor and
     *                                    above); null for everyone else, who see
     *                                    published records only and no donors
     */
    public function __construct(private ?int $userId = null, private ?\Closure $mayViewDraft = null)
    {
    }

    /** Recent records this user may see, for the picker. Empty if the filter fails. @return array<int,object> */
    public function listEntities(int $limit = 200): array
    {
        try {
            $hidden = $this->hiddenIds();
            $out = [];
            $before = PHP_INT_MAX;
            for ($batch = 0; $batch < self::PICKER_BATCHES && count($out) < $limit; ++$batch) {
                $query = DB::table('information_object as io')
                    ->join('information_object_i18n as i', function ($j) {
                        $j->on('i.id', '=', 'io.id')->where('i.culture', '=', 'en');
                    })
                    ->where('io.id', '>', 1)
                    ->where('io.id', '<', $before)
                    ->whereNotNull('i.title')->where('i.title', '<>', '');
                if ([] !== $hidden) {
                    $query->whereIntegerNotInRaw('io.id', $hidden);
                }
                if (null === $this->mayViewDraft) {
                    $query->whereExists(function ($q) {
                        $q->select(DB::raw(1))->from('status')
                            ->whereColumn('status.object_id', 'io.id')
                            ->where('status.type_id', self::PUBLICATION_STATUS_TYPE_ID)
                            ->where('status.status_id', self::PUBLISHED_ID);
                    });
                }
                $rows = $query->orderByDesc('io.id')->limit($limit)->get(['io.id as io_id', 'i.title'])->all();
                if ([] === $rows) {
                    break;
                }
                $before = (int) end($rows)->io_id;
                $visible = $this->visibleIds(array_map(static fn ($r) => (int) $r->io_id, $rows));
                foreach ($rows as $r) {
                    if (isset($visible[(int) $r->io_id]) && count($out) < $limit) {
                        $out[] = $r;
                    }
                }
                if (count($rows) < $limit) {
                    break;
                }
            }

            return $out;
        } catch (\Throwable $e) {
            error_log('ricExplorer.graph_filter_failed: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * The record's graph, holding only what this user may see. No nodes means the
     * centre is not visible or could not be checked: the caller answers 404. If
     * the centre is allowed and a later step fails, only the centre is returned.
     *
     * @return array{nodes:array,edges:array,summary:array}
     */
    public function build(int $ioId): array
    {
        $counts = ['creators' => 0, 'subjects' => 0, 'related' => 0, 'repository' => 0];

        try {
            if (!isset($this->visibleIds([$ioId])[$ioId])) {
                return ['nodes' => [], 'edges' => [], 'summary' => $counts];
            }
            $title = DB::table('information_object_i18n')->where('id', $ioId)->where('culture', 'en')->value('title')
                ?: ('Record #' . $ioId);
        } catch (\Throwable $e) {
            error_log('ricExplorer.graph_filter_failed: ' . $e->getMessage());

            return ['nodes' => [], 'edges' => [], 'summary' => $counts];
        }

        $center = ['id' => 'io-' . $ioId, 'label' => (string) $title, 'type' => 'record', 'center' => 1];

        try {
            return $this->withNeighbours($ioId, $center, $counts);
        } catch (\Throwable $e) {
            error_log('ricExplorer.graph_filter_failed: ' . $e->getMessage());

            return ['nodes' => [$center], 'edges' => [], 'summary' => $counts];
        }
    }

    /** The service as this signed-in user sees it, including which drafts they may view. */
    public static function forUser($user): self
    {
        $mayViewDraft = null;
        if ($user->hasCredential('administrator')) {
            $mayViewDraft = static fn (int $id): bool => true;
        } elseif ($user->hasCredential(['editor', 'contributor'], false)) {
            $mayViewDraft = static function (int $id): bool {
                $resource = \QubitInformationObject::getById($id);

                return null !== $resource && \QubitAcl::check($resource, 'viewDraft');
            };
        }

        return new self((int) $user->getAttribute('user_id') ?: null, $mayViewDraft);
    }

    /**
     * Which of these descriptions this user may see. Throws if that cannot be
     * determined, so a caller can refuse.
     *
     * @param int[] $ids
     *
     * @return array<int,true>
     */
    public function visibleRecordIds(array $ids): array
    {
        return $this->visibleIds($ids);
    }

    /** @return int[] */
    private function hiddenIds(): array
    {
        return $this->hidden ??= array_map('intval', \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()
            ->getRestrictedObjectIds($this->userId));
    }

    /**
     * Which of these ids are descriptions this user may see. Throws if that cannot
     * be determined. A record with no publication status counts as a draft.
     *
     * @param int[] $ids
     *
     * @return array<int,true>
     */
    private function visibleIds(array $ids): array
    {
        $ids = array_values(array_diff(array_unique(array_filter($ids, static fn ($id) => $id > 1)), $this->hiddenIds()));
        if ([] === $ids) {
            return [];
        }

        $published = [];
        $rows = DB::table('information_object as io')
            ->leftJoin('status as s', function ($j) {
                $j->on('s.object_id', '=', 'io.id')->where('s.type_id', '=', self::PUBLICATION_STATUS_TYPE_ID);
            })
            ->whereIntegerInRaw('io.id', $ids)
            ->get(['io.id', 's.status_id']);
        foreach ($rows as $r) {
            $id = (int) $r->id;
            $published[$id] = ($published[$id] ?? true) && self::PUBLISHED_ID === (int) $r->status_id;
        }

        $visible = [];
        foreach ($published as $id => $isPublished) {
            if ($isPublished || (null !== $this->mayViewDraft && ($this->mayViewDraft)($id))) {
                $visible[$id] = true;
            }
        }

        return $visible;
    }

    /** @return array{nodes:array,edges:array,summary:array} */
    private function withNeighbours(int $ioId, array $center, array $counts): array
    {
        $hidden = array_flip($this->hiddenIds());
        $nodes = [$center];
        $edges = [];

        // Holding repository.
        $repoId = (int) (DB::table('information_object')->where('id', $ioId)->value('repository_id') ?? 0);
        if ($repoId && !isset($hidden[$repoId])) {
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
            if (isset($seenActor[$aid]) || isset($hidden[$aid])) {
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
            if (isset($hidden[$tid])) {
                continue;
            }
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
            ->get(['subject_id', 'object_id', 'type_id']); // capped below, after filtering, so hidden rows do not use up the cap
        $otherId = static fn ($r) => ((int) $r->subject_id === $ioId) ? (int) $r->object_id : (int) $r->subject_id;
        $visibleRel = $this->visibleIds(array_map($otherId, $rels->all()));
        $seenRel = [];
        foreach ($rels as $r) {
            if ($counts['related'] >= self::CAP) {
                break;
            }
            $other = $otherId($r);
            if ($other === $ioId || isset($seenRel[$other]) || !isset($visibleRel[$other])) {
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
        if (null !== $this->mayViewDraft && $prov && !empty($prov->donor_id) && !isset($hidden[(int) $prov->donor_id])) { // donor names are staff-only
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
