<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Preservation triage: ranks records across the whole holding by how badly they
 * need conservation or digital-preservation attention.
 *
 * Reads data that already exists and writes nothing:
 *   - ahgConditionPlugin: condition_report (+ condition_damage),
 *     spectrum_condition_check, condition_assessment_schedule
 *   - this plugin: preservation_checksum, preservation_fixity_check,
 *     preservation_object_format + preservation_format +
 *     preservation_format_obsolescence, preservation_virus_scan
 *
 * The unit ranked is the information object (the record). Digital objects are
 * attributed to the record they belong to; a derivative inherits its master's
 * record. A record enters the ranking when it has condition data or at least one
 * digital object. Records with neither are counted, not scored: a description
 * with nothing attached says nothing about the state of anything.
 *
 * Score = sum of the weighted findings below. Every finding that adds points is
 * listed as a reason beside the record, so the number can always be checked by
 * hand against the weights table shown on the dashboard.
 *
 * ponytail: scoring runs in PHP over per-record aggregates fetched in a handful of
 * grouped queries. Fine to tens of thousands of records; a holding far beyond that
 * wants a nightly materialised score table instead.
 */
class PreservationTriageService
{
    /** Weights, in points. Shown on the dashboard; change here to retune. */
    public const WEIGHTS = [
        'condition_unacceptable' => 40, // latest rating unacceptable / critical
        'condition_poor' => 30,
        'condition_fair' => 15,
        'priority_urgent' => 15,        // treatment priority on the latest assessment
        'priority_high' => 10,
        'damage_severe' => 5,           // per active severe/critical damage entry
        'damage_cap' => 15,
        'assessment_overdue' => 10,     // next check date or schedule due date has passed
        'assessment_stale' => 10,       // latest assessment older than the age threshold
        'fixity_failed' => 20,          // per failed or missing-file fixity check
        'fixity_failed_cap' => 60,
        'fixity_error' => 5,            // fixity check could not complete (flat)
        'virus_infected' => 50,         // latest scan of any file found a threat
        'virus_error' => 5,             // latest scan of any file errored (flat)
        'format_critical' => 25,        // worst format risk across the record's files
        'format_high' => 15,
        'format_medium' => 5,
        'no_checksum' => 5,             // a master file has no stored checksum
        'fixity_stale' => 5,            // checksummed, but never verified or last verified beyond the threshold
    ];

    public const DEFAULT_AGE_YEARS = 5;
    public const FORECAST_MONTHS = 12;
    public const SETTING_AGE_YEARS = 'preservation_triage_age_years';

    private const RATING = [
        'excellent' => 0, 'good' => 0, 'fair' => 1, 'poor' => 2, 'unacceptable' => 3, 'critical' => 3,
    ];
    private const RISK = ['low' => 0, 'medium' => 1, 'high' => 2, 'critical' => 3];

    private int $ageYears;
    private string $today;
    private string $culture;
    private array $sources = [];

    public function __construct(?int $ageYears = null, ?string $today = null, string $culture = 'en')
    {
        $this->ageYears = max(1, min(50, $ageYears ?? self::configuredAgeYears()));
        $this->today = $today ?? date('Y-m-d');
        $this->culture = $culture;
    }

    public static function configuredAgeYears(): int
    {
        if (class_exists('\AtomExtensions\Services\AhgSettingsService')) {
            try {
                $v = (int) \AtomExtensions\Services\AhgSettingsService::get(self::SETTING_AGE_YEARS, self::DEFAULT_AGE_YEARS);

                return $v > 0 ? $v : self::DEFAULT_AGE_YEARS;
            } catch (\Throwable $e) {
            }
        }

        return self::DEFAULT_AGE_YEARS;
    }

    public function ageYears(): int
    {
        return $this->ageYears;
    }

    public function today(): string
    {
        return $this->today;
    }

    // =========================================
    // SCORING (pure: facts in, score + reasons out)
    // =========================================

    /**
     * Score one record from its facts.
     *
     * Facts keys (all optional): condition_rating, condition_priority,
     * condition_date, condition_source, next_due, severe_damage, fixity_failed,
     * fixity_errors, last_fixity, virus (infected|error|null), format_risk,
     * format_name, masters, no_checksum, checksummed.
     *
     * @return array{score:int, reasons:string[]}
     */
    public static function score(array $f, string $today, int $ageYears): array
    {
        $w = self::WEIGHTS;
        $score = 0;
        $reasons = [];
        $add = function (int $pts, string $why) use (&$score, &$reasons) {
            if ($pts > 0) {
                $score += $pts;
                $reasons[] = $why.' (+'.$pts.')';
            }
        };
        $staleBefore = date('Y-m-d', strtotime($today.' -'.$ageYears.' years'));

        $rating = strtolower((string) ($f['condition_rating'] ?? ''));
        $year = !empty($f['condition_date']) ? substr($f['condition_date'], 0, 4) : null;
        $level = self::RATING[$rating] ?? 0;
        if ($level > 0) {
            $key = [1 => 'condition_fair', 2 => 'condition_poor', 3 => 'condition_unacceptable'][$level];
            $add($w[$key], 'condition '.$rating.($year ? ', assessed '.$year : ''));
        }

        $prio = strtolower((string) ($f['condition_priority'] ?? ''));
        if ('urgent' === $prio) {
            $add($w['priority_urgent'], 'treatment priority urgent');
        } elseif ('high' === $prio) {
            $add($w['priority_high'], 'treatment priority high');
        }

        $damage = (int) ($f['severe_damage'] ?? 0);
        if ($damage > 0) {
            $add(min($w['damage_cap'], $damage * $w['damage_severe']), $damage.' active severe damage '.(1 === $damage ? 'entry' : 'entries'));
        }

        if (!empty($f['next_due']) && $f['next_due'] < $today) {
            $add($w['assessment_overdue'], 'assessment overdue since '.$f['next_due']);
        }
        if (!empty($f['condition_date']) && substr($f['condition_date'], 0, 10) < $staleBefore) {
            $add($w['assessment_stale'], 'last assessed '.$year.', over '.$ageYears.' years ago');
        }

        $failed = (int) ($f['fixity_failed'] ?? 0);
        if ($failed > 0) {
            $add(min($w['fixity_failed_cap'], $failed * $w['fixity_failed']), $failed.' failed fixity '.(1 === $failed ? 'check' : 'checks'));
        }
        if ((int) ($f['fixity_errors'] ?? 0) > 0) {
            $add($w['fixity_error'], $f['fixity_errors'].' fixity '.(1 === (int) $f['fixity_errors'] ? 'check' : 'checks').' could not complete');
        }

        if ('infected' === ($f['virus'] ?? null)) {
            $add($w['virus_infected'], 'virus scan found a threat');
        } elseif ('error' === ($f['virus'] ?? null)) {
            $add($w['virus_error'], 'virus scan did not complete');
        }

        $risk = strtolower((string) ($f['format_risk'] ?? ''));
        if (isset(['medium' => 1, 'high' => 1, 'critical' => 1][$risk])) {
            $add($w['format_'.$risk], 'format at risk ('.$risk.'): '.($f['format_name'] ?? 'unknown'));
        }

        $noSum = (int) ($f['no_checksum'] ?? 0);
        if ($noSum > 0) {
            $add($w['no_checksum'], $noSum.' of '.(int) ($f['masters'] ?? $noSum).' master '.(1 === (int) ($f['masters'] ?? $noSum) ? 'file has' : 'files have').' no checksum');
        }
        if (!empty($f['checksummed'])) {
            if (empty($f['last_fixity'])) {
                $add($w['fixity_stale'], 'fixity never verified');
            } elseif (substr($f['last_fixity'], 0, 10) < $staleBefore) {
                $add($w['fixity_stale'], 'fixity last verified '.substr($f['last_fixity'], 0, 4));
            }
        }

        return ['score' => $score, 'reasons' => $reasons];
    }

    // =========================================
    // DATA
    // =========================================

    /** Row counts per source table; null = table missing on this instance. */
    public function sources(): array
    {
        if ($this->sources) {
            return $this->sources;
        }
        $tables = [
            'condition_report' => 'Condition reports (ahgConditionPlugin)',
            'condition_damage' => 'Condition damage entries',
            'spectrum_condition_check' => 'Spectrum condition checks',
            'condition_assessment_schedule' => 'Condition assessment schedules',
            'preservation_checksum' => 'Stored checksums',
            'preservation_fixity_check' => 'Fixity checks',
            'preservation_object_format' => 'Format identifications',
            'preservation_format' => 'Format registry (risk levels)',
            'preservation_format_obsolescence' => 'Format obsolescence assessments',
            'preservation_virus_scan' => 'Virus scans',
            'preservation_event' => 'PREMIS events (shown, not scored)',
        ];
        $schema = DB::schema();
        foreach ($tables as $t => $label) {
            $this->sources[$t] = [
                'label' => $label,
                'count' => $schema->hasTable($t) ? (int) DB::table($t)->count() : null,
            ];
        }

        return $this->sources;
    }

    private function has(string $table): bool
    {
        return null !== ($this->sources()[$table]['count'] ?? null);
    }

    /**
     * Gather facts per record. Returns [ioId => facts].
     */
    public function facts(): array
    {
        $this->sources();
        $facts = [];
        $at = function ($id) use (&$facts) {
            if (!isset($facts[$id])) {
                $facts[$id] = [];
            }

            return $id;
        };

        // --- Condition reports (latest per record wins)
        if ($this->has('condition_report')) {
            $damage = [];
            if ($this->has('condition_damage')) {
                foreach (DB::table('condition_damage')->where('is_active', 1)
                    ->whereRaw("LOWER(severity) IN ('severe','critical')")
                    ->groupBy('condition_report_id')
                    ->select('condition_report_id', DB::raw('COUNT(*) as n'))->get() as $d) {
                    $damage[$d->condition_report_id] = (int) $d->n;
                }
            }
            foreach (DB::table('condition_report')->orderBy('assessment_date')->orderBy('id')->get() as $r) {
                $id = $at((int) $r->information_object_id);
                $date = substr((string) $r->assessment_date, 0, 10);
                if (empty($facts[$id]['condition_date']) || $date >= $facts[$id]['condition_date']) {
                    $facts[$id]['condition_date'] = $date;
                    $facts[$id]['condition_rating'] = $r->overall_rating;
                    $facts[$id]['condition_priority'] = $r->priority;
                    $facts[$id]['condition_source'] = 'condition report';
                    $facts[$id]['severe_damage'] = $damage[$r->id] ?? 0;
                    $facts[$id]['next_due'] = $r->next_check_date ? substr((string) $r->next_check_date, 0, 10) : null;
                }
            }
        }

        // --- Spectrum condition checks (completed ones only; a newer check supersedes)
        if ($this->has('spectrum_condition_check')) {
            $q = DB::table('spectrum_condition_check')
                ->whereNotNull('check_date')
                ->whereRaw("LOWER(COALESCE(overall_condition, condition_rating, '')) NOT IN ('', 'pending')")
                ->orderBy('check_date')->orderBy('id');
            foreach ($q->get() as $r) {
                $id = $at((int) $r->object_id);
                $date = substr((string) $r->check_date, 0, 10);
                // Same-day tie: a condition report (which carries damage entries)
                // stands; among Spectrum checks the later one wins.
                $cur = $facts[$id]['condition_date'] ?? null;
                $isReport = 'condition report' === ($facts[$id]['condition_source'] ?? null);
                if (!$cur || $date > $cur || ($date === $cur && !$isReport)) {
                    $facts[$id]['condition_date'] = $date;
                    $facts[$id]['condition_rating'] = $r->condition_rating ?: $r->overall_condition;
                    $facts[$id]['condition_priority'] = $r->treatment_priority;
                    $facts[$id]['condition_source'] = 'Spectrum condition check';
                    $facts[$id]['severe_damage'] = 0;
                    $facts[$id]['next_due'] = $r->next_check_date ? substr((string) $r->next_check_date, 0, 10) : null;
                }
            }
        }

        // --- Assessment schedules: the earliest active due date overrides a later one
        if ($this->has('condition_assessment_schedule')) {
            foreach (DB::table('condition_assessment_schedule')->where('is_active', 1)->whereNotNull('next_due_date')->get() as $s) {
                $id = $at((int) $s->object_id);
                $due = substr((string) $s->next_due_date, 0, 10);
                if (empty($facts[$id]['next_due']) || $due < $facts[$id]['next_due']) {
                    $facts[$id]['next_due'] = $due;
                }
                if ($s->last_assessment_date && empty($facts[$id]['condition_date'])) {
                    $facts[$id]['condition_date'] = substr((string) $s->last_assessment_date, 0, 10);
                }
            }
        }

        // --- Digital objects: file -> record (a derivative takes its master's record)
        $doMap = 'SELECT d.id AS do_id, d.parent_id, COALESCE(d.object_id, p.object_id) AS io_id'
            .' FROM digital_object d LEFT JOIN digital_object p ON p.id = d.parent_id';
        $dm = DB::table(DB::raw('('.$doMap.') dm'))
            ->join('information_object as io', 'io.id', '=', 'dm.io_id');

        // Master files and checksum coverage
        $masters = (clone $dm)->whereNull('dm.parent_id')
            ->groupBy('dm.io_id')
            ->select('dm.io_id', DB::raw('COUNT(*) AS masters'));
        if ($this->has('preservation_checksum')) {
            $masters->addSelect(DB::raw('SUM(NOT EXISTS (SELECT 1 FROM preservation_checksum c WHERE c.digital_object_id = dm.do_id)) AS no_checksum'));
        } else {
            $masters->addSelect(DB::raw('COUNT(*) AS no_checksum'));
        }
        foreach ($masters->get() as $r) {
            $id = $at((int) $r->io_id);
            $facts[$id]['masters'] = (int) $r->masters;
            $facts[$id]['no_checksum'] = (int) $r->no_checksum;
            $facts[$id]['checksummed'] = (int) $r->masters > (int) $r->no_checksum;
        }

        if ($this->has('preservation_fixity_check')) {
            $q = (clone $dm)->join('preservation_fixity_check as fc', 'fc.digital_object_id', '=', 'dm.do_id')
                ->groupBy('dm.io_id')
                ->select('dm.io_id',
                    DB::raw("SUM(fc.status IN ('fail','missing')) AS failed"),
                    DB::raw("SUM(fc.status = 'error') AS errors"),
                    DB::raw('MAX(fc.checked_at) AS last_check'));
            foreach ($q->get() as $r) {
                $id = $at((int) $r->io_id);
                $facts[$id]['fixity_failed'] = (int) $r->failed;
                $facts[$id]['fixity_errors'] = (int) $r->errors;
                $facts[$id]['last_fixity'] = $r->last_check ? substr((string) $r->last_check, 0, 10) : null;
            }
        }

        // Latest virus scan per file; infected beats error across a record's files
        if ($this->has('preservation_virus_scan')) {
            $q = (clone $dm)->join('preservation_virus_scan as vs', 'vs.digital_object_id', '=', 'dm.do_id')
                ->whereRaw('vs.id = (SELECT v2.id FROM preservation_virus_scan v2 WHERE v2.digital_object_id = vs.digital_object_id ORDER BY v2.scanned_at DESC, v2.id DESC LIMIT 1)')
                ->select('dm.io_id', 'vs.status');
            foreach ($q->get() as $r) {
                $id = $at((int) $r->io_id);
                if ('infected' === $r->status) {
                    $facts[$id]['virus'] = 'infected';
                } elseif ('error' === $r->status && 'infected' !== ($facts[$id]['virus'] ?? null)) {
                    $facts[$id]['virus'] = 'error';
                }
            }
        }

        // Worst format risk across a record's files: registry row by id, else by
        // PUID, raised by an obsolescence assessment of the same PUID.
        if ($this->has('preservation_object_format') && $this->has('preservation_format')) {
            $obs = $this->has('preservation_format_obsolescence');
            $q = (clone $dm)->join('preservation_object_format as pof', 'pof.digital_object_id', '=', 'dm.do_id')
                ->leftJoin('preservation_format as f', 'f.id', '=', 'pof.format_id')
                ->select('dm.io_id', 'pof.format_name', 'pof.puid', 'f.risk_level',
                    DB::raw('(SELECT MAX(CASE f2.risk_level WHEN \'critical\' THEN 3 WHEN \'high\' THEN 2 WHEN \'medium\' THEN 1 ELSE 0 END) FROM preservation_format f2 WHERE f2.puid = pof.puid) AS puid_risk'),
                    DB::raw($obs
                        ? '(SELECT MAX(CASE o.current_risk_level WHEN \'critical\' THEN 3 WHEN \'high\' THEN 2 WHEN \'medium\' THEN 1 ELSE 0 END) FROM preservation_format_obsolescence o WHERE o.puid = pof.puid) AS obs_risk'
                        : 'NULL AS obs_risk'));
            $names = array_flip(self::RISK);
            foreach ($q->get() as $r) {
                $level = null !== $r->risk_level ? (self::RISK[strtolower($r->risk_level)] ?? 0) : (int) $r->puid_risk;
                $level = max($level, (int) $r->obs_risk);
                $id = $at((int) $r->io_id);
                if ($level > 0 && $level > (self::RISK[$facts[$id]['format_risk'] ?? 'low'] ?? 0)) {
                    $facts[$id]['format_risk'] = $names[$level];
                    $facts[$id]['format_name'] = $r->format_name ?: ($r->puid ?: 'unknown');
                }
            }
        }

        unset($facts[0]);

        return $facts;
    }

    /**
     * Record metadata for the given ids: title, identifier, slug, repository,
     * top-level collection.
     */
    public function records(array $ids): array
    {
        $out = [];
        foreach (array_chunk($ids, 1000) as $chunk) {
            $rows = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as cur', function ($j) {
                    $j->on('cur.id', '=', 'io.id')->where('cur.culture', '=', $this->culture);
                })
                ->leftJoin('information_object_i18n as src', function ($j) {
                    $j->on('src.id', '=', 'io.id')->on('src.culture', '=', 'io.source_culture');
                })
                ->leftJoin('slug', 'slug.object_id', '=', 'io.id')
                ->leftJoin('information_object as top', function ($j) {
                    $j->where('top.parent_id', '=', 1)
                        ->on('top.lft', '<=', 'io.lft')->on('top.rgt', '>=', 'io.rgt');
                })
                ->whereIn('io.id', $chunk)
                ->select('io.id', 'io.identifier', 'io.repository_id', 'slug.slug',
                    DB::raw('COALESCE(cur.title, src.title) AS title'),
                    'top.id as top_id', 'top.repository_id as top_repository_id')
                ->get();
            foreach ($rows as $r) {
                $out[(int) $r->id] = $r;
            }
        }

        return $out;
    }

    /** [id => name] for the repository filter. */
    public function repositories(): array
    {
        return DB::table('repository as r')
            ->leftJoin('actor_i18n as a', function ($j) {
                $j->on('a.id', '=', 'r.id')->where('a.culture', '=', $this->culture);
            })
            ->leftJoin('actor as ac', 'ac.id', '=', 'r.id')
            ->leftJoin('actor_i18n as s', function ($j) {
                $j->on('s.id', '=', 'r.id')->on('s.culture', '=', 'ac.source_culture');
            })
            ->select('r.id', DB::raw('COALESCE(a.authorized_form_of_name, s.authorized_form_of_name) AS name'))
            ->orderBy('name')
            ->pluck('name', 'id')->map(fn ($n) => $n ?: '(untitled)')->all();
    }

    /** [id => title] for the top-level collection filter. */
    public function collections(): array
    {
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as cur', function ($j) {
                $j->on('cur.id', '=', 'io.id')->where('cur.culture', '=', $this->culture);
            })
            ->leftJoin('information_object_i18n as src', function ($j) {
                $j->on('src.id', '=', 'io.id')->on('src.culture', '=', 'io.source_culture');
            })
            ->where('io.parent_id', 1)
            ->select('io.id', DB::raw('COALESCE(cur.title, src.title, io.identifier) AS title'))
            ->orderBy('title')
            ->pluck('title', 'id')->map(fn ($t) => $t ?: '(untitled)')->all();
    }

    /**
     * The full triage: ranked rows, headline counts, forecast.
     *
     * @param array $filters repository_id, collection_id (ints, optional)
     */
    public function build(array $filters = []): array
    {
        $facts = $this->facts();
        $meta = $this->records(array_keys($facts));
        $repoNames = $this->repositories();
        $collNames = $this->collections();
        $repoFilter = (int) ($filters['repository_id'] ?? 0);
        $collFilter = (int) ($filters['collection_id'] ?? 0);

        $forecastEnd = date('Y-m-d', strtotime($this->today.' +'.self::FORECAST_MONTHS.' months'));
        $counts = array_fill_keys(['in_scope', 'needs_attention', 'condition_poor', 'overdue', 'stale_assessment',
            'fixity_failed', 'format_at_risk', 'infected', 'no_checksum', 'never_verified', 'forecast'], 0);
        $rows = [];
        $forecast = [];

        foreach ($facts as $id => $f) {
            $m = $meta[$id] ?? null;
            if (!$m) {
                continue; // fact points at an object that is not (or no longer) a record
            }
            $repoId = (int) ($m->repository_id ?: $m->top_repository_id);
            if ($repoFilter && $repoId !== $repoFilter) {
                continue;
            }
            if ($collFilter && (int) $m->top_id !== $collFilter) {
                continue;
            }

            $s = self::score($f, $this->today, $this->ageYears);
            $row = [
                'id' => $id,
                'slug' => $m->slug,
                'title' => $m->title ?: ($m->identifier ?: '#'.$id),
                'identifier' => $m->identifier,
                'repository' => $repoId ? ($repoNames[$repoId] ?? '') : '',
                'collection' => $m->top_id ? ($collNames[$m->top_id] ?? '') : '',
                'score' => $s['score'],
                'reasons' => $s['reasons'],
                'last_assessment' => $f['condition_date'] ?? null,
                'last_fixity' => $f['last_fixity'] ?? null,
            ];

            ++$counts['in_scope'];
            $level = self::RATING[strtolower((string) ($f['condition_rating'] ?? ''))] ?? 0;
            $counts['condition_poor'] += $level >= 2 ? 1 : 0;
            $counts['overdue'] += (!empty($f['next_due']) && $f['next_due'] < $this->today) ? 1 : 0;
            $counts['stale_assessment'] += (!empty($f['condition_date']) && $f['condition_date'] < $this->dueCutoff()) ? 1 : 0;
            $counts['fixity_failed'] += !empty($f['fixity_failed']) ? 1 : 0;
            $counts['format_at_risk'] += (self::RISK[$f['format_risk'] ?? 'low'] ?? 0) >= 2 ? 1 : 0;
            $counts['infected'] += 'infected' === ($f['virus'] ?? null) ? 1 : 0;
            $counts['no_checksum'] += !empty($f['no_checksum']) ? 1 : 0;
            $counts['never_verified'] += (!empty($f['checksummed']) && empty($f['last_fixity'])) ? 1 : 0;

            // Forecast: currently within the threshold, past it within FORECAST_MONTHS.
            foreach (['condition assessment' => $f['condition_date'] ?? null, 'fixity check' => $f['last_fixity'] ?? null] as $what => $date) {
                if (!$date) {
                    continue;
                }
                $due = date('Y-m-d', strtotime($date.' +'.$this->ageYears.' years'));
                if ($due >= $this->today && $due < $forecastEnd) {
                    $forecast[] = ['id' => $id, 'slug' => $m->slug, 'title' => $row['title'], 'what' => $what, 'last' => $date, 'due' => $due];
                }
            }

            if ($s['score'] > 0) {
                ++$counts['needs_attention'];
                $rows[] = $row;
            }
        }

        usort($rows, function ($a, $b) {
            return [$b['score'], $a['last_assessment'] ?? '9999', $a['id']] <=> [$a['score'], $b['last_assessment'] ?? '9999', $b['id']];
        });
        usort($forecast, fn ($a, $b) => [$a['due'], $a['id']] <=> [$b['due'], $b['id']]);
        $counts['forecast'] = count(array_unique(array_column($forecast, 'id')));

        $byMonth = [];
        for ($i = 0; $i < self::FORECAST_MONTHS; ++$i) {
            $byMonth[date('Y-m', strtotime(substr($this->today, 0, 7).'-01 +'.$i.' months'))] = 0;
        }
        foreach ($forecast as $fc) {
            if (isset($byMonth[substr($fc['due'], 0, 7)])) {
                ++$byMonth[substr($fc['due'], 0, 7)];
            }
        }

        return [
            'rows' => $rows,
            'counts' => $counts,
            'forecast' => $forecast,
            'forecastByMonth' => $byMonth,
            'sources' => $this->sources(),
            'repositories' => $repoNames,
            'collections' => $collNames,
            'ageYears' => $this->ageYears,
            'today' => $this->today,
            'weights' => self::WEIGHTS,
        ];
    }

    private function dueCutoff(): string
    {
        return date('Y-m-d', strtotime($this->today.' -'.$this->ageYears.' years'));
    }

    /** The ranked list as CSV text. */
    public static function toCsv(array $rows, callable $urlFor): string
    {
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['Rank', 'Score', 'Title', 'Identifier', 'Repository', 'Top-level collection', 'Last assessment', 'Last fixity check', 'Reasons', 'URL']);
        foreach ($rows as $i => $r) {
            fputcsv($fh, [$i + 1, $r['score'], self::csvSafe($r['title']), self::csvSafe($r['identifier']), self::csvSafe($r['repository']),
                self::csvSafe($r['collection']), $r['last_assessment'], $r['last_fixity'], self::csvSafe(implode('; ', $r['reasons'])),
                $r['slug'] ? $urlFor($r['slug']) : '']);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }

    /** Neutralise spreadsheet formula injection in free-text cells. */
    private static function csvSafe($v): string
    {
        $v = (string) $v;

        return ('' !== $v && false !== strpos('=+-@', $v[0])) ? "'".$v : $v;
    }
}
