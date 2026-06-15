<?php

namespace AhgAccessibility\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Image alternative text (WCAG 1.1.1) store + queries.
 *
 * Operates on image *master* digital objects (digital_object.media_type_id = 136,
 * usage_id = 140), whose object_id is the owning information_object. Everything is
 * a soft reference - no FK to base AtoM tables.
 */
class AltTextService
{
    /** digital_object.media_type_id for "Image" (taxonomy 46). */
    public const MEDIA_TYPE_IMAGE = 136;
    /** digital_object.usage_id for "Master". */
    public const USAGE_MASTER = 140;

    /** All authored alt text for a digital object, keyed by language. @return array<string,string> */
    public function map(int $digitalObjectId): array
    {
        return DB::table('image_alt_text')
            ->where('digital_object_id', $digitalObjectId)
            ->pluck('alt_text', 'lang')
            ->all();
    }

    /** One language's alt text, or null. */
    public function get(int $digitalObjectId, string $lang = 'en'): ?string
    {
        $v = DB::table('image_alt_text')
            ->where('digital_object_id', $digitalObjectId)
            ->where('lang', $lang)
            ->value('alt_text');

        return ($v === null || $v === '') ? null : (string) $v;
    }

    /**
     * Upsert one (digital object, language) alt text. An empty string deletes the
     * row so coverage reflects only genuinely-authored alternatives.
     */
    public function set(int $digitalObjectId, string $lang, string $alt, ?int $userId = null): void
    {
        $lang = $lang !== '' ? $lang : 'en';
        $alt = trim($alt);
        $now = date('Y-m-d H:i:s');

        if ($alt === '') {
            DB::table('image_alt_text')
                ->where('digital_object_id', $digitalObjectId)->where('lang', $lang)->delete();

            return;
        }

        $exists = DB::table('image_alt_text')
            ->where('digital_object_id', $digitalObjectId)->where('lang', $lang)->first();

        if ($exists) {
            DB::table('image_alt_text')->where('id', $exists->id)->update([
                'alt_text' => $alt,
                'updated_by' => $userId,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('image_alt_text')->insert([
                'digital_object_id' => $digitalObjectId,
                'lang' => $lang,
                'alt_text' => $alt,
                'contributed_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /** Coverage counts over image master digital objects. @return array{total:int,with_alt:int,missing:int,percent:float} */
    public function counts(string $lang = 'en'): array
    {
        $total = (int) DB::table('digital_object')
            ->where('media_type_id', self::MEDIA_TYPE_IMAGE)
            ->where('usage_id', self::USAGE_MASTER)
            ->count();

        $withAlt = (int) DB::table('digital_object as d')
            ->join('image_alt_text as a', function ($j) use ($lang) {
                $j->on('a.digital_object_id', '=', 'd.id')->where('a.lang', '=', $lang);
            })
            ->where('d.media_type_id', self::MEDIA_TYPE_IMAGE)
            ->where('d.usage_id', self::USAGE_MASTER)
            ->whereNotNull('a.alt_text')->where('a.alt_text', '!=', '')
            ->distinct()->count('d.id');

        return [
            'total' => $total,
            'with_alt' => $withAlt,
            'missing' => max(0, $total - $withAlt),
            'percent' => $total > 0 ? round($withAlt / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * Paginated list of image masters with their current alt text (for the
     * coverage dashboard). @param array $f keys: missing(bool), q, page, per, lang
     * @return array{items:array,total:int,page:int,pages:int}
     */
    public function imageList(array $f = []): array
    {
        $lang = $f['lang'] ?? 'en';
        $base = DB::table('digital_object as d')
            ->leftJoin('image_alt_text as a', function ($j) use ($lang) {
                $j->on('a.digital_object_id', '=', 'd.id')->where('a.lang', '=', $lang);
            })
            ->leftJoin('information_object_i18n as i', function ($j) {
                $j->on('i.id', '=', 'd.object_id')->where('i.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'd.object_id')
            ->where('d.media_type_id', self::MEDIA_TYPE_IMAGE)
            ->where('d.usage_id', self::USAGE_MASTER);

        if (!empty($f['missing'])) {
            $base->where(function ($w) {
                $w->whereNull('a.alt_text')->orWhere('a.alt_text', '=', '');
            });
        }
        if (!empty($f['q'])) {
            $base->where(function ($w) use ($f) {
                $w->where('i.title', 'LIKE', '%' . $f['q'] . '%')
                    ->orWhere('d.name', 'LIKE', '%' . $f['q'] . '%');
            });
        }

        $total = (int) (clone $base)->distinct()->count('d.id');
        $page = max(1, (int) ($f['page'] ?? 1));
        $per = min(100, max(1, (int) ($f['per'] ?? 50)));

        $items = $base->groupBy('d.id', 'd.name', 'd.object_id', 'i.title', 's.slug', 'a.alt_text')
            ->orderBy('i.title')
            ->offset(($page - 1) * $per)->limit($per)
            ->get(['d.id', 'd.name', 'd.object_id', 'i.title', 's.slug', 'a.alt_text'])
            ->all();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => (int) ceil($total / max(1, $per))];
    }

    /** All image masters for one information object, with their alt-text map. @return array<int,object> */
    public function forInformationObject(int $ioId): array
    {
        $dos = DB::table('digital_object')
            ->where('object_id', $ioId)
            ->where('media_type_id', self::MEDIA_TYPE_IMAGE)
            ->where('usage_id', self::USAGE_MASTER)
            ->get(['id', 'name'])->all();

        foreach ($dos as $d) {
            $d->alt = $this->map((int) $d->id);
        }

        return $dos;
    }
}
