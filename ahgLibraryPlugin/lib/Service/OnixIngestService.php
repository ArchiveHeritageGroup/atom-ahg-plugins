<?php

declare(strict_types=1);

/**
 * OnixIngestService — parse + validate + stage ONIX product feeds.
 *
 * Cloned from Heratio packages/ahg-library OnixIngestService (parse/validate/
 * ingest phase). Records land in a review queue (library_onix_ingest[_line]);
 * commit-to-catalogue is a follow-up phase.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;

class OnixIngestService
{
    // ── Parsing ───────────────────────────────────────────────────────────

    /**
     * Parse an ONIX message into ['version' => ?string, 'records' => array].
     *
     * @throws \RuntimeException on empty or malformed XML.
     */
    public function parse(string $xml): array
    {
        $xml = trim($xml);
        if ('' === $xml) {
            throw new \RuntimeException('Empty ONIX payload.');
        }

        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $ok = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA | LIBXML_COMPACT);
        if (!$ok) {
            $errs = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            $msg = $errs ? trim($errs[0]->message) : 'unknown parse error';
            throw new \RuntimeException('Malformed ONIX XML: ' . $msg);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xp = new \DOMXPath($dom);

        $version = null;
        $rel = $xp->query('//*[local-name()="ONIXMessage"]/@release');
        if ($rel && $rel->length) {
            $version = $rel->item(0)->nodeValue;
        }

        $records = [];
        foreach ($xp->query('//*[local-name()="Product"]') as $product) {
            $records[] = $this->extractProduct($xp, $product, $dom);
        }

        return ['version' => $version, 'records' => $records];
    }

    protected function extractProduct(\DOMXPath $xp, \DOMNode $product, \DOMDocument $dom): array
    {
        $isbn = $gtin = $isbn10 = $issn = null;
        foreach ($xp->query('.//*[local-name()="ProductIdentifier"]', $product) as $pi) {
            $type = $this->val($xp, $pi, './*[local-name()="ProductIDType"]');
            $idv = $this->val($xp, $pi, './*[local-name()="IDValue"]');
            if (null === $idv) {
                continue;
            }
            $clean = preg_replace('/[^0-9Xx]/', '', $idv);
            switch ($type) {
                case '15': $isbn = $clean; break;
                case '03': $gtin = $clean; break;
                case '02': $isbn10 = $clean; break;
                case '22': $issn = $clean; break;
                case '23': if (null === $issn) { $issn = $clean; } break;
            }
        }
        $isbn = $isbn ?: $gtin ?: $isbn10;

        $title = $this->val($xp, $product, './/*[local-name()="TitleDetail"]//*[local-name()="TitleText"]')
            ?? $this->val($xp, $product, './/*[local-name()="Title"]/*[local-name()="TitleText"]')
            ?? $this->val($xp, $product, './/*[local-name()="TitleText"]');
        $subtitle = $this->val($xp, $product, './/*[local-name()="TitleDetail"]//*[local-name()="Subtitle"]')
            ?? $this->val($xp, $product, './/*[local-name()="Subtitle"]');

        $creators = [];
        foreach ($xp->query('.//*[local-name()="Contributor"]', $product) as $c) {
            $name = $this->val($xp, $c, './*[local-name()="PersonName"]');
            if (null === $name) {
                $before = $this->val($xp, $c, './*[local-name()="NamesBeforeKey"]');
                $key = $this->val($xp, $c, './*[local-name()="KeyNames"]');
                $joined = trim(($before ? $before . ' ' : '') . ($key ?? ''));
                $name = '' !== $joined ? $joined : null;
            }
            if (null === $name) {
                $name = $this->val($xp, $c, './*[local-name()="CorporateName"]');
            }
            if (null === $name) {
                continue;
            }
            $role = $this->mapContributorRole($this->val($xp, $c, './*[local-name()="ContributorRole"]'));
            $creators[] = ['name' => $name, 'role' => $role];
        }

        $edition = $this->val($xp, $product, './/*[local-name()="EditionNumber"]');
        $form = $this->val($xp, $product, './/*[local-name()="ProductForm"]');
        $publisher = $this->val($xp, $product, './/*[local-name()="PublisherName"]');
        $place = $this->val($xp, $product, './/*[local-name()="CityOfPublication"]');

        $pubDate = $this->val($xp, $product, './/*[local-name()="PublishingDate"]/*[local-name()="Date"]')
            ?? $this->val($xp, $product, './/*[local-name()="PublicationDate"]')
            ?? $this->val($xp, $product, './/*[local-name()="CopyrightYear"]');
        $pubYear = $pubDate ? substr(preg_replace('/[^0-9]/', '', $pubDate), 0, 4) : null;

        $price = $this->val($xp, $product, './/*[local-name()="Price"]/*[local-name()="PriceAmount"]');
        $currency = $this->val($xp, $product, './/*[local-name()="Price"]/*[local-name()="CurrencyCode"]');
        $supplier = $this->val($xp, $product, './/*[local-name()="Supplier"]/*[local-name()="SupplierName"]');

        $author = implode('; ', array_map(static fn ($c) => $c['name'], $creators));

        return [
            'product_ref' => $this->val($xp, $product, './*[local-name()="RecordReference"]'),
            'isbn' => $isbn,
            'issn' => $issn,
            'title' => $title,
            'subtitle' => $subtitle,
            'author' => '' !== $author ? $author : null,
            'creators' => $creators,
            'publisher' => $publisher,
            'publication_place' => $place,
            'pub_year' => $pubYear,
            'edition' => $edition,
            'material_type' => $this->mapForm($form),
            'price' => null !== $price && is_numeric($price) ? (float) $price : null,
            'currency' => $currency,
            'supplier' => $supplier,
            'raw' => $dom->saveXML($product),
        ];
    }

    private function val(\DOMXPath $xp, \DOMNode $ctx, string $rel): ?string
    {
        $nodes = $xp->query($rel, $ctx);
        if (!$nodes || 0 === $nodes->length) {
            return null;
        }
        $text = trim($nodes->item(0)->textContent);

        return '' === $text ? null : $text;
    }

    private function mapContributorRole(?string $code): string
    {
        if (null === $code) {
            return 'contributor';
        }

        return match (true) {
            str_starts_with($code, 'A') => 'author',
            str_starts_with($code, 'B') => 'editor',
            str_starts_with($code, 'C') => 'translator',
            default => 'contributor',
        };
    }

    private function mapForm(?string $code): string
    {
        if (null === $code || '' === $code) {
            return 'monograph';
        }
        $c = strtoupper($code);

        return match (true) {
            str_starts_with($c, 'D') => 'ebook',
            str_starts_with($c, 'A') => 'audio',
            str_starts_with($c, 'V') => 'video',
            default => 'monograph',
        };
    }

    // ── Validation ────────────────────────────────────────────────────────

    public function validateRecord(array $rec): array
    {
        if (empty($rec['title'])) {
            return ['status' => 'invalid', 'error' => 'Missing title (TitleText).'];
        }

        $isbn = $rec['isbn'] ?? null;
        $issn = $rec['issn'] ?? null;

        if (empty($isbn) && empty($issn)) {
            return ['status' => 'invalid', 'error' => 'No ISBN or ISSN identifier.'];
        }
        if (!empty($isbn) && 13 === strlen($isbn) && !$this->isValidIsbn13($isbn)) {
            return ['status' => 'invalid', 'error' => "Invalid ISBN-13 checksum ({$isbn})."];
        }
        if (!empty($isbn) && 10 === strlen($isbn) && !$this->isValidIsbn10($isbn)) {
            return ['status' => 'invalid', 'error' => "Invalid ISBN-10 checksum ({$isbn})."];
        }
        if (!empty($issn) && !$this->isValidIssn($issn)) {
            return ['status' => 'invalid', 'error' => "Invalid ISSN checksum ({$issn})."];
        }

        try {
            $dupe = DB::table('library_item')
                ->when(!empty($isbn), fn ($q) => $q->orWhere('isbn', $isbn))
                ->when(!empty($issn), fn ($q) => $q->orWhere('issn', $issn))
                ->exists();
            if ($dupe) {
                return ['status' => 'duplicate', 'error' => 'Matching ISBN/ISSN already in catalogue.'];
            }
        } catch (\Throwable $e) {
            // library_item may be unavailable — treat as non-duplicate.
        }

        return ['status' => 'valid', 'error' => null];
    }

    public function isValidIsbn13(string $isbn): bool
    {
        if (!preg_match('/^\d{13}$/', $isbn)) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += ((int) $isbn[$i]) * (0 === $i % 2 ? 1 : 3);
        }

        return 0 === $sum % 10;
    }

    public function isValidIsbn10(string $isbn): bool
    {
        if (!preg_match('/^\d{9}[\dXx]$/', $isbn)) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $ch = $isbn[$i];
            $val = ('X' === $ch || 'x' === $ch) ? 10 : (int) $ch;
            $sum += $val * (10 - $i);
        }

        return 0 === $sum % 11;
    }

    public function isValidIssn(string $issn): bool
    {
        $issn = strtoupper($issn);
        if (!preg_match('/^\d{7}[\dX]$/', $issn)) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $sum += ((int) $issn[$i]) * (8 - $i);
        }
        $check = (11 - ($sum % 11)) % 11;
        $expected = 10 === $check ? 'X' : (string) $check;

        return $expected === $issn[7];
    }

    // ── Ingest (parse + validate + stage) ────────────────────────────────────

    public function ingest(string $xml, ?string $filename = null, string $source = 'file', ?int $userId = null): array
    {
        $parsed = $this->parse($xml);
        $records = $parsed['records'];

        $valid = 0;
        $errors = 0;
        $lines = [];
        $now = date('Y-m-d H:i:s');

        foreach ($records as $rec) {
            $v = $this->validateRecord($rec);
            if ('valid' === $v['status']) {
                $valid++;
            } else {
                $errors++;
            }
            $lines[] = [
                'product_ref' => $rec['product_ref'] ?? null,
                'isbn' => $rec['isbn'] ?? null,
                'issn' => $rec['issn'] ?? null,
                'title' => $this->clip($rec['title'] ?? null, 500),
                'subtitle' => $this->clip($rec['subtitle'] ?? null, 500),
                'author' => $this->clip($rec['author'] ?? null, 500),
                'publisher' => $this->clip($rec['publisher'] ?? null, 255),
                'pub_year' => $rec['pub_year'] ?? null,
                'edition' => $this->clip($rec['edition'] ?? null, 100),
                'material_type' => $rec['material_type'] ?? null,
                'price' => $rec['price'] ?? null,
                'currency' => $rec['currency'] ?? null,
                'supplier' => $this->clip($rec['supplier'] ?? null, 255),
                'status' => $v['status'],
                'error' => $this->clip($v['error'] ?? null, 1000),
                'raw' => $rec['raw'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return DB::connection()->transaction(function () use ($filename, $source, $parsed, $records, $valid, $errors, $userId, $lines, $now) {
            $ingestId = (int) DB::table('library_onix_ingest')->insertGetId([
                'filename' => $filename,
                'source' => $source,
                'onix_version' => $parsed['version'],
                'status' => 'parsed',
                'record_count' => count($records),
                'valid_count' => $valid,
                'error_count' => $errors,
                'imported_count' => 0,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($lines as $line) {
                $line['ingest_id'] = $ingestId;
                DB::table('library_onix_ingest_line')->insert($line);
            }

            return [
                'ingest_id' => $ingestId,
                'record_count' => count($records),
                'valid_count' => $valid,
                'error_count' => $errors,
            ];
        });
    }

    // ── Review queue ──────────────────────────────────────────────────────

    public function listIngests(array $filters = []): array
    {
        $q = DB::table('library_onix_ingest');
        if (!empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        return $q->orderByDesc('id')->limit((int) ($filters['limit'] ?? 100))->get()->all();
    }

    public function getIngest(int $id): ?object
    {
        return DB::table('library_onix_ingest')->where('id', $id)->first() ?: null;
    }

    public function getLines(int $ingestId, ?string $statusFilter = null): array
    {
        $q = DB::table('library_onix_ingest_line')->where('ingest_id', $ingestId);
        if ($statusFilter) {
            $q->where('status', $statusFilter);
        }

        return $q->orderBy('id')->get()->all();
    }

    public function updateLineStatus(int $lineId, string $status): bool
    {
        return DB::table('library_onix_ingest_line')->where('id', $lineId)
            ->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]) > 0;
    }

    public function deleteIngest(int $id): bool
    {
        DB::table('library_onix_ingest_line')->where('ingest_id', $id)->delete();

        return DB::table('library_onix_ingest')->where('id', $id)->delete() > 0;
    }

    private function clip(?string $v, int $len): ?string
    {
        if (null === $v) {
            return null;
        }

        return mb_substr($v, 0, $len);
    }
}
