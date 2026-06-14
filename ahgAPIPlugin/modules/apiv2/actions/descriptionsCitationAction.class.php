<?php

use AtomFramework\Http\Controllers\AhgApiController;

/**
 * GET /api/v2/descriptions/:slug/citation?format=bib|ris|dc
 *
 * Returns a formatted citation for an archival description in BibTeX, RIS, or
 * Dublin Core XML. Read-only; no DB writes.
 */
class apiv2DescriptionsCitationAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $slug = $request->getParameter('slug');
        if (empty($slug)) {
            return $this->error(400, 'Bad Request', 'Slug parameter required');
        }

        $d = $this->repository->getFullDescription($slug);
        if (!$d) {
            return $this->error(404, 'Not Found', "Description '{$slug}' not found");
        }

        $format = strtolower((string) $request->getParameter('format', 'bib'));
        $meta = $this->buildCitationMeta($d, $slug);

        switch ($format) {
            case 'ris':
                $mime = 'application/x-research-info-systems; charset=utf-8';
                $body = $this->toRis($meta);
                break;
            case 'dc':
            case 'dc.xml':
                $mime = 'application/xml; charset=utf-8';
                $body = $this->toDublinCore($meta);
                break;
            case 'bib':
            case 'bibtex':
            default:
                $mime = 'application/x-bibtex; charset=utf-8';
                $body = $this->toBibtex($meta);
                break;
        }

        $this->getResponse()->setHttpHeader('Content-Type', $mime);

        return $this->renderText($body);
    }

    private function buildCitationMeta(array $d, string $slug): array
    {
        // Authors from related actors (defensive on key names).
        $authors = [];
        foreach (($d['names'] ?? []) as $n) {
            $n = (array) $n;
            $name = $n['name'] ?? $n['authorized_form_of_name'] ?? $n['actor_name'] ?? null;
            if ($name) {
                $authors[] = $name;
            }
        }

        // Year from the first available event date.
        $year = '';
        foreach (($d['dates'] ?? []) as $e) {
            $e = (array) $e;
            $raw = $e['date_display'] ?? $e['start_date'] ?? '';
            if ($raw && preg_match('/(\d{4})/', (string) $raw, $m)) {
                $year = $m[1];
                break;
            }
        }

        $base = rtrim((string) \sfConfig::get('app_siteBaseUrl', ''), '/');

        return [
            'key' => preg_replace('/[^A-Za-z0-9]/', '', (string) ($d['identifier'] ?? $slug)) ?: 'record',
            'title' => (string) ($d['title'] ?? 'Untitled'),
            'authors' => $authors,
            'year' => $year,
            'identifier' => (string) ($d['identifier'] ?? ''),
            'url' => $base ? ($base . '/' . $slug) : $slug,
            'abstract' => (string) ($d['scope_and_content'] ?? ''),
        ];
    }

    private function toBibtex(array $m): string
    {
        $fields = [];
        $fields[] = '  title = {' . $this->bibEscape($m['title']) . '}';
        if ($m['authors']) {
            $fields[] = '  author = {' . $this->bibEscape(implode(' and ', $m['authors'])) . '}';
        }
        if ($m['year']) {
            $fields[] = '  year = {' . $m['year'] . '}';
        }
        if ($m['identifier']) {
            $fields[] = '  note = {' . $this->bibEscape($m['identifier']) . '}';
        }
        $fields[] = '  howpublished = {\url{' . $m['url'] . '}}';

        return '@misc{' . $m['key'] . ",\n" . implode(",\n", $fields) . "\n}\n";
    }

    private function toRis(array $m): string
    {
        $lines = ['TY  - GEN'];
        $lines[] = 'TI  - ' . $m['title'];
        foreach ($m['authors'] as $a) {
            $lines[] = 'AU  - ' . $a;
        }
        if ($m['year']) {
            $lines[] = 'PY  - ' . $m['year'];
        }
        if ($m['identifier']) {
            $lines[] = 'CN  - ' . $m['identifier'];
        }
        if ($m['abstract']) {
            $lines[] = 'AB  - ' . trim(preg_replace('/\s+/', ' ', $m['abstract']));
        }
        $lines[] = 'UR  - ' . $m['url'];
        $lines[] = 'ER  - ';

        return implode("\r\n", $lines) . "\r\n";
    }

    private function toDublinCore(array $m): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $root = $doc->createElementNS('http://www.openarchives.org/OAI/2.0/oai_dc/', 'oai_dc:dc');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $doc->appendChild($root);

        $add = function (string $name, string $value) use ($doc, $root) {
            if ('' === $value) {
                return;
            }
            $el = $doc->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:' . $name);
            $el->appendChild($doc->createTextNode($value));
            $root->appendChild($el);
        };

        $add('title', $m['title']);
        foreach ($m['authors'] as $a) {
            $add('creator', $a);
        }
        $add('date', $m['year']);
        $add('identifier', $m['identifier']);
        $add('identifier', $m['url']);
        $add('description', trim(preg_replace('/\s+/', ' ', $m['abstract'])));

        return $doc->saveXML();
    }

    private function bibEscape(string $s): string
    {
        return str_replace(['{', '}'], ['\{', '\}'], $s);
    }
}
