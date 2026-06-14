<?php

namespace AhgTermTaxonomy\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * SKOS Export Service.
 *
 * Serialises an AtoM taxonomy and its terms as a SKOS concept scheme in
 * RDF/XML, Turtle, N-Triples or JSON-LD. Read-only — builds the concept graph
 * from term / term_i18n / other_name (altLabels) / note (scope notes) and the
 * term hierarchy (parent_id) for skos:broader / skos:topConceptOf.
 */
class SkosExportService
{
    protected string $culture;
    protected string $base;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
        $this->base = rtrim((string) \sfConfig::get('app_siteBaseUrl', ''), '/');
    }

    /**
     * Supported serialisation formats => [mime, extension].
     */
    public static function formats(): array
    {
        return [
            'rdf' => ['application/rdf+xml; charset=utf-8', 'rdf'],
            'ttl' => ['text/turtle; charset=utf-8', 'ttl'],
            'nt' => ['application/n-triples; charset=utf-8', 'nt'],
            'jsonld' => ['application/ld+json; charset=utf-8', 'jsonld'],
        ];
    }

    /**
     * Serialise a taxonomy to the requested format.
     *
     * @param bool $skosXl emit SKOS-XL (skosxl:Label) reified labels alongside plain ones
     */
    public function export(int $taxonomyId, string $format = 'rdf', bool $skosXl = false): string
    {
        $scheme = $this->loadScheme($taxonomyId);
        $concepts = $this->loadConcepts($taxonomyId);

        return match ($format) {
            'ttl' => $this->toTurtle($scheme, $concepts, $skosXl),
            'nt' => $this->toNTriples($scheme, $concepts),
            'jsonld' => $this->toJsonLd($scheme, $concepts),
            default => $this->toRdfXml($scheme, $concepts, $skosXl),
        };
    }

    // ----------------------------------------------------------------
    // Data loading
    // ----------------------------------------------------------------

    protected function loadScheme(int $taxonomyId): array
    {
        $name = DB::table('taxonomy_i18n')
            ->where('id', $taxonomyId)
            ->where('culture', $this->culture)
            ->value('name');

        return [
            'uri' => $this->base . '/index.php/taxonomy/' . $taxonomyId,
            'label' => $name ?: ('Taxonomy ' . $taxonomyId),
        ];
    }

    /**
     * @return array<int, array> concepts keyed by term id
     */
    protected function loadConcepts(int $taxonomyId): array
    {
        $rootId = \QubitTerm::ROOT_ID;

        $rows = DB::table('term')
            ->leftJoin('term_i18n', function ($j) {
                $j->on('term.id', '=', 'term_i18n.id')
                    ->where('term_i18n.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'term.id', '=', 'slug.object_id')
            ->where('term.taxonomy_id', $taxonomyId)
            ->where('term.id', '!=', $rootId)
            ->select('term.id', 'term.parent_id', 'term_i18n.name', 'slug.slug')
            ->get()
            ->all();

        if (empty($rows)) {
            return [];
        }

        $ids = array_map(static fn ($r) => (int) $r->id, $rows);
        $altLabels = $this->loadAltLabels($ids);
        $scopeNotes = $this->loadScopeNotes($ids);

        $concepts = [];
        foreach ($rows as $r) {
            $id = (int) $r->id;
            $concepts[$id] = [
                'id' => $id,
                'uri' => $this->termUri($r),
                'prefLabel' => $r->name ?? '',
                'altLabels' => $altLabels[$id] ?? [],
                'scopeNote' => $scopeNotes[$id] ?? null,
                'parentId' => (int) $r->parent_id,
                'isTopConcept' => ((int) $r->parent_id === $rootId),
            ];
        }

        return $concepts;
    }

    protected function loadAltLabels(array $termIds): array
    {
        $rows = DB::table('other_name')
            ->join('other_name_i18n', function ($j) {
                $j->on('other_name.id', '=', 'other_name_i18n.id')
                    ->where('other_name_i18n.culture', '=', $this->culture);
            })
            ->whereIn('other_name.object_id', $termIds)
            ->where('other_name.type_id', \QubitTerm::ALTERNATIVE_LABEL_ID)
            ->select('other_name.object_id', 'other_name_i18n.name')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            if (!empty($row->name)) {
                $out[(int) $row->object_id][] = $row->name;
            }
        }

        return $out;
    }

    protected function loadScopeNotes(array $termIds): array
    {
        $rows = DB::table('note')
            ->join('note_i18n', function ($j) {
                $j->on('note.id', '=', 'note_i18n.id')
                    ->where('note_i18n.culture', '=', $this->culture);
            })
            ->whereIn('note.object_id', $termIds)
            ->where('note.type_id', \QubitTerm::SCOPE_NOTE_ID)
            ->select('note.object_id', 'note_i18n.content')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            if (!empty($row->content) && !isset($out[(int) $row->object_id])) {
                $out[(int) $row->object_id] = $row->content;
            }
        }

        return $out;
    }

    protected function termUri(object $row): string
    {
        $slug = $row->slug ?? null;

        return $this->base . '/index.php/' . ($slug ?: ('term/' . (int) $row->id));
    }

    protected function conceptUri(array $concepts, int $id, array $scheme): string
    {
        return $concepts[$id]['uri'] ?? $scheme['uri'];
    }

    // ----------------------------------------------------------------
    // Serialisers
    // ----------------------------------------------------------------

    protected function toRdfXml(array $scheme, array $concepts, bool $skosXl): string
    {
        $w = new \XMLWriter();
        $w->openMemory();
        $w->setIndent(true);
        $w->startDocument('1.0', 'UTF-8');
        $w->startElement('rdf:RDF');
        $w->writeAttribute('xmlns:rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        $w->writeAttribute('xmlns:skos', 'http://www.w3.org/2004/02/skos/core#');
        $w->writeAttribute('xmlns:skosxl', 'http://www.w3.org/2008/05/skos-xl#');
        $w->writeAttribute('xmlns:dct', 'http://purl.org/dc/terms/');

        // ConceptScheme
        $w->startElement('skos:ConceptScheme');
        $w->writeAttribute('rdf:about', $scheme['uri']);
        $w->startElement('dct:title');
        $w->writeAttribute('xml:lang', $this->culture);
        $w->text($scheme['label']);
        $w->endElement();
        foreach ($concepts as $c) {
            if ($c['isTopConcept']) {
                $w->startElement('skos:hasTopConcept');
                $w->writeAttribute('rdf:resource', $c['uri']);
                $w->endElement();
            }
        }
        $w->endElement(); // ConceptScheme

        foreach ($concepts as $c) {
            $w->startElement('skos:Concept');
            $w->writeAttribute('rdf:about', $c['uri']);

            $w->startElement('skos:prefLabel');
            $w->writeAttribute('xml:lang', $this->culture);
            $w->text($c['prefLabel']);
            $w->endElement();

            foreach ($c['altLabels'] as $alt) {
                $w->startElement('skos:altLabel');
                $w->writeAttribute('xml:lang', $this->culture);
                $w->text($alt);
                $w->endElement();
            }

            if ($skosXl) {
                $this->rdfXlLabel($w, 'skosxl:prefLabel', $c['uri'] . '#prefLabel', $c['prefLabel']);
            }

            if ($c['scopeNote'] !== null) {
                $w->startElement('skos:scopeNote');
                $w->writeAttribute('xml:lang', $this->culture);
                $w->text($c['scopeNote']);
                $w->endElement();
            }

            $w->startElement('skos:inScheme');
            $w->writeAttribute('rdf:resource', $scheme['uri']);
            $w->endElement();

            if ($c['isTopConcept']) {
                $w->startElement('skos:topConceptOf');
                $w->writeAttribute('rdf:resource', $scheme['uri']);
                $w->endElement();
            } elseif (isset($concepts[$c['parentId']])) {
                $w->startElement('skos:broader');
                $w->writeAttribute('rdf:resource', $concepts[$c['parentId']]['uri']);
                $w->endElement();
            }

            $w->endElement(); // Concept
        }

        $w->endElement(); // rdf:RDF
        $w->endDocument();

        return $w->outputMemory();
    }

    protected function rdfXlLabel(\XMLWriter $w, string $predicate, string $labelUri, string $literal): void
    {
        $w->startElement($predicate);
        $w->startElement('skosxl:Label');
        $w->writeAttribute('rdf:about', $labelUri);
        $w->startElement('skosxl:literalForm');
        $w->writeAttribute('xml:lang', $this->culture);
        $w->text($literal);
        $w->endElement();
        $w->endElement();
        $w->endElement();
    }

    protected function toTurtle(array $scheme, array $concepts, bool $skosXl): string
    {
        $lang = $this->culture;
        $out = "@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .\n";
        $out .= "@prefix skos: <http://www.w3.org/2004/02/skos/core#> .\n";
        $out .= "@prefix skosxl: <http://www.w3.org/2008/05/skos-xl#> .\n";
        $out .= "@prefix dct: <http://purl.org/dc/terms/> .\n\n";

        $tops = [];
        foreach ($concepts as $c) {
            if ($c['isTopConcept']) {
                $tops[] = '<' . $c['uri'] . '>';
            }
        }
        $out .= '<' . $scheme['uri'] . "> a skos:ConceptScheme ;\n";
        $out .= '    dct:title ' . $this->ttlLiteral($scheme['label'], $lang);
        if ($tops) {
            $out .= " ;\n    skos:hasTopConcept " . implode(', ', $tops);
        }
        $out .= " .\n\n";

        foreach ($concepts as $c) {
            $out .= '<' . $c['uri'] . "> a skos:Concept ;\n";
            $lines = ['    skos:prefLabel ' . $this->ttlLiteral($c['prefLabel'], $lang)];
            foreach ($c['altLabels'] as $alt) {
                $lines[] = '    skos:altLabel ' . $this->ttlLiteral($alt, $lang);
            }
            if ($c['scopeNote'] !== null) {
                $lines[] = '    skos:scopeNote ' . $this->ttlLiteral($c['scopeNote'], $lang);
            }
            $lines[] = '    skos:inScheme <' . $scheme['uri'] . '>';
            if ($c['isTopConcept']) {
                $lines[] = '    skos:topConceptOf <' . $scheme['uri'] . '>';
            } elseif (isset($concepts[$c['parentId']])) {
                $lines[] = '    skos:broader <' . $concepts[$c['parentId']]['uri'] . '>';
            }
            if ($skosXl) {
                $lines[] = '    skosxl:prefLabel [ a skosxl:Label ; skosxl:literalForm ' . $this->ttlLiteral($c['prefLabel'], $lang) . ' ]';
            }
            $out .= implode(" ;\n", $lines) . " .\n\n";
        }

        return $out;
    }

    protected function toNTriples(array $scheme, array $concepts): string
    {
        $rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
        $skos = 'http://www.w3.org/2004/02/skos/core#';
        $out = '';
        $out .= $this->ntTriple($scheme['uri'], $rdf, $skos . 'ConceptScheme', true);
        $out .= $this->ntTriple($scheme['uri'], 'http://purl.org/dc/terms/title', $scheme['label'], false);

        foreach ($concepts as $c) {
            $out .= $this->ntTriple($c['uri'], $rdf, $skos . 'Concept', true);
            $out .= $this->ntTriple($c['uri'], $skos . 'prefLabel', $c['prefLabel'], false);
            foreach ($c['altLabels'] as $alt) {
                $out .= $this->ntTriple($c['uri'], $skos . 'altLabel', $alt, false);
            }
            if ($c['scopeNote'] !== null) {
                $out .= $this->ntTriple($c['uri'], $skos . 'scopeNote', $c['scopeNote'], false);
            }
            $out .= $this->ntTriple($c['uri'], $skos . 'inScheme', $scheme['uri'], true);
            if ($c['isTopConcept']) {
                $out .= $this->ntTriple($c['uri'], $skos . 'topConceptOf', $scheme['uri'], true);
            } elseif (isset($concepts[$c['parentId']])) {
                $out .= $this->ntTriple($c['uri'], $skos . 'broader', $concepts[$c['parentId']]['uri'], true);
            }
        }

        return $out;
    }

    protected function toJsonLd(array $scheme, array $concepts): string
    {
        $graph = [];
        $tops = [];
        foreach ($concepts as $c) {
            if ($c['isTopConcept']) {
                $tops[] = $c['uri'];
            }
        }
        $graph[] = array_filter([
            '@id' => $scheme['uri'],
            '@type' => 'skos:ConceptScheme',
            'dct:title' => ['@value' => $scheme['label'], '@language' => $this->culture],
            'skos:hasTopConcept' => array_map(static fn ($u) => ['@id' => $u], $tops) ?: null,
        ]);

        foreach ($concepts as $c) {
            $node = [
                '@id' => $c['uri'],
                '@type' => 'skos:Concept',
                'skos:prefLabel' => ['@value' => $c['prefLabel'], '@language' => $this->culture],
                'skos:inScheme' => ['@id' => $scheme['uri']],
            ];
            if ($c['altLabels']) {
                $node['skos:altLabel'] = array_map(fn ($a) => ['@value' => $a, '@language' => $this->culture], $c['altLabels']);
            }
            if ($c['scopeNote'] !== null) {
                $node['skos:scopeNote'] = ['@value' => $c['scopeNote'], '@language' => $this->culture];
            }
            if ($c['isTopConcept']) {
                $node['skos:topConceptOf'] = ['@id' => $scheme['uri']];
            } elseif (isset($concepts[$c['parentId']])) {
                $node['skos:broader'] = ['@id' => $concepts[$c['parentId']]['uri']];
            }
            $graph[] = $node;
        }

        return json_encode([
            '@context' => [
                'skos' => 'http://www.w3.org/2004/02/skos/core#',
                'dct' => 'http://purl.org/dc/terms/',
            ],
            '@graph' => $graph,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    // ----------------------------------------------------------------
    // Literal helpers
    // ----------------------------------------------------------------

    protected function ttlLiteral(string $value, string $lang): string
    {
        $escaped = str_replace(['\\', '"', "\n", "\r", "\t"], ['\\\\', '\\"', '\\n', '\\r', '\\t'], $value);

        return '"' . $escaped . '"@' . $lang;
    }

    protected function ntTriple(string $subject, string $predicate, string $object, bool $objectIsUri): string
    {
        $o = $objectIsUri
            ? '<' . $object . '>'
            : '"' . str_replace(['\\', '"', "\n", "\r", "\t"], ['\\\\', '\\"', '\\n', '\\r', '\\t'], $object) . '"@' . $this->culture;

        return '<' . $subject . '> <' . $predicate . '> ' . $o . " .\n";
    }
}
