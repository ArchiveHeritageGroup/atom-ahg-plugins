<?php

namespace AhgIiif\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * IIIF Change Discovery (Activity Streams) + OCR export.
 *
 * Change Discovery 1.0: a chronological OrderedCollection of Create/Update
 * activities over published descriptions' v3 manifests, so aggregators can
 * harvest what changed. OCR export returns stored OCR text in plain / JSON /
 * minimal ALTO form. Pure read; no AI.
 */
class IiifDiscoveryService
{
    public const PAGE_SIZE = 100;
    private const PUB_TYPE = 158;
    private const PUB_PUBLISHED = 160;

    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) (class_exists('sfConfig') ? \sfConfig::get('app_siteBaseUrl', '') : ''), '/');
    }

    public function totalItems(): int
    {
        return (int) $this->publishedQuery()->count();
    }

    public function lastPageIndex(): int
    {
        $total = $this->totalItems();

        return $total > 0 ? (int) floor(($total - 1) / self::PAGE_SIZE) : 0;
    }

    /** Top-level OrderedCollection (IIIF Change Discovery 1.0). */
    public function collection(): array
    {
        $activityBase = $this->baseUrl . '/iiif/activity';
        $last = $this->lastPageIndex();

        return [
            '@context' => 'http://iiif.io/api/discovery/1/context.json',
            'id' => $activityBase,
            'type' => 'OrderedCollection',
            'totalItems' => $this->totalItems(),
            'first' => ['id' => $activityBase . '/page/0', 'type' => 'OrderedCollectionPage'],
            'last' => ['id' => $activityBase . '/page/' . $last, 'type' => 'OrderedCollectionPage'],
        ];
    }

    /** One OrderedCollectionPage of activities, chronological (oldest first). */
    public function page(int $n): array
    {
        $n = max(0, $n);
        $activityBase = $this->baseUrl . '/iiif/activity';
        $last = $this->lastPageIndex();

        // Timestamps live on the base `object` table (shared id), not on
        // information_object.
        $rows = $this->publishedQuery()
            ->join('object as o', 'o.id', '=', 'io.id')
            ->orderBy('o.updated_at')
            ->orderBy('io.id')
            ->offset($n * self::PAGE_SIZE)
            ->limit(self::PAGE_SIZE)
            ->get(['io.id', 'o.created_at', 'o.updated_at', 's.slug']);

        $items = [];
        foreach ($rows as $r) {
            $created = (string) $r->created_at;
            $updated = (string) ($r->updated_at ?: $r->created_at);
            $items[] = [
                'type' => ($updated === $created || !$r->updated_at) ? 'Create' : 'Update',
                'object' => [
                    'id' => $this->baseUrl . '/iiif/v3/manifest/' . $r->slug,
                    'type' => 'Manifest',
                ],
                'endTime' => $this->iso($updated),
            ];
        }

        $page = [
            '@context' => 'http://iiif.io/api/discovery/1/context.json',
            'id' => $activityBase . '/page/' . $n,
            'type' => 'OrderedCollectionPage',
            'partOf' => ['id' => $activityBase, 'type' => 'OrderedCollection'],
            'startIndex' => $n * self::PAGE_SIZE,
            'orderedItems' => $items,
        ];
        if ($n > 0) {
            $page['prev'] = ['id' => $activityBase . '/page/' . ($n - 1), 'type' => 'OrderedCollectionPage'];
        }
        if ($n < $last) {
            $page['next'] = ['id' => $activityBase . '/page/' . ($n + 1), 'type' => 'OrderedCollectionPage'];
        }

        return $page;
    }

    /** Stored OCR for an object (prefers object_id, falls back to digital_object_id). */
    public function ocrForObject(int $objectId): ?object
    {
        try {
            $row = DB::table('iiif_ocr_text')->where('object_id', $objectId)->orderByDesc('updated_at')->first();
            if (!$row) {
                $row = DB::table('iiif_ocr_text')->where('digital_object_id', $objectId)->orderByDesc('updated_at')->first();
            }

            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Minimal ALTO XML wrapping plain OCR text (no coordinates available). */
    public function toAlto(string $text, string $language = ''): string
    {
        $esc = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $lines = preg_split('/\r\n|\r|\n/', $esc) ?: [];
        $body = '';
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }
            $body .= '        <TextLine ID="line_' . $i . '"><String ID="s_' . $i . '" CONTENT="' . $line . '"/></TextLine>' . "\n";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<alto xmlns="http://www.loc.gov/standards/alto/ns-v4#">' . "\n"
            . '  <Description><MeasurementUnit>pixel</MeasurementUnit>'
            . ($language ? '<Language>' . htmlspecialchars($language, ENT_XML1, 'UTF-8') . '</Language>' : '')
            . '</Description>' . "\n"
            . '  <Layout><Page ID="page_1" PHYSICAL_IMG_NR="1"><PrintSpace><TextBlock ID="block_1">' . "\n"
            . $body
            . '  </TextBlock></PrintSpace></Page></Layout>' . "\n"
            . '</alto>' . "\n";
    }

    private function publishedQuery()
    {
        return DB::table('information_object as io')
            ->join('status as st', function ($j) {
                $j->on('st.object_id', '=', 'io.id')
                    ->where('st.type_id', '=', self::PUB_TYPE)
                    ->where('st.status_id', '=', self::PUB_PUBLISHED);
            })
            ->join('slug as s', 's.object_id', '=', 'io.id')
            ->where('io.id', '>', 1);
    }

    private function iso(string $dt): string
    {
        $ts = strtotime($dt);

        return $ts ? gmdate('Y-m-d\TH:i:s\Z', $ts) : $dt;
    }
}
