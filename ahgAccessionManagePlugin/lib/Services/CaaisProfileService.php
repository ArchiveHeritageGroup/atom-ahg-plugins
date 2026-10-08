<?php

/**
 * CaaisProfileService - AtoM side
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

namespace AhgAccessionManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CAAIS 1.0 (Canadian Archival Accession Information Standard, Canadian Council
 * of Archives, 15 May 2019) accession profile for AtoM (atom-ahg-plugins#199,
 * #203). A port of Heratio's CaaisProfileService (heratio#1514).
 *
 * Both systems run on one database, so this reads and writes the same
 * accession_caais* side tables Heratio created (install_caais.sql) and the
 * same ahg_dropdown codes and ahg_settings switch. Keep the codes, the save
 * rules and the export keys in step with
 * packages/ahg-accession-manage/src/Services/CaaisProfileService.php: an
 * accession edited on one side must read the same on the other, and the JSON
 * export is meant to be the same document whichever side produced it.
 *
 * Two differences are forced by AtoM. There is no Laravel validator, so
 * clean() applies Heratio's rules by hand. And the accession form is saved by
 * base AtoM's edit action, which this plugin cannot reach, so the profile is
 * saved after it (see ahgAccessionManagePluginConfiguration) and cannot veto
 * the save: values Heratio would reject are dropped and reported instead.
 */
class CaaisProfileService
{
    public const SETTING = 'accession_caais_enabled';

    /** Last table in install_caais.sql - present only when the whole file ran. */
    public const SENTINEL_TABLE = 'accession_caais_revision';

    /** The two events CAAIS 5.1 makes mandatory (codes in caais_event_type). */
    public const EVENT_PHYSICAL = 'physical_transfer';

    public const EVENT_LEGAL = 'legal_transfer';

    /** Form key => ahg_dropdown taxonomy. */
    public const TAXONOMIES = [
        'extent_type' => 'caais_extent_type',
        'unit' => 'caais_extent_unit',
        'content_type' => 'caais_content_type',
        'carrier_type' => 'caais_carrier_type',
        'confidentiality' => 'caais_source_confidentiality',
        'language' => 'caais_language',
        'requirement_type' => 'caais_preservation_type',
        'event_type' => 'caais_event_type',
        'revision_type' => 'caais_revision_type',
    ];

    /**
     * The crosswalk: export key => [CAAIS element, source]. Published inside
     * every export so a receiving system can map without this code. The
     * wording is Heratio's, so both sides publish the same document.
     */
    public const CROSSWALK = [
        'repository' => ['1.1', 'accession_caais.repository_id -> repository authorised name'],
        'identifiers' => ['1.2', 'accession.identifier (Accession number) + other_name alternative identifiers'],
        'accession_title' => ['1.3', 'accession_i18n.title'],
        'acquisition_method' => ['1.5', 'accession.acquisition_type_id (term)'],
        'status' => ['1.7', 'accession.processing_status_id (term)'],
        'source_of_material' => ['2.1', 'donor relation + contact_information; 2.1.6 from accession_caais_source'],
        'preliminary_custodial_history' => ['2.2', 'accession_i18n.archival_history'],
        'date_of_material' => ['3.1', 'event rows on the accession (display date or start/end)'],
        'extent_statement' => ['3.2', 'accession_caais_extent; accession_i18n.received_extent_units as a note when none'],
        'preliminary_scope_and_content' => ['3.3', 'accession_i18n.scope_and_content'],
        'language_of_material' => ['3.4', 'accession_caais_language'],
        'storage_location' => ['4.1', 'accession_i18n.location_information'],
        'rights' => ['4.2', 'rights linked by relation'],
        'preservation_requirements' => ['4.3', 'accession_caais_preservation; accession_i18n.physical_characteristics when none'],
        'appraisal' => ['4.4', 'accession_i18n.appraisal'],
        'events' => ['5.1', 'accession_caais_event + core accession_event'],
        'general_note' => ['6.1', 'accession_i18n.processing_notes'],
        'rules_or_conventions' => ['7.1', 'accession_caais.rules_or_conventions'],
        'date_of_creation_or_revision' => ['7.2', 'accession_caais_revision'],
        'language_of_accession_record' => ['7.3', 'accession.source_culture'],
    ];

    /**
     * CSV columns: header => [record path, field of each list entry]. One row
     * per accession; a repeatable element becomes one column per field, its
     * entries joined with " | " in the same order in every column, so the nth
     * value of extent_types belongs with the nth value of extent_quantities.
     */
    public const CSV_COLUMNS = [
        'repository' => ['identity.repository', null],
        'identifier_types' => ['identity.identifiers', 'type'],
        'identifier_values' => ['identity.identifiers', 'value'],
        'accession_title' => ['identity.accession_title', null],
        'acquisition_method' => ['identity.acquisition_method', null],
        'status' => ['identity.status', null],
        'source_names' => ['source.source_of_material', 'source_name'],
        'source_roles' => ['source.source_of_material', 'source_role'],
        'source_contact_information' => ['source.source_of_material', 'source_contact_information'],
        'source_confidentiality' => ['source.source_of_material', 'source_confidentiality'],
        'preliminary_custodial_history' => ['source.preliminary_custodial_history', null],
        'date_of_material' => ['materials.date_of_material', null],
        'extent_types' => ['materials.extent_statement', 'extent_type'],
        'extent_quantities' => ['materials.extent_statement', 'quantity_and_unit_of_measure'],
        'extent_content_types' => ['materials.extent_statement', 'content_type'],
        'extent_carrier_types' => ['materials.extent_statement', 'carrier_type'],
        'extent_digital_file_formats' => ['materials.extent_statement', 'digital_file_formats'],
        'extent_notes' => ['materials.extent_statement', 'extent_note'],
        'preliminary_scope_and_content' => ['materials.preliminary_scope_and_content', null],
        'language_of_material' => ['materials.language_of_material', null],
        'storage_location' => ['management.storage_location', null],
        'rights_types' => ['management.rights', 'rights_type'],
        'rights_values' => ['management.rights', 'rights_value'],
        'rights_notes' => ['management.rights', 'rights_note'],
        'preservation_requirement_types' => ['management.preservation_requirements', 'preservation_requirement_type'],
        'preservation_requirement_values' => ['management.preservation_requirements', 'preservation_requirement_value'],
        'preservation_requirement_notes' => ['management.preservation_requirements', 'preservation_requirement_note'],
        'appraisal' => ['management.appraisal', 'appraisal_value'],
        'event_types' => ['events', 'event_type'],
        'event_dates' => ['events', 'event_date'],
        'event_agents' => ['events', 'event_agent'],
        'event_notes' => ['events', 'event_note'],
        'general_note' => ['general.general_note', null],
        'rules_or_conventions' => ['control.rules_or_conventions', null],
        'revision_types' => ['control.date_of_creation_or_revision', 'creation_or_revision_type'],
        'revision_dates' => ['control.date_of_creation_or_revision', 'creation_or_revision_date'],
        'revision_agents' => ['control.date_of_creation_or_revision', 'creation_or_revision_agent'],
        'language_of_accession_record' => ['control.language_of_accession_record', null],
        'missing_mandatory' => ['conformance.missing_mandatory', null],
    ];

    /**
     * XML: the child element used for each entry of a repeatable element. A
     * list not named here holds plain values, one <value> each.
     */
    public const XML_LIST_ITEMS = [
        'crosswalk' => 'map',
        'records' => 'record',
        'identifiers' => 'identifier',
        'source_of_material' => 'source',
        'extent_statement' => 'extent',
        'rights' => 'right',
        'preservation_requirements' => 'preservation_requirement',
        'appraisal' => 'appraisal_entry',
        'events' => 'event',
        'date_of_creation_or_revision' => 'creation_or_revision',
        'missing_mandatory' => 'element',
    ];

    public const XML_SERIALISATION = 'ahg-caais-xml/1';

    /** Upper bound on rows per repeatable element in one save. */
    private const MAX_ROWS = 100;

    /** QubitTerm::RIGHT_ID and DONOR_ID - literal so this runs outside Symfony. */
    private const RELATION_RIGHT = 168;

    private const RELATION_DONOR = 169;

    private static ?bool $installed = null;

    private ?array $choiceCache = null;

    /** Labels of inactive codes still held by old rows. */
    private array $retired = [];

    private string $culture;

    public function __construct(?string $culture = null)
    {
        $this->culture = $culture ?? self::currentCulture();
    }

    public function installed(): bool
    {
        // Once per request: the edit form, the view page and the browse list
        // each ask, and information_schema is not free.
        if (null === self::$installed) {
            try {
                self::$installed = DB::table('information_schema.tables')
                    ->whereRaw('table_schema = DATABASE()')
                    ->where('table_name', self::SENTINEL_TABLE)
                    ->exists();
            } catch (\Throwable $e) {
                self::$installed = false;
            }
        }

        return self::$installed;
    }

    public function enabled(): bool
    {
        if (!$this->installed()) {
            return false;
        }
        $v = DB::table('ahg_settings')->where('setting_key', self::SETTING)->value('setting_value');

        return in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Active dropdown options per form key, code => label, in sort order.
     *
     * @return array<string, array<string, string>>
     */
    public function choices(): array
    {
        if (null !== $this->choiceCache) {
            return $this->choiceCache;
        }
        $rows = DB::table('ahg_dropdown')
            ->whereIn('taxonomy', array_values(self::TAXONOMIES))
            ->where('is_active', 1)
            ->orderBy('sort_order')->orderBy('label')
            ->get(['taxonomy', 'code', 'label']);

        $out = array_fill_keys(array_keys(self::TAXONOMIES), []);
        $byTaxonomy = array_flip(self::TAXONOMIES);
        foreach ($rows as $r) {
            $out[$byTaxonomy[$r->taxonomy]][$r->code] = $r->label;
        }

        return $this->choiceCache = $out;
    }

    /** Label for a stored code; a retired term's label, or the raw code. */
    public function label(string $key, ?string $code): ?string
    {
        if (null === $code || '' === $code) {
            return null;
        }
        $active = $this->choices()[$key][$code] ?? null;
        if (null !== $active) {
            return $active;
        }
        if (!array_key_exists($key.':'.$code, $this->retired)) {
            $this->retired[$key.':'.$code] = DB::table('ahg_dropdown')
                ->where('taxonomy', self::TAXONOMIES[$key])->where('code', $code)->value('label');
        }

        return $this->retired[$key.':'.$code] ?? $code;
    }

    /**
     * Repositories for the 1.1 picker: id => authorised name. Falls back to the
     * repository's source culture, so a cataloguer working in another
     * language still sees every repository rather than an empty list.
     */
    public function repositoryOptions(): array
    {
        return DB::table('repository')
            ->join('actor', 'actor.id', '=', 'repository.id')
            ->leftJoin('actor_i18n as cur', function ($j) {
                $j->on('cur.id', '=', 'repository.id')->where('cur.culture', '=', $this->culture);
            })
            ->leftJoin('actor_i18n as src', function ($j) {
                $j->on('src.id', '=', 'repository.id')->on('src.culture', '=', 'actor.source_culture');
            })
            ->selectRaw('repository.id, COALESCE(cur.authorized_form_of_name, src.authorized_form_of_name) AS name')
            ->whereRaw('COALESCE(cur.authorized_form_of_name, src.authorized_form_of_name) IS NOT NULL')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** Donors linked to the accession, for the 2.1.6 picker: [id, name] rows. */
    public function sources(int $accessionId): array
    {
        return array_map(fn ($d) => ['id' => (int) $d->id, 'name' => $d->name], $this->coreDonors($accessionId));
    }

    /**
     * Everything the profile holds for one accession.
     */
    public function get(int $accessionId): array
    {
        $empty = [
            'repository_id' => null, 'repository_name' => null, 'rules_or_conventions' => null,
            'sources' => [], 'extents' => [], 'languages' => [], 'preservation' => [], 'events' => [], 'revisions' => [],
        ];
        if (!$this->installed()) {
            return $empty;
        }

        $main = DB::table('accession_caais')->where('accession_id', $accessionId)->first();
        $rows = fn (string $table) => DB::table($table)->where('accession_id', $accessionId)
            ->orderBy('sort_order')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();

        return [
            'repository_id' => $main->repository_id ?? null,
            'repository_name' => ($main->repository_id ?? null) ? $this->repositoryName((int) $main->repository_id) : null,
            'rules_or_conventions' => $main->rules_or_conventions ?? null,
            'sources' => DB::table('accession_caais_source')->where('accession_id', $accessionId)
                ->pluck('confidentiality', 'actor_id')->all(),
            'extents' => $rows('accession_caais_extent'),
            'languages' => $rows('accession_caais_language'),
            'preservation' => $rows('accession_caais_preservation'),
            'events' => $rows('accession_caais_event'),
            'revisions' => DB::table('accession_caais_revision')->where('accession_id', $accessionId)
                ->orderBy('revision_date')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
        ];
    }

    /**
     * Heratio's validation rules, applied by hand. Returns [clean input,
     * messages]. The accession itself is already saved by the time this runs,
     * so nothing can be refused: a value that would fail is left out and named
     * in the messages. A bad repository or rules value is left out of the
     * input altogether, which save() reads as "keep what is stored", rather
     * than being wiped by a typo.
     *
     * @return array{0: array, 1: string[]}
     */
    public function clean(array $input): array
    {
        $c = $this->choices();
        $errors = [];
        $out = ['_profile' => !empty($input['_profile']) ? 1 : 0];

        $repo = trim((string) ($input['repository_id'] ?? ''));
        if ('' === $repo) {
            $out['repository_id'] = null;
        } elseif (ctype_digit($repo) && DB::table('repository')->where('id', (int) $repo)->exists()) {
            $out['repository_id'] = (int) $repo;
        } else {
            $errors[] = $this->t('Repository (CAAIS 1.1): not a known repository - left unchanged.');
        }

        if (!$out['_profile']) {
            return [$out, $errors];
        }

        $rules = $input['rules_or_conventions'] ?? null;
        if (null !== $rules && mb_strlen((string) $rules) > 1024) {
            $errors[] = $this->t('Rules or conventions (CAAIS 7.1): longer than 1024 characters - left unchanged.');
        } else {
            $out['rules_or_conventions'] = $rules;
        }

        $out['sources'] = [];
        $sources = array_slice((array) ($input['sources'] ?? []), 0, self::MAX_ROWS, true);
        $actorIds = array_filter(array_keys($sources), fn ($k) => ctype_digit((string) $k));
        $known = $actorIds === [] ? [] : DB::table('actor')->whereIn('id', $actorIds)->pluck('id')->map(fn ($i) => (int) $i)->all();
        foreach ($sources as $actorId => $code) {
            $code = is_array($code) ? '' : (string) $code;
            if ('' === $code || !in_array((int) $actorId, $known, true)) {
                continue;
            }
            if (!isset($c['confidentiality'][$code])) {
                $errors[] = $this->t('Source confidentiality (CAAIS 2.1.6): unknown value - not saved.');

                continue;
            }
            $out['sources'][(int) $actorId] = $code;
        }

        $out['extents'] = $this->cleanRows($input['extents'] ?? [], $this->t('Extent statement (CAAIS 3.2)'), $errors, function (array $r) use ($c) {
            $q = trim((string) ($r['quantity'] ?? ''));
            if ('' === trim((string) ($r['extent_type'] ?? ''))) {
                return $this->t('an extent type is required');
            }
            foreach (['extent_type', 'unit', 'content_type', 'carrier_type'] as $k) {
                if ('' !== trim((string) ($r[$k] ?? '')) && !isset($c[$k][$r[$k]])) {
                    return $this->t('unknown %1%', ['%1%' => str_replace('_', ' ', $k)]);
                }
            }
            if ('' !== $q && (!is_numeric($q) || $q < 0 || $q > 99999999999)) {
                return $this->t('the quantity must be a number from 0');
            }
            if ('' !== $q && '' === trim((string) ($r['unit'] ?? ''))) {
                return $this->t('a quantity needs a unit');
            }
            if (mb_strlen((string) ($r['digital_file_formats'] ?? '')) > 1024) {
                return $this->t('digital file formats longer than 1024 characters');
            }

            return null;
        });

        $out['languages'] = $this->cleanRows($input['languages'] ?? [], $this->t('Language of material (CAAIS 3.4)'), $errors, function (array $r) use ($c) {
            if ('' !== trim((string) ($r['language'] ?? '')) && !isset($c['language'][$r['language']])) {
                return $this->t('unknown language');
            }
            if (mb_strlen((string) ($r['note'] ?? '')) > 1024) {
                return $this->t('statement longer than 1024 characters');
            }

            return null;
        });

        $out['preservation'] = $this->cleanRows($input['preservation'] ?? [], $this->t('Preservation requirement (CAAIS 4.3)'), $errors, function (array $r) use ($c) {
            $type = trim((string) ($r['requirement_type'] ?? ''));
            if ('' === $type || !isset($c['requirement_type'][$type])) {
                return $this->t('a known requirement type is required');
            }
            if ('' === trim((string) ($r['requirement_value'] ?? ''))) {
                return $this->t('the requirement itself is required');
            }

            return null;
        });

        // A suggested row is one the form pre-filled with a mandatory event
        // type. Untouched, it carries only that type and must not be saved, or
        // it would satisfy the 5.1 check without recording anything.
        $events = [];
        foreach (array_slice(array_values((array) ($input['events'] ?? [])), 0, self::MAX_ROWS + 2) as $row) {
            if (is_array($row) && !empty($row['_suggested'])) {
                unset($row['_suggested']);
                $row = $this->blank(array_diff_key($row, ['event_type' => 1])) ? [] : $row;
            }
            $events[] = $row;
        }
        $out['events'] = $this->cleanRows($events, $this->t('Event (CAAIS 5.1)'), $errors, function (array &$r) use ($c) {
            $type = trim((string) ($r['event_type'] ?? ''));
            if ('' === $type || !isset($c['event_type'][$type])) {
                return $this->t('a known event type is required');
            }
            $date = trim((string) ($r['event_date'] ?? ''));
            if ('' !== $date) {
                $ts = strtotime($date);
                if (false === $ts) {
                    return $this->t('the date is not a date');
                }
                $r['event_date'] = date('Y-m-d', $ts);
            }
            if (mb_strlen((string) ($r['agent'] ?? '')) > 255) {
                return $this->t('agent longer than 255 characters');
            }

            return null;
        });

        return [$out, $errors];
    }

    /**
     * Save cleaned `caais` input. The repository link is always saved. The
     * repeatable elements are replaced only when the form carried the profile
     * section (caais[_profile]=1), so saving with the profile switched off
     * never wipes what was recorded while it was on.
     */
    public function save(int $accessionId, array $input): void
    {
        if (!$this->installed()) {
            return;
        }

        DB::connection()->transaction(function () use ($accessionId, $input) {
            $main = [];
            if (array_key_exists('repository_id', $input)) {
                $main['repository_id'] = $input['repository_id'] ?: null;
            }
            $withProfile = !empty($input['_profile']);
            if ($withProfile && array_key_exists('rules_or_conventions', $input)) {
                $main['rules_or_conventions'] = $this->nullIfBlank($input['rules_or_conventions']);
            }
            DB::table('accession_caais')->updateOrInsert(['accession_id' => $accessionId], $main + ['updated_at' => date('Y-m-d H:i:s')]);

            if (!$withProfile) {
                return;
            }

            DB::table('accession_caais_source')->where('accession_id', $accessionId)->delete();
            foreach ((array) ($input['sources'] ?? []) as $actorId => $code) {
                if (null !== $code && '' !== $code && ctype_digit((string) $actorId)) {
                    DB::table('accession_caais_source')->insert([
                        'accession_id' => $accessionId, 'actor_id' => (int) $actorId, 'confidentiality' => $code,
                    ]);
                }
            }

            $this->replaceRows($accessionId, 'accession_caais_extent', $input['extents'] ?? [], 'extent_type', fn ($r) => [
                'extent_type' => $r['extent_type'],
                'quantity' => ($r['quantity'] ?? '') === '' ? null : $r['quantity'],
                'is_estimate' => empty($r['is_estimate']) ? 0 : 1,
                'unit' => $this->nullIfBlank($r['unit'] ?? null),
                'content_type' => $this->nullIfBlank($r['content_type'] ?? null),
                'carrier_type' => $this->nullIfBlank($r['carrier_type'] ?? null),
                'digital_file_formats' => $this->nullIfBlank($r['digital_file_formats'] ?? null),
                'note' => $this->nullIfBlank($r['note'] ?? null),
            ]);
            $this->replaceRows($accessionId, 'accession_caais_language', $input['languages'] ?? [], null, fn ($r) => [
                'language' => $this->nullIfBlank($r['language'] ?? null),
                'note' => $this->nullIfBlank($r['note'] ?? null),
            ]);
            $this->replaceRows($accessionId, 'accession_caais_preservation', $input['preservation'] ?? [], 'requirement_type', fn ($r) => [
                'requirement_type' => $r['requirement_type'],
                'requirement_value' => (string) $r['requirement_value'],
                'note' => $this->nullIfBlank($r['note'] ?? null),
            ]);
            $this->replaceRows($accessionId, 'accession_caais_event', $input['events'] ?? [], 'event_type', fn ($r) => [
                'event_type' => $r['event_type'],
                'event_date' => $this->nullIfBlank($r['event_date'] ?? null),
                'agent' => $this->nullIfBlank($r['agent'] ?? null),
                'note' => $this->nullIfBlank($r['note'] ?? null),
            ]);
        });
    }

    /**
     * CAAIS 7.2: append one creation or revision entry. 7.2.3 agent is the
     * user's name, snapshotted so it survives a deleted account.
     */
    public function recordRevision(int $accessionId, string $type, ?int $userId = null, ?string $note = null): void
    {
        if (!$this->installed()) {
            return;
        }
        DB::table('accession_caais_revision')->insert([
            'accession_id' => $accessionId,
            'revision_type' => $type,
            'revision_date' => date('Y-m-d H:i:s'),
            'agent' => $userId ? DB::table('user')->where('id', $userId)->value('username') : null,
            'user_id' => $userId,
            'note' => $note,
        ]);
    }

    /**
     * CAAIS mandatory elements this accession does not yet satisfy, as
     * "element - name" strings. Empty = conformant at the mandatory level.
     */
    public function missingMandatory(object $accession, array $profile, int $sourceCount, int $dateCount): array
    {
        $missing = [];
        if ('' === trim((string) ($accession->identifier ?? ''))) {
            $missing[] = '1.2 - '.$this->t('Identifiers');
        }
        if (0 === $sourceCount) {
            $missing[] = '2.1 - '.$this->t('Source of material');
        }
        if (0 === $dateCount) {
            $missing[] = '3.1 - '.$this->t('Date of material');
        }
        if ([] === $profile['extents'] && '' === trim((string) ($accession->received_extent_units ?? ''))) {
            $missing[] = '3.2 - '.$this->t('Extent statement');
        }
        $types = array_column($profile['events'], 'event_type');
        if (!in_array(self::EVENT_PHYSICAL, $types, true)) {
            $missing[] = '5.1 - '.$this->t('Event: physical transfer');
        }
        if (!in_array('created', array_column($profile['revisions'], 'revision_type'), true)) {
            $missing[] = '7.2 - '.$this->t('Date of creation (record created)');
        }

        return $missing;
    }

    /** The view page's warning list: missingMandatory() with the counts looked up. */
    public function missingFor(int $accessionId): array
    {
        $a = $this->coreAccession($accessionId);
        if (!$a) {
            return [];
        }
        $donors = count($this->coreDonors($accessionId));

        return $this->missingMandatory(
            $a,
            $this->get($accessionId),
            $donors ?: ('' === trim((string) $a->source_of_acquisition) ? 0 : 1),
            count($this->coreDates($accessionId))
        );
    }

    /**
     * One accession as a CAAIS 1.0 record, keyed by the crosswalk above. The
     * CCA publishes CAAIS as an element set with no XML schema or JSON binding,
     * so this is a JSON serialisation whose keys follow the element names and
     * whose `crosswalk` block names each element number.
     *
     * With $external, sources that carry a 2.1.6 confidentiality instruction
     * are withheld, which is the behaviour CAAIS asks of shared outputs.
     */
    public function exportRecord(int $accessionId, bool $external = false): ?array
    {
        $a = $this->coreAccession($accessionId);
        if (!$a) {
            return null;
        }
        $p = $this->get($accessionId);
        $terms = $this->coreTermNames(array_filter([$a->acquisition_type_id, $a->processing_status_id]));

        $identifiers = [['type' => 'Accession number', 'value' => $a->identifier, 'note' => null]];
        foreach ($this->coreAltIdentifiers($accessionId) as $alt) {
            $identifiers[] = ['type' => $alt->label, 'value' => $alt->identifier, 'note' => null];
        }

        $sources = [];
        $donors = $this->coreDonors($accessionId);
        foreach ($donors as $d) {
            $conf = $p['sources'][$d->id] ?? null;
            if ($external && $conf) {
                continue;
            }
            $c = $this->coreContact((int) $d->id);
            $sources[] = [
                'source_type' => null,
                'source_name' => $d->name,
                'source_contact_information' => $c ? $this->joinNonEmpty([
                    $c->contact_person ?? null, $c->street_address ?? null, $c->city ?? null,
                    $c->region ?? null, $c->postal_code ?? null, $c->country_code ?? null,
                    $c->telephone ?? null, $c->email ?? null,
                ]) : null,
                'source_role' => 'Donor',
                'source_note' => null,
                'source_confidentiality' => $this->label('confidentiality', $conf),
            ];
        }
        if ([] === $donors && '' !== trim((string) $a->source_of_acquisition)) {
            $sources[] = [
                'source_type' => null, 'source_name' => $a->source_of_acquisition,
                'source_contact_information' => null, 'source_role' => 'Immediate source of acquisition',
                'source_note' => null, 'source_confidentiality' => null,
            ];
        }

        // Conformance counts every source, including ones withheld above.
        $sourceTotal = count($donors) ?: ('' === trim((string) $a->source_of_acquisition) ? 0 : 1);

        $dates = array_values(array_filter(array_map(fn ($d) => $d->date_display
            ?: $this->joinNonEmpty([$d->start_date, $d->end_date], ' - '), $this->coreDates($accessionId))));

        $extents = array_map(fn ($e) => [
            'extent_type' => $this->label('extent_type', $e['extent_type']),
            'quantity_and_unit_of_measure' => $this->joinNonEmpty([
                $e['is_estimate'] ? 'ca.' : null,
                null === $e['quantity'] ? null : self::formatQuantity($e['quantity']),
                $this->label('unit', $e['unit']),
            ], ' '),
            'content_type' => $this->label('content_type', $e['content_type']),
            'carrier_type' => $this->label('carrier_type', $e['carrier_type']),
            'digital_file_formats' => $e['digital_file_formats'],
            'extent_note' => $e['note'],
        ], $p['extents']);
        if ([] === $extents && '' !== trim((string) $a->received_extent_units)) {
            $extents[] = ['extent_type' => $this->label('extent_type', 'extent_received'), 'quantity_and_unit_of_measure' => null,
                'content_type' => null, 'carrier_type' => null, 'digital_file_formats' => null, 'extent_note' => $a->received_extent_units];
        }

        $preservation = array_map(fn ($r) => [
            'preservation_requirement_type' => $this->label('requirement_type', $r['requirement_type']),
            'preservation_requirement_value' => $r['requirement_value'],
            'preservation_requirement_note' => $r['note'],
        ], $p['preservation']);
        if ([] === $preservation && '' !== trim((string) $a->physical_characteristics)) {
            $preservation[] = ['preservation_requirement_type' => $this->label('requirement_type', 'physical_condition'),
                'preservation_requirement_value' => $a->physical_characteristics, 'preservation_requirement_note' => null];
        }

        $events = array_map(fn ($e) => [
            'event_type' => $this->label('event_type', $e['event_type']),
            'event_date' => $e['event_date'], 'event_agent' => $e['agent'], 'event_note' => $e['note'],
        ], $p['events']);
        if ($a->date) {
            // Core accession.date is the acquisition date, not the date of the
            // material (3.1), so it travels as an event.
            $events[] = ['event_type' => 'Accession date', 'event_date' => (string) $a->date, 'event_agent' => null, 'event_note' => null];
        }
        foreach ($this->coreAccessionEvents($accessionId) as $e) {
            $events[] = ['event_type' => $e->type_name, 'event_date' => $e->date, 'event_agent' => $e->agent, 'event_note' => $e->note];
        }

        $rights = array_map(fn ($r) => [
            'rights_type' => $r->basis_name,
            'rights_value' => $this->joinNonEmpty([$r->start_date, $r->end_date], ' - '),
            'rights_note' => $r->rights_note,
        ], $this->coreRights($accessionId));

        $text = fn ($v) => '' === trim((string) $v) ? [] : [(string) $v];

        return [
            'identity' => [
                'repository' => $p['repository_name'],
                'identifiers' => $identifiers,
                'accession_title' => $a->title,
                'archival_unit' => [],
                'acquisition_method' => $a->acquisition_type_id ? ($terms[$a->acquisition_type_id] ?? null) : null,
                'disposition_authority' => [],
                'status' => $a->processing_status_id ? ($terms[$a->processing_status_id] ?? null) : null,
            ],
            'source' => [
                'source_of_material' => array_values($sources),
                'preliminary_custodial_history' => $text($a->archival_history),
            ],
            'materials' => [
                'date_of_material' => [] === $dates ? null : implode('; ', $dates),
                'extent_statement' => $extents,
                'preliminary_scope_and_content' => $text($a->scope_and_content),
                'language_of_material' => array_values(array_filter(array_map(
                    fn ($l) => $this->joinNonEmpty([$this->label('language', $l['language']), $l['note']], ' - '),
                    $p['languages']
                ))),
            ],
            'management' => [
                'storage_location' => $text($a->location_information),
                'rights' => $rights,
                'preservation_requirements' => $preservation,
                'appraisal' => [] === $text($a->appraisal) ? [] : [['appraisal_type' => null, 'appraisal_value' => $a->appraisal, 'appraisal_note' => null]],
                'associated_documentation' => [],
            ],
            'events' => $events,
            'general' => [
                'general_note' => $text($a->processing_notes),
            ],
            'control' => [
                'rules_or_conventions' => $p['rules_or_conventions'],
                'date_of_creation_or_revision' => array_map(fn ($r) => [
                    'creation_or_revision_type' => $this->label('revision_type', $r['revision_type']),
                    'creation_or_revision_date' => (string) $r['revision_date'],
                    'creation_or_revision_agent' => $r['agent'],
                    'creation_or_revision_note' => $r['note'],
                ], $p['revisions']),
                'language_of_accession_record' => $a->source_culture,
            ],
            'conformance' => [
                'missing_mandatory' => $this->missingMandatory($a, $p, $sourceTotal, count($dates)),
            ],
        ];
    }

    /** Wrap records in the export envelope, crosswalk included - Heratio's JSON format. */
    public function envelope(array $records): array
    {
        return [
            'standard' => 'Canadian Archival Accession Information Standard (CAAIS)',
            'standard_version' => '1.0',
            'standard_publisher' => 'Canadian Council of Archives',
            'serialisation' => 'heratio-caais-json/1',
            'generated_at' => date('c'),
            'crosswalk' => array_map(fn ($c) => ['element' => $c[0], 'source' => $c[1]], self::CROSSWALK),
            'records' => $records,
        ];
    }

    /**
     * CSV, one row per accession, columns as CSV_COLUMNS. Within a cell the
     * entries of a repeatable element are joined with " | "; a literal "|" or
     * "\" inside a value is escaped with a backslash so the split is never
     * ambiguous.
     */
    public static function toCsv(array $records): string
    {
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, array_keys(self::CSV_COLUMNS));
        foreach ($records as $record) {
            $row = [];
            foreach (self::CSV_COLUMNS as [$path, $field]) {
                $value = $record;
                foreach (explode('.', $path) as $step) {
                    $value = $value[$step] ?? null;
                }
                if (is_array($value)) {
                    $value = implode(' | ', array_map(
                        fn ($v) => str_replace(['\\', '|'], ['\\\\', '\|'], (string) (null === $field ? $v : ($v[$field] ?? ''))),
                        $value
                    ));
                }
                $row[] = self::spreadsheetSafe((string) $value);
            }
            fputcsv($fh, $row);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }

    /**
     * The same envelope as XML (serialisation ahg-caais-xml/1). Every key of
     * the JSON document becomes an element of the same name; a repeatable
     * element is a wrapper whose entries are named by XML_LIST_ITEMS, or
     * <value> for plain strings; a missing value is an empty element. The
     * crosswalk is a list of <map key="..." element="...">source</map>.
     */
    public function toXml(array $envelope): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $root = $doc->appendChild($doc->createElement('caais_export'));
        $root->setAttribute('serialisation', self::XML_SERIALISATION);

        foreach ($envelope as $key => $value) {
            if ('serialisation' === $key) {
                continue;
            }
            if ('crosswalk' === $key) {
                $cw = $root->appendChild($doc->createElement('crosswalk'));
                foreach ($value as $name => $c) {
                    $map = $cw->appendChild($doc->createElement('map'));
                    $map->setAttribute('key', $name);
                    $map->setAttribute('element', $c['element']);
                    $map->appendChild($doc->createTextNode($c['source']));
                }

                continue;
            }
            $this->xmlAppend($doc, $root, $key, $value);
        }

        return $doc->saveXML();
    }

    /** 12.500 -> 12.5, 10.000 -> 10, 10 -> 10; blank -> ''. */
    public static function formatQuantity($q): string
    {
        $q = trim((string) $q);

        return str_contains($q, '.') ? rtrim(rtrim($q, '0'), '.') : $q;
    }

    private function xmlAppend(\DOMDocument $doc, \DOMNode $parent, string $name, $value): void
    {
        $el = $parent->appendChild($doc->createElement($name));
        if (!is_array($value)) {
            if (null !== $value) {
                $el->appendChild($doc->createTextNode((string) $value));
            }

            return;
        }
        if (array_is_list($value)) {
            $item = self::XML_LIST_ITEMS[$name] ?? 'value';
            foreach ($value as $v) {
                $this->xmlAppend($doc, $el, $item, $v);
            }

            return;
        }
        foreach ($value as $k => $v) {
            $this->xmlAppend($doc, $el, (string) $k, $v);
        }
    }

    /**
     * A cell starting with = + @ is run as a formula by spreadsheet programs;
     * a leading apostrophe makes it text. Donor names and notes are typed by
     * people, so this export must not be a way to plant a formula.
     */
    private static function spreadsheetSafe(string $v): string
    {
        return '' !== $v && in_array($v[0], ['=', '+', '@', "\t", "\r"], true) ? "'".$v : $v;
    }

    /**
     * Shared row cleaning: drop the form's blank template rows, cap the count,
     * and drop (and report) rows the check refuses. The check may normalise
     * the row it is given.
     */
    private function cleanRows($rows, string $element, array &$errors, callable $check): array
    {
        $out = [];
        $n = 0;
        foreach (array_values((array) $rows) as $row) {
            if (!is_array($row) || $this->blank($row)) {
                continue;
            }
            ++$n;
            if (count($out) >= self::MAX_ROWS) {
                $errors[] = $this->t('%1%: more than %2% rows - the rest were not saved.', ['%1%' => $element, '%2%' => self::MAX_ROWS]);

                break;
            }
            $problem = $check($row);
            if (null !== $problem) {
                $errors[] = $this->t('%1%, row %2%: %3% - row not saved.', ['%1%' => $element, '%2%' => $n, '%3%' => $problem]);

                continue;
            }
            $out[] = $row;
        }

        return $out;
    }

    private function repositoryName(int $id): ?string
    {
        return DB::table('actor_i18n')->where('id', $id)->where('culture', $this->culture)->value('authorized_form_of_name')
            ?? DB::table('actor_i18n')->where('id', $id)->value('authorized_form_of_name');
    }

    /**
     * Replace one repeatable element's rows. Rows with every field blank are
     * the form's template row and are dropped; $typeKey rows without a type
     * cannot pass clean(), so they never reach here.
     */
    private function replaceRows(int $accessionId, string $table, array $rows, ?string $typeKey, callable $map): void
    {
        DB::table($table)->where('accession_id', $accessionId)->delete();
        $sort = 0;
        foreach (array_values($rows) as $row) {
            if (!is_array($row) || $this->blank($row)) {
                continue;
            }
            if (null !== $typeKey && ($row[$typeKey] ?? '') === '') {
                continue;
            }
            DB::table($table)->insert(['accession_id' => $accessionId, 'sort_order' => $sort++] + $map($row));
        }
    }

    private function blank(array $row): bool
    {
        foreach ($row as $k => $v) {
            if ('is_estimate' !== $k && '_suggested' !== $k && !is_array($v) && null !== $v && '' !== trim((string) $v)) {
                return false;
            }
        }

        return true;
    }

    private function nullIfBlank($v): ?string
    {
        $v = null === $v ? '' : trim((string) $v);

        return '' === $v ? null : $v;
    }

    private function joinNonEmpty(array $parts, string $sep = ', '): ?string
    {
        $parts = array_filter(array_map(fn ($p) => trim((string) $p), $parts), fn ($p) => '' !== $p);

        return [] === $parts ? null : implode($sep, $parts);
    }

    private function t(string $text, array $args = []): string
    {
        if (class_exists('sfContext', false) && \sfContext::hasInstance()) {
            return \sfContext::getInstance()->getI18N()->__($text, $args);
        }

        return strtr($text, $args);
    }

    private static function currentCulture(): string
    {
        if (class_exists('sfContext', false) && \sfContext::hasInstance()) {
            return (string) \sfContext::getInstance()->getUser()->getCulture();
        }

        return 'en';
    }

    // Core accession reads. Heratio takes these from its AccessionService;
    // AtoM has no equivalent outside Propel, so they live here, with the same
    // columns. i18n falls back to the record's source culture, as AtoM's own
    // pages do with cultureFallback.

    private function coreAccession(int $id): ?object
    {
        $a = DB::table('accession')
            ->join('object', 'accession.id', '=', 'object.id')
            ->join('slug', 'accession.id', '=', 'slug.object_id')
            ->where('accession.id', $id)
            ->first(['accession.id', 'accession.identifier', 'accession.date', 'accession.acquisition_type_id',
                'accession.processing_priority_id', 'accession.processing_status_id', 'accession.resource_type_id',
                'accession.source_culture', 'object.created_at', 'object.updated_at', 'slug.slug']);
        if (!$a) {
            return null;
        }
        $i18n = DB::table('accession_i18n')->where('id', $id)
            ->whereIn('culture', array_unique([$this->culture, (string) $a->source_culture]))
            ->orderByRaw('culture = ? DESC', [$this->culture])
            ->first();
        foreach (['title', 'scope_and_content', 'appraisal', 'archival_history', 'location_information',
            'physical_characteristics', 'processing_notes', 'received_extent_units', 'source_of_acquisition'] as $f) {
            $a->{$f} = $i18n->{$f} ?? null;
        }

        return $a;
    }

    private function coreTermNames(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }
        $out = [];
        foreach (DB::table('term_i18n')->whereIn('id', $ids)->whereIn('culture', array_unique([$this->culture, 'en']))
            ->orderByRaw('culture = ? ASC', [$this->culture])->get(['id', 'name']) as $r) {
            $out[$r->id] = $r->name;
        }

        return $out;
    }

    private function coreAltIdentifiers(int $accessionId): array
    {
        return DB::table('other_name')
            ->leftJoin('other_name_i18n', function ($j) {
                $j->on('other_name.id', '=', 'other_name_i18n.id')->where('other_name_i18n.culture', '=', $this->culture);
            })
            ->leftJoin('term_i18n', function ($j) {
                $j->on('other_name.type_id', '=', 'term_i18n.id')->where('term_i18n.culture', '=', $this->culture);
            })
            ->where('other_name.object_id', $accessionId)
            ->orderBy('other_name.id')
            ->get(['other_name.id', 'other_name_i18n.name as identifier', 'term_i18n.name as label'])
            ->all();
    }

    private function coreDonors(int $accessionId): array
    {
        return DB::table('relation')
            ->join('actor', 'actor.id', '=', 'relation.object_id')
            ->leftJoin('actor_i18n as cur', function ($j) {
                $j->on('cur.id', '=', 'relation.object_id')->where('cur.culture', '=', $this->culture);
            })
            ->leftJoin('actor_i18n as src', function ($j) {
                $j->on('src.id', '=', 'relation.object_id')->on('src.culture', '=', 'actor.source_culture');
            })
            ->where('relation.subject_id', $accessionId)
            ->where('relation.type_id', self::RELATION_DONOR)
            ->orderBy('relation.id')
            ->get(['relation.object_id as id', DB::raw('COALESCE(cur.authorized_form_of_name, src.authorized_form_of_name) AS name')])
            ->all();
    }

    private function coreContact(int $actorId): ?object
    {
        $c = DB::table('contact_information')
            ->leftJoin('contact_information_i18n', function ($j) {
                $j->on('contact_information.id', '=', 'contact_information_i18n.id')
                    ->where('contact_information_i18n.culture', '=', $this->culture);
            })
            ->where('contact_information.actor_id', $actorId)
            ->orderByDesc('contact_information.primary_contact')->orderBy('contact_information.id')
            ->first(['contact_information.contact_person', 'contact_information.street_address',
                'contact_information.email', 'contact_information.telephone', 'contact_information.postal_code',
                'contact_information.country_code', 'contact_information_i18n.city', 'contact_information_i18n.region']);

        // Heratio encrypts email and city when its contact encryption is on and
        // marks the value with an ENC2: prefix. AtoM cannot decrypt it, and
        // ciphertext in an export is worse than a gap.
        foreach (['email', 'city'] as $f) {
            if ($c && is_string($c->{$f}) && str_starts_with($c->{$f}, 'ENC2:')) {
                $c->{$f} = null;
            }
        }

        return $c;
    }

    private function coreDates(int $accessionId): array
    {
        return DB::table('event')
            ->leftJoin('event_i18n', function ($j) {
                $j->on('event.id', '=', 'event_i18n.id')->where('event_i18n.culture', '=', $this->culture);
            })
            ->where('event.object_id', $accessionId)
            ->orderBy('event.id')
            ->get(['event.id', 'event.start_date', 'event.end_date', 'event_i18n.date as date_display'])
            ->all();
    }

    private function coreAccessionEvents(int $accessionId): array
    {
        $events = DB::table('accession_event')
            ->leftJoin('accession_event_i18n', function ($j) {
                $j->on('accession_event.id', '=', 'accession_event_i18n.id')
                    ->where('accession_event_i18n.culture', '=', $this->culture);
            })
            ->leftJoin('term_i18n', function ($j) {
                $j->on('accession_event.type_id', '=', 'term_i18n.id')->where('term_i18n.culture', '=', $this->culture);
            })
            ->where('accession_event.accession_id', $accessionId)
            ->orderBy('accession_event.id')
            ->get(['accession_event.id', 'accession_event.date', 'term_i18n.name as type_name', 'accession_event_i18n.agent'])
            ->all();

        foreach ($events as $event) {
            $event->note = DB::table('note')
                ->leftJoin('note_i18n', function ($j) {
                    $j->on('note.id', '=', 'note_i18n.id')->where('note_i18n.culture', '=', $this->culture);
                })
                ->where('note.object_id', $event->id)
                ->value('note_i18n.content');
        }

        return $events;
    }

    private function coreRights(int $accessionId): array
    {
        return DB::table('relation')
            ->join('rights', 'relation.object_id', '=', 'rights.id')
            ->leftJoin('rights_i18n', function ($j) {
                $j->on('rights.id', '=', 'rights_i18n.id')->where('rights_i18n.culture', '=', $this->culture);
            })
            ->leftJoin('term_i18n', function ($j) {
                $j->on('rights.basis_id', '=', 'term_i18n.id')->where('term_i18n.culture', '=', $this->culture);
            })
            ->where('relation.subject_id', $accessionId)
            ->where('relation.type_id', self::RELATION_RIGHT)
            ->orderBy('rights.id')
            ->get(['rights.id', 'rights.start_date', 'rights.end_date', 'term_i18n.name as basis_name', 'rights_i18n.rights_note'])
            ->all();
    }
}
