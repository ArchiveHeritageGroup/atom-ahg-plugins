<?php

namespace AhgArchaeologyPlugin\Services;

use AhgCore\Core\AhgDb;
use AtomFramework\Services\Write\StandaloneInformationObjectWriteService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Sites, contexts, finds, and the stratigraphic sequence between contexts.
 *
 * atom-ahg-plugins#190. Sites and contexts with their CRUD, the description path
 * that gives every context somewhere to hang its plan and section drawings, the
 * stratigraphic relationships between contexts, and the Harris Matrix computed
 * from them. CSV import and the PDF context sheet land in a later phase against
 * the same tables.
 *
 * The sequence is a directed acyclic graph, not a tree, which is why it cannot
 * ride the information_object hierarchy and needs its own edge table.
 *
 * Everything reads through the Laravel Query Builder. Nothing here uses Propel
 * except by way of the framework's information-object write service, which owns
 * the object -> information_object -> i18n insert chain.
 */
class ArchaeologyService
{
    /**
     * Taxonomy names this module looks terms up by.
     *
     * Matched on the taxonomy's name rather than a hardcoded id: a taxonomy
     * created by the seeder gets whatever id the instance hands out, and an id
     * baked in here would be right on exactly one installation.
     */
    public const VOCABULARIES = [
        'context_type' => 'Archaeological Context Type',
        'phase' => 'Archaeological Phase',
        'site_type' => 'Archaeological Site Type',
        'period' => 'Archaeological Period',
        'object_type' => 'Archaeological Object Type',
        'material' => 'Archaeological Material',
    ];

    /** Context columns a form may set. Anything not listed here is ignored. */
    private const CONTEXT_WRITABLE = [
        'context_number', 'context_type_id', 'description', 'interpretation',
        'top_elevation_m', 'bottom_elevation_m', 'excavation_reference',
        'excavator', 'excavation_date', 'phase_id',
        'date_earliest', 'date_latest', 'dating_note', 'status',
    ];

    /** Find columns a form may set. Anything not listed here is ignored. */
    private const FIND_WRITABLE = [
        'accession_number', 'object_type_id', 'material_id', 'technique_id', 'period_id',
        'recovery_method_id', 'context_reference', 'excavation_reference',
        'find_date', 'find_location', 'finder',
        'date_earliest', 'date_latest', 'dating_method_id', 'dating_note',
        'item_count', 'weight_g', 'length_mm', 'width_mm', 'thickness_mm',
        'diameter_mm', 'dimensions_note',
        'condition_id', 'repository_id', 'storage_location', 'provenance', 'notes', 'status',
    ];

    /** Site columns a form may set. */
    private const SITE_WRITABLE = [
        'site_number', 'national_site_number', 'site_type_id', 'period_id',
        'region', 'locality', 'location_description', 'latitude', 'longitude',
        'elevation_m', 'spatial_accuracy_m', 'area_sqm',
        'date_earliest', 'date_latest', 'dating_note',
        'discovery_date', 'discovered_by', 'excavated', 'excavation_years',
        'excavator', 'excavation_institution', 'permit_number',
        'protection_status_id', 'threats', 'research_potential',
        'publications', 'notes', 'status',
    ];

    private function culture(): string
    {
        return \sfContext::hasInstance()
            ? \sfContext::getInstance()->getUser()->getCulture()
            : 'en';
    }

    private function userId(): ?int
    {
        if (!\sfContext::hasInstance()) {
            return null;
        }

        $user = \sfContext::getInstance()->getUser();

        return method_exists($user, 'getUserID') ? ($user->getUserID() ?: null) : null;
    }

    /** True when the module's tables have actually been installed. */
    public function installed(): bool
    {
        return AhgDb::hasOptionalTable('archaeology_context');
    }

    // -----------------------------------------------------------------------
    // Vocabularies
    // -----------------------------------------------------------------------

    /**
     * The terms of one of this module's taxonomies, ordered by name.
     *
     * @return array<int, object> id + name
     */
    public function vocabulary(string $key): array
    {
        $taxonomyName = self::VOCABULARIES[$key] ?? null;

        if (null === $taxonomyName) {
            return [];
        }

        $culture = $this->culture();

        return DB::table('term as t')
            ->join('term_i18n as ti', function ($j) use ($culture) {
                $j->on('ti.id', '=', 't.id')->where('ti.culture', '=', $culture);
            })
            ->join('taxonomy_i18n as tx', function ($j) use ($culture) {
                $j->on('tx.id', '=', 't.taxonomy_id')->where('tx.culture', '=', $culture);
            })
            ->where('tx.name', $taxonomyName)
            ->orderBy('ti.name')
            ->get(['t.id', 'ti.name'])
            ->all();
    }

    /** Every vocabulary this module uses, keyed as in self::VOCABULARIES. */
    public function vocabularies(): array
    {
        $out = [];

        foreach (array_keys(self::VOCABULARIES) as $key) {
            $out[$key] = $this->vocabulary($key);
        }

        return $out;
    }

    // -----------------------------------------------------------------------
    // Sites
    // -----------------------------------------------------------------------

    public function sites(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $culture = $this->culture();
        $page = max(1, $page);

        $query = DB::table('archaeology_site as s')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('ioi.id', '=', 's.information_object_id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as stype', function ($j) use ($culture) {
                $j->on('stype.id', '=', 's.site_type_id')->where('stype.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as period', function ($j) use ($culture) {
                $j->on('period.id', '=', 's.period_id')->where('period.culture', '=', $culture);
            });

        if ('' !== ($term = trim((string) ($filters['q'] ?? '')))) {
            $query->where(function ($w) use ($term) {
                $w->where('s.site_number', 'like', '%'.$term.'%')
                    ->orWhere('s.national_site_number', 'like', '%'.$term.'%')
                    ->orWhere('s.locality', 'like', '%'.$term.'%')
                    ->orWhere('ioi.title', 'like', '%'.$term.'%');
            });
        }

        if (!empty($filters['region'])) {
            $query->where('s.region', $filters['region']);
        }

        if (isset($filters['excavated']) && '' !== $filters['excavated']) {
            $query->where('s.excavated', (int) (bool) $filters['excavated']);
        }

        $total = (clone $query)->count();

        $rows = $query->orderBy('s.site_number')
            ->forPage($page, $perPage)
            ->get([
                's.*',
                'ioi.title',
                'stype.name as site_type_name',
                'period.name as period_name',
            ])
            ->all();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ];
    }

    public function site(int $id): ?object
    {
        $culture = $this->culture();

        return DB::table('archaeology_site as s')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('ioi.id', '=', 's.information_object_id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as stype', function ($j) use ($culture) {
                $j->on('stype.id', '=', 's.site_type_id')->where('stype.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as period', function ($j) use ($culture) {
                $j->on('period.id', '=', 's.period_id')->where('period.culture', '=', $culture);
            })
            ->where('s.id', $id)
            ->first([
                's.*',
                'ioi.title',
                'stype.name as site_type_name',
                'period.name as period_name',
            ]);
    }

    /** Sites for a select, cheapest possible query. */
    public function sitePickList(): array
    {
        return DB::table('archaeology_site')
            ->where('status', 'active')
            ->orderBy('site_number')
            ->get(['id', 'site_number'])
            ->all();
    }

    public function saveSite(array $data, ?int $id = null): int
    {
        $row = $this->filterWritable($data, self::SITE_WRITABLE);
        $row['excavated'] = (int) (bool) ($data['excavated'] ?? 0);
        $userId = $this->userId();

        if ($id) {
            $row['updated_by'] = $userId;
            DB::table('archaeology_site')->where('id', $id)->update($row);

            return $id;
        }

        $row['created_by'] = $userId;
        $row['updated_by'] = $userId;

        return (int) DB::table('archaeology_site')->insertGetId($row);
    }

    // -----------------------------------------------------------------------
    // Contexts
    // -----------------------------------------------------------------------

    /**
     * A site's contexts, ordered by context number.
     *
     * Ordered naturally where the numbers are numeric, so 2, 10, 100 do not come
     * back as 10, 100, 2. Context numbers are strings by design (SF221, A.14),
     * so the numeric cast is a sort key only.
     *
     * @return array<int, object>
     */
    public function contextsForSite(int $siteId): array
    {
        if (!$this->installed()) {
            return [];
        }

        $culture = $this->culture();

        return DB::table('archaeology_context as c')
            ->leftJoin('term_i18n as ty', function ($j) use ($culture) {
                $j->on('ty.id', '=', 'c.context_type_id')->where('ty.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as ph', function ($j) use ($culture) {
                $j->on('ph.id', '=', 'c.phase_id')->where('ph.culture', '=', $culture);
            })
            ->where('c.site_id', $siteId)
            ->orderByRaw('CAST(c.context_number AS UNSIGNED), c.context_number')
            ->get([
                'c.*',
                'ty.name as type_name',
                'ph.name as phase_name',
            ])
            ->all();
    }

    public function context(int $id): ?object
    {
        if (!$this->installed()) {
            return null;
        }

        $culture = $this->culture();

        return DB::table('archaeology_context as c')
            ->leftJoin('archaeology_site as s', 's.id', '=', 'c.site_id')
            ->leftJoin('term_i18n as ty', function ($j) use ($culture) {
                $j->on('ty.id', '=', 'c.context_type_id')->where('ty.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as ph', function ($j) use ($culture) {
                $j->on('ph.id', '=', 'c.phase_id')->where('ph.culture', '=', $culture);
            })
            ->where('c.id', $id)
            ->first([
                'c.*',
                'ty.name as type_name',
                'ph.name as phase_name',
                's.site_number',
                's.information_object_id as site_information_object_id',
            ]);
    }

    /** The finds recovered from one context. */
    public function findsForContext(int $contextId): array
    {
        if (!AhgDb::hasOptionalTable('archaeology_object')) {
            return [];
        }

        $culture = $this->culture();

        return DB::table('archaeology_object as o')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('ioi.id', '=', 'o.information_object_id')->where('ioi.culture', '=', $culture);
            })
            ->where('o.context_id', $contextId)
            ->orderBy('o.accession_number')
            ->get(['o.id', 'o.accession_number', 'o.item_count', 'ioi.title'])
            ->all();
    }

    /**
     * Create or update a context, and make sure it has a description to hang its
     * drawings on.
     *
     * Returns the context id.
     */
    public function saveContext(array $data, ?int $id = null): int
    {
        $row = $this->filterWritable($data, self::CONTEXT_WRITABLE);
        $userId = $this->userId();

        // Elevations and term ids arrive from a form as strings; an empty string
        // is "not recorded", not zero. Storing 0.000 for an unmeasured elevation
        // would put the context at datum in every section that ever gets drawn.
        foreach (['top_elevation_m', 'bottom_elevation_m'] as $f) {
            $row[$f] = $this->numOrNull($row[$f] ?? null);
        }

        foreach (['context_type_id', 'phase_id'] as $f) {
            $row[$f] = $this->intOrNull($row[$f] ?? null);
        }

        $row['excavation_date'] = $this->dateOrNull($row['excavation_date'] ?? null);

        if ($id) {
            $row['updated_by'] = $userId;
            DB::table('archaeology_context')->where('id', $id)->update($row);
            $context = $this->context($id);

            if ($context) {
                $this->ensureContextDescription($id, (int) $context->site_id, (string) $context->context_number);
            }

            return $id;
        }

        $siteId = (int) ($data['site_id'] ?? 0);

        if ($siteId <= 0) {
            throw new \InvalidArgumentException('A context must belong to a site.');
        }

        $row['site_id'] = $siteId;
        $row['created_by'] = $userId;
        $row['updated_by'] = $userId;

        $newId = (int) DB::table('archaeology_context')->insertGetId($row);

        $this->ensureContextDescription($newId, $siteId, (string) ($row['context_number'] ?? $newId));

        return $newId;
    }

    /**
     * Give a context a descriptive record if it does not have one.
     *
     * The description is created as a child of the site's own description, so a
     * plan or section drawing uploaded to the context sits under the site in the
     * archival hierarchy rather than floating at the top of the tree.
     */
    private function ensureContextDescription(int $contextId, int $siteId, string $number): void
    {
        $existing = DB::table('archaeology_context')
            ->where('id', $contextId)
            ->value('information_object_id');

        if ($existing) {
            return;
        }

        $site = DB::table('archaeology_site')->where('id', $siteId)->first(['information_object_id', 'site_number']);

        if (!$site || !$site->information_object_id) {
            // No site description to hang it under. Leave it unset rather than
            // creating an orphan at the root of the tree - a stray top-level
            // description is worse than a missing one, because nobody goes
            // looking for it.
            return;
        }

        $title = 'Context '.$number.' ('.$site->site_number.')';
        $ioId = $this->createDescription($title, (int) $site->information_object_id);

        DB::table('archaeology_context')
            ->where('id', $contextId)
            ->update(['information_object_id' => $ioId]);
    }

    /**
     * Give a site a descriptive record if it does not have one.
     *
     * A site needs a description for the same reason a context does - it is what
     * holds the site's own digital objects - but it is also the parent every
     * context description is spliced beneath. Without it, contexts get a
     * description that cannot be placed in the tree, because placeInNestedSet()
     * refuses to splice a child under a parent that is itself outside the set.
     *
     * $parentIoId is where the site sits in the archival hierarchy: the project
     * or accession it belongs to. Defaults to the root.
     */
    public function ensureSiteDescription(int $siteId, ?string $title = null, ?int $parentIoId = null): ?int
    {
        $site = DB::table('archaeology_site')->where('id', $siteId)->first();

        if (!$site) {
            return null;
        }

        if ($site->information_object_id) {
            return (int) $site->information_object_id;
        }

        $parentIoId = $parentIoId ?: \QubitInformationObject::ROOT_ID;
        $title = $title ?: ('Site '.$site->site_number);

        $ioId = $this->createDescription($title, $parentIoId);

        DB::table('archaeology_site')
            ->where('id', $siteId)
            ->update(['information_object_id' => $ioId]);

        return $ioId;
    }

    /**
     * Create an information_object as the last child of $parentId.
     *
     * The framework's write service owns the object -> information_object ->
     * i18n chain and the slug, but it does NOT set lft/rgt: those columns are
     * nullable, so the insert succeeds and the description simply never appears
     * in the tree until someone runs propel:build-nested-set. Rather than leave
     * that landmine, the node is spliced into the nested set here, which is what
     * Propel would have done.
     */
    private function createDescription(string $title, int $parentId): int
    {
        $writer = new StandaloneInformationObjectWriteService();

        return DB::transaction(function () use ($writer, $title, $parentId) {
            $ioId = $writer->createInformationObject([
                'parent_id' => $parentId,
                'title' => $title,
            ], $this->culture());

            $this->placeInNestedSet((int) $ioId, $parentId);
            $this->setPublicationStatus((int) $ioId);

            return (int) $ioId;
        });
    }

    /**
     * Give a description a publication status.
     *
     * Not optional, and not cosmetic. `QubitInformationObjectAcl::isReadAllowed()`
     * throws outright when `getPublicationStatus()` returns null - it does not
     * fall back to a default and does not deny access, it raises an exception -
     * so a description without a status row is unviewable by everyone, including
     * administrators. The framework's information-object write service does not
     * create the row, the same way it does not set lft/rgt.
     *
     * The status follows the instance's own `defaultPubStatus`, falling back to
     * draft, which is what AtoM's own edit and upload actions do. Draft is also
     * the safe default: a record created by a background process should not
     * become publicly visible because nobody said otherwise.
     */
    private function setPublicationStatus(int $ioId): void
    {
        $exists = DB::table('status')
            ->where('object_id', $ioId)
            ->where('type_id', \QubitTerm::STATUS_TYPE_PUBLICATION_ID)
            ->exists();

        if ($exists) {
            return;
        }

        $statusId = (int) \sfConfig::get(
            'app_defaultPubStatus',
            \QubitTerm::PUBLICATION_STATUS_DRAFT_ID
        );

        DB::table('status')->insert([
            'object_id' => $ioId,
            'type_id' => \QubitTerm::STATUS_TYPE_PUBLICATION_ID,
            'status_id' => $statusId ?: \QubitTerm::PUBLICATION_STATUS_DRAFT_ID,
        ]);
    }

    /**
     * Backfill publication status onto this module's descriptions that lack one.
     *
     * Returns the number fixed. Safe to re-run.
     */
    public function backfillPublicationStatus(): int
    {
        $ids = [];

        foreach (['archaeology_site', 'archaeology_context', 'archaeology_object'] as $table) {
            if (!AhgDb::hasOptionalTable($table)) {
                continue;
            }

            foreach (DB::table($table)->whereNotNull('information_object_id')->pluck('information_object_id') as $ioId) {
                $ids[(int) $ioId] = true;
            }
        }

        $fixed = 0;

        foreach (array_keys($ids) as $ioId) {
            $has = DB::table('status')
                ->where('object_id', $ioId)
                ->where('type_id', \QubitTerm::STATUS_TYPE_PUBLICATION_ID)
                ->exists();

            if (!$has) {
                $this->setPublicationStatus($ioId);
                ++$fixed;
            }
        }

        return $fixed;
    }

    /**
     * Splice a freshly inserted node in as the last child of its parent.
     *
     * Standard nested-set insert: everything at or after the parent's right edge
     * shifts out by two, then the new node takes the gap. Rows whose lft/rgt are
     * NULL - including the new node itself, which is why it is updated last -
     * are untouched, since NULL fails every comparison.
     */
    private function placeInNestedSet(int $nodeId, int $parentId): void
    {
        $parent = DB::table('information_object')->where('id', $parentId)->first(['lft', 'rgt']);

        if (!$parent || null === $parent->rgt) {
            // The parent is itself outside the tree. Adding to it would corrupt
            // the set; leave the node unplaced for the next rebuild.
            return;
        }

        $edge = (int) $parent->rgt;

        DB::table('information_object')->where('rgt', '>=', $edge)->increment('rgt', 2);
        DB::table('information_object')->where('lft', '>', $edge)->increment('lft', 2);

        DB::table('information_object')
            ->where('id', $nodeId)
            ->update(['lft' => $edge, 'rgt' => $edge + 1]);
    }

    /**
     * Link finds to contexts using the legacy free-text context_reference.
     *
     * Only fills blanks - a find that already has a context_id is left alone, so
     * this is safe to re-run. Returns the number of finds linked.
     */
    public function backfillContextIds(): int
    {
        if (!$this->installed() || !AhgDb::hasOptionalTable('archaeology_object')) {
            return 0;
        }

        $linked = 0;

        $candidates = DB::table('archaeology_object')
            ->whereNull('context_id')
            ->whereNotNull('context_reference')
            ->where('context_reference', '!=', '')
            ->whereNotNull('site_id')
            ->get(['id', 'site_id', 'context_reference']);

        foreach ($candidates as $find) {
            $contextId = DB::table('archaeology_context')
                ->where('site_id', $find->site_id)
                ->where('context_number', trim((string) $find->context_reference))
                ->value('id');

            if ($contextId) {
                DB::table('archaeology_object')
                    ->where('id', $find->id)
                    ->update(['context_id' => $contextId]);
                ++$linked;
            }
        }

        return $linked;
    }

    // -----------------------------------------------------------------------
    // Stratigraphic relationships (Phase 2)
    // -----------------------------------------------------------------------

    /**
     * relationship_type => [reciprocal, human label, temporal direction].
     *
     * Harris allows only three kinds of connection between two units: none,
     * superposition, and correlation as parts of a once-whole deposit. These nine
     * recording types are how excavators actually write it down, and they collapse
     * onto those three: the "later" three (above/cuts/fills) and their reciprocals
     * are superposition, same_as is correlation, and bonds_with/abuts carry no
     * ordering at all.
     *
     * Only the directed later-than edges are used for cycle detection and for
     * layering the matrix.
     */
    public const REL_TYPES = [
        'above'      => ['reciprocal' => 'below',      'label' => 'is above',       'dir' => 'later'],
        'below'      => ['reciprocal' => 'above',      'label' => 'is below',       'dir' => 'earlier'],
        'cuts'       => ['reciprocal' => 'cut_by',     'label' => 'cuts',           'dir' => 'later'],
        'cut_by'     => ['reciprocal' => 'cuts',       'label' => 'is cut by',      'dir' => 'earlier'],
        'fills'      => ['reciprocal' => 'filled_by',  'label' => 'fills',          'dir' => 'later'],
        'filled_by'  => ['reciprocal' => 'fills',      'label' => 'is filled by',   'dir' => 'earlier'],
        'same_as'    => ['reciprocal' => 'same_as',    'label' => 'is the same as', 'dir' => 'none'],
        'bonds_with' => ['reciprocal' => 'bonds_with', 'label' => 'bonds with',     'dir' => 'none'],
        'abuts'      => ['reciprocal' => 'abuts',      'label' => 'abuts',          'dir' => 'none'],
    ];

    /** The later-than edge types: the source is stratigraphically later. */
    private const LATER_THAN = ['above', 'cuts', 'fills'];

    /**
     * A context's own relationships, resolved to the related context's number.
     *
     * @return array<int, object>
     */
    public function relationshipsForContext(int $contextId): array
    {
        if (!AhgDb::hasOptionalTable('archaeology_context_relationship')) {
            return [];
        }

        return DB::table('archaeology_context_relationship as r')
            ->join('archaeology_context as c', 'c.id', '=', 'r.related_context_id')
            ->where('r.context_id', $contextId)
            ->orderBy('r.relationship_type')
            ->orderByRaw('CAST(c.context_number AS UNSIGNED), c.context_number')
            ->get(['r.id', 'r.relationship_type', 'r.note', 'c.id as related_id', 'c.context_number as related_number'])
            ->all();
    }

    /** Other contexts in the same site, for the relationship-target dropdown. */
    public function contextPickList(int $siteId, ?int $excludeId = null): array
    {
        if (!$this->installed()) {
            return [];
        }

        $q = DB::table('archaeology_context')
            ->where('site_id', $siteId)
            ->where('status', 'active');

        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return $q->orderByRaw('CAST(context_number AS UNSIGNED), context_number')
            ->get(['id', 'context_number'])
            ->all();
    }

    /**
     * Add a relationship and its mirror.
     *
     * Rejects self-relations, unknown types, and any directional edge that would
     * make the sequence impossible. Catching a loop here - while the trench is
     * still open - is the whole point; discovering it in post-excavation three
     * years later is how sequences get quietly "corrected" into fiction.
     *
     * @return array{ok: bool, error?: string}
     */
    public function addRelationship(int $contextId, int $relatedId, string $type, ?string $note = null): array
    {
        if (!isset(self::REL_TYPES[$type])) {
            return ['ok' => false, 'error' => 'Unknown relationship type.'];
        }

        if ($contextId === $relatedId) {
            return ['ok' => false, 'error' => 'A context cannot relate to itself.'];
        }

        $meta = self::REL_TYPES[$type];

        if ('none' !== $meta['dir']) {
            [$later, $earlier] = 'later' === $meta['dir']
                ? [$contextId, $relatedId]
                : [$relatedId, $contextId];

            // A loop exists if the context we are about to declare EARLIER can
            // already be reached from the later one by following later-than edges.
            if ($this->laterThanReaches($earlier, $later)) {
                return ['ok' => false, 'error' => 'That would create a stratigraphic loop - the other context is already earlier in the sequence.'];
            }
        }

        $now = date('Y-m-d H:i:s');
        $userId = $this->userId();

        // uk_arch_ctxrel makes this idempotent, so re-running an import cannot
        // duplicate the stratigraphy. The affected-row count is kept because
        // "already recorded" and "newly recorded" are different things to report:
        // telling an operator 22 relationships were added when nothing changed is
        // a lie about what the run did.
        $inserted = DB::table('archaeology_context_relationship')->insertOrIgnore([
            'context_id' => $contextId,
            'related_context_id' => $relatedId,
            'relationship_type' => $type,
            'note' => $note,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $userId,
        ]);

        DB::table('archaeology_context_relationship')->insertOrIgnore([
            'context_id' => $relatedId,
            'related_context_id' => $contextId,
            'relationship_type' => $meta['reciprocal'],
            'note' => $note,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $userId,
        ]);

        return ['ok' => true, 'existed' => 0 === (int) $inserted];
    }

    /** Remove a relationship and its mirror. */
    public function removeRelationship(int $id): void
    {
        if (!AhgDb::hasOptionalTable('archaeology_context_relationship')) {
            return;
        }

        $row = DB::table('archaeology_context_relationship')->where('id', $id)->first();

        if (!$row) {
            return;
        }

        DB::table('archaeology_context_relationship')->where('id', $id)->delete();

        $reciprocal = self::REL_TYPES[$row->relationship_type]['reciprocal'] ?? null;

        if ($reciprocal) {
            DB::table('archaeology_context_relationship')
                ->where('context_id', $row->related_context_id)
                ->where('related_context_id', $row->context_id)
                ->where('relationship_type', $reciprocal)
                ->delete();
        }
    }

    /**
     * Can $from reach $target by following later-than edges?
     *
     * Iterative depth-first search with a seen-set, so a graph that already
     * contains a cycle cannot hang this.
     */
    private function laterThanReaches(int $from, int $target): bool
    {
        if (!AhgDb::hasOptionalTable('archaeology_context_relationship')) {
            return false;
        }

        $rows = DB::table('archaeology_context_relationship')
            ->whereIn('relationship_type', self::LATER_THAN)
            ->get(['context_id', 'related_context_id']);

        $adjacency = [];

        foreach ($rows as $row) {
            $adjacency[(int) $row->context_id][] = (int) $row->related_context_id;
        }

        $seen = [];
        $stack = [$from];

        while ($stack) {
            $node = (int) array_pop($stack);

            if ($node === $target) {
                return true;
            }

            if (isset($seen[$node])) {
                continue;
            }

            $seen[$node] = true;

            foreach ($adjacency[$node] ?? [] as $next) {
                $stack[] = $next;
            }
        }

        return false;
    }

    // -----------------------------------------------------------------------
    // Harris Matrix (Phase 3)
    // -----------------------------------------------------------------------

    /**
     * Build the Harris Matrix for a site.
     *
     * Two steps. Contexts recorded as the same event are merged into one node by
     * union-find, so two numbers assigned in different trenches to one deposit
     * resolve to a single box rather than appearing twice. Then the merged nodes
     * are layered by longest path (Kahn), which puts a context exactly as far
     * down as its deepest chain of superposition requires - the conventional
     * reading, latest at the top.
     *
     * Computed server-side and rendered as plain markup: no charting library, no
     * CDN, nothing for CSP to block. The Mermaid source is offered separately for
     * anyone who wants to redraw it elsewhere.
     *
     * @return array{tiers: array, edges: array, has_cycle: bool, mermaid: string, context_count: int, relationship_count: int}
     */
    public function harrisMatrix(int $siteId): array
    {
        $contexts = $this->contextsForSite($siteId);

        $empty = [
            'tiers' => [],
            'edges' => [],
            'has_cycle' => false,
            'mermaid' => '',
            'context_count' => count($contexts),
            'relationship_count' => 0,
        ];

        if (!$contexts || !AhgDb::hasOptionalTable('archaeology_context_relationship')) {
            return $empty;
        }

        $ids = array_map(static fn ($c) => (int) $c->id, $contexts);

        $rels = DB::table('archaeology_context_relationship')
            ->whereIn('context_id', $ids)
            ->whereIn('related_context_id', $ids)
            ->get(['context_id', 'related_context_id', 'relationship_type']);

        // Union-find over same_as, with path halving.
        $parent = [];

        foreach ($ids as $id) {
            $parent[$id] = $id;
        }

        $find = static function (int $x) use (&$parent): int {
            while ($parent[$x] !== $x) {
                $parent[$x] = $parent[$parent[$x]];
                $x = $parent[$x];
            }

            return $x;
        };

        foreach ($rels as $rel) {
            if ('same_as' === $rel->relationship_type) {
                $a = $find((int) $rel->context_id);
                $b = $find((int) $rel->related_context_id);

                if ($a !== $b) {
                    $parent[$a] = $b;
                }
            }
        }

        $groups = [];

        foreach ($contexts as $context) {
            $groups[$find((int) $context->id)][] = $context;
        }

        // Directed later-than edges between merged nodes.
        $edges = [];

        foreach ($rels as $rel) {
            if (!in_array($rel->relationship_type, self::LATER_THAN, true)) {
                continue;
            }

            $a = $find((int) $rel->context_id);
            $b = $find((int) $rel->related_context_id);

            if ($a !== $b) {
                $edges[$a.'|'.$b] = $rel->relationship_type;
            }
        }

        // Kahn longest-path layering. Level 0 is latest, drawn at the top.
        $adjacency = $indegree = $level = [];

        foreach (array_keys($groups) as $group) {
            $adjacency[$group] = [];
            $indegree[$group] = 0;
        }

        foreach (array_keys($edges) as $key) {
            [$a, $b] = explode('|', $key);
            $adjacency[$a][] = $b;
            ++$indegree[$b];
        }

        $queue = [];

        foreach (array_keys($groups) as $group) {
            if (0 === $indegree[$group]) {
                $queue[] = $group;
                $level[$group] = 0;
            }
        }

        $processed = 0;
        $remaining = $indegree;

        while ($queue) {
            $group = array_shift($queue);
            ++$processed;

            foreach ($adjacency[$group] as $next) {
                $level[$next] = max($level[$next] ?? 0, ($level[$group] ?? 0) + 1);

                if (0 === --$remaining[$next]) {
                    $queue[] = $next;
                }
            }
        }

        // Fewer nodes drained than exist means at least one is stuck behind a
        // cycle. The guard in addRelationship should make this unreachable, but a
        // matrix that quietly omits contexts would be worse than one that says so.
        $hasCycle = $processed < count($groups);

        $tiers = [];

        if (!$hasCycle) {
            foreach ($groups as $group => $members) {
                $tiers[$level[$group] ?? 0][] = $members;
            }

            ksort($tiers);
        }

        // Draw only the immediate relationships. Layering above deliberately uses
        // the FULL edge set - reduction preserves reachability and longest-path
        // distance, but computing tiers first means the reduction cannot move a
        // context between tiers even if that reasoning is ever wrong.
        $drawnEdges = $hasCycle ? $edges : $this->transitivelyReduce($edges);

        return [
            'tiers' => $tiers,
            'edges' => $drawnEdges,
            'has_cycle' => $hasCycle,
            'mermaid' => $this->mermaidSource($groups, $drawnEdges),
            'context_count' => count($contexts),
            'relationship_count' => intdiv(count($rels), 2),
            // Stated rather than silently discarded: a redundant relationship is
            // still a real thing the excavator recorded, and the count is how a
            // reader knows the diagram is a reduction of what was written down.
            'redundant_count' => count($edges) - count($drawnEdges),
        ];
    }

    /**
     * Check the recorded stratigraphy for contradictions.
     *
     * Cycle detection alone only catches the one error that makes a matrix
     * impossible to draw. These are the errors that leave a drawable matrix which
     * happens to be wrong - the kind Le Stratifiant checks and we did not.
     *
     * Every check is CONSERVATIVE: it reports only what the recorded data makes
     * unambiguous. A report that cried wolf on ordinary excavation messiness would
     * be turned off within a week, and then it would catch nothing at all.
     *
     * @return array{findings: array<int, array{severity:string, kind:string, message:string}>, checked: array<int,string>}
     */
    public function consistencyReport(int $siteId): array
    {
        $findings = [];
        $checked = [];

        $contexts = $this->contextsForSite($siteId);

        if (!$contexts) {
            return ['findings' => [], 'checked' => []];
        }

        $byId = [];
        $numberOf = [];

        foreach ($contexts as $context) {
            $byId[(int) $context->id] = $context;
            $numberOf[(int) $context->id] = (string) $context->context_number;
        }

        $rels = $this->relationshipsForSite($siteId);

        // --- cycle -----------------------------------------------------------
        $checked[] = 'stratigraphic loops';
        $matrix = $this->harrisMatrix($siteId);

        if (!empty($matrix['has_cycle'])) {
            $findings[] = [
                'severity' => 'error',
                'kind' => 'cycle',
                'message' => 'The sequence contains a loop, so it cannot be ordered. '
                    .'The matrix cannot be drawn until the contradicting relationships are corrected.',
            ];
        }

        // --- contexts with nothing recorded about them -----------------------
        $checked[] = 'contexts with no relationships';
        $related = [];

        foreach ($rels as $rel) {
            $related[(int) $rel->context_id] = true;
            $related[(int) $rel->related_context_id] = true;
        }

        $isolated = [];

        foreach ($byId as $id => $context) {
            if (!isset($related[$id])) {
                $isolated[] = $numberOf[$id];
            }
        }

        if ($isolated) {
            sort($isolated);
            $findings[] = [
                'severity' => 'warning',
                'kind' => 'isolated',
                'message' => sprintf(
                    '%d context%s no recorded relationship, so %s outside the sequence entirely: %s.',
                    count($isolated),
                    1 === count($isolated) ? ' has' : 's have',
                    1 === count($isolated) ? 'it sits' : 'they sit',
                    implode(', ', array_slice($isolated, 0, 12)).(count($isolated) > 12 ? ', ...' : '')
                ),
            ];
        }

        // --- disconnected pieces ---------------------------------------------
        // Treated as undirected: the question is whether the record ties the dig
        // together at all, not which way round any one relationship runs.
        $checked[] = 'sequence split into unconnected pieces';
        $adjacency = [];

        foreach ($rels as $rel) {
            $adjacency[(int) $rel->context_id][] = (int) $rel->related_context_id;
            $adjacency[(int) $rel->related_context_id][] = (int) $rel->context_id;
        }

        $seen = [];
        $components = 0;

        foreach (array_keys($byId) as $id) {
            if (isset($seen[$id]) || !isset($related[$id])) {
                continue;   // isolated contexts are reported above, not counted here
            }

            ++$components;
            $stack = [$id];

            while ($stack) {
                $node = array_pop($stack);

                if (isset($seen[$node])) {
                    continue;
                }

                $seen[$node] = true;

                foreach ($adjacency[$node] ?? [] as $next) {
                    if (!isset($seen[$next])) {
                        $stack[] = $next;
                    }
                }
            }
        }

        if ($components > 1) {
            $findings[] = [
                'severity' => 'warning',
                'kind' => 'disconnected',
                'message' => sprintf(
                    'The sequence is in %d unconnected pieces. That is normal for separate '
                    .'trenches with nothing correlated between them, and a problem if they '
                    .'were meant to be tied together.',
                    $components
                ),
            ];
        }

        // --- same_as that also asserts superposition -------------------------
        $checked[] = 'contexts both correlated and superposed';
        $laterPairs = [];

        foreach ($rels as $rel) {
            if (in_array($rel->relationship_type, self::LATER_THAN, true)) {
                $laterPairs[(int) $rel->context_id.'|'.(int) $rel->related_context_id] = true;
            }
        }

        foreach ($rels as $rel) {
            if ('same_as' !== $rel->relationship_type) {
                continue;
            }

            $a = (int) $rel->context_id;
            $b = (int) $rel->related_context_id;

            if ($a > $b) {
                continue;   // symmetric, report once
            }

            if (isset($laterPairs[$a.'|'.$b]) || isset($laterPairs[$b.'|'.$a])) {
                $findings[] = [
                    'severity' => 'error',
                    'kind' => 'same_as_superposed',
                    'message' => sprintf(
                        'Contexts %s and %s are recorded as the same feature AND one above the '
                        .'other. They cannot be both.',
                        $numberOf[$a] ?? $a,
                        $numberOf[$b] ?? $b
                    ),
                ];
            }
        }

        // --- elevation against superposition ---------------------------------
        // ONLY 'above'. A cut extends downward from the surface it was cut from,
        // so its top sitting exactly at the bottom of the deposit it cuts is
        // correct archaeology, not a contradiction - and a fill sits inside that
        // cut, below the surrounding deposit. Including cuts and fills here
        // produced a false positive for every single one of them.
        //
        // Strict inequality, too: equal elevations are the normal case where one
        // deposit sits directly on another.
        $checked[] = 'elevations against superposition (above only)';

        foreach ($rels as $rel) {
            if ('above' !== $rel->relationship_type) {
                continue;
            }

            $later = $byId[(int) $rel->context_id] ?? null;
            $earlier = $byId[(int) $rel->related_context_id] ?? null;

            if (!$later || !$earlier) {
                continue;
            }

            if (null === $later->top_elevation_m || null === $earlier->bottom_elevation_m) {
                continue;
            }

            if ((float) $later->top_elevation_m < (float) $earlier->bottom_elevation_m) {
                $findings[] = [
                    'severity' => 'warning',
                    'kind' => 'elevation',
                    'message' => sprintf(
                        'Context %s is recorded as %s %s, but its top (%.2f m) is at or below '
                        .'the bottom of %s (%.2f m) - it lies entirely underneath what it is '
                        .'said to be later than.',
                        $numberOf[(int) $rel->context_id],
                        (string) $rel->relationship_type,
                        $numberOf[(int) $rel->related_context_id],
                        (float) $later->top_elevation_m,
                        $numberOf[(int) $rel->related_context_id],
                        (float) $earlier->bottom_elevation_m
                    ),
                ];
            }
        }

        // --- phase against superposition -------------------------------------
        // Which way phase numbers run is a SITE CONVENTION, not a universal: some
        // schemes number the earliest phase 1, others the latest. Asserting either
        // would generate a false positive for every relationship on half the sites
        // in the world - as it did here on the first run.
        //
        // So infer the convention from the site's own data and report only the
        // relationships that disagree with it. A site with no clear majority gets
        // no finding, because there is nothing to be inconsistent with.
        $phaseOrder = $this->phaseOrder();

        if ($phaseOrder) {
            $checked[] = 'phases against superposition';
            $laterIsHigher = 0;
            $laterIsLower = 0;
            $pairs = [];

            foreach ($rels as $rel) {
                if (!in_array($rel->relationship_type, self::LATER_THAN, true)) {
                    continue;
                }

                $later = $byId[(int) $rel->context_id] ?? null;
                $earlier = $byId[(int) $rel->related_context_id] ?? null;

                if (!$later || !$earlier) {
                    continue;
                }

                $lp = $phaseOrder[(int) $later->phase_id] ?? null;
                $ep = $phaseOrder[(int) $earlier->phase_id] ?? null;

                if (null === $lp || null === $ep || $lp === $ep) {
                    continue;   // same phase, or one of them unphased: says nothing
                }

                $lp > $ep ? ++$laterIsHigher : ++$laterIsLower;
                $pairs[] = [$rel, $lp > $ep];
            }

            $total = $laterIsHigher + $laterIsLower;

            // Need a clear majority before calling anything an outlier.
            if ($total >= 4 && $laterIsHigher !== $laterIsLower) {
                $convention = $laterIsHigher > $laterIsLower;
                $outliers = [];

                foreach ($pairs as [$rel, $isHigher]) {
                    if ($isHigher !== $convention) {
                        $outliers[] = sprintf(
                            '%s later than %s',
                            $numberOf[(int) $rel->context_id],
                            $numberOf[(int) $rel->related_context_id]
                        );
                    }
                }

                if ($outliers) {
                    $findings[] = [
                        'severity' => 'warning',
                        'kind' => 'phase',
                        'message' => sprintf(
                            'This site numbers phases so that a later context carries the %s phase '
                            .'number (%d of %d relationships agree). %d disagree%s: %s.',
                            $convention ? 'higher' : 'lower',
                            max($laterIsHigher, $laterIsLower),
                            $total,
                            count($outliers),
                            1 === count($outliers) ? 's' : '',
                            implode(', ', array_slice($outliers, 0, 10)).(count($outliers) > 10 ? ', ...' : '')
                        ),
                    ];
                }
            }
        }

        return ['findings' => $findings, 'checked' => $checked];
    }

    /**
     * Phase term id => order, from the taxonomy's nested set.
     *
     * "Unphased" is deliberately absent: it records that no phase was assigned,
     * and treating it as the latest phase would generate contradictions out of
     * missing data.
     */
    private function phaseOrder(): array
    {
        $terms = $this->vocabulary('phase');
        $order = [];
        $i = 0;

        foreach ($terms as $term) {
            if (0 === strcasecmp(trim((string) $term->name), 'Unphased')) {
                continue;
            }

            $order[(int) $term->id] = ++$i;
        }

        return $order;
    }

    /**
     * Relationship words other tools use, mapped onto ours.
     *
     * An import that only accepted our own nine names would reject every file the
     * rest of the field produces. Everything here is a synonym with the SAME
     * meaning - nothing is coerced into a near-enough type.
     */
    public const RELATIONSHIP_SYNONYMS = [
        'later' => 'above',
        'later than' => 'above',
        'over' => 'above',
        'overlies' => 'above',
        'earlier' => 'below',
        'earlier than' => 'below',
        'under' => 'below',
        'underlies' => 'below',
        'cut by' => 'cut_by',
        'filled by' => 'filled_by',
        'same as' => 'same_as',
        'equal' => 'same_as',
        'equal to' => 'same_as',
        'equals' => 'same_as',
        'correlates with' => 'same_as',
        'bonds with' => 'bonds_with',
        'butts' => 'abuts',
        'abuts against' => 'abuts',
    ];

    /**
     * Words that name a real archaeological idea we do not model.
     *
     * `contemporary_with` is the important one: ArchEd and Stratify use it for
     * units of the same period that are not physically joined. Our closest types
     * are bonds_with and abuts, and both assert PHYSICAL contact - so mapping it
     * would invent an observation the excavator never made. Better to say so.
     */
    public const RELATIONSHIP_UNSUPPORTED = [
        'contemporary' => 'contemporary_with has no equivalent here - bonds_with and abuts both assert physical contact, which "contemporary" does not claim',
        'contemporary with' => 'contemporary_with has no equivalent here - bonds_with and abuts both assert physical contact, which "contemporary" does not claim',
    ];

    /**
     * Import stratigraphic relationships between contexts that already exist.
     *
     * Accepts rows of source / type / target, which is the shape both PHASER's
     * four-column CSV and an LST file reduce to. Contexts are matched by number
     * within the site and are NEVER created here: a relationship naming a context
     * that does not exist is a data problem the importer should report, not paper
     * over by inventing a context nobody recorded.
     *
     * Every row goes through addRelationship(), so reciprocity, the self-reference
     * check and the cycle guard apply exactly as they do to typed entry. An import
     * cannot introduce a contradiction the form would have refused.
     *
     * Wrapped in a transaction rolled back unless $commit, so a preview reports
     * real counts and real warnings from a real run without writing anything.
     *
     * @param array<int, array{source: string, type: string, target: string, line?: int}> $rows
     *
     * @return array{added:int, duplicate:int, skipped:int, warnings:array<int,string>, committed:bool}
     */
    public function importRelationshipsCsv(int $siteId, array $rows, bool $commit): array
    {
        $result = ['added' => 0, 'duplicate' => 0, 'skipped' => 0, 'warnings' => [], 'committed' => false];

        if (!$this->installed()) {
            $result['warnings'][] = 'The archaeology tables are not installed.';

            return $result;
        }

        $idByNumber = [];

        foreach ($this->contextsForSite($siteId) as $context) {
            $idByNumber[(string) $context->context_number] = (int) $context->id;
        }

        if (!$idByNumber) {
            $result['warnings'][] = 'This site has no contexts, so there is nothing to relate. Import the contexts first.';

            return $result;
        }

        DB::beginTransaction();

        try {
            foreach ($rows as $i => $row) {
                $line = (int) ($row['line'] ?? $i + 2);
                $source = trim((string) ($row['source'] ?? ''));
                $target = trim((string) ($row['target'] ?? ''));
                $raw = strtolower(trim((string) ($row['type'] ?? '')));

                if ('' === $source || '' === $target || '' === $raw) {
                    ++$result['skipped'];
                    $result['warnings'][] = "Line {$line}: source, relationship and target are all required.";

                    continue;
                }

                // Normalise separators before matching, so "cut by", "cut_by" and
                // "Cut-By" are one thing.
                $key = str_replace(['_', '-'], ' ', $raw);
                $key = preg_replace('/\s+/', ' ', $key);

                if (isset(self::RELATIONSHIP_UNSUPPORTED[$key])) {
                    ++$result['skipped'];
                    $result['warnings'][] = "Line {$line}: ".self::RELATIONSHIP_UNSUPPORTED[$key].'.';

                    continue;
                }

                $type = self::RELATIONSHIP_SYNONYMS[$key] ?? str_replace(' ', '_', $key);

                if (!isset(self::REL_TYPES[$type])) {
                    ++$result['skipped'];
                    $result['warnings'][] = "Line {$line}: unknown relationship '{$raw}'.";

                    continue;
                }

                if (!isset($idByNumber[$source]) || !isset($idByNumber[$target])) {
                    $missing = !isset($idByNumber[$source]) ? $source : $target;
                    ++$result['skipped'];
                    $result['warnings'][] = "Line {$line}: context '{$missing}' is not recorded on this site.";

                    continue;
                }

                $outcome = $this->addRelationship($idByNumber[$source], $idByNumber[$target], $type);

                if (!empty($outcome['ok'])) {
                    if (!empty($outcome['existed'])) {
                        ++$result['duplicate'];
                    } else {
                        ++$result['added'];
                    }

                    continue;
                }

                // An already-recorded relationship is not a failure - re-importing
                // the same file should be safe and say so.
                if (false !== stripos((string) ($outcome['error'] ?? ''), 'already')) {
                    ++$result['duplicate'];

                    continue;
                }

                ++$result['skipped'];
                $result['warnings'][] = "Line {$line}: {$source} {$raw} {$target} - ".($outcome['error'] ?? 'refused');
            }

            if ($commit) {
                DB::commit();
                $result['committed'] = true;
            } else {
                DB::rollBack();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $result['warnings'][] = 'Import failed: '.$e->getMessage();
        }

        return $result;
    }

    /**
     * Parse PHASER's four-column CSV into import rows.
     *
     * siteCode, sourceID, stratRelationship, targetID. siteCode is read but not
     * used to choose the site - the operator picked that already, and silently
     * importing into whatever a file names would be a good way to write another
     * dig's stratigraphy into this one.
     *
     * @return array{rows: array, error: ?string, other_sites: array<string,int>}
     */
    public function parsePhaserCsv(string $path, ?string $expectedSiteCode = null): array
    {
        $parsed = $this->parseCsv($path, 'sourceid');

        if (!empty($parsed['error'])) {
            return ['rows' => [], 'error' => $parsed['error'], 'other_sites' => []];
        }

        $rows = [];
        $otherSites = [];

        foreach ($parsed['rows'] as $i => $row) {
            $lower = [];

            foreach ($row as $k => $v) {
                $lower[strtolower(trim((string) $k))] = $v;
            }

            $code = trim((string) ($lower['sitecode'] ?? ''));

            if (null !== $expectedSiteCode && '' !== $code && 0 !== strcasecmp($code, $expectedSiteCode)) {
                $otherSites[$code] = ($otherSites[$code] ?? 0) + 1;

                continue;
            }

            $rows[] = [
                'source' => (string) ($lower['sourceid'] ?? ''),
                'type' => (string) ($lower['stratrelationship'] ?? ''),
                'target' => (string) ($lower['targetid'] ?? ''),
                'line' => $i + 2,
            ];
        }

        return ['rows' => $rows, 'error' => null, 'other_sites' => $otherSites];
    }

    /**
     * Parse an LST file - the format BASP Harris, Stratify and ArchEd write.
     *
     * Structure: the first three lines are ignored, the first unit name is on line
     * four, and every unit name is followed by exactly FOUR relationship lines, in
     * this order, each a comma-separated list that may be empty:
     *
     *   above, contemporary_with, equal_to, below
     *
     * All four lines are always present, so the parser advances in blocks of five
     * rather than trying to guess which line it is looking at.
     *
     * `contemporary_with` is collected and reported rather than imported - see
     * RELATIONSHIP_UNSUPPORTED for why mapping it would be an invention.
     *
     * @return array{rows: array, error: ?string, contemporary: int, units: int}
     */
    public function parseLst(string $path): array
    {
        if (!is_readable($path)) {
            return ['rows' => [], 'error' => 'The uploaded file could not be read.', 'contemporary' => 0, 'units' => 0];
        }

        $raw = file_get_contents($path);

        if (false === $raw) {
            return ['rows' => [], 'error' => 'The uploaded file could not be read.', 'contemporary' => 0, 'units' => 0];
        }

        // LST files are ASCII or ANSI and often carry CRLF.
        $lines = preg_split('/\r\n|\r|\n/', $raw);

        // The first three lines are a header the format tells us to ignore.
        $lines = array_slice($lines, 3);

        $rows = [];
        $contemporary = 0;
        $units = 0;
        $count = count($lines);

        for ($i = 0; $i < $count; $i += 5) {
            $name = trim((string) ($lines[$i] ?? ''));

            if ('' === $name) {
                continue;   // trailing blank lines at end of file
            }

            ++$units;

            $above = $this->lstList($lines[$i + 1] ?? '');
            $contemporaryWith = $this->lstList($lines[$i + 2] ?? '');
            $equalTo = $this->lstList($lines[$i + 3] ?? '');
            $below = $this->lstList($lines[$i + 4] ?? '');

            $contemporary += count($contemporaryWith);

            foreach ($above as $other) {
                $rows[] = ['source' => $name, 'type' => 'above', 'target' => $other, 'line' => $i + 5];
            }

            foreach ($below as $other) {
                $rows[] = ['source' => $name, 'type' => 'below', 'target' => $other, 'line' => $i + 8];
            }

            foreach ($equalTo as $other) {
                $rows[] = ['source' => $name, 'type' => 'same_as', 'target' => $other, 'line' => $i + 7];
            }
        }

        return ['rows' => $rows, 'error' => null, 'contemporary' => $contemporary, 'units' => $units];
    }

    /** One LST relationship line into a list of unit names. */
    private function lstList(string $line): array
    {
        $line = trim($line);

        if ('' === $line) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $line)), static fn ($v) => '' !== $v));
    }

    /**
     * Context types that are INTERFACES in Harris's sense, not deposits.
     *
     * Harris divides stratigraphic units into deposits and interfaces, and a cut
     * is an interface - it is the surface left by an act of removal, not a body of
     * material. Everything else we record (fill, layer, masonry, skeleton,
     * structure, surface) is a deposit for this purpose.
     */
    public const INTERFACE_TYPES = ['Cut', 'Interface'];

    /**
     * Export a site as a Harris Matrix Data Package.
     *
     * Follows the table schema Thomas Dye defined for the `hm` package, which the
     * Harris Matrix Data Package specification builds on:
     *
     *   contexts      label, unit-type, position, period, phase, url
     *   observations  younger, older, url
     *
     * `observations` carries NO relation-type column - it records superposition
     * and nothing else, so our nine relation types reduce to the later-than pairs
     * and the rest is not expressible. That is the format's design, not a loss on
     * our side: cuts and fills are both statements that one context is younger.
     *
     * `position` is left empty. hm uses it to mark surface and basal contexts, and
     * while we could infer it from the graph's top and bottom tiers, "surface" is a
     * claim about the ground, not about a diagram. Inferring it and writing it as
     * recorded data would put a guess where an excavator's observation belongs.
     *
     * same_as correlations have no home in those two tables, so rather than drop
     * them they go into a `correlations` resource, named in the descriptor as an
     * AHG extension so no consumer mistakes it for part of the specification.
     *
     * @return array<string, string> filename => contents
     */
    public function exportDataPackage(int $siteId): array
    {
        $site = $this->site($siteId);
        $contexts = $this->contextsForSite($siteId);
        $rels = $this->relationshipsForSite($siteId);

        $byId = [];

        foreach ($contexts as $context) {
            $byId[(int) $context->id] = $context;
        }

        // contexts.csv
        $contextRows = [['label', 'unit-type', 'position', 'period', 'phase', 'url']];

        foreach ($contexts as $context) {
            $contextRows[] = [
                (string) $context->context_number,
                in_array((string) $context->type_name, self::INTERFACE_TYPES, true) ? 'interface' : 'deposit',
                '',
                '',
                (string) ($context->phase_name ?? ''),
                '',
            ];
        }

        // observations.csv - one row per later-than statement, deduplicated.
        // Reciprocals are stored in both directions, so iterating raw rows would
        // emit each superposition twice.
        $observations = [];

        foreach ($rels as $rel) {
            if (!in_array($rel->relationship_type, self::LATER_THAN, true)) {
                continue;
            }

            $younger = $byId[(int) $rel->context_id]->context_number ?? null;
            $older = $byId[(int) $rel->related_context_id]->context_number ?? null;

            if (null === $younger || null === $older) {
                continue;
            }

            $observations[$younger.'|'.$older] = [$younger, $older, ''];
        }

        $observationRows = array_merge([['younger', 'older', 'url']], array_values($observations));

        // correlations.csv - AHG extension, see the note above.
        $correlations = [];

        foreach ($rels as $rel) {
            if ('same_as' !== $rel->relationship_type) {
                continue;
            }

            $a = $byId[(int) $rel->context_id]->context_number ?? null;
            $b = $byId[(int) $rel->related_context_id]->context_number ?? null;

            if (null === $a || null === $b) {
                continue;
            }

            // Symmetric, so store one row per pair rather than both directions.
            $key = strcmp((string) $a, (string) $b) <= 0 ? $a.'|'.$b : $b.'|'.$a;
            $correlations[$key] = explode('|', $key);
        }

        $correlationRows = array_merge([['context', 'same-as']], array_values($correlations));

        $files = [
            'contexts.csv' => $this->toCsv($contextRows),
            'observations.csv' => $this->toCsv($observationRows),
        ];

        if (count($correlationRows) > 1) {
            $files['correlations.csv'] = $this->toCsv($correlationRows);
        }

        $files['datapackage.json'] = $this->dataPackageDescriptor($site, array_keys($files));

        return $files;
    }

    /**
     * Export the sequence as GraphViz DOT.
     *
     * The drawn edges, so what comes out is the reduced matrix rather than every
     * relationship recorded - the same diagram the page shows.
     */
    public function exportDot(int $siteId): string
    {
        $site = $this->site($siteId);
        $matrix = $this->harrisMatrix($siteId);
        $contexts = $this->contextsForSite($siteId);

        $label = [];

        foreach ($contexts as $context) {
            $label[(int) $context->id] = (string) $context->context_number;
        }

        $name = preg_replace('/[^A-Za-z0-9_]/', '_', (string) ($site->site_number ?? 'site'));
        $out = 'digraph '.$name." {\n";
        $out .= "  rankdir=TB;\n";
        $out .= "  node [shape=box, fontname=\"Helvetica\"];\n";

        foreach ($contexts as $context) {
            $isInterface = in_array((string) $context->type_name, self::INTERFACE_TYPES, true);
            $out .= sprintf(
                "  \"%s\" [label=\"%s\\n%s\"%s];\n",
                addslashes((string) $context->context_number),
                addslashes((string) $context->context_number),
                addslashes((string) ($context->type_name ?? '')),
                $isInterface ? ', style=dashed' : ''
            );
        }

        foreach (array_keys($matrix['edges']) as $key) {
            [$from, $to] = explode('|', $key);
            $out .= sprintf(
                "  \"%s\" -> \"%s\";\n",
                addslashes($label[(int) $from] ?? $from),
                addslashes($label[(int) $to] ?? $to)
            );
        }

        return $out."}\n";
    }

    /**
     * Export relationships as PHASER's four-column CSV.
     *
     * siteCode, sourceID, stratRelationship, targetID - the interchange the MATRIX
     * project's Phaser tool reads.
     */
    public function exportPhaserCsv(int $siteId): string
    {
        $site = $this->site($siteId);
        $contexts = $this->contextsForSite($siteId);
        $rels = $this->relationshipsForSite($siteId);

        $byId = [];

        foreach ($contexts as $context) {
            $byId[(int) $context->id] = (string) $context->context_number;
        }

        $siteCode = (string) ($site->site_number ?? '');
        $rows = [['siteCode', 'sourceID', 'stratRelationship', 'targetID']];
        $seen = [];

        foreach ($rels as $rel) {
            $source = $byId[(int) $rel->context_id] ?? null;
            $target = $byId[(int) $rel->related_context_id] ?? null;

            if (null === $source || null === $target) {
                continue;
            }

            $type = (string) $rel->relationship_type;
            $direction = self::REL_TYPES[$type]['dir'] ?? 'none';

            // One row per LOGICAL relationship. Reciprocals are stored in both
            // directions, so emitting every row would hand a consumer 44 statements
            // where the excavator recorded 22 - and 'A above B' plus 'B below A'
            // are one observation written twice, not two observations.
            //
            // Keep the later-than direction, which is the one that carries the
            // sequence; its reciprocal says the same thing backwards.
            if ('earlier' === $direction) {
                continue;
            }

            // Symmetric relations (same_as, bonds_with, abuts) have no direction to
            // prefer, so canonicalise on the pair instead.
            $key = 'none' === $direction
                ? $type.'|'.(strcmp($source, $target) <= 0 ? $source.'|'.$target : $target.'|'.$source)
                : $source.'|'.$type.'|'.$target;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $rows[] = [$siteCode, $source, $type, $target];
        }

        return $this->toCsv($rows);
    }

    /** Every relationship row for a site, both directions as stored. */
    private function relationshipsForSite(int $siteId): array
    {
        if (!AhgDb::hasOptionalTable('archaeology_context_relationship')) {
            return [];
        }

        $ids = array_map(
            static fn ($c) => (int) $c->id,
            $this->contextsForSite($siteId)
        );

        if (!$ids) {
            return [];
        }

        return DB::table('archaeology_context_relationship')
            ->whereIn('context_id', $ids)
            ->whereIn('related_context_id', $ids)
            ->get(['context_id', 'related_context_id', 'relationship_type'])
            ->all();
    }

    /** RFC 4180 CSV, written through a stream so quoting is not hand-rolled. */
    private function toCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $out = stream_get_contents($handle);
        fclose($handle);

        return $out;
    }

    /** Frictionless tabular-data-package descriptor for the exported resources. */
    private function dataPackageDescriptor(?object $site, array $filenames): string
    {
        $schemas = [
            'contexts.csv' => [
                'name' => 'contexts',
                'fields' => [
                    ['name' => 'label', 'type' => 'string', 'description' => 'context identifier (primary key)'],
                    ['name' => 'unit-type', 'type' => 'string', 'description' => 'interface or deposit'],
                    ['name' => 'position', 'type' => 'string', 'description' => 'surface, basal or other; left empty - not recorded'],
                    ['name' => 'period', 'type' => 'string', 'description' => 'period identifier (foreign key)'],
                    ['name' => 'phase', 'type' => 'string', 'description' => 'phase identifier (foreign key)'],
                    ['name' => 'url', 'type' => 'string', 'description' => 'node link (svg output only)'],
                ],
                'primaryKey' => 'label',
            ],
            'observations.csv' => [
                'name' => 'observations',
                'fields' => [
                    ['name' => 'younger', 'type' => 'string', 'description' => 'stratigraphically superior context label'],
                    ['name' => 'older', 'type' => 'string', 'description' => 'stratigraphically inferior context label'],
                    ['name' => 'url', 'type' => 'string', 'description' => 'arc link (svg output only)'],
                ],
            ],
            'correlations.csv' => [
                'name' => 'correlations',
                'fields' => [
                    ['name' => 'context', 'type' => 'string'],
                    ['name' => 'same-as', 'type' => 'string'],
                ],
                'description' => 'AHG EXTENSION, not part of the Harris Matrix Data Package specification. '
                    .'same_as correlations have no home in the contexts or observations tables; they are '
                    .'emitted here so the information is not lost, and named so no consumer mistakes them '
                    .'for standard content.',
            ],
        ];

        $resources = [];

        foreach ($filenames as $filename) {
            if (!isset($schemas[$filename])) {
                continue;
            }

            $schema = $schemas[$filename];
            $resource = [
                'name' => $schema['name'],
                'path' => $filename,
                'profile' => 'tabular-data-resource',
                'format' => 'csv',
                'mediatype' => 'text/csv',
                'encoding' => 'utf-8',
                'schema' => array_filter([
                    'fields' => $schema['fields'],
                    'primaryKey' => $schema['primaryKey'] ?? null,
                ]),
            ];

            if (isset($schema['description'])) {
                $resource['description'] = $schema['description'];
            }

            $resources[] = $resource;
        }

        return json_encode([
            'profile' => 'tabular-data-package',
            'name' => strtolower(preg_replace('/[^a-z0-9-]/i', '-', (string) ($site->site_number ?? 'site'))),
            'title' => trim(sprintf('%s stratigraphy', (string) ($site->site_number ?? ''))),
            'description' => 'Stratigraphic data exported from AtoM (ahgArchaeologyPlugin), following the '
                .'table schema used by the Harris Matrix Data Package. observations records superposition '
                .'only; the source records nine relation types, of which the later-than ones (above, cuts, '
                .'fills) are expressible here.',
            'created' => gmdate('c'),
            'resources' => $resources,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Remove edges implied by a longer path - the transitive reduction.
     *
     * Harris's Law of Stratigraphic Succession says a matrix shows only the
     * IMMEDIATE relationships. Excavators routinely record all three of A above B,
     * B above C and A above C; the third is implied by the first two, and drawing
     * it is both wrong by the method and, on a real site, the difference between a
     * readable diagram and a hairball.
     *
     * Keeping the recorded relationship and suppressing only the drawn edge is
     * deliberate: the excavator's record is not edited, just read correctly.
     *
     * ALGORITHM. Reachability is computed ONCE, as a bitset of descendants per node
     * in reverse topological order, rather than searching the graph again for every
     * edge. The obvious per-edge search is O(E x (V+E)) and was measured at 10.5
     * SECONDS on 3000 contexts with 12000 relationships - and this runs on every
     * view of the stratigraphy page. The same input reduces in 60ms this way, with
     * identical output on every case tested.
     *
     * Only valid on a DAG: the caller must not pass a graph with a cycle, because
     * the topological order this depends on would not exist.
     *
     * @param array<string, string> $edges keyed 'from|to'
     *
     * @return array<string, string> the same shape, minus the implied edges
     */
    private function transitivelyReduce(array $edges): array
    {
        $adjacency = [];
        $nodes = [];

        foreach (array_keys($edges) as $key) {
            [$from, $to] = explode('|', $key);
            $adjacency[$from][] = $to;
            $nodes[$from] = true;
            $nodes[$to] = true;
        }

        $position = array_flip(array_keys($nodes));
        $zero = str_repeat("\0", (int) ((count($position) + 7) / 8));
        $descendants = [];

        foreach ($this->reverseTopological($adjacency, array_keys($nodes)) as $node) {
            $descendants[$node] = $this->unionOfSuccessors($adjacency[$node] ?? [], $descendants, $position, $zero, null);
        }

        $kept = [];

        foreach ($edges as $key => $type) {
            [$from, $to] = explode('|', $key);

            // Reachable from $from WITHOUT the direct hop under test. If $to is
            // still in that set, this edge says nothing the longer path did not.
            $without = $this->unionOfSuccessors($adjacency[$from] ?? [], $descendants, $position, $zero, $to);

            if (!$this->bitIsSet($without, $position[$to])) {
                $kept[$key] = $type;
            }
        }

        return $kept;
    }

    /**
     * Nodes ordered so every successor precedes its predecessor.
     *
     * Iterative post-order DFS: a deep sequence is ordinary on a real site, and
     * PHP's recursion limit is not something a dig should discover.
     */
    private function reverseTopological(array $adjacency, array $nodes): array
    {
        $state = [];
        $order = [];

        foreach ($nodes as $node) {
            if (isset($state[$node])) {
                continue;
            }

            $stack = [[$node, false]];

            while ($stack) {
                [$current, $expanded] = array_pop($stack);

                if ($expanded) {
                    $order[] = $current;

                    continue;
                }

                if (isset($state[$current])) {
                    continue;
                }

                $state[$current] = true;
                $stack[] = [$current, true];

                foreach ($adjacency[$current] ?? [] as $successor) {
                    if (!isset($state[$successor])) {
                        $stack[] = [$successor, false];
                    }
                }
            }
        }

        return $order;
    }

    /**
     * Union of (each successor, plus everything below it), as a bitset.
     *
     * $skip excludes one successor, which is how an edge is tested without using
     * itself as the proof.
     */
    private function unionOfSuccessors(array $successors, array $descendants, array $position, string $zero, ?string $skip): string
    {
        $accumulated = $zero;

        foreach ($successors as $successor) {
            if (null !== $skip && $successor === $skip) {
                continue;
            }

            $below = $descendants[$successor] ?? $zero;
            $accumulated |= $this->withBit($below, $position[$successor]);
        }

        return $accumulated;
    }

    private function withBit(string $bitset, int $bit): string
    {
        $byte = $bit >> 3;
        $bitset[$byte] = chr(ord($bitset[$byte]) | (1 << ($bit & 7)));

        return $bitset;
    }

    private function bitIsSet(string $bitset, int $bit): bool
    {
        return 0 !== (ord($bitset[$bit >> 3]) & (1 << ($bit & 7)));
    }

    /** Mermaid flowchart source for the matrix, later pointing to earlier. */
    private function mermaidSource(array $groups, array $edges): string
    {
        $out = "flowchart TD\n";

        foreach ($groups as $group => $members) {
            $labels = [];

            foreach ($members as $member) {
                $labels[] = $this->contextLabel($member);
            }

            $out .= '  g'.$group.'["'.str_replace('"', "'", implode(' = ', $labels)).'"]'."\n";
        }

        foreach ($edges as $key => $type) {
            [$a, $b] = explode('|', $key);
            $out .= '  g'.$a.' -->'.('above' === $type ? '' : '|'.$type.'|').' g'.$b."\n";
        }

        return $out;
    }

    /**
     * A context's number in conventional notation.
     *
     * Cuts and other interfaces take square brackets, deposits and fills round
     * ones. Derived from the context type rather than from how the number was
     * typed, so the notation is consistent across the matrix, the section and the
     * context sheet whatever the recorder entered.
     */
    public function contextLabel(object $context): string
    {
        $type = strtolower((string) ($context->type_name ?? ''));
        $isInterface = in_array($type, ['cut', 'interface'], true);

        return $isInterface
            ? '['.$context->context_number.']'
            : '('.$context->context_number.')';
    }

    // -----------------------------------------------------------------------
    // Dig plan
    // -----------------------------------------------------------------------

    /**
     * The dig arranged for drawing: trenches, their contexts, and where the finds
     * are.
     *
     * A cut, fill, wall or burial is a FEATURE - it occupies part of a trench, not
     * the whole width of it - so it is separated from the deposits here rather
     * than in the template. Drawing a wall as a full-width layer is not a styling
     * choice, it is a false statement about the site.
     *
     * Deposits that share an elevation range are assigned to columns so they sit
     * side by side instead of on top of one another.
     */
    /**
     * @param array $filters trenches[], types[], features (bool), min, max
     */
    public function sitePlan(int $siteId, array $filters = []): array
    {
        $contexts = $this->contextsForSite($siteId);

        if (!$contexts) {
            return ['trenches' => [], 'min' => null, 'max' => null, 'unplaced' => []];
        }

        $findCounts = [];

        if (AhgDb::hasOptionalTable('archaeology_object')) {
            foreach (DB::table('archaeology_object')
                ->whereNotNull('context_id')
                ->selectRaw('context_id, COUNT(*) as n')
                ->groupBy('context_id')->get() as $row) {
                $findCounts[(int) $row->context_id] = (int) $row->n;
            }
        }

        $elevations = [];

        foreach ($contexts as $c) {
            if (null !== $c->top_elevation_m) {
                $elevations[] = (float) $c->top_elevation_m;
            }

            if (null !== $c->bottom_elevation_m) {
                $elevations[] = (float) $c->bottom_elevation_m;
            }
        }

        $min = $elevations ? min($elevations) : null;
        $max = $elevations ? max($elevations) : null;

        $trenches = [];
        $unplaced = [];

        $wantTrenches = array_filter((array) ($filters['trenches'] ?? []));
        $wantTypes = array_filter((array) ($filters['types'] ?? []));
        $showFeatures = !array_key_exists('features', $filters) || (bool) $filters['features'];
        $minElev = ('' === ($filters['min'] ?? '')) ? null : (float) $filters['min'];
        $maxElev = ('' === ($filters['max'] ?? '')) ? null : (float) $filters['max'];

        $excluded = 0;

        foreach ($contexts as $c) {
            $c->find_count = $findCounts[(int) $c->id] ?? 0;
            $c->label = $this->contextLabel($c);
            $c->is_feature = in_array((string) $c->type_name, self::FEATURE_TYPES, true);

            // Filters are applied here rather than in the query so that the
            // elevation range and the trench list are still computed from the
            // whole site - a filtered view should say what it left out.
            $trench = trim((string) $c->excavation_reference);
            $trench = '' === $trench ? 'Unassigned' : $trench;

            if ($wantTrenches && !in_array($trench, $wantTrenches, true)) {
                ++$excluded;

                continue;
            }

            if ($wantTypes && !in_array((string) $c->type_name, $wantTypes, true)) {
                ++$excluded;

                continue;
            }

            if (!$showFeatures && $c->is_feature) {
                ++$excluded;

                continue;
            }

            if (null !== $minElev && null !== $c->bottom_elevation_m && (float) $c->bottom_elevation_m < $minElev) {
                ++$excluded;

                continue;
            }

            if (null !== $maxElev && null !== $c->top_elevation_m && (float) $c->top_elevation_m > $maxElev) {
                ++$excluded;

                continue;
            }

            // Without both elevations a context cannot be placed on a scaled
            // drawing. Listing it separately is honest; guessing a position is not.
            if (null === $c->top_elevation_m || null === $c->bottom_elevation_m) {
                $unplaced[] = $c;

                continue;
            }

            $trenches[$trench][] = $c;
        }

        ksort($trenches);

        $out = [];

        foreach ($trenches as $name => $rows) {
            usort($rows, static fn ($a, $b) => (float) $b->top_elevation_m <=> (float) $a->top_elevation_m);

            $beds = array_values(array_filter($rows, static fn ($r) => !$r->is_feature));
            $features = array_values(array_filter($rows, static fn ($r) => $r->is_feature));

            // Column assignment for deposits that overlap in elevation.
            $columns = [];

            foreach ($beds as $bed) {
                $placed = false;

                foreach ($columns as $i => $column) {
                    $clash = false;

                    foreach ($column as $other) {
                        if ((float) $bed->bottom_elevation_m < (float) $other->top_elevation_m
                            && (float) $other->bottom_elevation_m < (float) $bed->top_elevation_m) {
                            $clash = true;

                            break;
                        }
                    }

                    if (!$clash) {
                        $columns[$i][] = $bed;
                        $placed = true;

                        break;
                    }
                }

                if (!$placed) {
                    $columns[] = [$bed];
                }
            }

            $out[] = [
                'name' => $name,
                'columns' => $columns,
                'features' => $features,
                'context_count' => count($rows),
                'find_count' => array_sum(array_map(static fn ($r) => $r->find_count, $rows)),
            ];
        }

        return [
            'trenches' => $out,
            'min' => $min,
            'max' => $max,
            'unplaced' => $unplaced,
            'excluded' => $excluded,
            'all_trenches' => $this->trenchNames($siteId),
            'all_types' => $this->typeNamesInUse($siteId),
        ];
    }

    /** Distinct trench references recorded for a site, for the plan filter. */
    public function trenchNames(int $siteId): array
    {
        $out = [];

        foreach (DB::table('archaeology_context')->where('site_id', $siteId)
            ->distinct()->pluck('excavation_reference') as $name) {
            $name = trim((string) $name);
            $out[] = '' === $name ? 'Unassigned' : $name;
        }

        $out = array_values(array_unique($out));
        sort($out);

        return $out;
    }

    /** Context type names actually in use on a site, for the plan filter. */
    public function typeNamesInUse(int $siteId): array
    {
        $out = [];

        foreach ($this->contextsForSite($siteId) as $c) {
            if ($c->type_name) {
                $out[] = (string) $c->type_name;
            }
        }

        $out = array_values(array_unique($out));
        sort($out);

        return $out;
    }

    /** Context types that are features rather than layers. */
    public const FEATURE_TYPES = ['Cut', 'Fill', 'Masonry', 'Structure', 'Skeleton'];

    /**
     * A site's position, formatted, or null when nothing is recorded.
     *
     * Returns decimal degrees, degrees-minutes-seconds, and the accuracy radius,
     * because a coordinate without its accuracy invites a reader to treat a
     * 5 km estimate as a survey point.
     */
    public function sitePosition(int $siteId): ?array
    {
        $site = DB::table('archaeology_site')->where('id', $siteId)
            ->first(['latitude', 'longitude', 'elevation_m', 'spatial_accuracy_m']);

        if (!$site || null === $site->latitude || null === $site->longitude) {
            return null;
        }

        $lat = (float) $site->latitude;
        $lng = (float) $site->longitude;

        return [
            'lat' => $lat,
            'lng' => $lng,
            'lat_dms' => $this->toDms($lat, 'S', 'N'),
            'lng_dms' => $this->toDms($lng, 'W', 'E'),
            'elevation_m' => $site->elevation_m,
            'accuracy_m' => $site->spatial_accuracy_m,
        ];
    }

    private function toDms(float $value, string $negative, string $positive): string
    {
        $hemisphere = $value < 0 ? $negative : $positive;
        $value = abs($value);
        $deg = (int) floor($value);
        $minFloat = ($value - $deg) * 60;
        $min = (int) floor($minFloat);
        $sec = ($minFloat - $min) * 60;

        return sprintf('%d°%02d\'%04.1f"%s', $deg, $min, $sec, $hemisphere);
    }

    // -----------------------------------------------------------------------
    // CSV import (Phase 4b)
    // -----------------------------------------------------------------------

    /** Context columns an import CSV may carry. Only context_number is required. */
    public const CSV_CONTEXT_FIELDS = [
        'context_number', 'context_type', 'phase', 'description', 'interpretation',
        'top_elevation_m', 'bottom_elevation_m', 'excavation_reference', 'excavator',
        'excavation_date', 'date_earliest', 'date_latest', 'dating_note',
    ];

    /** Relationship columns. Each holds one or more OTHER context numbers. */
    public const CSV_REL_FIELDS = [
        'above', 'below', 'cuts', 'cut_by', 'fills', 'filled_by',
        'same_as', 'bonds_with', 'abuts',
    ];

    /**
     * Parse an uploaded CSV into rows keyed by lower-cased header.
     *
     * BOM-tolerant, because a spreadsheet saved from Excel begins with one and it
     * would otherwise make the first column name unmatchable - the import would
     * report every row as missing a context number while the file looks correct.
     *
     * @return array{rows: array<int, array<string, string>>, error: ?string}
     */
    /**
     * Read a headered CSV into rows keyed by lower-cased column name.
     *
     * $required names the column the file must have. It defaults to
     * context_number because that is what the context importer needs, but the
     * relationship importers read different files - the check exists to give a
     * clear error on the wrong file, not to tie this reader to one format.
     */
    public function parseCsv(string $path, string $required = 'context_number'): array
    {
        if (!is_readable($path)) {
            return ['rows' => [], 'error' => 'The uploaded file could not be read.'];
        }

        $handle = fopen($path, 'r');

        if (false === $handle) {
            return ['rows' => [], 'error' => 'The uploaded file could not be opened.'];
        }

        $header = fgetcsv($handle);

        if (false === $header || !$header) {
            fclose($handle);

            return ['rows' => [], 'error' => 'The file is empty.'];
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $header = array_map(static fn ($h) => strtolower(trim((string) $h)), $header);

        if ('' !== $required && !in_array($required, $header, true)) {
            fclose($handle);

            return ['rows' => [], 'error' => "The file has no {$required} column."];
        }

        $rows = [];

        while (false !== ($line = fgetcsv($handle))) {
            if (1 === count($line) && (null === $line[0] || '' === trim((string) $line[0]))) {
                continue;
            }

            $row = [];

            foreach ($header as $i => $name) {
                $row[$name] = isset($line[$i]) ? trim((string) $line[$i]) : '';
            }

            $rows[] = $row;
        }

        fclose($handle);

        return ['rows' => $rows, 'error' => null];
    }

    /**
     * Import contexts and their relationships for a site.
     *
     * Two passes. Every context is upserted first, then the relationship columns
     * are resolved to context ids - a row may name a context defined further down
     * the same file, which a single pass could not resolve.
     *
     * Relationships go through addRelationship(), so reciprocity and the cycle
     * guard apply exactly as they do to typed entry. An import cannot introduce a
     * contradiction that the form would have refused.
     *
     * The whole run is wrapped in a transaction that is rolled back unless
     * $commit is true, so a preview reports real counts and real warnings from a
     * real run without writing anything.
     *
     * @return array{created:int, updated:int, relationships:int, warnings:array<int,string>, committed:bool}
     */
    public function importContextsCsv(int $siteId, array $rows, bool $commit): array
    {
        $result = ['created' => 0, 'updated' => 0, 'relationships' => 0, 'warnings' => [], 'committed' => false];

        if (!$this->installed()) {
            $result['warnings'][] = 'The archaeology tables are not installed.';

            return $result;
        }

        $types = $this->termNameMap('context_type');
        $phases = $this->termNameMap('phase');

        DB::beginTransaction();

        try {
            $idByNumber = [];

            // Pass one: the contexts themselves.
            foreach ($rows as $i => $row) {
                $line = $i + 2; // header is line 1
                $number = trim((string) ($row['context_number'] ?? ''));

                if ('' === $number) {
                    $result['warnings'][] = "Line {$line}: no context_number, row skipped.";

                    continue;
                }

                $data = ['site_id' => $siteId, 'context_number' => $number];

                foreach (self::CSV_CONTEXT_FIELDS as $field) {
                    if ('context_number' === $field || !array_key_exists($field, $row)) {
                        continue;
                    }

                    if (!in_array($field, ['context_type', 'phase'], true)) {
                        $data[$field] = $row[$field];
                    }
                }

                // Terms are matched by name, case-insensitively. An unknown value
                // is left unset and reported rather than silently invented.
                foreach ([['context_type', $types, 'context_type_id'], ['phase', $phases, 'phase_id']] as [$col, $map, $target]) {
                    $value = trim((string) ($row[$col] ?? ''));

                    if ('' === $value) {
                        continue;
                    }

                    $key = mb_strtolower($value);

                    if (isset($map[$key])) {
                        $data[$target] = $map[$key];
                    } else {
                        $result['warnings'][] = "Line {$line}: unknown {$col} \"{$value}\", left blank.";
                    }
                }

                $existing = DB::table('archaeology_context')
                    ->where('site_id', $siteId)
                    ->where('context_number', $number)
                    ->value('id');

                $id = $this->saveContext($data, $existing ? (int) $existing : null);
                $idByNumber[$number] = $id;

                if ($existing) {
                    ++$result['updated'];
                } else {
                    ++$result['created'];
                }
            }

            // Contexts already recorded for this site, so a relationship may point
            // at one that is not in this file.
            foreach (DB::table('archaeology_context')->where('site_id', $siteId)->get(['id', 'context_number']) as $c) {
                $idByNumber[$c->context_number] = (int) $c->id;
            }

            // Pass two: relationships.
            foreach ($rows as $i => $row) {
                $line = $i + 2;
                $number = trim((string) ($row['context_number'] ?? ''));

                if ('' === $number || !isset($idByNumber[$number])) {
                    continue;
                }

                foreach (self::CSV_REL_FIELDS as $type) {
                    $cell = trim((string) ($row[$type] ?? ''));

                    if ('' === $cell) {
                        continue;
                    }

                    foreach (preg_split('/[;,]/', $cell) as $target) {
                        $target = trim($target);

                        if ('' === $target) {
                            continue;
                        }

                        if (!isset($idByNumber[$target])) {
                            $result['warnings'][] = "Line {$line}: {$type} refers to context \"{$target}\", which does not exist.";

                            continue;
                        }

                        $added = $this->addRelationship($idByNumber[$number], $idByNumber[$target], $type);

                        if ($added['ok']) {
                            ++$result['relationships'];
                        } else {
                            $result['warnings'][] = "Line {$line}: {$number} {$type} {$target} refused - ".($added['error'] ?? 'unknown reason');
                        }
                    }
                }
            }

            if ($commit) {
                DB::commit();
                $result['committed'] = true;
            } else {
                DB::rollBack();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $result['warnings'][] = 'Import aborted: '.$e->getMessage();
        }

        return $result;
    }

    /** Term names of a vocabulary, lower-cased, mapped to their ids. */
    private function termNameMap(string $key): array
    {
        $map = [];

        foreach ($this->vocabulary($key) as $term) {
            $map[mb_strtolower((string) $term->name)] = (int) $term->id;
        }

        return $map;
    }

    /** A ready-to-fill CSV template, with one worked example row. */
    public function csvTemplate(): string
    {
        $header = array_merge(self::CSV_CONTEXT_FIELDS, self::CSV_REL_FIELDS);

        $example = [
            'context_number' => '1002',
            'context_type' => 'Deposit',
            'phase' => 'Phase 2',
            'description' => 'Ashy occupation deposit',
            'interpretation' => 'Occupation surface',
            'top_elevation_m' => '1.050',
            'bottom_elevation_m' => '0.820',
            'excavation_reference' => 'Trench 1',
            'excavator' => '',
            'excavation_date' => '2026-06-15',
            'date_earliest' => 'c. 1600 AD',
            'date_latest' => 'c. 1850 AD',
            'dating_note' => '',
            'below' => '1001',
            'above' => '1005',
        ];

        $out = fopen('php://temp', 'r+');
        fputcsv($out, $header);
        fputcsv($out, array_map(static fn ($h) => $example[$h] ?? '', $header));
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    /**
     * A self-contained context sheet, for print or PDF.
     *
     * Everything is inline and local: dompdf is run with remote fetching off, so
     * an external stylesheet or webfont would silently not arrive and the sheet
     * would print unstyled. Colours are the house green.
     */
    public function contextSheetHtml(int $contextId): string
    {
        $ctx = $this->context($contextId);

        if (!$ctx) {
            return '<p>No such context.</p>';
        }

        $relationships = $this->relationshipsForContext($contextId);
        $finds = $this->findsForContext($contextId);

        $e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $rows = [
            'Context' => $this->contextLabel($ctx),
            'Type' => $ctx->type_name,
            'Phase' => $ctx->phase_name,
            'Top elevation (m)' => $ctx->top_elevation_m,
            'Bottom elevation (m)' => $ctx->bottom_elevation_m,
            'Trench / square / spit' => $ctx->excavation_reference,
            'Excavator' => $ctx->excavator,
            'Excavation date' => $ctx->excavation_date,
            'Earliest date' => $ctx->date_earliest,
            'Latest date' => $ctx->date_latest,
            'Dating note' => $ctx->dating_note,
        ];

        $html = '<html><head><meta charset="utf-8"><style>'
            .'body{font-family:"DejaVu Sans",Arial,sans-serif;font-size:10pt;color:#1f2933;}'
            .'h1{font-size:15pt;color:#10373E;margin:0 0 2mm 0;}'
            .'h2{font-size:11pt;color:#10373E;margin:6mm 0 2mm 0;border-bottom:1px solid #10373E;padding-bottom:1mm;}'
            .'table{width:100%;border-collapse:collapse;margin-bottom:3mm;}'
            .'th,td{border:1px solid #b9c8c7;padding:1.5mm 2mm;text-align:left;vertical-align:top;}'
            .'th{background:#10373E;color:#fff;font-weight:bold;}'
            .'th.k{background:#eef3f2;color:#1f2933;width:35%;}'
            .'.muted{color:#5b6b6a;font-size:8pt;}'
            .'</style></head><body>';

        $html .= '<h1>Context sheet '.$e($this->contextLabel($ctx)).'</h1>';
        $html .= '<div class="muted">Site '.$e($ctx->site_number).'</div>';

        $html .= '<h2>Record</h2><table>';

        foreach ($rows as $label => $value) {
            if (null === $value || '' === $value) {
                continue;
            }

            $html .= '<tr><th class="k">'.$e($label).'</th><td>'.$e($value).'</td></tr>';
        }

        $html .= '</table>';

        foreach (['Description' => $ctx->description, 'Interpretation' => $ctx->interpretation] as $heading => $text) {
            if ($text) {
                $html .= '<h2>'.$e($heading).'</h2><p>'.nl2br($e($text)).'</p>';
            }
        }

        $html .= '<h2>Stratigraphic relationships</h2>';

        if (!$relationships) {
            $html .= '<p class="muted">None recorded.</p>';
        } else {
            // Grouped in the conventional order rather than alphabetically, so the
            // sheet reads the way a context sheet is expected to read.
            $order = array_keys(self::REL_TYPES);
            $grouped = [];

            foreach ($relationships as $rel) {
                $grouped[$rel->relationship_type][] = $rel->related_number;
            }

            $html .= '<table><tr><th>Relationship</th><th>Contexts</th></tr>';

            foreach ($order as $type) {
                if (empty($grouped[$type])) {
                    continue;
                }

                $html .= '<tr><td>'.$e(self::REL_TYPES[$type]['label']).'</td>'
                    .'<td>'.$e(implode(', ', $grouped[$type])).'</td></tr>';
            }

            $html .= '</table>';
        }

        $html .= '<h2>Finds</h2>';

        if (!$finds) {
            $html .= '<p class="muted">No finds are linked to this context.</p>';
        } else {
            $html .= '<table><tr><th>Accession</th><th>Description</th><th>Count</th></tr>';

            foreach ($finds as $find) {
                $html .= '<tr><td>'.$e($find->accession_number).'</td>'
                    .'<td>'.$e($find->title ?? '').'</td>'
                    .'<td>'.(int) $find->item_count.'</td></tr>';
            }

            $html .= '</table>';
        }

        $html .= '<p class="muted">Site '.$e($ctx->site_number).' &middot; context '
            .$e($ctx->context_number).' &middot; generated '.date('Y-m-d H:i').'</p>';

        return $html.'</body></html>';
    }

    // -----------------------------------------------------------------------
    // Spatial (Phase 4b)
    // -----------------------------------------------------------------------

    /**
     * Sites that have a recorded position.
     *
     * Spatial querying is done here rather than through Elasticsearch because
     * AtoM's information_object mapping has no geo field at all - 48 mapped
     * properties, none of them geo_point. Adding one means editing
     * config/search.yml, which is a base AtoM file. So the index answers "what is
     * this called", the database answers "where is it", and neither is asked to
     * do the other's job.
     *
     * @return array<int, object>
     */
    public function sitesWithCoordinates(): array
    {
        $culture = $this->culture();

        return DB::table('archaeology_site as s')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('ioi.id', '=', 's.information_object_id')->where('ioi.culture', '=', $culture);
            })
            ->whereNotNull('s.latitude')
            ->whereNotNull('s.longitude')
            ->orderBy('s.site_number')
            ->get([
                's.id', 's.site_number', 's.latitude', 's.longitude',
                's.elevation_m', 's.spatial_accuracy_m', 's.region', 's.locality',
                's.excavated', 'ioi.title',
            ])
            ->all();
    }

    /**
     * Sites within $radiusKm of a point, nearest first.
     *
     * Great-circle distance computed in SQL. A bounding box narrows the candidate
     * set first so the trigonometry runs over few rows; the box is deliberately
     * generous, because a box that clips the true circle would silently drop
     * sites near its corners.
     *
     * @return array<int, object> each with a `distance_km`
     */
    public function sitesNear(float $lat, float $lng, float $radiusKm): array
    {
        $culture = $this->culture();

        // One degree of latitude is ~111 km everywhere; longitude shrinks with
        // the cosine of the latitude, and is clamped so a site near the poles
        // does not produce an absurd or divide-by-zero span.
        $latDelta = $radiusKm / 111.0;
        $cos = max(0.01, cos(deg2rad($lat)));
        $lngDelta = $radiusKm / (111.0 * $cos);

        $rows = DB::table('archaeology_site as s')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('ioi.id', '=', 's.information_object_id')->where('ioi.culture', '=', $culture);
            })
            ->whereNotNull('s.latitude')
            ->whereNotNull('s.longitude')
            ->whereBetween('s.latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('s.longitude', [$lng - $lngDelta, $lng + $lngDelta])
            ->selectRaw(
                's.id, s.site_number, s.latitude, s.longitude, s.elevation_m,'
                .' s.spatial_accuracy_m, s.region, s.locality, s.excavated, ioi.title,'
                .' (6371 * ACOS(LEAST(1.0, GREATEST(-1.0,'
                .'   COS(RADIANS(?)) * COS(RADIANS(s.latitude))'
                .'   * COS(RADIANS(s.longitude) - RADIANS(?))'
                .'   + SIN(RADIANS(?)) * SIN(RADIANS(s.latitude))'
                .' )))) AS distance_km',
                [$lat, $lng, $lat]
            )
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km')
            ->get()
            ->all();

        return $rows;
    }

    /**
     * What the spatial layer can and cannot answer, for display.
     *
     * Surfaced rather than assumed: an interface that quietly omits sites without
     * coordinates lets a user conclude there are none there.
     */
    public function spatialCoverage(): array
    {
        $total = DB::table('archaeology_site')->count();
        $placed = DB::table('archaeology_site')
            ->whereNotNull('latitude')->whereNotNull('longitude')->count();
        $noAccuracy = DB::table('archaeology_site')
            ->whereNotNull('latitude')->whereNull('spatial_accuracy_m')->count();

        return [
            'total' => $total,
            'placed' => $placed,
            'unplaced' => $total - $placed,
            'without_accuracy' => $noAccuracy,
        ];
    }

    // -----------------------------------------------------------------------
    // Finds (Phase 4a)
    // -----------------------------------------------------------------------

    public function objects(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        if (!AhgDb::hasOptionalTable('archaeology_object')) {
            return ['rows' => [], 'total' => 0, 'page' => 1, 'perPage' => $perPage];
        }

        $culture = $this->culture();
        $page = max(1, $page);

        $query = DB::table('archaeology_object as o')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('ioi.id', '=', 'o.information_object_id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('archaeology_site as s', 's.id', '=', 'o.site_id')
            ->leftJoin('archaeology_context as c', 'c.id', '=', 'o.context_id')
            ->leftJoin('term_i18n as otype', function ($j) use ($culture) {
                $j->on('otype.id', '=', 'o.object_type_id')->where('otype.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as mat', function ($j) use ($culture) {
                $j->on('mat.id', '=', 'o.material_id')->where('mat.culture', '=', $culture);
            });

        if ('' !== ($term = trim((string) ($filters['q'] ?? '')))) {
            $query->where(function ($w) use ($term) {
                $w->where('o.accession_number', 'like', '%'.$term.'%')
                    ->orWhere('ioi.title', 'like', '%'.$term.'%')
                    ->orWhere('o.storage_location', 'like', '%'.$term.'%');
            });
        }

        if (!empty($filters['site_id'])) {
            $query->where('o.site_id', (int) $filters['site_id']);
        }

        if (!empty($filters['context_id'])) {
            $query->where('o.context_id', (int) $filters['context_id']);
        }

        // "Not linked to a context" is a real question during post-excavation:
        // it is the backlog of material whose provenance has not been resolved.
        if (!empty($filters['no_context'])) {
            $query->whereNull('o.context_id');
        }

        $total = (clone $query)->count();

        $rows = $query->orderBy('o.accession_number')
            ->forPage($page, $perPage)
            ->get([
                'o.*', 'ioi.title',
                's.site_number', 'c.context_number',
                'otype.name as object_type_name', 'mat.name as material_name',
            ])
            ->all();

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    public function object(int $id): ?object
    {
        if (!AhgDb::hasOptionalTable('archaeology_object')) {
            return null;
        }

        $culture = $this->culture();

        return DB::table('archaeology_object as o')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('ioi.id', '=', 'o.information_object_id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('archaeology_site as s', 's.id', '=', 'o.site_id')
            ->leftJoin('archaeology_context as c', 'c.id', '=', 'o.context_id')
            ->leftJoin('term_i18n as otype', function ($j) use ($culture) {
                $j->on('otype.id', '=', 'o.object_type_id')->where('otype.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as mat', function ($j) use ($culture) {
                $j->on('mat.id', '=', 'o.material_id')->where('mat.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as per', function ($j) use ($culture) {
                $j->on('per.id', '=', 'o.period_id')->where('per.culture', '=', $culture);
            })
            ->where('o.id', $id)
            ->first([
                'o.*', 'ioi.title',
                's.site_number', 's.id as site_pk',
                'c.context_number',
                'otype.name as object_type_name',
                'mat.name as material_name',
                'per.name as period_name',
            ]);
    }

    /**
     * Create or update a find.
     *
     * A find's description is created beneath its CONTEXT where one is recorded,
     * and beneath the site otherwise. That is not cosmetic: it is what makes the
     * assemblage from a deposit visible as a group in the archival hierarchy
     * rather than only through a query.
     */
    public function saveFind(array $data, ?int $id = null): int
    {
        $row = $this->filterWritable($data, self::FIND_WRITABLE);
        $userId = $this->userId();

        foreach (['object_type_id', 'material_id', 'technique_id', 'period_id',
            'recovery_method_id', 'dating_method_id', 'condition_id', 'repository_id'] as $f) {
            $row[$f] = $this->intOrNull($row[$f] ?? null);
        }

        foreach (['weight_g', 'length_mm', 'width_mm', 'thickness_mm', 'diameter_mm'] as $f) {
            $row[$f] = $this->numOrNull($row[$f] ?? null);
        }

        $row['find_date'] = $this->dateOrNull($row['find_date'] ?? null);
        $row['item_count'] = max(1, (int) ($row['item_count'] ?? 1));

        $siteId = $this->intOrNull($data['site_id'] ?? null);
        $contextId = $this->intOrNull($data['context_id'] ?? null);

        // A context belongs to exactly one site. Accepting a pair that disagrees
        // would put the find in one site's assemblage and one context's, which is
        // a contradiction nothing downstream could resolve.
        if ($contextId) {
            $owner = DB::table('archaeology_context')->where('id', $contextId)->value('site_id');

            if (!$owner) {
                $contextId = null;
            } elseif ($siteId && (int) $owner !== $siteId) {
                throw new \InvalidArgumentException('That context belongs to a different site.');
            } else {
                $siteId = (int) $owner;
            }
        }

        $row['site_id'] = $siteId;
        $row['context_id'] = $contextId;

        if ($id) {
            $row['updated_by'] = $userId;
            DB::table('archaeology_object')->where('id', $id)->update($row);
            $this->ensureFindDescription($id);

            return $id;
        }

        $row['created_by'] = $userId;
        $row['updated_by'] = $userId;

        $newId = (int) DB::table('archaeology_object')->insertGetId($row);
        $this->ensureFindDescription($newId);

        return $newId;
    }

    /** Give a find a description under its context, or its site. */
    private function ensureFindDescription(int $findId): void
    {
        $find = DB::table('archaeology_object')->where('id', $findId)
            ->first(['information_object_id', 'accession_number', 'context_id', 'site_id']);

        if (!$find || $find->information_object_id) {
            return;
        }

        $parentIo = null;

        if ($find->context_id) {
            $parentIo = DB::table('archaeology_context')->where('id', $find->context_id)
                ->value('information_object_id');
        }

        if (!$parentIo && $find->site_id) {
            $parentIo = DB::table('archaeology_site')->where('id', $find->site_id)
                ->value('information_object_id');
        }

        if (!$parentIo) {
            // Nothing to hang it under. A stray description at the root of the
            // tree is worse than none, because nobody goes looking for it.
            return;
        }

        $ioId = $this->createDescription('Find '.$find->accession_number, (int) $parentIo);

        DB::table('archaeology_object')->where('id', $findId)
            ->update(['information_object_id' => $ioId]);
    }

    // -----------------------------------------------------------------------
    // Dashboard
    // -----------------------------------------------------------------------

    public function statistics(): array
    {
        $stats = [
            'sites' => 0,
            'excavated_sites' => 0,
            'contexts' => 0,
            'finds' => 0,
            'finds_with_context' => 0,
        ];

        if (AhgDb::hasOptionalTable('archaeology_site')) {
            $stats['sites'] = DB::table('archaeology_site')->count();
            $stats['excavated_sites'] = DB::table('archaeology_site')->where('excavated', 1)->count();
        }

        if ($this->installed()) {
            $stats['contexts'] = DB::table('archaeology_context')->count();
        }

        if (AhgDb::hasOptionalTable('archaeology_object')) {
            $stats['finds'] = DB::table('archaeology_object')->count();
            $stats['finds_with_context'] = DB::table('archaeology_object')->whereNotNull('context_id')->count();
        }

        return $stats;
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function filterWritable(array $data, array $allowed): array
    {
        $out = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                $out[$field] = ('' === $value) ? null : $value;
            }
        }

        return $out;
    }

    private function numOrNull($v): ?float
    {
        if (null === $v || '' === $v || !is_numeric($v)) {
            return null;
        }

        return (float) $v;
    }

    private function intOrNull($v): ?int
    {
        if (null === $v || '' === $v || !is_numeric($v)) {
            return null;
        }

        return (int) $v;
    }

    private function dateOrNull($v): ?string
    {
        if (null === $v || '' === trim((string) $v)) {
            return null;
        }

        $ts = strtotime((string) $v);

        return false === $ts ? null : date('Y-m-d', $ts);
    }
}
