<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to validate a taxonomy against core SKOS integrity rules.
 *
 * Read-only. Checks:
 *   S1  every concept has a non-empty skos:prefLabel
 *   S2  no duplicate prefLabel within the taxonomy (per culture)
 *   S3  no skos:broader cycles in the term hierarchy
 *
 * Usage:
 *   php symfony skos:validate --taxonomy-id=42
 *   php symfony skos:validate --taxonomy-id=42 --culture=fr --format=json
 */
class skosValidateTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('taxonomy-id', null, sfCommandOption::PARAMETER_REQUIRED, 'Taxonomy id to validate'),
            new sfCommandOption('culture', null, sfCommandOption::PARAMETER_OPTIONAL, 'Culture/language code', 'en'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_OPTIONAL, 'Output format (table, json)', 'table'),
        ]);

        $this->namespace = 'skos';
        $this->name = 'validate';
        $this->briefDescription = 'Validate a taxonomy against core SKOS integrity rules';
        $this->detailedDescription = <<<'EOF'
Validate a taxonomy's terms against core SKOS rules (prefLabel presence,
duplicate prefLabels, broader cycles). Read-only — reports violations only.

  php symfony skos:validate --taxonomy-id=42
  php symfony skos:validate --taxonomy-id=42 --format=json
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        $taxonomyId = (int) ($options['taxonomy-id'] ?? 0);
        if ($taxonomyId < 1) {
            throw new sfCommandException('--taxonomy-id is required and must be a positive integer.');
        }
        $culture = $options['culture'] ?: 'en';
        $rootId = \QubitTerm::ROOT_ID;

        $terms = DB::table('term')
            ->leftJoin('term_i18n', function ($j) use ($culture) {
                $j->on('term.id', '=', 'term_i18n.id')
                    ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', $taxonomyId)
            ->where('term.id', '!=', $rootId)
            ->select('term.id', 'term.parent_id', 'term_i18n.name')
            ->get()
            ->all();

        $violations = [];

        // S1: prefLabel presence
        $byName = [];
        $parentMap = [];
        foreach ($terms as $t) {
            $parentMap[(int) $t->id] = (int) $t->parent_id;
            $name = trim((string) ($t->name ?? ''));
            if ($name === '') {
                $violations[] = ['rule' => 'S1', 'term' => (int) $t->id, 'message' => 'Concept has no prefLabel for culture ' . $culture];
            } else {
                $byName[mb_strtolower($name)][] = (int) $t->id;
            }
        }

        // S2: duplicate prefLabel
        foreach ($byName as $name => $ids) {
            if (count($ids) > 1) {
                $violations[] = ['rule' => 'S2', 'term' => implode(',', $ids), 'message' => 'Duplicate prefLabel "' . $name . '" on ' . count($ids) . ' concepts'];
            }
        }

        // S3: broader cycles
        foreach (array_keys($parentMap) as $start) {
            $seen = [];
            $cur = $start;
            while (isset($parentMap[$cur]) && $parentMap[$cur] !== $rootId && $parentMap[$cur] !== 0) {
                $cur = $parentMap[$cur];
                if (isset($seen[$cur])) {
                    $violations[] = ['rule' => 'S3', 'term' => $start, 'message' => 'skos:broader cycle detected via concept ' . $cur];
                    break;
                }
                $seen[$cur] = true;
            }
        }

        $summary = [
            'taxonomyId' => $taxonomyId,
            'culture' => $culture,
            'concepts' => count($terms),
            'violations' => count($violations),
            'details' => $violations,
        ];

        if (($options['format'] ?? 'table') === 'json') {
            echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

            return;
        }

        $this->logSection('skos', sprintf('Taxonomy %d (%s): %d concepts, %d violation(s)', $taxonomyId, $culture, count($terms), count($violations)));
        foreach ($violations as $v) {
            $this->logSection($v['rule'], '[term ' . $v['term'] . '] ' . $v['message'], null, 'ERROR');
        }
        if (empty($violations)) {
            $this->logSection('skos', 'No SKOS violations found.');
        }
    }
}
