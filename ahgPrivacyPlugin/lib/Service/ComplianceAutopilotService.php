<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Compliance autopilot (#153, parity with heratio#1199): scan the catalogue for
 * personal data, then draft an Article 30 ROPA entry, a retention schedule and,
 * where the screen says one is needed, a DPIA, each for the data protection
 * officer to review and accept. Nothing is saved by the scan itself.
 *
 * Only findings the scanner keeps become data categories. PiiDetectionService
 * already drops identifiers that fail their checksum, so every finding counted
 * here passed its check or has none.
 *
 * The AI gateway only words the drafts (retention suggestions, DPIA narrative),
 * grounded in the category names. Whether a DPIA is needed is decided by the
 * rule screen below, never by the model. Without the gateway the drafts use
 * fixed wording. Jurisdiction-neutral throughout: the statute comes from the
 * institution's own regime.
 */
class ComplianceAutopilotService
{
    public const RETENTION_TABLE = 'ahg_retention_proposal';
    public const AUTO_ROPA_NAME = 'Archival description and access (auto-drafted)';
    public const AUTO_DPIA_NAME = 'Archival catalogue personal data (auto-drafted)';

    /** Scanner type => [category key, label]. Keys match Heratio's where they overlap. */
    private const CATEGORIES = [
        'EMAIL' => ['email', 'Email addresses'],
        'PHONE_SA' => ['phone', 'Phone numbers'],
        'PHONE_INTL' => ['phone', 'Phone numbers'],
        'CREDIT_CARD' => ['credit_card', 'Payment card numbers'],
        'BANK_ACCOUNT' => ['bank_account', 'Bank account numbers'],
        'IBAN' => ['bank_account', 'Bank account numbers'],
        'TAX_NUMBER' => ['tax_number', 'Tax numbers'],
        'KE_KRA_PIN' => ['tax_number', 'Tax numbers'],
        'AU_TFN' => ['tax_number', 'Tax numbers'],
        'PASSPORT' => ['passport', 'Passport numbers'],
    ];

    /**
     * Any other identifier type is a national identifier, UK_NHS included, as in
     * Heratio. Not "health": a 10-digit phone number passes the NHS check digit
     * about one time in eleven, and a health category would call for a DPIA.
     */
    private const NATIONAL_ID = ['national_id', 'National identifiers'];

    // Rule screen for a DPIA (Article 35(3) / WP29 high-risk triggers), as Heratio's DpiaRiskService.
    private const SPECIAL_CATEGORY_TERMS = ['health', 'medical', 'racial', 'race', 'ethnic', 'ethnicity', 'political',
        'religion', 'religious', 'philosophical', 'belief', 'genetic', 'biometric', 'sex life', 'sexual', 'orientation',
        'trade union', 'criminal', 'offence', 'conviction', 'disability'];
    private const PROFILING_TERMS = ['profil', 'scoring', 'score', 'systematic monitoring', 'monitoring',
        'automated decision', 'automated-decision', 'evaluation', 'evaluat', 'predict', 'behavioural', 'behavioral',
        'tracking', 'surveillance'];
    private const BIOMETRIC_TERMS = ['biometric', 'genetic', 'fingerprint', 'facial recognition', 'face recognition',
        'iris', 'voiceprint', 'dna'];
    private const TRIGGER_LABELS = [
        'special_category' => 'Special category data present',
        'large_scale_profiling' => 'Large-scale profiling or systematic monitoring',
        'biometric' => 'Biometric or genetic processing',
        'cross_border_non_adequate' => 'Cross-border transfer without documented safeguards',
    ];

    /** A seeded demonstration record, not a holding (same markers as Heratio's). */
    public static function isDemonstrationRecord(?string $title, ?string $text): bool
    {
        return str_starts_with(ltrim((string) $title), '[SYNTHETIC]')
            || str_contains((string) $text, 'SYNTHETIC DEMONSTRATION RECORD');
    }

    /**
     * Scan up to $limit descriptions (title and scope and content, every culture)
     * and total the findings by category.
     */
    public function scanCatalogue(int $limit = 2000): array
    {
        require_once __DIR__.'/PiiDetectionService.php';
        $scanner = new PiiDetectionService();

        $ids = DB::table('information_object_i18n')->where('id', '>', 1)
            ->where(fn ($q) => $q->whereNotNull('title')->orWhereNotNull('scope_and_content'))
            ->distinct()->orderBy('id')->limit($limit)->pluck('id')->all();

        $agg = [];
        $scanned = $withPii = $synthetic = 0;
        foreach (array_chunk($ids, 500) as $chunk) {
            $texts = [];
            $titles = [];
            foreach (DB::table('information_object_i18n')->whereIn('id', $chunk)->get(['id', 'title', 'scope_and_content']) as $r) {
                $texts[$r->id] = trim(($texts[$r->id] ?? '').' '.strip_tags((string) $r->title.' '.(string) $r->scope_and_content));
                $titles[$r->id] = $titles[$r->id] ?? (string) $r->title;
            }
            foreach ($texts as $id => $text) {
                ++$scanned;
                if ('' === $text) {
                    continue;
                }
                // Excluded here, not in the scanner: the scanner must still find
                // PII in demonstration records; only the compliance drafts skip them.
                if (self::isDemonstrationRecord($titles[$id], $text)) {
                    ++$synthetic;
                    continue;
                }
                $found = $scanner->detectPii($text)['entities'] ?? [];
                if (!$found) {
                    continue;
                }
                ++$withPii;
                $seen = [];
                foreach ($found as $f) {
                    [$key, $label] = self::CATEGORIES[$f['type']] ?? self::NATIONAL_ID;
                    $agg[$key] ??= ['type' => $key, 'label' => $label, 'count' => 0, 'records' => 0, 'samples' => []];
                    ++$agg[$key]['count'];
                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        ++$agg[$key]['records'];
                        if (count($agg[$key]['samples']) < 5) {
                            $agg[$key]['samples'][] = (int) $id;
                        }
                    }
                }
            }
        }
        $cats = array_values($agg);
        usort($cats, fn ($a, $b) => $b['count'] <=> $a['count']);

        return ['scanned' => $scanned, 'records_with_pii' => $withPii, 'categories' => $cats, 'synthetic_excluded' => $synthetic];
    }

    /** An Article 30 draft from a scan. Not saved: the administrator reviews it first. */
    public function draftRopa(array $scan): array
    {
        $cats = array_column($scan['categories'] ?? [], 'label');

        return [
            'name' => self::AUTO_ROPA_NAME,
            'purpose' => 'Cataloguing, preservation and provision of access to archival and collection records, some of which contain personal data.',
            'lawful_basis' => 'Public task / archiving in the public interest',
            'data_categories' => $cats ?: ['Personal data within records'],
            'data_subjects' => 'Individuals named or identifiable in the records (creators, correspondents, subjects of records)',
            'recipients' => 'Researchers and the public, subject to access controls and redaction',
            'retention_period' => 'Permanent, as archival records (appraisal policy)',
            'security_measures' => 'Access controls, field-level PII redaction on restricted records, audit logging.',
        ];
    }

    /** The ROPA entry made from an earlier draft, if any. */
    public function autoRopa(): ?object
    {
        return DB::table('privacy_processing_activity')->where('name', self::AUTO_ROPA_NAME)->orderByDesc('id')->first(['id', 'name', 'status']);
    }

    /**
     * One retention proposal per data category, refreshed on each run. A
     * proposal the DPO has accepted is never overwritten.
     *
     * @return array{proposals: array, source: string}
     */
    public function draftRetentionSchedule(array $scan): array
    {
        $cats = $scan['categories'] ?? [];
        if (!$cats) {
            return ['proposals' => [], 'source' => 'none'];
        }
        $suggested = $this->suggestRetention($cats);
        $source = null === $suggested ? 'heuristic' : 'llm';
        $now = date('Y-m-d H:i:s');

        foreach ($cats as $c) {
            $s = ($suggested[$c['type']] ?? null) ?: self::fallbackRetention($c['label']);
            $existing = DB::table(self::RETENTION_TABLE)->where('category', $c['type'])->first();
            if ($existing && 'accepted' === $existing->status) {
                continue;
            }
            $row = [
                'category_label' => self::clip($c['label'], 191),
                'records_affected' => (int) $c['records'],
                'retention_period' => self::clip($s['retention_period'] ?? '', 191) ?: 'Review against the applicable retention policy',
                'legal_basis' => self::clip($s['legal_basis'] ?? '', 2000) ?: null,
                'disposal_action' => self::clip($s['disposal_action'] ?? '', 191) ?: null,
                'rationale' => self::clip($s['rationale'] ?? '', 2000) ?: null,
                'source' => $source,
                'status' => 'proposed',
                'updated_at' => $now,
            ];
            if ($existing) {
                DB::table(self::RETENTION_TABLE)->where('id', $existing->id)->update($row);
            } else {
                DB::table(self::RETENTION_TABLE)->insert($row + ['category' => $c['type'], 'created_at' => $now]);
            }
        }

        return ['proposals' => $this->retentionProposals(), 'source' => $source];
    }

    public function retentionProposals(): array
    {
        return DB::table(self::RETENTION_TABLE)->orderByRaw("status = 'accepted'")->orderByDesc('records_affected')->get()->all();
    }

    public function acceptRetention(int $id, ?int $userId): bool
    {
        return DB::table(self::RETENTION_TABLE)->where('id', $id)->where('status', 'proposed')
            ->update(['status' => 'accepted', 'accepted_at' => date('Y-m-d H:i:s'), 'accepted_by' => $userId]) > 0;
    }

    /** Rule screen: does processing like this need a DPIA? */
    public static function screen(array $a): array
    {
        $hay = strtolower(implode(' ', [(string) ($a['name'] ?? ''), (string) ($a['purpose'] ?? ''),
            implode(' ', (array) ($a['data_categories'] ?? [])), (string) ($a['data_subjects'] ?? '')]));
        $has = function (array $terms) use ($hay) {
            foreach ($terms as $t) {
                if (str_contains($hay, $t)) {
                    return true;
                }
            }

            return false;
        };
        $triggers = [];
        if ($has(self::SPECIAL_CATEGORY_TERMS)) {
            $triggers[] = 'special_category';
        }
        if ($has(self::PROFILING_TERMS)) {
            $triggers[] = 'large_scale_profiling';
        }
        if ($has(self::BIOMETRIC_TERMS)) {
            $triggers[] = 'biometric';
        }
        if (!empty($a['transfers_outside']) && '' === trim((string) ($a['safeguards'] ?? ''))) {
            $triggers[] = 'cross_border_non_adequate';
        }
        $labels = array_map(fn ($t) => self::TRIGGER_LABELS[$t], $triggers);

        return ['high_risk' => (bool) $triggers, 'triggers' => $triggers, 'labels' => $labels,
            'note' => $triggers ? 'DPIA required: '.implode('; ', $labels).'.' : 'No high-risk triggers found. A DPIA is not required on screening grounds.'];
    }

    /**
     * Screen the scan; if a DPIA is needed, save or refresh one draft in the
     * DPIA register. A DPIA the DPO has moved past draft is left alone.
     */
    public function draftDpia(array $scan, ?int $userId): array
    {
        $labels = array_column($scan['categories'] ?? [], 'label');
        $screen = self::screen([
            'name' => self::AUTO_DPIA_NAME,
            'purpose' => 'Cataloguing, preservation and provision of access to archival records that contain personal data found by an automated catalogue scan.',
            'data_categories' => $labels ?: ['Personal data within records'],
            'data_subjects' => 'Individuals named or identifiable in the records',
        ]);
        $out = $screen + ['source' => 'screen', 'dpia' => null];
        if (!$screen['high_risk']) {
            return $out;
        }

        $existing = DB::table('privacy_dpia')->where('name', self::AUTO_DPIA_NAME)->orderByDesc('id')->first();
        if ($existing && DpiaService::STATUS_DRAFT !== $existing->status) {
            return ['dpia' => $existing] + $out;
        }
        $narrative = $this->suggestDpiaNarrative($labels, $screen['labels']);
        $out['source'] = null === $narrative ? 'heuristic' : 'llm';
        $narrative ??= self::fallbackDpiaNarrative($labels, $screen['labels']);

        require_once __DIR__.'/DpiaService.php';
        $svc = new DpiaService();
        $ropa = $this->autoRopa();
        $payload = $narrative + ['name' => self::AUTO_DPIA_NAME, 'status' => DpiaService::STATUS_DRAFT, 'high_risk' => 1,
            'processing_activity_id' => $existing->processing_activity_id ?? ($ropa->id ?? null)];
        $id = $existing ? (int) $existing->id : 0;
        if ($id) {
            $svc->update($id, $payload);
        } else {
            $id = $svc->create($payload, $userId);
        }
        $out['dpia'] = $svc->find($id);

        return $out;
    }

    public function autoDpia(): ?object
    {
        return DB::table('privacy_dpia')->where('name', self::AUTO_DPIA_NAME)->orderByDesc('id')->first();
    }

    /** DPO accept: the draft moves into review on the DPIA register. */
    public function acceptDpia(int $id): bool
    {
        return DB::table('privacy_dpia')->where('id', $id)->where('name', self::AUTO_DPIA_NAME)
            ->where('status', DpiaService::STATUS_DRAFT)
            ->update(['status' => DpiaService::STATUS_REVIEW, 'updated_at' => date('Y-m-d H:i:s')]) > 0;
    }

    /** @return array<string,array>|null category key => suggestion, or null when the gateway is unavailable */
    private function suggestRetention(array $cats): ?array
    {
        $list = implode("\n", array_map(fn ($c) => "- {$c['type']}: {$c['label']} ({$c['records']} records)", $cats));
        $json = $this->ask(<<<PROMPT
You are a records-management assistant helping an archive draft a defensible retention schedule.
A catalogue scan found these categories of personal data (category key: label (records affected)):
{$list}

For EACH category key propose:
- retention_period: a short, defensible period (e.g. "7 years after last contact", "Permanent - archival value")
- legal_basis: a generic basis, e.g. "the applicable data-protection retention regime and the institution's appraisal and retention policy". Do not name any country's law.
- disposal_action: one of "Secure deletion", "Anonymise", "Transfer to permanent archive", "Periodic disposal review"
- rationale: one sentence about the category named, nothing else.

Ground every suggestion only in the category names above. Do not invent record contents, names or facts.
Return strict JSON only: an object keyed by category key, each value an object with keys retention_period, legal_basis, disposal_action, rationale.
PROMPT, 'compliance.retention_schedule');
        if (!$json) {
            return null;
        }
        $out = [];
        foreach ($json as $k => $v) {
            if (is_array($v) && '' !== trim((string) ($v['retention_period'] ?? ''))) {
                $out[(string) $k] = array_map(fn ($x) => is_scalar($x) ? (string) $x : '', $v);
            }
        }

        return $out ?: null;
    }

    private function suggestDpiaNarrative(array $labels, array $triggers): ?array
    {
        $cats = $labels ? implode(', ', $labels) : 'personal data within records';
        $trigs = implode('; ', $triggers);
        $json = $this->ask(<<<PROMPT
You are a data-protection assistant helping an archive draft a Data Protection Impact Assessment for processing already found to be high risk.
A catalogue scan found these categories of personal data: {$cats}.
The high-risk triggers are: {$trigs}.

Provide:
- description: one or two sentences on the processing and the personal data involved.
- necessity_proportionality: why the processing is necessary and proportionate for an archive's public-interest task.
- risks_to_subjects: the concrete risks to data subjects from these categories and triggers.
- measures_to_mitigate: practical measures (access controls, field-level redaction, retention limits, audit logging).
- residual_risks: the risk left after mitigation, stated plainly.

Ground every statement only in the categories and triggers above. Do not invent record contents, names or facts. Refer to "the applicable data-protection regime" and do not name any country's law.
Return strict JSON only: an object with keys description, necessity_proportionality, risks_to_subjects, measures_to_mitigate, residual_risks.
PROMPT, 'compliance.dpia_draft');
        if (!$json || ('' === trim((string) ($json['risks_to_subjects'] ?? '')) && '' === trim((string) ($json['measures_to_mitigate'] ?? '')))) {
            return null;
        }
        $fallback = self::fallbackDpiaNarrative($labels, $triggers);
        foreach ($fallback as $k => $v) {
            $t = is_scalar($json[$k] ?? null) ? trim((string) $json[$k]) : '';
            $fallback[$k] = '' === $t ? $v : $t;
        }

        return $fallback;
    }

    /** One gateway call; the first JSON object of the answer, or null. */
    private function ask(string $prompt, string $purpose): ?array
    {
        try {
            $gw = \AtomFramework\Services\AI\AiGatewayClient::fromSettings();
            if (!$gw->isConfigured()) {
                return null;
            }
            $r = $gw->generate($prompt, null, ['temperature' => 0.1, 'think' => false, 'timeout' => 120]);
            if (empty($r['success'])) {
                error_log("[ahgPrivacy] autopilot {$purpose}: ".($r['error'] ?? 'gateway failed'));

                return null;
            }
            $s = (string) $r['text'];
            $a = strpos($s, '{');
            $b = strrpos($s, '}');
            $json = false !== $a && false !== $b && $b > $a ? json_decode(substr($s, $a, $b - $a + 1), true) : null;

            return is_array($json) ? $json : null;
        } catch (\Throwable $e) {
            error_log("[ahgPrivacy] autopilot {$purpose}: ".$e->getMessage());

            return null;
        }
    }

    private static function fallbackRetention(string $label): array
    {
        return [
            'retention_period' => 'Review against the applicable retention policy',
            'legal_basis' => "The applicable data-protection retention regime and the institution's appraisal and retention policy.",
            'disposal_action' => 'Periodic disposal review',
            'rationale' => "Placeholder for \"{$label}\": set the period and basis from the institution's own regime.",
        ];
    }

    private static function fallbackDpiaNarrative(array $labels, array $triggers): array
    {
        $cats = $labels ? implode(', ', $labels) : 'personal data within records';

        return [
            'description' => "Processing of archival records containing personal data ({$cats}) found by an automated catalogue scan.",
            'necessity_proportionality' => "The processing is necessary for the institution's archiving and public-access task. Access controls, redaction of restricted records and retention limits keep it proportionate. Confirm both against the applicable data-protection regime.",
            'risks_to_subjects' => 'High-risk triggers: '.implode('; ', $triggers).'. Risks to data subjects include unauthorised disclosure, re-identification and use beyond the archival purpose.',
            'measures_to_mitigate' => 'Field-level PII redaction on restricted records, role-based access controls, audit logging of access and edits, and a defensible retention schedule.',
            'residual_risks' => 'With these measures the residual risk is appropriate for public-interest archiving; the DPO should confirm this against the applicable regime before sign-off.',
        ];
    }

    private static function clip(?string $s, int $max): string
    {
        return mb_substr(trim((string) $s), 0, $max);
    }
}
