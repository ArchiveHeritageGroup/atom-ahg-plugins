<?php

use AtomFramework\Services\Write\StandaloneTermWriteService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Create the taxonomies and terms the archaeology module needs.
 *
 * Idempotent: a taxonomy or term that already exists is left alone, so this is
 * safe to re-run after an upgrade that adds new terms.
 *
 * Terms are created through the framework's term write service rather than by
 * hand, because that service parents them to QubitTerm::ROOT_ID and generates a
 * slug. A term with no slug row makes search:populate throw "Couldn't find term"
 * on every index run - base AtoM joins slug with an INNER JOIN.
 */
class archaeologySeedVocabulariesTask extends sfBaseTask
{
    /**
     * The controlled vocabularies, and the terms each starts with.
     *
     * Context types follow single-context recording practice. The split between
     * cuts and deposits is not cosmetic: only a cut can truncate another unit,
     * and it is what decides whether a context number is written in square or
     * round brackets on a section.
     */
    private const SEED = [
        'Archaeological Context Type' => [
            'Deposit', 'Cut', 'Fill', 'Layer', 'Surface',
            'Masonry', 'Skeleton', 'Structure', 'Interface',
        ],
        'Archaeological Phase' => [
            'Phase 1', 'Phase 2', 'Phase 3', 'Unphased',
        ],
        'Archaeological Site Type' => [
            'Rock shelter', 'Open site', 'Cave', 'Settlement', 'Burial site',
            'Rock art site', 'Industrial site', 'Midden', 'Quarry',
        ],
        'Archaeological Period' => [
            'Earlier Stone Age', 'Middle Stone Age', 'Later Stone Age',
            'Early Iron Age', 'Late Iron Age', 'Historical', 'Colonial', 'Unknown',
        ],
        'Archaeological Object Type' => [
            'Ceramic', 'Lithic', 'Bead', 'Faunal remains', 'Metal',
            'Glass', 'Shell', 'Botanical remains', 'Human remains',
        ],
        'Archaeological Material' => [
            'Clay', 'Quartz', 'Quartzite', 'Chert', 'Dolerite', 'Bone',
            'Ostrich eggshell', 'Iron', 'Copper', 'Glass', 'Stone',
        ],
    ];

    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('culture', null, sfCommandOption::PARAMETER_REQUIRED, 'The culture to seed names in', 'en'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Report what would be created without writing'),
        ]);

        $this->namespace = 'ahg';
        $this->name = 'archaeology-seed-vocabularies';
        $this->briefDescription = 'Create the archaeology taxonomies and their terms';
        $this->detailedDescription = <<<'EOF'
The [ahg:archaeology-seed-vocabularies|INFO] task creates the controlled
vocabularies the archaeology module reads: context types, phases, site types,
periods, object types and materials.

It is idempotent - existing taxonomies and terms are left untouched - so it can
be re-run after an upgrade adds new terms.

  [php symfony ahg:archaeology-seed-vocabularies|INFO]
  [php symfony ahg:archaeology-seed-vocabularies --dry-run|INFO]
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        // Wires up the Laravel Query Builder connection. Deliberately NOT
        // getApplicationConfiguration() - calling that from the CLI rebuilds and
        // can corrupt the production cache.
        new sfDatabaseManager($this->configuration);

        $culture = $options['culture'] ?? 'en';
        $dryRun = !empty($options['dry-run']);

        if (!\AhgCore\Core\AhgDb::hasOptionalTable('archaeology_context')) {
            $this->logSection('archaeology', 'The archaeology tables are not installed. Run the plugin install first.', null, 'ERROR');

            return;
        }

        $termWriter = new StandaloneTermWriteService();
        $createdTaxonomies = 0;
        $createdTerms = 0;

        foreach (self::SEED as $taxonomyName => $termNames) {
            $taxonomyId = $this->findTaxonomy($taxonomyName, $culture);

            if (null === $taxonomyId) {
                if ($dryRun) {
                    $this->logSection('archaeology', sprintf('would create taxonomy "%s" with %d terms', $taxonomyName, count($termNames)));

                    continue;
                }

                $taxonomyId = $this->createTaxonomy($taxonomyName, $culture);
                ++$createdTaxonomies;
                $this->logSection('archaeology', sprintf('created taxonomy "%s" (id %d)', $taxonomyName, $taxonomyId));
            }

            foreach ($termNames as $termName) {
                if ($this->termExists($taxonomyId, $termName, $culture)) {
                    continue;
                }

                if ($dryRun) {
                    $this->logSection('archaeology', sprintf('  would create term "%s"', $termName));

                    continue;
                }

                $termWriter->createTerm($taxonomyId, $termName, $culture);
                ++$createdTerms;
            }
        }

        if ($dryRun) {
            $this->logSection('archaeology', 'Dry run - nothing written.');

            return;
        }

        $this->logSection('archaeology', sprintf(
            'Done. %d taxonom%s and %d term%s created.',
            $createdTaxonomies,
            1 === $createdTaxonomies ? 'y' : 'ies',
            $createdTerms,
            1 === $createdTerms ? '' : 's'
        ));

        if ($createdTerms > 0) {
            $this->logSection('archaeology', 'Run propel:build-nested-set and search:populate to index the new terms.');
        }
    }

    private function findTaxonomy(string $name, string $culture): ?int
    {
        $id = DB::table('taxonomy_i18n')
            ->where('name', $name)
            ->where('culture', $culture)
            ->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Create a taxonomy, following AtoM's entity inheritance chain by hand.
     *
     * object -> taxonomy -> taxonomy_i18n, plus a slug row. The framework's write
     * services cover terms but not taxonomies, and the chain is short enough that
     * duplicating it here is better than widening a shared service for one caller.
     */
    private function createTaxonomy(string $name, string $culture): int
    {
        return DB::transaction(function () use ($name, $culture) {
            $now = date('Y-m-d H:i:s');

            $id = (int) DB::table('object')->insertGetId([
                'class_name' => 'QubitTaxonomy',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('taxonomy')->insert([
                'id' => $id,
                'parent_id' => \QubitTaxonomy::ROOT_ID,
                'source_culture' => $culture,
            ]);

            DB::table('taxonomy_i18n')->insert([
                'id' => $id,
                'culture' => $culture,
                'name' => $name,
            ]);

            DB::table('slug')->insert([
                'object_id' => $id,
                'slug' => $this->uniqueSlug($name),
            ]);

            return $id;
        });
    }

    private function termExists(int $taxonomyId, string $name, string $culture): bool
    {
        return DB::table('term as t')
            ->join('term_i18n as ti', 'ti.id', '=', 't.id')
            ->where('t.taxonomy_id', $taxonomyId)
            ->where('ti.culture', $culture)
            ->where('ti.name', $name)
            ->exists();
    }

    private function uniqueSlug(string $name): string
    {
        $base = trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9]+/', '-', strtolower($name))), '-');
        $base = '' === $base ? 'taxonomy' : $base;
        $slug = $base;
        $n = 1;

        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$n);
        }

        return $slug;
    }
}
