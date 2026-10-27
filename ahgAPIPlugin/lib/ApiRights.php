<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * A description's rights and Local Contexts labels for the REST API, in the
 * shape OpenRiC Workspace uses, so a client (the AHG Archives MCP) reads both
 * the same way:
 *
 *   ['rules' => [['statement' => {iri, code, label}|null, 'licence' => ...|null,
 *                 'holder' => {name, iri}|null, 'starts_on', 'ends_on', 'grants' => []]],
 *    'labels' => [['iri', 'code', 'label', 'source_code']]]
 *
 * Read only, from the tables of ahgRightsPlugin, ahgExtendedRightsPlugin and
 * ahgICIPPlugin, each only when present (AhgDb::hasOptionalTable). Only rights in
 * force today. Holders are given to staff only: a holder is often a donor.
 *
 * Labels carry their Local Contexts code (tk_s, tk_co, ...), worked out from the
 * label's Local Contexts address, then its English name - never from the stored
 * code: ahgRightsPlugin stores "TK-SS" for Secret/Sacred, while tk_ss is
 * Local Contexts' Seasonal. The stored code is kept as source_code.
 */
class ApiRights
{
    /** Local Contexts label address slug => code (localcontexts.org/label/<slug>/). */
    private const LC_CODES = [
        'tk-attribution' => 'tk_a', 'tk-clan' => 'tk_cl', 'tk-family' => 'tk_f', 'tk-multiple-communities' => 'tk_mc',
        'tk-non-commercial' => 'tk_nc', 'tk-outreach' => 'tk_o', 'tk-secret-sacred' => 'tk_s', 'tk-verified' => 'tk_v',
        'tk-non-verified' => 'tk_nv', 'tk-culturally-sensitive' => 'tk_cs', 'tk-community-voice' => 'tk_cv',
        'tk-community-use-only' => 'tk_co', 'tk-women-restricted' => 'tk_wr', 'tk-women-general' => 'tk_wg',
        'tk-men-restricted' => 'tk_mr', 'tk-men-general' => 'tk_mg', 'tk-seasonal' => 'tk_ss', 'tk-open-to-collaboration' => 'tk_cb',
        'bc-provenance' => 'bc_p', 'bc-multiple-communities' => 'bc_mc', 'bc-clan' => 'bc_cl', 'bc-consent-verified' => 'bc_cv',
        'bc-consent-non-verified' => 'bc_cnv', 'bc-research-use' => 'bc_r', 'bc-outreach' => 'bc_o', 'bc-non-commercial' => 'bc_cnc',
        'bc-open-to-collaboration' => 'bc_cb',
    ];

    public static function forObject(int $objectId, bool $staff, string $culture = 'en'): array
    {
        $rules = [];
        $labels = [];
        $today = date('Y-m-d');
        $has = fn (string $t) => \AhgCore\Core\AhgDb::hasOptionalTable($t);

        // Rights statements set directly on the description.
        if ($has('object_rights_statement') && $has('rights_statement')) {
            foreach (DB::table('object_rights_statement')->where('object_id', $objectId)->pluck('rights_statement_id') as $sid) {
                if ($s = self::statement((int) $sid, $culture)) {
                    $rules[] = self::rule($s, null, null, null, null);
                }
            }
        }

        // Extended rights: statement, licence, holder and dates; its TK labels.
        if ($has('extended_rights')) {
            $rows = DB::table('extended_rights')->where('object_id', $objectId)
                ->where(fn ($q) => $q->whereNull('rights_date')->orWhere('rights_date', '<=', $today))
                ->where(fn ($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', $today))
                ->get();
            foreach ($rows as $r) {
                $holder = $staff && $r->rights_holder ? ['name' => $r->rights_holder, 'iri' => $r->rights_holder_uri ?: null] : null;
                $rules[] = self::rule(
                    $r->rights_statement_id ? self::statement((int) $r->rights_statement_id, $culture) : null,
                    $r->creative_commons_license_id ? self::licence((int) $r->creative_commons_license_id) : null,
                    $holder,
                    $r->rights_date,
                    $r->expiry_date
                );
                if ($has('extended_rights_tk_label') && $has('rights_tk_label')) {
                    $q = DB::table('extended_rights_tk_label as x')->join('rights_tk_label as l', 'l.id', '=', 'x.tk_label_id')
                        ->where('x.extended_rights_id', $r->id)->select('l.id', 'l.code', 'l.uri');
                    foreach ($q->get() as $l) {
                        $name = $has('rights_tk_label_i18n')
                            ? DB::table('rights_tk_label_i18n')->where('id', $l->id)->where('culture', 'en')->value('name') : null;
                        $labels[] = self::label($l->uri, $name, $l->code);
                    }
                }
            }
        }

        // ICIP plugin labels, already in Local Contexts codes.
        if ($has('icip_tk_label') && $has('icip_tk_label_type')) {
            $q = DB::table('icip_tk_label as x')->join('icip_tk_label_type as t', 't.id', '=', 'x.label_type_id')
                ->where('x.information_object_id', $objectId)->select('t.code', 't.name', 't.local_contexts_url');
            foreach ($q->get() as $l) {
                $labels[] = self::label($l->local_contexts_url, $l->name, $l->code);
            }
        }

        // Holders recorded as donors (staff only).
        if ($staff && $has('object_rights_holder')) {
            foreach (DB::table('object_rights_holder as h')->leftJoin('actor_i18n as a', function ($j) use ($culture) {
                $j->on('a.id', '=', 'h.donor_id')->where('a.culture', '=', $culture);
            })->where('h.object_id', $objectId)->pluck('a.authorized_form_of_name') as $name) {
                if ($name) {
                    $rules[] = self::rule(null, null, ['name' => $name, 'iri' => null], null, null);
                }
            }
        }

        $seen = [];
        $labels = array_values(array_filter($labels, function ($l) use (&$seen) {
            return !isset($seen[$l['code']]) && ($seen[$l['code']] = true);
        }));

        return ['rules' => $rules, 'labels' => $labels];
    }

    /** The Local Contexts code of a label, from its address, then its English name. */
    public static function localContextsCode(?string $uri, ?string $name, string $sourceCode): string
    {
        if ($uri && preg_match('#localcontexts\.org/label/([a-z-]+)/?#', $uri, $m) && isset(self::LC_CODES[$m[1]])) {
            return self::LC_CODES[$m[1]];
        }
        $slug = trim(preg_replace('/[^a-z]+/', '-', strtolower(str_replace('/', '-', (string) $name))), '-');
        if (isset(self::LC_CODES[$slug])) {
            return self::LC_CODES[$slug];
        }

        // Not a Local Contexts label we know: keep it visible, never mistaken for one.
        return 'local:'.$sourceCode;
    }

    private static function label(?string $uri, ?string $name, string $sourceCode): array
    {
        $code = self::localContextsCode($uri, $name, $sourceCode);
        $known = 0 !== strpos($code, 'local:');

        return [
            'iri' => $known ? 'https://localcontexts.org/label/'.array_search($code, self::LC_CODES, true).'/' : ($uri ?: null),
            'code' => $code,
            'label' => $name ?: $sourceCode,
            'source_code' => $sourceCode,
        ];
    }

    private static function rule(?array $statement, ?array $licence, ?array $holder, $starts, $ends): array
    {
        return ['statement' => $statement, 'licence' => $licence, 'holder' => $holder,
            'starts_on' => $starts ?: null, 'ends_on' => $ends ?: null, 'grants' => (object) []];
    }

    private static function statement(int $id, string $culture): ?array
    {
        $s = DB::table('rights_statement as s')
            ->leftJoin('rights_statement_i18n as i', fn ($j) => $j->on('i.rights_statement_id', '=', 's.id')->where('i.culture', '=', $culture))
            ->where('s.id', $id)->select('s.code', 's.uri', 'i.name')->first();

        return $s ? ['iri' => $s->uri, 'code' => $s->code, 'label' => $s->name ?: $s->code] : null;
    }

    private static function licence(int $id): ?array
    {
        if (!\AhgCore\Core\AhgDb::hasOptionalTable('rights_cc_license')) {
            return null;
        }
        $l = DB::table('rights_cc_license')->where('id', $id)->select('code', 'version', 'uri')->first();
        if (!$l) {
            return null;
        }
        // The stored code may already end in its version ("by-nc-nd-4.0"); add it only once.
        $code = preg_match('/^cc[-_ ]?0/i', $l->code) ? 'CC0' : 'CC-'.strtoupper(preg_replace('/^cc[-_ ]?/i', '', $l->code));
        if ($l->version && !preg_match('/-'.preg_quote($l->version, '/').'$/i', $code)) {
            $code .= '-'.$l->version;
        }

        return ['iri' => $l->uri, 'code' => $code, 'label' => str_replace('-', ' ', $code)];
    }
}
