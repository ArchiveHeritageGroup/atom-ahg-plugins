<?php

namespace AtomExtensions\Label;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Label templates + record resolution for batch label printing.
 * Pure DB/PHP; no AI.
 */
class LabelService
{
    public const PAGE_SIZES = ['A4', 'Letter'];
    public const BARCODE_SOURCES = ['identifier', 'accession', 'call_number', 'isbn'];

    // ---- template CRUD ----------------------------------------------------

    public function listTemplates(): array
    {
        return DB::table('label_template')->orderByDesc('is_default')->orderBy('name')->get()->all();
    }

    public function getTemplate(int $id): ?object
    {
        return DB::table('label_template')->where('id', $id)->first();
    }

    public function getDefault(): ?object
    {
        return DB::table('label_template')->orderByDesc('is_default')->orderBy('id')->first();
    }

    public function saveTemplate(array $d, ?int $id = null): int
    {
        $row = [
            'name' => trim((string) ($d['name'] ?? '')) ?: 'Untitled template',
            'page_size' => in_array($d['page_size'] ?? '', self::PAGE_SIZES, true) ? $d['page_size'] : 'A4',
            'columns' => max(1, min(10, (int) ($d['columns'] ?? 3))),
            'rows' => max(1, min(20, (int) ($d['rows'] ?? 8))),
            'label_width_mm' => (float) ($d['label_width_mm'] ?? 63.5),
            'label_height_mm' => (float) ($d['label_height_mm'] ?? 33.9),
            'margin_mm' => (float) ($d['margin_mm'] ?? 10),
            'gutter_mm' => (float) ($d['gutter_mm'] ?? 2.5),
            'font_size_pt' => max(5, min(24, (int) ($d['font_size_pt'] ?? 9))),
            'show_title' => !empty($d['show_title']) ? 1 : 0,
            'show_identifier' => !empty($d['show_identifier']) ? 1 : 0,
            'show_repository' => !empty($d['show_repository']) ? 1 : 0,
            'show_barcode' => !empty($d['show_barcode']) ? 1 : 0,
            'barcode_source' => in_array($d['barcode_source'] ?? '', self::BARCODE_SOURCES, true) ? $d['barcode_source'] : 'identifier',
            'show_qr' => !empty($d['show_qr']) ? 1 : 0,
            'qr_target' => in_array($d['qr_target'] ?? '', ['url', 'identifier'], true) ? $d['qr_target'] : 'url',
            'is_default' => !empty($d['is_default']) ? 1 : 0,
        ];

        if ($row['is_default']) {
            DB::table('label_template')->update(['is_default' => 0]);
        }

        if ($id) {
            DB::table('label_template')->where('id', $id)->update($row);

            return $id;
        }

        return (int) DB::table('label_template')->insertGetId($row);
    }

    public function deleteTemplate(int $id): void
    {
        DB::table('label_template')->where('id', $id)->delete();
    }

    // ---- record resolution for a batch ------------------------------------

    /**
     * Resolve a set of information objects into label rows.
     *
     * @param int[] $ids
     * @return array<int,array{id:int,slug:?string,identifier:?string,title:?string,repository:?string,accession:?string,call_number:?string,isbn:?string}>
     */
    public function resolveByIds(array $ids, string $culture = 'en'): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }

        return $this->baseRecordQuery($culture)->whereIn('io.id', $ids)->get()->map(fn ($r) => $this->mapRow($r))->all();
    }

    public function resolveByRepository(int $repositoryId, string $culture = 'en', int $limit = 500): array
    {
        if ($repositoryId <= 0) {
            return [];
        }

        return $this->baseRecordQuery($culture)
            ->where('io.repository_id', $repositoryId)
            ->where('io.id', '>', 1)
            ->limit($limit)
            ->get()->map(fn ($r) => $this->mapRow($r))->all();
    }

    /** Repositories for the batch picker: id => name. */
    public function repositoryOptions(string $culture = 'en'): array
    {
        $out = [];
        try {
            $rows = DB::table('repository as r')
                ->join('actor_i18n as ai', function ($j) use ($culture) {
                    $j->on('ai.id', '=', 'r.id')->where('ai.culture', '=', $culture);
                })
                ->orderBy('ai.authorized_form_of_name')
                ->get(['r.id', 'ai.authorized_form_of_name as name']);
            foreach ($rows as $r) {
                $out[(int) $r->id] = (string) $r->name;
            }
        } catch (\Throwable $e) {
            // repositories optional
        }

        return $out;
    }

    private function baseRecordQuery(string $culture)
    {
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('ioi.id', '=', 'io.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->leftJoin('actor_i18n as ri', function ($j) use ($culture) {
                $j->on('ri.id', '=', 'io.repository_id')->where('ri.culture', '=', $culture);
            })
            ->select([
                'io.id',
                'io.identifier',
                'ioi.title',
                's.slug',
                'ri.authorized_form_of_name as repository',
            ]);
    }

    private function mapRow(object $r): array
    {
        $oid = (int) $r->id;

        return [
            'id' => $oid,
            'slug' => $r->slug ?? null,
            'identifier' => $r->identifier ?? null,
            'title' => $r->title ?? null,
            'repository' => $r->repository ?? null,
            'accession' => $this->safe('accession', $oid),
            'call_number' => $this->safe('library_item', $oid, 'call_number'),
            'isbn' => $this->safe('library_item', $oid, 'isbn'),
        ];
    }

    private function safe(string $table, int $oid, string $col = 'identifier'): ?string
    {
        try {
            $v = DB::table($table)->where('information_object_id', $oid)->value($col);

            return $v !== null ? (string) $v : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
