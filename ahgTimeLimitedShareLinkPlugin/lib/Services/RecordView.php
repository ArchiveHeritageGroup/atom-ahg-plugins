<?php

declare(strict_types=1);

namespace AhgShareLink\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * What a share-link recipient sees of the record: title, reference code, level,
 * repository, dates, creators, extent, scope and content, and the reference image
 * unless the record's rights disallow display.
 *
 * The page showed only title, reference code and scope. The rights check is the
 * same rule the API and MCP server apply (AtoM granted_right "Display" act, and
 * ahgRightsPlugin rights_grant "render"), written here because a plugin keeps its
 * own code.
 */
class RecordView
{
    private const RIGHT_RELATION_TYPE_ID = 168;
    private const CREATION_EVENT_ID = 111;
    private const REFERENCE_USAGE_ID = 141;

    public static function build(int $ioId, string $culture): array
    {
        $i18n = fn (string $table, int $id, string $col) => DB::table($table)->where('id', $id)
            ->orderByRaw('culture = ? DESC', [$culture])->orderByRaw("culture = 'en' DESC")->value($col);

        $io = DB::table('information_object')->where('id', $ioId)->first(['id', 'identifier', 'level_of_description_id', 'lft', 'rgt']);

        $repository = null;
        if ($io) {
            $repoId = DB::table('information_object')->where('lft', '<=', $io->lft)->where('rgt', '>=', $io->rgt)
                ->whereNotNull('repository_id')->orderByDesc('lft')->value('repository_id');
            $repository = $repoId ? $i18n('actor_i18n', (int) $repoId, 'authorized_form_of_name') : null;
        }

        $dates = [];
        $creators = [];
        foreach (DB::table('event')->where('object_id', $ioId)->orderBy('id')->get(['id', 'type_id', 'actor_id']) as $ev) {
            $date = $i18n('event_i18n', (int) $ev->id, 'date');
            $type = $ev->type_id ? $i18n('term_i18n', (int) $ev->type_id, 'name') : null;
            if ($date) {
                $dates[] = trim($date . ($type ? ' (' . $type . ')' : ''));
            }
            if ($ev->actor_id && self::CREATION_EVENT_ID === (int) $ev->type_id) {
                $name = $i18n('actor_i18n', (int) $ev->actor_id, 'authorized_form_of_name');
                if ($name && !in_array($name, $creators, true)) {
                    $creators[] = $name;
                }
            }
        }

        return [
            'title' => $i18n('information_object_i18n', $ioId, 'title') ?: ('#' . $ioId),
            'identifier' => $io->identifier ?? null,
            'level' => ($io && $io->level_of_description_id) ? $i18n('term_i18n', (int) $io->level_of_description_id, 'name') : null,
            'repository' => $repository,
            'dates' => $dates,
            'creators' => $creators,
            'extent' => $i18n('information_object_i18n', $ioId, 'extent_and_medium'),
            'scope' => $i18n('information_object_i18n', $ioId, 'scope_and_content'),
            'image' => self::displayDisallowed($ioId) ? null : self::referenceImage($ioId),
            'imageWithheld' => self::displayDisallowed($ioId),
        ];
    }

    /** The reference derivative of the master file, as a site-relative URL. */
    private static function referenceImage(int $ioId): ?string
    {
        $master = DB::table('digital_object')->where('object_id', $ioId)->whereNull('parent_id')->first(['id', 'path', 'name', 'mime_type']);
        if (!$master) {
            return null;
        }
        $ref = DB::table('digital_object')->where('parent_id', $master->id)->where('usage_id', self::REFERENCE_USAGE_ID)->first(['path', 'name']);
        if ($ref) {
            return rtrim((string) $ref->path, '/') . '/' . $ref->name;
        }
        // A small image master with no derivative can be shown as it is; never a PDF or video.
        return str_starts_with((string) $master->mime_type, 'image/') ? rtrim((string) $master->path, '/') . '/' . $master->name : null;
    }

    /** Does a current right on the record disallow display (render)? */
    public static function displayDisallowed(int $ioId): bool
    {
        $today = date('Y-m-d');
        $current = fn ($q, string $p = '') => $q
            ->where(fn ($w) => $w->whereNull($p . 'start_date')->orWhere($p . 'start_date', '<=', $today))
            ->where(fn ($w) => $w->whereNull($p . 'end_date')->orWhere($p . 'end_date', '>=', $today));
        try {
            $atom = DB::table('relation as rel')->join('rights as r', 'r.id', '=', 'rel.object_id')
                ->join('granted_right as g', 'g.rights_id', '=', 'r.id')
                ->join('term_i18n as t', fn ($j) => $j->on('t.id', '=', 'g.act_id')->where('t.culture', '=', 'en'))
                ->where('rel.subject_id', $ioId)->where('rel.type_id', self::RIGHT_RELATION_TYPE_ID)
                ->where('g.restriction', 0)->whereRaw('LOWER(t.name) = ?', ['display']);
            $current($atom, 'r.');
            $current($atom, 'g.');
            if ($atom->exists()) {
                return true;
            }
        } catch (\Throwable $e) {
        }
        try {
            $ahg = DB::table('rights_record as rr')->join('rights_grant as g', 'g.rights_record_id', '=', 'rr.id')
                ->where('rr.object_id', $ioId)->whereRaw('LOWER(g.act) = ?', ['render'])->whereRaw('LOWER(g.restriction) = ?', ['disallow']);
            $current($ahg, 'rr.');
            $current($ahg, 'g.');

            return $ahg->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
