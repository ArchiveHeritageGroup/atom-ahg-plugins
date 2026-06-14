<?php

namespace AhgTermTaxonomy\Services;

use AtomFramework\Services\Write\WriteServiceFactory;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * SKOS Import Service.
 *
 * Parses a SKOS RDF/XML document and creates terms in a target taxonomy.
 * Round-trips with SkosExportService output. Concepts are created
 * parent-before-child (topological order on skos:broader) so each term is
 * created with its correct parentId — no nested-set re-parenting.
 *
 * parse() performs NO database writes (safe for --dry-run verification).
 */
class SkosImportService
{
    public const RDF_NS = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    public const SKOS_NS = 'http://www.w3.org/2004/02/skos/core#';

    protected string $culture;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    /**
     * Parse SKOS RDF/XML into a list of concepts. No DB access.
     *
     * @return array<int, array{uri:string,prefLabel:string,altLabels:array,scopeNote:?string,broader:array}>
     */
    public function parse(string $xml): array
    {
        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $doc->loadXML($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (!$ok) {
            $first = $errors[0]->message ?? 'unknown error';
            throw new \RuntimeException('Invalid RDF/XML: ' . trim($first));
        }

        $xp = new \DOMXPath($doc);
        $xp->registerNamespace('rdf', self::RDF_NS);
        $xp->registerNamespace('skos', self::SKOS_NS);

        $concepts = [];
        foreach ($xp->query('//skos:Concept') as $node) {
            $uri = $node->getAttributeNS(self::RDF_NS, 'about');
            $prefLabel = $this->firstText($xp, 'skos:prefLabel', $node);
            if ($uri === '' || $prefLabel === '') {
                continue; // a concept needs an identifier and a prefLabel
            }

            $altLabels = [];
            foreach ($xp->query('skos:altLabel', $node) as $alt) {
                $val = trim($alt->textContent);
                if ($val !== '') {
                    $altLabels[] = $val;
                }
            }

            $broader = [];
            foreach ($xp->query('skos:broader', $node) as $b) {
                $res = $b->getAttributeNS(self::RDF_NS, 'resource');
                if ($res !== '') {
                    $broader[] = $res;
                }
            }

            $concepts[] = [
                'uri' => $uri,
                'prefLabel' => $prefLabel,
                'altLabels' => $altLabels,
                'scopeNote' => $this->firstText($xp, 'skos:scopeNote', $node) ?: null,
                'broader' => $broader,
            ];
        }

        return $concepts;
    }

    /**
     * Import parsed concepts into a taxonomy.
     *
     * @param int|null $defaultParentId parent for concepts whose broader is external/missing (default: taxonomy root)
     *
     * @return array{created:int,skipped:int,errors:array,dryRun:bool,wouldCreate:int}
     */
    public function import(int $taxonomyId, array $concepts, ?int $defaultParentId = null, bool $dryRun = false): array
    {
        $rootId = \QubitTerm::ROOT_ID;
        $parentDefault = $defaultParentId ?: $rootId;

        // Existing prefLabels in this taxonomy (lowercased) — used to skip duplicates.
        $existing = [];
        $rows = DB::table('term')
            ->join('term_i18n', function ($j) {
                $j->on('term.id', '=', 'term_i18n.id')
                    ->where('term_i18n.culture', '=', $this->culture);
            })
            ->where('term.taxonomy_id', $taxonomyId)
            ->select('term_i18n.name')
            ->get();
        foreach ($rows as $r) {
            if (!empty($r->name)) {
                $existing[mb_strtolower(trim($r->name))] = true;
            }
        }

        // Order concepts parent-before-child (topological on broader within the set).
        $ordered = $this->topoSort($concepts);

        $report = ['created' => 0, 'skipped' => 0, 'errors' => [], 'dryRun' => $dryRun, 'wouldCreate' => 0];
        $uriToId = [];
        $writer = $dryRun ? null : WriteServiceFactory::term();

        foreach ($ordered as $c) {
            $key = mb_strtolower(trim($c['prefLabel']));
            if (isset($existing[$key])) {
                ++$report['skipped'];
                continue;
            }

            // Resolve parent: an in-set broader already created, else the default.
            $parentId = $parentDefault;
            foreach ($c['broader'] as $bUri) {
                if (isset($uriToId[$bUri])) {
                    $parentId = $uriToId[$bUri];
                    break;
                }
            }

            if ($dryRun) {
                ++$report['wouldCreate'];
                // Reserve a placeholder so children can resolve their parent in the report.
                $uriToId[$c['uri']] = -1;
                $existing[$key] = true;
                continue;
            }

            try {
                $term = $writer->createTerm($taxonomyId, $c['prefLabel'], $this->culture, $parentId);
                $id = (int) $term->id;
                $uriToId[$c['uri']] = $id;
                $existing[$key] = true;

                foreach ($c['altLabels'] as $alt) {
                    $writer->createOtherName($id, $alt, \QubitTerm::ALTERNATIVE_LABEL_ID, $this->culture);
                }
                if (!empty($c['scopeNote'])) {
                    $writer->createNote($id, \QubitTerm::SCOPE_NOTE_ID, $c['scopeNote'], $this->culture);
                }

                ++$report['created'];
            } catch (\Throwable $e) {
                $report['errors'][] = $c['prefLabel'] . ': ' . $e->getMessage();
            }
        }

        return $report;
    }

    protected function firstText(\DOMXPath $xp, string $expr, \DOMNode $ctx): string
    {
        $nodes = $xp->query($expr, $ctx);

        return ($nodes && $nodes->length) ? trim($nodes->item(0)->textContent) : '';
    }

    /**
     * Order concepts so a concept's in-set broader appears before it.
     * Concepts in a broader cycle (or with only external broaders) keep input order.
     */
    protected function topoSort(array $concepts): array
    {
        $byUri = [];
        foreach ($concepts as $c) {
            $byUri[$c['uri']] = $c;
        }

        $ordered = [];
        $placed = [];

        $visit = function (array $c, array $stack) use (&$visit, &$ordered, &$placed, $byUri) {
            if (isset($placed[$c['uri']]) || isset($stack[$c['uri']])) {
                return; // already placed or cycle guard
            }
            $stack[$c['uri']] = true;
            foreach ($c['broader'] as $bUri) {
                if (isset($byUri[$bUri]) && !isset($placed[$bUri])) {
                    $visit($byUri[$bUri], $stack);
                }
            }
            if (!isset($placed[$c['uri']])) {
                $placed[$c['uri']] = true;
                $ordered[] = $c;
            }
        };

        foreach ($concepts as $c) {
            $visit($c, []);
        }

        return $ordered;
    }
}
