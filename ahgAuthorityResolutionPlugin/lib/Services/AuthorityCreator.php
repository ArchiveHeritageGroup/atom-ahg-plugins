<?php

/**
 * AuthorityCreator - AtoM Heratio
 *
 * Task 6 of the AHG Authority Resolution Engine. Creates new authority
 * records (actors for PERSON / ORG; terms for PLACE) via the Qubit class-
 * table-inheritance pattern, mirroring the Laravel-side AuthorityCreator
 * but using Capsule against the AtoM `archive` database.
 *
 * Insert sequence:
 *   - PERSON / ORG: object -> actor -> actor_i18n -> slug
 *   - PLACE       : object -> term  -> term_i18n  -> slug
 *
 * ISAAR-CPF mandatory fields enforced for persons / orgs:
 *   - authorized_form_of_name (non-empty)
 *   - dates_of_existence      (non-empty)
 *   - history                 (non-empty)
 *
 * descriptive_standard defaults to 'ISAAR-CPF' (lands on actor.source_standard).
 *
 * For places: name is mandatory. If one coordinate is supplied, the other
 * must be too. The places taxonomy id is 42 (Qubit). parent_id defaults to
 * the Qubit places-taxonomy root (term 110 == QubitTerm::ROOT_ID).
 *
 * All inserts wrap in a single Capsule transaction so a partial failure
 * leaves no orphaned object / slug rows behind.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * Licensed under the GNU General Public License v3.0 or later.
 */

namespace AtomFramework\Services\AuthorityResolution;

use Illuminate\Database\Capsule\Manager as DB;

class AuthorityCreator
{
    // Qubit term ids for actor.entity_type_id.
    private const CORPORATE_BODY_ID = 131;
    private const PERSON_ID = 132;

    // Qubit places taxonomy + root term id.
    private const TAXONOMY_PLACE_ID = 42;
    private const TERM_ROOT_ID = 110; // QubitTerm::ROOT_ID

    // Qubit actor root id (used as parent_id default for persons / orgs).
    private const ACTOR_ROOT_ID = 3;  // QubitActor::ROOT_ID

    /**
     * Insert a PERSON actor row tree. Returns new actor.id.
     *
     * @param array $form keys: authorized_form_of_name, dates_of_existence,
     *                    history, descriptive_standard?, source_culture?,
     *                    parent_id?
     */
    public function createPerson(array $form, int $userId, string $culture = 'en'): int
    {
        $this->assertIsaarCpf($form, false);
        $culture = (string) ($form['source_culture'] ?? $culture);

        return DB::connection()->transaction(function () use ($form, $culture) {
            return $this->insertActor($form, self::PERSON_ID, $culture);
        });
    }

    /**
     * Insert an ORG / corporate body actor row tree. Returns new actor.id.
     */
    public function createOrg(array $form, int $userId, string $culture = 'en'): int
    {
        $this->assertIsaarCpf($form, true);
        $culture = (string) ($form['source_culture'] ?? $culture);

        return DB::connection()->transaction(function () use ($form, $culture) {
            return $this->insertActor($form, self::CORPORATE_BODY_ID, $culture);
        });
    }

    /**
     * Insert a PLACE term row tree under the places taxonomy. Returns new term.id.
     *
     * @param array $form keys: name (required), latitude?, longitude?, parent_id?
     */
    public function createPlace(array $form, int $userId, string $culture = 'en'): int
    {
        $name = trim((string) ($form['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Place name is required.');
        }
        $lat = $form['latitude'] ?? null;
        $lng = $form['longitude'] ?? null;
        $latFilled = $lat !== null && $lat !== '';
        $lngFilled = $lng !== null && $lng !== '';
        if (($latFilled && !$lngFilled) || (!$latFilled && $lngFilled)) {
            throw new \InvalidArgumentException(
                'Latitude and longitude must be provided together (or both omitted).'
            );
        }
        $parentId = isset($form['parent_id']) && (int) $form['parent_id'] > 0
            ? (int) $form['parent_id']
            : self::TERM_ROOT_ID;
        $culture = (string) ($form['source_culture'] ?? $culture);

        return DB::connection()->transaction(function () use ($name, $culture, $parentId, $latFilled, $lat, $lng) {
            $now = date('Y-m-d H:i:s');

            $termId = (int) DB::table('object')->insertGetId([
                'class_name' => 'QubitTerm',
                'created_at' => $now,
                'updated_at' => $now,
                'serial_number' => 0,
            ]);

            DB::table('term')->insert([
                'id' => $termId,
                'taxonomy_id' => self::TAXONOMY_PLACE_ID,
                'parent_id' => $parentId,
                'source_culture' => $culture,
            ]);

            // term_i18n: name is the visible label. Coordinates, when present,
            // are stored as a "lat,lng" trailing line on the description column
            // (matches the parsing the review-screen resolvePlaceCoord() uses).
            $description = null;
            if ($latFilled) {
                $description = sprintf('%s,%s', (string) $lat, (string) $lng);
            }
            DB::table('term_i18n')->insert([
                'id' => $termId,
                'culture' => $culture,
                'name' => $name,
                'description' => $description,
            ]);

            DB::table('slug')->insert([
                'object_id' => $termId,
                'slug' => $this->generateUniqueSlug($name),
                'serial_number' => 0,
            ]);

            return $termId;
        });
    }

    // -------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------

    private function insertActor(array $form, int $entityTypeId, string $culture): int
    {
        $now = date('Y-m-d H:i:s');

        $actorId = (int) DB::table('object')->insertGetId([
            'class_name' => 'QubitActor',
            'created_at' => $now,
            'updated_at' => $now,
            'serial_number' => 0,
        ]);

        $parentId = isset($form['parent_id']) && (int) $form['parent_id'] > 0
            ? (int) $form['parent_id']
            : self::ACTOR_ROOT_ID;

        $descriptiveStandard = (string) ($form['descriptive_standard'] ?? 'ISAAR-CPF');

        DB::table('actor')->insert([
            'id' => $actorId,
            'entity_type_id' => $entityTypeId,
            'parent_id' => $parentId,
            'source_culture' => $culture,
            'source_standard' => $descriptiveStandard,
        ]);

        $name = (string) $form['authorized_form_of_name'];
        $dates = isset($form['dates_of_existence']) ? (string) $form['dates_of_existence'] : null;
        $history = isset($form['history']) ? (string) $form['history'] : null;
        $places = isset($form['places']) ? (string) $form['places'] : null;
        $mandates = isset($form['mandates']) ? (string) $form['mandates'] : null;
        $functions = isset($form['functions']) ? (string) $form['functions'] : null;
        $legalStatus = isset($form['legal_status']) ? (string) $form['legal_status'] : null;

        DB::table('actor_i18n')->insert([
            'id' => $actorId,
            'culture' => $culture,
            'authorized_form_of_name' => $name,
            'dates_of_existence' => $dates,
            'history' => $history,
            'places' => $places,
            'mandates' => $mandates,
            'functions' => $functions,
            'legal_status' => $legalStatus,
        ]);

        DB::table('slug')->insert([
            'object_id' => $actorId,
            'slug' => $this->generateUniqueSlug($name),
            'serial_number' => 0,
        ]);

        return $actorId;
    }

    private function assertIsaarCpf(array $form, bool $isOrg): void
    {
        $missing = [];
        foreach (['authorized_form_of_name', 'dates_of_existence', 'history'] as $key) {
            if (!isset($form[$key]) || trim((string) $form[$key]) === '') {
                $missing[] = $key;
            }
        }
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Missing ISAAR-CPF mandatory field(s) for '
                . ($isOrg ? 'organisation' : 'person')
                . ': ' . implode(', ', $missing)
            );
        }
    }

    /**
     * Hand-rolled slugifier - matches QubitSlug::slugify() output without
     * needing the SF1.4 model class. Length cap at 250 chars (slug column
     * is varchar(255), leaving headroom for numeric suffix).
     */
    private function generateUniqueSlug(string $name): string
    {
        $base = $this->slugify($name);
        if ($base === '') {
            $base = 'untitled';
        }
        $base = substr($base, 0, 240);

        $slug = $base;
        $counter = 1;
        while ($this->slugExists($slug)) {
            $slug = $base . '-' . $counter;
            $counter++;
            if ($counter > 1000) {
                $slug = $base . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
                break;
            }
        }
        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        try {
            return DB::table('slug')->where('slug', $slug)->exists();
        } catch (\Throwable $e) {
            // If the slug check ever errors, fall back to "not present" - the
            // unique constraint on slug.slug catches a collision at insert time.
            return false;
        }
    }

    private function slugify(string $s): string
    {
        // Transliterate to ASCII, lowercase, replace non-alnum with -.
        $s = trim($s);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            if (is_string($converted) && $converted !== '') {
                $s = $converted;
            }
        }
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/i', '-', $s);
        $s = preg_replace('/-+/', '-', (string) $s);
        return trim((string) $s, '-');
    }
}
