<?php

namespace AtomFramework\Console\Commands\Museum;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class GettyLinkCommand extends BaseCommand
{
    protected string $name = 'museum:getty-link';
    protected string $description = 'Link taxonomy terms to Getty vocabularies (AAT, TGN, ULAN)';
    protected string $detailedDescription = <<<'EOF'
    Batch link AtoM taxonomy terms to Getty Vocabulary URIs via SPARQL.

    Examples:
      php bin/atom museum:getty-link --taxonomy-id=35
      php bin/atom museum:getty-link --vocabulary=tgn --taxonomy-id=42
      php bin/atom museum:getty-link --taxonomy-id=35 --dry-run
      php bin/atom museum:getty-link --taxonomy-id=35 --limit=50
    EOF;

    protected function configure(): void
    {
        $this->addOption('taxonomy-id', null, 'Taxonomy ID to link');
        $this->addOption('vocabulary', null, 'Getty vocabulary (aat, tgn, ulan)', 'aat');
        $this->addOption('limit', null, 'Limit number of terms', '100');
        $this->addOption('dry-run', null, 'Show what would be done');
    }

    protected function handle(): int
    {
        $this->bold('Getty Vocabulary Linking Task');
        $this->line(str_repeat('=', 30));

        $vocabulary = $this->option('vocabulary') ?? 'aat';
        $limit = (int) ($this->option('limit') ?? 100);
        $dryRun = $this->hasOption('dry-run');
        $taxonomyId = $this->option('taxonomy-id');

        if (!$taxonomyId || $taxonomyId === '1') {
            $this->error('Please specify --taxonomy-id');
            $this->info('Available taxonomies:');

            $taxonomies = DB::table('taxonomy')
                ->join('taxonomy_i18n', 'taxonomy.id', '=', 'taxonomy_i18n.id')
                ->where('taxonomy_i18n.culture', 'en')
                ->select('taxonomy.id', 'taxonomy_i18n.name')
                ->orderBy('taxonomy_i18n.name')
                ->get();

            foreach ($taxonomies as $tax) {
                $this->line(sprintf('  --taxonomy-id=%d  (%s)', $tax->id, $tax->name));
            }

            return 1;
        }

        // Get terms from taxonomy
        $terms = DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->leftJoin('getty_vocabulary_link', 'term.id', '=', 'getty_vocabulary_link.term_id')
            ->where('term.taxonomy_id', $taxonomyId)
            ->where('term_i18n.culture', 'en')
            ->whereNull('getty_vocabulary_link.id')
            ->select('term.id', 'term_i18n.name')
            ->limit($limit)
            ->get();

        $this->info(sprintf('Found %d unlinked terms in taxonomy %d', count($terms), $taxonomyId));

        if (count($terms) === 0) {
            $this->line('All terms are already linked or no terms found.');

            return 0;
        }

        $linked = 0;
        $failed = 0;

        foreach ($terms as $term) {
            $this->line(sprintf('Processing: %s (ID: %d)', $term->name, $term->id));

            $result = $this->searchGetty($term->name, $vocabulary);

            if ($result) {
                if ($dryRun) {
                    $this->comment(sprintf('  Would link to: %s (%s)', $result['label'], $result['uri']));
                } else {
                    DB::table('getty_vocabulary_link')->insert([
                        'term_id' => $term->id,
                        'vocabulary' => $vocabulary,
                        'getty_uri' => $result['uri'],
                        'getty_id' => $result['id'],
                        'getty_pref_label' => $result['label'],
                        'status' => 'suggested',
                        'confidence' => $result['confidence'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $this->success(sprintf('  Linked to: %s (confidence: %d%%)', $result['label'], $result['confidence'] * 100));
                    $linked++;
                }
            } else {
                $this->warning('  No match found');
                $failed++;
            }

            // Rate limiting
            usleep(500000); // 0.5 second delay
        }

        $this->line(str_repeat('=', 30));
        $this->info(sprintf('Linked: %d, No match: %d', $linked, $failed));

        return 0;
    }

    private function searchGetty(string $term, string $vocabulary): ?array
    {
        $graphs = [
            'aat' => 'http://vocab.getty.edu/aat/',
            'tgn' => 'http://vocab.getty.edu/tgn/',
            'ulan' => 'http://vocab.getty.edu/ulan/',
        ];

        $endpoint = 'http://vocab.getty.edu/sparql';
        $graph = $graphs[$vocabulary] ?? $graphs['aat'];

        $sparql = sprintf('
            SELECT ?subject ?prefLabel WHERE {
                ?subject a skos:Concept ;
                         skos:inScheme <%s> ;
                         skos:prefLabel ?prefLabel .
                FILTER(LANG(?prefLabel) = "en" || LANG(?prefLabel) = "")
                FILTER(CONTAINS(LCASE(?prefLabel), LCASE("%s")))
            }
            LIMIT 1
        ', $graph, addslashes($term));

        $url = $endpoint . '?query=' . urlencode($sparql) . '&format=json';

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Accept: application/sparql-results+json',
                'timeout' => 10,
            ],
        ]);

        try {
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                return null;
            }

            $data = json_decode($response, true);
            if (empty($data['results']['bindings'])) {
                return null;
            }

            $binding = $data['results']['bindings'][0];
            $uri = $binding['subject']['value'];
            $label = $binding['prefLabel']['value'];

            preg_match('/(\d+)$/', $uri, $matches);
            $id = $matches[1] ?? '';

            $confidence = (strtolower($term) === strtolower($label)) ? 1.0 : 0.7;

            return [
                'uri' => $uri,
                'id' => $id,
                'label' => $label,
                'confidence' => $confidence,
            ];
        } catch (\Exception $e) {
            $this->error('Getty API error: ' . $e->getMessage());

            return null;
        }
    }
}
