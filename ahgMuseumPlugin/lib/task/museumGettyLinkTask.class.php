<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Getty Batch Linking Task.
 *
 * CLI task for batch linking AtoM taxonomy terms to Getty vocabularies.
 */
class museumGettyLinkTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('taxonomy', null, sfCommandOption::PARAMETER_OPTIONAL, 'Taxonomy name to link'),
            new sfCommandOption('taxonomy-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Taxonomy ID to link'),
            new sfCommandOption('vocabulary', null, sfCommandOption::PARAMETER_OPTIONAL, 'Getty vocabulary (aat, tgn, ulan)', 'aat'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Limit number of terms', 100),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be done'),
        ]);

        $this->namespace = 'museum';
        $this->name = 'getty-link';
        $this->briefDescription = 'Link taxonomy terms to Getty vocabularies (AAT, TGN, ULAN)';
        $this->detailedDescription = <<<EOF
The [museum:getty-link|INFO] task links AtoM taxonomy terms to Getty Vocabulary URIs.

Examples:
  [php symfony museum:getty-link --taxonomy-id=35|INFO]
    Link terms from taxonomy ID 35 to AAT

  [php symfony museum:getty-link --vocabulary=tgn --taxonomy-id=42|INFO]
    Link place terms to TGN
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        
        $this->logSection('getty', 'Getty Vocabulary Linking Task');
        $this->logSection('getty', '============================');

        $vocabulary = $options['vocabulary'] ?? 'aat';
        $limit = (int)($options['limit'] ?? 100);
        $dryRun = $options['dry-run'] ?? false;
        $taxonomyId = $options['taxonomy-id'] ?? null;

        if (!$taxonomyId) {
            $this->logSection('error', 'Please specify --taxonomy-id');
            $this->logSection('info', 'Available taxonomies:');
            
            $taxonomies = DB::table('taxonomy')
                ->join('taxonomy_i18n', 'taxonomy.id', '=', 'taxonomy_i18n.id')
                ->where('taxonomy_i18n.culture', 'en')
                ->select('taxonomy.id', 'taxonomy_i18n.name')
                ->orderBy('taxonomy_i18n.name')
                ->get();

            foreach ($taxonomies as $tax) {
                $this->logSection('info', sprintf('  --taxonomy-id=%d  (%s)', $tax->id, $tax->name));
            }
            return;
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

        $this->logSection('getty', sprintf('Found %d unlinked terms in taxonomy %d', count($terms), $taxonomyId));

        if (count($terms) === 0) {
            $this->logSection('info', 'All terms are already linked or no terms found.');
            return;
        }

        $linked = 0;
        $failed = 0;

        foreach ($terms as $term) {
            $this->logSection('getty', sprintf('Processing: %s (ID: %d)', $term->name, $term->id));

            // Search Getty SPARQL endpoint
            $result = $this->searchGetty($term->name, $vocabulary);

            if ($result) {
                if ($dryRun) {
                    $this->logSection('dry-run', sprintf(
                        '  Would link to: %s (%s)',
                        $result['label'],
                        $result['uri']
                    ));
                } else {
                    // Insert link
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
                    $this->logSection('getty', sprintf(
                        '  Linked to: %s (confidence: %d%%)',
                        $result['label'],
                        $result['confidence'] * 100
                    ));
                    $linked++;
                }
            } else {
                $this->logSection('warning', '  No match found');
                $failed++;
            }

            // Rate limiting
            usleep(500000); // 0.5 second delay
        }

        $this->logSection('getty', '============================');
        $this->logSection('getty', sprintf('Linked: %d, No match: %d', $linked, $failed));
    }

    /**
     * Search Getty vocabulary via SPARQL.
     */
    private function searchGetty(string $term, string $vocabulary): ?array
    {
        $endpoints = [
            'aat' => 'http://vocab.getty.edu/sparql',
            'tgn' => 'http://vocab.getty.edu/sparql',
            'ulan' => 'http://vocab.getty.edu/sparql',
        ];

        $graphs = [
            'aat' => 'http://vocab.getty.edu/aat/',
            'tgn' => 'http://vocab.getty.edu/tgn/',
            'ulan' => 'http://vocab.getty.edu/ulan/',
        ];

        $endpoint = $endpoints[$vocabulary] ?? $endpoints['aat'];
        $graph = $graphs[$vocabulary] ?? $graphs['aat'];

        // SPARQL query to find matching term
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

            // Extract ID from URI
            preg_match('/(\d+)$/', $uri, $matches);
            $id = $matches[1] ?? '';

            // Calculate confidence based on exact match
            $confidence = (strtolower($term) === strtolower($label)) ? 1.0 : 0.7;

            return [
                'uri' => $uri,
                'id' => $id,
                'label' => $label,
                'confidence' => $confidence,
            ];
        } catch (Exception $e) {
            $this->logSection('error', 'Getty API error: ' . $e->getMessage());
            return null;
        }
    }
}
