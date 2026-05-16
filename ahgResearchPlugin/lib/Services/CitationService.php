<?php

/**
 * CitationService - per-record citation manager export.
 *
 * Spec: docs/atom-heratio-research-enhancements-spec.md §2.1
 *
 * Six formats: RIS, BibTeX, EndNote XML, APA 7, MLA 9, Chicago 17.
 * The first three are citation-manager file formats (Zotero, Mendeley,
 * EndNote, JabRef, LaTeX). The last three are styled plain-text citations
 * for paste-into-essay use.
 */

use Illuminate\Database\Capsule\Manager as DB;

class CitationService
{
    public const FORMATS = [
        'ris'     => 'RIS (Zotero / Mendeley / EndNote)',
        'bibtex'  => 'BibTeX (LaTeX, JabRef)',
        'endnote' => 'EndNote XML',
        'apa'     => 'APA 7',
        'mla'     => 'MLA 9',
        'chicago' => 'Chicago 17 (Notes-Bibliography)',
    ];

    public const MIME = [
        'ris'     => ['mime' => 'application/x-research-info-systems', 'ext' => 'ris'],
        'bibtex'  => ['mime' => 'application/x-bibtex',                 'ext' => 'bib'],
        'endnote' => ['mime' => 'application/xml',                       'ext' => 'xml'],
        'apa'     => ['mime' => 'text/plain',                            'ext' => 'txt'],
        'mla'     => ['mime' => 'text/plain',                            'ext' => 'txt'],
        'chicago' => ['mime' => 'text/plain',                            'ext' => 'txt'],
    ];

    /**
     * Export a single record in the requested format.
     *
     * @return array{format:string,label:string,body:string,filename:string,mime:string}
     */
    public function export(int $objectId, string $format): array
    {
        $format = strtolower($format);
        if (!isset(self::FORMATS[$format])) {
            throw new \InvalidArgumentException("Unknown citation format: {$format}");
        }

        $record = $this->loadRecord($objectId);
        if (!$record) {
            throw new \RuntimeException("Record {$objectId} not found");
        }

        $body = match ($format) {
            'ris'     => $this->toRis($record),
            'bibtex'  => $this->toBibtex($record),
            'endnote' => $this->toEndnoteXml($record),
            'apa'     => $this->toApa($record),
            'mla'     => $this->toMla($record),
            'chicago' => $this->toChicago($record),
        };

        $slugPart = $record->slug ? preg_replace('/[^a-z0-9-]+/i', '-', $record->slug) : "record-{$objectId}";
        $mime = self::MIME[$format];

        return [
            'format'   => $format,
            'label'    => self::FORMATS[$format],
            'body'     => $body,
            'filename' => "{$slugPart}.{$mime['ext']}",
            'mime'     => $mime['mime'],
        ];
    }

    /**
     * Load a record with joined repository, creator (first event actor), and slug.
     */
    protected function loadRecord(int $objectId): ?object
    {
        $culture = $this->culture();

        // Repository extends Actor (FK: repository.id -> actor.id).
        // The repository's display name lives on actor_i18n, not repository_i18n.
        $row = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('actor_i18n as ri', function ($join) use ($culture) {
                $join->on('io.repository_id', '=', 'ri.id')->where('ri.culture', '=', $culture);
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', $objectId)
            ->select(
                'ioi.title',
                'ioi.extent_and_medium',
                'ioi.scope_and_content',
                'io.identifier as ref_code',
                'io.repository_id',
                'ri.authorized_form_of_name as repository_name',
                'slug.slug'
            )
            ->first();

        if (!$row) {
            return null;
        }

        // Creator + date from first event with an actor (joined on event.object_id)
        $event = DB::table('event as e')
            ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
                $join->on('e.actor_id', '=', 'ai.id')->where('ai.culture', '=', $culture);
            })
            ->leftJoin('event_i18n as ei18', function ($join) use ($culture) {
                $join->on('e.id', '=', 'ei18.id')->where('ei18.culture', '=', $culture);
            })
            ->where('e.object_id', $objectId)
            ->select(
                'ai.authorized_form_of_name as creator',
                'e.start_date',
                'e.end_date',
                'ei18.date as date_display'
            )
            ->first();

        $row->creator      = $event->creator      ?? null;
        $row->start_date   = $event->start_date   ?? null;
        $row->end_date     = $event->end_date     ?? null;
        $row->date_display = $event->date_display ?? null;

        $row->year = $this->extractYear($row->start_date ?: $row->date_display);

        return $row;
    }

    protected function culture(): string
    {
        if (class_exists('\\AtomExtensions\\Helpers\\CultureHelper')) {
            return \AtomExtensions\Helpers\CultureHelper::getCulture();
        }
        return class_exists('\\sfContext') ? \sfContext::getInstance()->getUser()->getCulture() : 'en';
    }

    protected function extractYear(?string $date): ?string
    {
        if (!$date) {
            return null;
        }
        if (preg_match('/(\d{4})/', $date, $m)) {
            return $m[1];
        }
        return null;
    }

    protected function publicUrl(?string $slug): string
    {
        if (!$slug) {
            return '';
        }
        $base = \sfConfig::get('app_site_base_url') ?: '';
        if (!$base && class_exists('\\sfContext')) {
            $req = \sfContext::getInstance()->getRequest();
            $base = $req->getUriPrefix();
        }
        return rtrim($base, '/') . '/index.php/' . ltrim($slug, '/');
    }

    // =========================================================================
    // FORMAT METHODS
    // =========================================================================

    protected function toRis(object $r): string
    {
        $lines = [];
        $lines[] = 'TY  - ARCHIVE';
        if ($r->title)               $lines[] = 'TI  - ' . $this->stripNl($r->title);
        if ($r->creator)             $lines[] = 'AU  - ' . $this->stripNl($r->creator);
        if ($r->year)                $lines[] = 'PY  - ' . $r->year;
        if ($r->date_display)        $lines[] = 'DA  - ' . $this->stripNl($r->date_display);
        if ($r->repository_name)     $lines[] = 'PB  - ' . $this->stripNl($r->repository_name);
        if ($r->ref_code)            $lines[] = 'CN  - ' . $this->stripNl($r->ref_code);
        if ($r->extent_and_medium)   $lines[] = 'M3  - ' . $this->stripNl($r->extent_and_medium);
        if ($r->scope_and_content)   $lines[] = 'AB  - ' . $this->stripNl(strip_tags($r->scope_and_content));
        if ($r->slug)                $lines[] = 'UR  - ' . $this->publicUrl($r->slug);
        $lines[] = 'ER  - ';
        $lines[] = '';
        return implode("\r\n", $lines);
    }

    protected function toBibtex(object $r): string
    {
        $key = $this->bibtexKey($r);
        $entries = [];
        if ($r->title)             $entries[] = "  title       = {{" . $this->bibEscape($r->title) . "}}";
        if ($r->creator)           $entries[] = "  author      = {" . $this->bibEscape($r->creator) . "}";
        if ($r->year)              $entries[] = "  year        = {" . $r->year . "}";
        if ($r->repository_name)   $entries[] = "  institution = {" . $this->bibEscape($r->repository_name) . "}";
        if ($r->ref_code)          $entries[] = "  number      = {" . $this->bibEscape($r->ref_code) . "}";
        if ($r->date_display)      $entries[] = "  note        = {" . $this->bibEscape($r->date_display) . "}";
        if ($r->scope_and_content) $entries[] = "  abstract    = {" . $this->bibEscape(strip_tags($r->scope_and_content)) . "}";
        if ($r->slug)              $entries[] = "  url         = {" . $this->publicUrl($r->slug) . "}";

        return "@misc{{$key},\n" . implode(",\n", $entries) . "\n}\n";
    }

    protected function bibtexKey(object $r): string
    {
        $creatorPart = $r->creator ? preg_replace('/[^a-z]+/', '', strtolower($r->creator)) : 'anon';
        $creatorPart = substr($creatorPart, 0, 20) ?: 'anon';
        $yearPart    = $r->year ?: 'nd';
        $codePart    = $r->ref_code ? preg_replace('/[^a-z0-9]+/i', '', strtolower($r->ref_code)) : '';
        $codePart    = substr($codePart, 0, 10);
        return rtrim("{$creatorPart}{$yearPart}{$codePart}", '');
    }

    protected function bibEscape(string $s): string
    {
        return str_replace(['{', '}', '\\', '&', '%', '$', '#', '_', '^', '~'],
                           ['\\{', '\\}', '\\textbackslash{}', '\\&', '\\%', '\\$', '\\#', '\\_', '\\^{}', '\\~{}'], $s);
    }

    protected function toEndnoteXml(object $r): string
    {
        $w = new \XMLWriter();
        $w->openMemory();
        $w->setIndent(true);
        $w->setIndentString('    ');
        $w->startDocument('1.0', 'UTF-8');
        $w->startElement('xml');
        $w->startElement('records');
        $w->startElement('record');

        $w->startElement('ref-type');
        $w->writeAttribute('name', 'Archival Material');
        $w->text('45'); // EndNote ref type for Manuscript / Archival
        $w->endElement();

        if ($r->creator) {
            $w->startElement('contributors');
            $w->startElement('authors');
            $w->startElement('author');
            $w->writeElement('style', $r->creator);
            $w->endElement();
            $w->endElement();
            $w->endElement();
        }

        if ($r->title) {
            $w->startElement('titles');
            $w->writeElement('title', $r->title);
            $w->endElement();
        }

        if ($r->repository_name) {
            $w->writeElement('publisher', $r->repository_name);
        }
        if ($r->year)            $w->writeElement('year', $r->year);
        if ($r->date_display)    $w->writeElement('dates', $r->date_display);
        if ($r->ref_code)        $w->writeElement('call-num', $r->ref_code);
        if ($r->slug)            $w->writeElement('url', $this->publicUrl($r->slug));
        if ($r->scope_and_content) {
            $w->startElement('abstract');
            $w->text(strip_tags($r->scope_and_content));
            $w->endElement();
        }

        $w->endElement(); // record
        $w->endElement(); // records
        $w->endElement(); // xml
        $w->endDocument();

        return $w->outputMemory();
    }

    protected function toApa(object $r): string
    {
        // APA 7: Author. (Year). Title [Description]. Repository. URL
        $parts = [];
        if ($r->creator) {
            $parts[] = rtrim($r->creator, '.') . '.';
        }
        $parts[] = '(' . ($r->year ?: 'n.d.') . ').';
        if ($r->title) {
            $title = rtrim($r->title, '.');
            $desc  = $r->extent_and_medium ? ' [' . $this->trim($r->extent_and_medium, 80) . ']' : '';
            $parts[] = $title . $desc . '.';
        }
        if ($r->ref_code) {
            $parts[] = '(' . $r->ref_code . ').';
        }
        if ($r->repository_name) {
            $parts[] = rtrim($r->repository_name, '.') . '.';
        }
        if ($r->slug) {
            $parts[] = $this->publicUrl($r->slug);
        }
        return implode(' ', $parts);
    }

    protected function toMla(object $r): string
    {
        // MLA 9: Author. "Title." Repository, Ref code, Year, URL.
        $parts = [];
        if ($r->creator)         $parts[] = rtrim($r->creator, '.') . '.';
        if ($r->title)           $parts[] = '"' . rtrim($r->title, '.') . '."';
        if ($r->repository_name) $parts[] = rtrim($r->repository_name, '.') . ',';
        if ($r->ref_code)        $parts[] = $r->ref_code . ',';
        if ($r->year)            $parts[] = $r->year . ',';
        if ($r->slug)            $parts[] = $this->publicUrl($r->slug) . '.';
        return rtrim(implode(' ', $parts), ',') . (str_ends_with(implode(' ', $parts), '.') ? '' : '.');
    }

    protected function toChicago(object $r): string
    {
        // Chicago 17 Notes-Bibliography: Author. "Title." Type, Date, Ref code, Repository, City. URL.
        $parts = [];
        if ($r->creator)         $parts[] = rtrim($r->creator, '.') . '.';
        if ($r->title)           $parts[] = '"' . rtrim($r->title, '.') . '."';
        if ($r->extent_and_medium) $parts[] = $this->trim($r->extent_and_medium, 80) . ',';
        if ($r->date_display)    $parts[] = $r->date_display . ',';
        elseif ($r->year)        $parts[] = $r->year . ',';
        if ($r->ref_code)        $parts[] = $r->ref_code . ',';
        if ($r->repository_name) $parts[] = rtrim($r->repository_name, '.') . '.';
        if ($r->slug)            $parts[] = $this->publicUrl($r->slug) . '.';
        return rtrim(implode(' ', $parts), ',');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    protected function stripNl(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    protected function trim(string $s, int $max): string
    {
        $s = $this->stripNl($s);
        return mb_strlen($s) <= $max ? $s : mb_substr($s, 0, $max - 1) . '…';
    }
}
