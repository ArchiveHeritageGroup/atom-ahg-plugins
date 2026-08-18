<?php

/**
 * Import site coordinates from the original RMXML dump.
 *
 * RARI's pre-AtoM system held a latitude and a longitude on its site records.
 * Neither reached AtoM: the migration carried the map sheet reference across but
 * not the coordinates, so 3,062 measured positions have been sitting unused in
 * fulldump-orig.xml while the catalogue shows nothing.
 *
 * The fields are `site_coordinates_latitude` and `site_coordinates_longtitude`
 * - the misspelling is the source system's and is matched literally, because a
 * corrected spelling silently matches nothing.
 *
 * WHY THIS VALIDATES RATHER THAN TRUSTS
 *
 * The same record usually carries a 1:50,000 map sheet reference, which encodes
 * its own position. That makes the sheet an independent witness to the
 * coordinate, so every pair can be checked without trusting the parse:
 *
 *   confirmed     the coordinate falls inside its own sheet cell
 *   transposed    latitude and longitude are swapped; correctable
 *   sign          an eastern longitude carries a west sign; correctable
 *   near-miss     outside the cell by under 0.3 degrees; a person decides
 *   conflict      the two disagree outright; a person decides
 *   unverified    no sheet recorded, so nothing contradicts it either
 *
 * Only confirmed, corrected and unverified rows are written, and only with
 * --apply. Near-miss and conflict are always held back and listed.
 *
 * THREE WAYS THIS DATA IS SILENTLY WRONG IF PARSED NAIVELY
 *
 *   - Decimal commas. 25 12' 13,64'' is European notation. Split on the comma
 *     and the fractional seconds vanish, moving the point about 20 metres.
 *   - Northern hemisphere. Libya and Ethiopia sites are north of the equator and
 *     mostly carry no N/S letter. Defaulting to south puts them in the Kalahari,
 *     about 5,500 km out, which is why the country field decides the sign.
 *   - Absent hemisphere letters generally: 721 of the values have none.
 *
 * DISCLOSURE
 *
 * Every row is written with locality_sensitive left as it is, which is 1 on all
 * of RARI's site records. The coordinates therefore arrive gated by
 * LocalityVisibilityService. That is a real change in posture even so: before
 * this task RARI holds no coordinates at all, and afterwards the gate is the
 * only thing between an exact site position and a public request.
 *
 * Dry run unless --apply is given.
 */
class siteRecordImportCoordinatesTask extends sfBaseTask
{
    /** Countries in this dataset that sit north of the equator. */
    protected static $northern = [
        'Libya', 'Ethiopia', 'Egypt', 'Algeria', 'Chad', 'Niger', 'Sudan',
        'Morocco', 'Tunisia', 'Kenya', 'Uganda', 'Somalia', 'Mali',
    ];

    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_OPTIONAL, 'The environment', 'cli'),
            new sfCommandOption('file', null, sfCommandOption::PARAMETER_REQUIRED, 'Path to the RMXML dump'),
            new sfCommandOption('apply', null, sfCommandOption::PARAMETER_NONE, 'Write the coordinates. Without this the task only reports.'),
            new sfCommandOption('csv', null, sfCommandOption::PARAMETER_OPTIONAL, 'Write the full per-site report to this CSV path'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Process at most this many sites', null),
            new sfCommandOption('include-held', null, sfCommandOption::PARAMETER_NONE, 'Also write near-miss and conflict rows. Not recommended.'),
        ]);

        $this->namespace = 'siterecord';
        $this->name = 'import-coordinates';
        $this->briefDescription = 'Import site coordinates from the original RMXML dump, validated against map sheets';
        $this->detailedDescription = <<<'EOF'
Reads site_coordinates_latitude / site_coordinates_longtitude from the RMXML
dump, matches each site to its authority record by name, validates the parsed
position against the record's own map sheet reference, and updates
ahg_site_record.

Reports without writing unless --apply is given:

  php symfony siterecord:import-coordinates --file=/path/fulldump-orig.xml
  php symfony siterecord:import-coordinates --file=... --csv=/tmp/report.csv
  php symfony siterecord:import-coordinates --file=... --apply
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $file = $options['file'] ?? null;
        if (!$file || !is_readable($file)) {
            throw new sfException('--file must point at a readable RMXML dump');
        }

        $apply = !empty($options['apply']);
        $includeHeld = !empty($options['include-held']);
        $limit = $options['limit'] ? (int) $options['limit'] : null;

        $db = \Illuminate\Database\Capsule\Manager::connection();

        $this->logSection('siterecord', 'reading ' . $file);
        $sites = $this->readSites($file, $limit);
        $this->logSection('siterecord', count($sites) . ' sites carry both coordinates');

        // Index authority records by normalised name. Ambiguous names are kept as
        // such rather than resolved by picking one - on RARI a name matching more
        // than one actor is the fingerprint of the duplicate-import problem, and
        // guessing here would attach a coordinate to a record due to be merged away.
        $byName = [];
        $rows = $db->table('actor as a')
            ->join('actor_i18n as ai', function ($j) {
                $j->on('ai.id', '=', 'a.id')->where('ai.culture', '=', 'en');
            })
            ->whereNotNull('ai.authorized_form_of_name')
            ->where('ai.authorized_form_of_name', '!=', '')
            ->select('a.id', 'ai.authorized_form_of_name as nm')
            ->get();
        foreach ($rows as $r) {
            $byName[$this->normalise($r->nm)][] = (int) $r->id;
        }
        $this->logSection('siterecord', count($byName) . ' distinct authority-record names');

        $report = [];
        $tally = ['confirmed' => 0, 'transposed' => 0, 'sign' => 0, 'near-miss' => 0,
            'conflict' => 0, 'unverified' => 0, 'unparsed' => 0, 'no-match' => 0,
            'ambiguous' => 0, 'no-site-record' => 0];

        foreach ($sites as $s) {
            $entry = ['site_id' => $s['site_id'], 'site_name' => $s['site_name'],
                'sheet' => $s['sheet'], 'country' => $s['country'],
                'raw_lat' => $s['lat'], 'raw_lon' => $s['lon'],
                'actor_id' => '', 'latitude' => '', 'longitude' => '',
                'verdict' => '', 'action' => 'skip'];

            $pos = $this->toDecimal($s['lat'], $s['lon'], $s['country']);
            if (null === $pos) {
                $entry['verdict'] = 'unparsed';
                ++$tally['unparsed'];
                $report[] = $entry;

                continue;
            }
            [$lat, $lon] = $pos;

            $key = $this->normalise($s['site_name']);
            $ids = $byName[$key] ?? [];
            if (!$ids) {
                $entry['verdict'] = 'no-match';
                ++$tally['no-match'];
                $report[] = $entry;

                continue;
            }
            if (count($ids) > 1) {
                $entry['verdict'] = 'ambiguous';
                $entry['actor_id'] = implode('|', $ids);
                ++$tally['ambiguous'];
                $report[] = $entry;

                continue;
            }
            $actorId = $ids[0];
            $entry['actor_id'] = $actorId;

            [$verdict, $lat, $lon] = $this->validate($lat, $lon, $s['sheet']);
            $entry['verdict'] = $verdict;
            $entry['latitude'] = $lat;
            $entry['longitude'] = $lon;
            ++$tally[$verdict];

            $writable = in_array($verdict, ['confirmed', 'transposed', 'sign', 'unverified'], true)
                || ($includeHeld && in_array($verdict, ['near-miss', 'conflict'], true));

            if (!$writable) {
                $entry['action'] = 'held';
                $report[] = $entry;

                continue;
            }

            $exists = $db->table('ahg_site_record')->where('actor_id', $actorId)->first();
            if (!$exists) {
                $entry['verdict'] = 'no-site-record';
                $entry['action'] = 'held';
                ++$tally['no-site-record'];
                $report[] = $entry;

                continue;
            }

            $entry['action'] = $apply ? 'updated' : 'would update';

            if ($apply) {
                // locality_sensitive is deliberately not touched: whatever the record
                // already says about its own sensitivity governs, and the default is 1.
                $db->table('ahg_site_record')
                    ->where('actor_id', $actorId)
                    ->update([
                        'latitude' => $lat,
                        'longitude' => $lon,
                        'coordinate_datum' => 'WGS84',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            $report[] = $entry;
        }

        $this->printSummary($tally, $report, $apply, $includeHeld);

        if (!empty($options['csv'])) {
            $this->writeCsv($options['csv'], $report);
            $this->logSection('siterecord', 'report written to ' . $options['csv']);
        }
    }

    /** Pull top-level <site> elements carrying both coordinate fields. */
    protected function readSites(string $file, ?int $limit): array
    {
        $out = [];
        $r = new XMLReader();
        $r->open($file);
        while ($r->read()) {
            if (XMLReader::ELEMENT !== $r->nodeType || 'site' !== $r->name) {
                continue;
            }
            $xml = $r->readOuterXml();
            if (false === strpos($xml, '<site_coordinates_latitude>')) {
                continue;
            }
            $el = @simplexml_load_string($xml);
            if (!$el) {
                continue;
            }
            $lat = trim((string) $el->site_coordinates_latitude);
            $lon = trim((string) $el->site_coordinates_longtitude);
            if ('' === $lat || '' === $lon) {
                continue;
            }
            $sheet = '';
            if (isset($el->map_sheet->_)) {
                $sheet = trim((string) $el->map_sheet->_[0]);
            }
            $out[] = [
                'site_id' => trim((string) $el->site_id),
                'site_name' => trim((string) $el->site_name),
                'lat' => $lat, 'lon' => $lon, 'sheet' => $sheet,
                'country' => trim((string) $el->country),
            ];
            if ($limit && count($out) >= $limit) {
                break;
            }
        }
        $r->close();

        return $out;
    }

    /** Degrees/minutes/seconds in any of the dump's notations to signed decimal. */
    protected function toDecimal(string $lat, string $lon, string $country): ?array
    {
        $north = in_array($country, self::$northern, true);
        $a = $this->oneValue($lat);
        $b = $this->oneValue($lon);
        if (null === $a || null === $b) {
            return null;
        }
        [$va, $ha] = $a;
        [$vb, $hb] = $b;

        // An explicit letter always wins; the country only decides when there is none.
        if ('N' === $ha) {
            $signedLat = $va;
        } elseif ('S' === $ha) {
            $signedLat = -$va;
        } else {
            $signedLat = $north ? $va : -$va;
        }
        $signedLon = ('W' === $hb) ? -$vb : $vb;

        if ($va > 90 || $vb > 180) {
            return null;
        }

        return [round($signedLat, 7), round($signedLon, 7)];
    }

    protected function oneValue(string $s): ?array
    {
        $t = str_replace(["\xc2\xb0", "\xb0", '&deg;'], ' ', $s);
        $t = str_replace(['’', '′', '´'], "'", $t);
        $t = str_replace(['”', '″', "''"], '"', $t);
        // Decimal comma before anything splits on digits, or 13,64 becomes 13 and 64.
        $t = preg_replace('/(\d),(\d)/', '$1.$2', $t);

        $hemi = null;
        if (preg_match('/[NSEWnsew]/', $t, $m)) {
            $hemi = strtoupper($m[0]);
        }
        $t = preg_replace('/[NSEWnsew]/', ' ', $t);

        if (!preg_match_all('/\d+(?:\.\d+)?/', $t, $mm) || !$mm[0]) {
            return null;
        }
        $n = array_map('floatval', $mm[0]);
        $v = $n[0]
            + (isset($n[1]) ? $n[1] / 60 : 0)
            + (isset($n[2]) ? $n[2] / 3600 : 0);

        return [$v, $hemi];
    }

    /** Bounding box of a 1:50,000 sheet reference, or null if unreadable. */
    protected function sheetBox(string $sheet): ?array
    {
        if (!preg_match('/^(\d{2})(\d{2})([A-D])([A-D])$/i', trim($sheet), $m)) {
            return null;
        }
        $south = (float) $m[1];
        $west = (float) $m[2];
        $size = 1.0;
        foreach ([strtoupper($m[3]), strtoupper($m[4])] as $q) {
            $size /= 2;
            if ('C' === $q || 'D' === $q) {
                $south += $size;
            }
            if ('B' === $q || 'D' === $q) {
                $west += $size;
            }
        }

        return [$south, $south + $size, $west, $west + $size];
    }

    protected function inBox(?array $box, float $lat, float $lon, float $tol = 0.02): bool
    {
        if (!$box) {
            return false;
        }
        [$n, $s, $w, $e] = $box;

        return abs($lat) >= $n - $tol && abs($lat) <= $s + $tol
            && $lon >= $w - $tol && $lon <= $e + $tol;
    }

    /** Cross-check the parsed position against the record's own sheet. */
    protected function validate(float $lat, float $lon, string $sheet): array
    {
        $box = $this->sheetBox($sheet);
        if (!$box) {
            return ['unverified', $lat, $lon];
        }
        if ($this->inBox($box, $lat, $lon)) {
            return ['confirmed', $lat, $lon];
        }
        // Swapped: the sheet says the values belong the other way round.
        if ($this->inBox($box, -abs($lon), abs($lat))) {
            return ['transposed', -abs($lon), abs($lat)];
        }
        // A west sign on what the sheet says is an eastern longitude.
        if ($lon < 0 && $this->inBox($box, $lat, abs($lon))) {
            return ['sign', $lat, abs($lon)];
        }
        if ($this->inBox($box, $lat, $lon, 0.30)) {
            return ['near-miss', $lat, $lon];
        }

        return ['conflict', $lat, $lon];
    }

    protected function normalise(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9 ]/', ' ', $s);

        return trim(preg_replace('/\s+/', ' ', $s));
    }

    protected function printSummary(array $tally, array $report, bool $apply, bool $includeHeld): void
    {
        $w = function ($k, $v) { $this->log(sprintf('    %-16s %6d', $k, $v)); };

        $this->log('');
        $this->log('  Validation against the record\'s own map sheet');
        foreach (['confirmed', 'transposed', 'sign', 'unverified'] as $k) {
            $w($k, $tally[$k]);
        }
        $this->log('');
        $this->log('  Held back for a person to decide');
        foreach (['near-miss', 'conflict', 'ambiguous', 'no-match', 'unparsed', 'no-site-record'] as $k) {
            $w($k, $tally[$k]);
        }

        $written = 0;
        foreach ($report as $r) {
            if ('updated' === $r['action'] || 'would update' === $r['action']) {
                ++$written;
            }
        }
        $this->log('');
        $this->log(sprintf('  %s %d site records', $apply ? 'UPDATED' : 'Would update', $written));
        if (!$apply) {
            $this->log('  Dry run - nothing was written. Add --apply to write.');
        }
        if ($includeHeld) {
            $this->log('  --include-held was given: near-miss and conflict rows were NOT held back.');
        }

        $held = array_values(array_filter($report, function ($r) {
            return in_array($r['verdict'], ['conflict', 'ambiguous'], true);
        }));
        if ($held) {
            $this->log('');
            $this->log(sprintf('  First %d of %d needing a decision:', min(10, count($held)), count($held)));
            foreach (array_slice($held, 0, 10) as $r) {
                $this->log(sprintf('    %-10s %-11s %-34s sheet %-8s %s, %s',
                    $r['verdict'], $r['site_id'], substr($r['site_name'], 0, 34),
                    $r['sheet'], $r['latitude'], $r['longitude']));
            }
        }
    }

    protected function writeCsv(string $path, array $report): void
    {
        $fh = fopen($path, 'w');
        fputcsv($fh, ['site_id', 'site_name', 'actor_id', 'country', 'map_sheet',
            'raw_latitude', 'raw_longitude', 'latitude', 'longitude', 'verdict', 'action']);
        foreach ($report as $r) {
            fputcsv($fh, [$r['site_id'], $r['site_name'], $r['actor_id'], $r['country'],
                $r['sheet'], $r['raw_lat'], $r['raw_lon'], $r['latitude'],
                $r['longitude'], $r['verdict'], $r['action']]);
        }
        fclose($fh);
    }
}
