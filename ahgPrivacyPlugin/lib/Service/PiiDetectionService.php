<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * PII Detection Service
 *
 * Extends NER with regex-based PII detection for:
 * - ID Numbers (SA ID, Nigerian NIN, Passport)
 * - Email addresses
 * - Phone numbers
 * - Financial data (bank accounts, tax numbers)
 *
 * Integrates with:
 * - ahgAIPlugin (entity extraction, translation, summarization)
 * - ahgPrivacyPlugin (data inventory)
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class PiiDetectionService
{
    /**
     * PII patterns for regex detection
     */
    private static array $patterns = [
        // South African ID: 13 digits, YYMMDD + gender + citizenship + checksum
        'SA_ID' => '/\b(\d{2})(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])(\d{4})(\d)(\d)(\d)\b/',

        // Nigerian NIN: 11 digits
        'NG_NIN' => '/\b\d{11}\b/',

        // Passport: Various formats (2 letters + 6-9 digits or similar)
        'PASSPORT' => '/\b[A-Z]{1,2}\d{6,9}\b/i',

        // Email addresses
        'EMAIL' => '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/',

        // Phone numbers (SA format: 0XX XXX XXXX or +27 XX XXX XXXX)
        'PHONE_SA' => '/\b(?:\+27|0)[1-9]\d{1,2}[\s-]?\d{3}[\s-]?\d{4}\b/',

        // International phone (generic)
        'PHONE_INTL' => '/\b\+\d{1,3}[\s-]?\d{2,4}[\s-]?\d{3,4}[\s-]?\d{3,4}\b/',

        // Bank account (SA: typically 10-11 digits)
        'BANK_ACCOUNT' => '/\b\d{10,11}\b/',

        // Tax number (SA: 10 digits starting with specific prefixes)
        'TAX_NUMBER' => '/\b[0-9]{10}\b/',

        // Credit card (basic Luhn-eligible patterns)
        'CREDIT_CARD' => '/\b(?:\d{4}[\s-]?){3}\d{4}\b/',

        // ── Jurisdiction identifiers ────────────────────────────────────
        // Every mostly-numeric pattern below is either checksum validated or
        // context gated. A bare run of digits scored as a national ID is the
        // defect this class already carried once with NG_NIN.

        // UK National Insurance number: 2 letters (with excluded prefixes),
        // 6 digits, 1 suffix letter A-D. Distinctive enough to stand alone.
        'UK_NINO' => '/\b[A-CEGHJ-PR-TW-Z][A-CEGHJ-NPR-TW-Z]\s?\d{2}\s?\d{2}\s?\d{2}\s?[A-D]\b/i',

        // UK NHS number: 10 digits, mod 11 check digit. Validated.
        'UK_NHS' => '/\b\d{3}[\s-]?\d{3}[\s-]?\d{4}\b/',

        // Canadian Social Insurance Number: 9 digits, Luhn. Validated.
        'CA_SIN' => '/\b\d{3}[\s-]\d{3}[\s-]\d{3}\b/',

        // US Social Security Number. Separators are required deliberately: a
        // bare 9-digit run is far too common in archival metadata to claim.
        'US_SSN' => '/\b\d{3}-\d{2}-\d{4}\b/',

        // Kenyan national ID: 7-8 digits, no checksum exists. Context gated.
        'KE_ID' => '/\b\d{7,8}\b/',

        // Kenyan KRA PIN: letter, 9 digits, letter. Context gated.
        'KE_KRA_PIN' => '/\b[A-Z]\d{9}[A-Z]\b/i',

        // Brazilian CPF: 11 digits with two mod 11 check digits. Validated.
        'BR_CPF' => '/\b\d{3}\.?\d{3}\.?\d{3}-?\d{2}\b/',

        // Australian Tax File Number: 8-9 digits, weighted mod 11. Validated.
        'AU_TFN' => '/\b\d{3}\s?\d{3}\s?\d{2,3}\b/',

        // Singapore NRIC/FIN: prefix letter, 7 digits, suffix letter. The
        // suffix is a checksum, but the letter tables are not implemented here,
        // so this is treated as format-only and never counts as validated.
        'SG_NRIC' => '/\b[STFGM]\d{7}[A-Z]\b/i',

        // IBAN: country, 2 check digits, up to 30 alphanumerics, mod 97.
        // International rather than tied to one jurisdiction. Validated.
        // Two alternatives: the compact form, and the standard printed form in
        // groups of four. A single permissive run that allowed spaces would keep
        // swallowing following words in prose, and the checksum would then reject
        // the whole thing, losing the IBAN rather than finding it.
        'IBAN' => '/\b[A-Z]{2}\d{2}(?:[A-Z0-9]{11,30}|(?:\s[A-Z0-9]{4}){2,7}(?:\s[A-Z0-9]{1,4})?)\b/i',
    ];

    /**
     * PII category mappings (to privacy_data_inventory.data_type)
     */
    private static array $categoryMap = [
        'PERSON' => 'personal',
        'SA_ID' => 'personal',
        'NG_NIN' => 'personal',
        'PASSPORT' => 'personal',
        'EMAIL' => 'personal',
        'PHONE_SA' => 'personal',
        'PHONE_INTL' => 'personal',
        'BANK_ACCOUNT' => 'financial',
        'TAX_NUMBER' => 'financial',
        'CREDIT_CARD' => 'financial',
        'UK_NINO' => 'personal',
        'UK_NHS' => 'personal',
        'CA_SIN' => 'personal',
        'US_SSN' => 'personal',
        'KE_ID' => 'personal',
        'KE_KRA_PIN' => 'financial',
        'BR_CPF' => 'personal',
        'AU_TFN' => 'financial',
        'SG_NRIC' => 'personal',
        'IBAN' => 'financial',
        'DATE' => 'personal',
        'ORG' => 'personal',
        'GPE' => 'personal',
        // ISAD Access Point types
        'ISAD_SUBJECT' => 'personal',
        'ISAD_PLACE' => 'personal',
        'ISAD_NAME' => 'personal',
        'ISAD_DATE' => 'personal',
    ];

    /**
     * Risk levels for PII types
     */
    private static array $riskLevels = [
        'SA_ID' => 'high',
        'NG_NIN' => 'high',
        'PASSPORT' => 'high',
        'CREDIT_CARD' => 'critical',
        'BANK_ACCOUNT' => 'high',
        'TAX_NUMBER' => 'high',
        'UK_NINO' => 'high',
        'UK_NHS' => 'high',
        'CA_SIN' => 'high',
        'US_SSN' => 'high',
        'KE_ID' => 'high',
        'KE_KRA_PIN' => 'high',
        'BR_CPF' => 'high',
        'AU_TFN' => 'high',
        'SG_NRIC' => 'high',
        'IBAN' => 'high',
        'EMAIL' => 'medium',
        'PHONE_SA' => 'medium',
        'PHONE_INTL' => 'medium',
        'PERSON' => 'medium',
        'DATE' => 'low',
        'ORG' => 'low',
        'GPE' => 'low',
        // ISAD Access Point types
        'ISAD_SUBJECT' => 'low',
        'ISAD_PLACE' => 'low',
        'ISAD_NAME' => 'medium',
        'ISAD_DATE' => 'low',
    ];

    /**
     * Get taxonomy ID for subjects (uses AhgTaxonomy for dynamic lookup)
     */
    private static function getSubjectsTaxonomyId(): int
    {
        return \AhgCore\Taxonomy\AhgTaxonomy::getId('subjects') ?? 35;
    }

    /**
     * Get taxonomy ID for places (uses AhgTaxonomy for dynamic lookup)
     */
    private static function getPlacesTaxonomyId(): int
    {
        return \AhgCore\Taxonomy\AhgTaxonomy::getId('places') ?? 42;
    }

    // ── Pure decision logic ─────────────────────────────────────────────
    //
    // These statics take primitives and return primitives: no database, no
    // settings, no Symfony. The instance methods below keep the I/O and call
    // these for the judgement. That split exists because the severity-accounting
    // bugs this class carried (a whole risk band silently discarded) are pure
    // logic, and pure logic is testable without standing up a harness.

    /**
     * The risk bands, in descending severity. SINGLE SOURCE OF TRUTH.
     *
     * This constant exists because the bands were previously enumerated in four
     * separate places - the detectPii() summary initialiser, the scanObject()
     * initialiser, two hardcoded aggregation lists, and an isset() guard - which
     * were free to disagree, and did. 'critical' was missing from all of them, so
     * a detected credit card (the only critical type) incremented 'total' and
     * nothing else: it scored zero and never reached the data inventory, while an
     * unvalidated ten-digit reference number scored 40. Anything that enumerates
     * bands must derive from here.
     */
    public const RISK_BANDS = ['critical', 'high', 'medium', 'low'];

    /** Points contributed to the 0-100 risk score by one finding in each band. */
    public const RISK_WEIGHTS = ['critical' => 30, 'high' => 20, 'medium' => 5, 'low' => 1];

    /** A zeroed summary carrying every band. */
    public static function emptySummary(): array
    {
        $summary = ['total' => 0];
        foreach (self::RISK_BANDS as $band) {
            $summary[$band . '_risk'] = 0;
        }

        return $summary;
    }

    /**
     * Count one finding of $riskLevel into $summary.
     *
     * An unrecognised band is counted as medium rather than dropped - the
     * previous isset() guard discarded such findings silently, which is how a
     * whole band went missing unnoticed. Definition-time consistency is asserted
     * by the unit tests instead, where a mismatch is loud.
     */
    public static function countEntity(array $summary, string $riskLevel): array
    {
        $summary += self::emptySummary();
        $summary['total'] = (int) $summary['total'] + 1;

        $key = $riskLevel . '_risk';
        if (!array_key_exists($key, $summary)) {
            $key = 'medium_risk';
        }
        $summary[$key] = (int) $summary[$key] + 1;

        return $summary;
    }

    /** Add two summaries band-by-band, preserving bands present in either. */
    public static function mergeSummary(array $base, array $add): array
    {
        $out = $base + self::emptySummary();
        foreach ($add as $key => $value) {
            $out[$key] = (int) ($out[$key] ?? 0) + (int) $value;
        }

        return $out;
    }

    /** Overall risk score (0-100), weighted across every band. */
    public static function calculateRiskScore(array $summary): int
    {
        $score = 0;
        foreach (self::RISK_WEIGHTS as $band => $weight) {
            $score += (int) ($summary[$band . '_risk'] ?? 0) * $weight;
        }

        return min($score, 100);
    }

    /** True when the summary carries any finding in a high or critical band. */
    public static function isHighRisk(array $summary): bool
    {
        return (int) ($summary['high_risk'] ?? 0) > 0
            || (int) ($summary['critical_risk'] ?? 0) > 0;
    }

    /**
     * True when at least one finding is severe AND validated.
     *
     * Gate for anything that PERSISTS AS A COMPLIANCE ASSERTION. A privacy data
     * inventory entry declares that this record contains a category of personal
     * data; a pattern-only match - an eleven-digit accession number matching the
     * NIN shape, say - must never manufacture that declaration. Unvalidated
     * findings are still surfaced to the reviewer, they just do not speak for the
     * institution.
     *
     * @param array<int,array<string,mixed>> $entities
     */
    public static function hasReportableFinding(array $entities): bool
    {
        foreach ($entities as $entity) {
            if (empty($entity['validated'])) {
                continue;
            }
            if (in_array($entity['risk_level'] ?? '', ['high', 'critical'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Drop findings whose span is claimed by a more authoritative detector.
     *
     * Two defects motivate this. BANK_ACCOUNT (\d{10,11}) and TAX_NUMBER
     * ([0-9]{10}) are in a subset relationship, so every ten-digit number was
     * emitted twice, both 'high' - 40 risk points from one number; PHONE_SA and
     * PHONE_INTL collided the same way on +27 numbers. Beyond double-counting,
     * an overlap resolved the wrong way MISCLASSIFIES: a broad detector can
     * swallow a span a specific one should have claimed and relabel it as a more
     * severe category of data than it is.
     *
     * Authority order: validated beats unvalidated, then type precedence, then
     * the longer match, then confidence.
     *
     * @param array<int,array<string,mixed>> $candidates
     * @return array<int,array<string,mixed>>
     */
    public static function resolveOverlaps(array $candidates): array
    {
        $kept = [];
        foreach ($candidates as $candidate) {
            $superseded = false;
            foreach ($candidates as $other) {
                if ($candidate === $other || !self::spansOverlap($candidate, $other)) {
                    continue;
                }
                if (self::authorityOf($other) > self::authorityOf($candidate)) {
                    $superseded = true;
                    break;
                }
            }
            if (!$superseded) {
                $kept[] = $candidate;
            }
        }

        return $kept;
    }

    /** @param array<string,mixed> $a @param array<string,mixed> $b */
    private static function spansOverlap(array $a, array $b): bool
    {
        $aStart = (int) ($a['position'] ?? 0);
        $bStart = (int) ($b['position'] ?? 0);

        return $aStart < $bStart + (int) ($b['length'] ?? 0)
            && $bStart < $aStart + (int) ($a['length'] ?? 0);
    }

    /**
     * Specificity of each detector, used to resolve overlapping spans. Higher
     * wins. A checksum-backed type outranks a shape-only one.
     */
    private const TYPE_PRECEDENCE = [
        'SA_ID' => 100, 'SG_NRIC' => 98, 'UK_NINO' => 97, 'BR_CPF' => 96,
        'IBAN' => 95, 'AU_TFN' => 94, 'CA_SIN' => 93, 'UK_NHS' => 92,
        'US_SSN' => 91, 'CREDIT_CARD' => 90, 'EMAIL' => 80, 'KE_KRA_PIN' => 70,
        'NG_NIN' => 60, 'KE_ID' => 58, 'PASSPORT' => 55, 'TAX_NUMBER' => 50,
        'BANK_ACCOUNT' => 45, 'PHONE_SA' => 40, 'PHONE_INTL' => 30,
    ];

    /** Sort key for overlap resolution; larger is more authoritative. */
    private static function authorityOf(array $candidate): float
    {
        return (empty($candidate['validated']) ? 0.0 : 1000000.0)
            + (float) (self::TYPE_PRECEDENCE[$candidate['type'] ?? ''] ?? 0) * 1000.0
            + (float) ($candidate['length'] ?? 0) * 10.0
            + (float) ($candidate['confidence'] ?? 0);
    }

    /** Luhn check over the digits of a value. */
    public static function passesLuhn(string $value): bool
    {
        $digits = preg_replace('/\D/', '', $value);
        if (null === $digits || strlen($digits) < 2) {
            return false;
        }
        $sum = 0;
        $double = false;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $digit = (int) $digits[$i];
            if ($double) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
            $double = !$double;
        }

        return $sum % 10 === 0;
    }

    /** UK NHS number: 10 digits, weighted mod 11 check digit. */
    public static function passesNhsCheck(string $value): bool
    {
        $d = preg_replace('/\\D/', '', $value);
        if (10 !== strlen((string) $d)) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $d[$i] * (10 - $i);
        }
        $check = 11 - ($sum % 11);
        if (11 === $check) {
            $check = 0;
        }
        if (10 === $check) {
            return false;
        }

        return $check === (int) $d[9];
    }

    /** Brazilian CPF: 11 digits with two mod 11 check digits. */
    public static function passesCpfCheck(string $value): bool
    {
        $d = preg_replace('/\\D/', '', $value);
        if (11 !== strlen((string) $d) || preg_match('/^(\\d)\\1{10}$/', $d)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $d[$i] * (($t + 1) - $i);
            }
            $r = $sum % 11;
            $check = $r < 2 ? 0 : 11 - $r;
            if ($check !== (int) $d[$t]) {
                return false;
            }
        }

        return true;
    }

    /** Australian Tax File Number: weighted sum divisible by 11. */
    public static function passesTfnCheck(string $value): bool
    {
        $d = preg_replace('/\\D/', '', $value);
        $len = strlen((string) $d);
        if (8 !== $len && 9 !== $len) {
            return false;
        }
        $weights = [1, 4, 3, 7, 5, 8, 6, 9, 10];
        $sum = 0;
        for ($i = 0; $i < $len; $i++) {
            $sum += (int) $d[$i] * $weights[$i];
        }

        return 0 === $sum % 11;
    }

    /** IBAN: rearrange, letters to digits, mod 97 must equal 1. */
    public static function passesIbanCheck(string $value): bool
    {
        $v = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $value));
        if (strlen($v) < 15 || strlen($v) > 34) {
            return false;
        }
        $rearranged = substr($v, 4) . substr($v, 0, 4);
        $numeric = '';
        foreach (str_split($rearranged) as $ch) {
            $numeric .= ctype_alpha($ch) ? (string) (ord($ch) - 55) : $ch;
        }
        // mod 97 in chunks, because the number exceeds native integer range.
        $remainder = 0;
        foreach (str_split($numeric, 7) as $chunk) {
            $remainder = (int) (((string) $remainder . $chunk) % 97);
        }

        return 1 === $remainder;
    }

    /**
     * US SSN structural rules: area not 000/666/900-999, group not 00, serial
     * not 0000. These exclusions are real but they are not a checksum, so a
     * passing value is still reported as unvalidated and cannot on its own
     * create a compliance assertion.
     */
    public static function isPlausibleSsn(string $value): bool
    {
        if (!preg_match('/^(\\d{3})-(\\d{2})-(\\d{4})$/', trim($value), $m)) {
            return false;
        }
        $area = (int) $m[1];

        return !(0 === $area || 666 === $area || $area >= 900)
            && '00' !== $m[2] && '0000' !== $m[3];
    }

    /**
     * Select detection patterns for a set of jurisdiction codes.
     *
     * Universal patterns always apply; jurisdiction-specific ones apply when
     * their jurisdiction is among $codes. An EMPTY $codes means "no jurisdiction
     * configured", which returns EVERY pattern - never none. A configured
     * jurisdiction for which no specific pattern exists likewise widens to the
     * universal set rather than narrowing to nothing. Silently detecting nothing
     * while reporting success is the worst available outcome for a privacy scan.
     *
     * @param array<int,string> $codes
     * @return array<string,string> type => regex
     */
    public static function patternsForJurisdictions(array $codes): array
    {
        if (empty($codes)) {
            return self::$patterns;
        }
        $codes = array_map('strtolower', $codes);

        $selected = [];
        foreach (self::$patterns as $type => $pattern) {
            $required = self::$jurisdictionPatterns[$type] ?? null;
            if (null === $required || array_intersect($required, $codes)) {
                $selected[$type] = $pattern;
            }
        }

        return empty($selected) ? self::$patterns : $selected;
    }

    /** Per-request cache of the installed jurisdiction codes. */
    private static ?array $installedJurisdictions = null;

    /**
     * Codes of the jurisdictions installed on this instance.
     *
     * Fails OPEN by design: a missing or unreadable registry returns an empty
     * list, which patternsForJurisdictions() treats as "no jurisdiction
     * configured" and answers with every pattern. Detection breadth must never
     * be reduced by an error reading configuration.
     */
    public static function installedJurisdictionCodes(): array
    {
        if (null !== self::$installedJurisdictions) {
            return self::$installedJurisdictions;
        }

        try {
            self::$installedJurisdictions = DB::table('privacy_jurisdiction_registry')
                ->where('is_installed', 1)
                ->pluck('code')
                ->all();
        } catch (\Throwable $e) {
            self::$installedJurisdictions = [];
        }

        return self::$installedJurisdictions;
    }

    /**
     * The identifier pattern types implemented for one jurisdiction.
     *
     * Universal patterns (email, cards, IBAN, international dialling) are not
     * listed: they run everywhere and would make every jurisdiction look covered.
     *
     * @return array<int,string>
     */
    public static function patternTypesForJurisdiction(string $code): array
    {
        $code = strtolower($code);
        $types = [];
        foreach (self::$jurisdictionPatterns as $type => $required) {
            if (in_array($code, $required, true)) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * Installed jurisdictions for which no specific pattern exists.
     *
     * These still scan, because the universal patterns always apply, but nothing
     * detects their national identifiers. A Kenyan ID number, for instance, is
     * seven or eight digits and no pattern here has that shape. Silence is
     * indistinguishable from coverage unless someone is told, which is what this
     * method exists to allow.
     *
     * @param array<int,string> $codes
     * @return array<int,string>
     */
    public static function jurisdictionsWithoutPatterns(array $codes): array
    {
        $implemented = [];
        foreach (self::$jurisdictionPatterns as $required) {
            foreach ($required as $code) {
                $implemented[$code] = true;
            }
        }

        $missing = [];
        foreach ($codes as $code) {
            if (!isset($implemented[strtolower((string) $code)])) {
                $missing[] = $code;
            }
        }

        return $missing;
    }

    /**
     * Jurisdiction-specific detectors. Types absent from this map are universal
     * (email, cards, international dialling) and always apply.
     */
    private static array $jurisdictionPatterns = [
        'SA_ID' => ['popia'],
        'PHONE_SA' => ['popia'],
        'NG_NIN' => ['ndpa'],
        'UK_NINO' => ['uk_gdpr'],
        'UK_NHS' => ['uk_gdpr'],
        'CA_SIN' => ['pipeda'],
        'US_SSN' => ['ccpa'],
        'KE_ID' => ['kenya_dpa'],
        'KE_KRA_PIN' => ['kenya_dpa'],
        'BR_CPF' => ['lgpd'],
        'AU_TFN' => ['australia_privacy'],
        'SG_NRIC' => ['pdpa_sg'],
        // IBAN is deliberately absent: it is international rather than the
        // identifier of any one jurisdiction, so it belongs to the universal
        // set and runs everywhere.
        //
        // 'gdpr' is deliberately absent too. GDPR is a regulation across 27
        // member states and has no national identifier of its own; each state's
        // identifier would need its own pattern. It will therefore continue to
        // appear in jurisdictionsWithoutPatterns(), which is accurate rather
        // than a gap waiting to be filled.
    ];

    // ── Detection ───────────────────────────────────────────────────────

    /**
     * Scan text for PII using regex patterns
     */
    public function detectPii(string $text): array
    {
        $results = [
            'entities' => [],
            'summary' => self::emptySummary(),
            'categories' => [],
        ];

        $candidates = [];

        // Patterns are selected from the installed jurisdictions. The selector
        // widens rather than narrows: no jurisdiction configured, or one with no
        // specific patterns, still yields the full universal set.
        foreach (self::patternsForJurisdictions(self::installedJurisdictionCodes()) as $type => $pattern) {
            // preg_match_all() returns false on failure, which is falsy, so an
            // aborted match was indistinguishable from "found nothing". A pattern
            // that exhausts the backtrack limit on a long description, or hits
            // invalid UTF-8, therefore dropped out of the scan silently and the
            // report looked clean. Skipping is still the only option; being quiet
            // about it is not.
            $matchCount = preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

            if (false === $matchCount || PREG_NO_ERROR !== preg_last_error()) {
                error_log(sprintf(
                    '[ahgPrivacy] pattern %s could not be evaluated (%s). Any %s in this text is NOT reported by this scan.',
                    $type,
                    preg_last_error_msg(),
                    $type
                ));

                continue;
            }

            if ($matchCount > 0) {
                foreach ($matches[0] as $match) {
                    $value = $match[0];
                    $position = $match[1];

                    // A finding is "validated" only when something beyond its
                    // shape confirms it: a checksum, or a format parse. That flag
                    // gates every downstream compliance assertion.
                    $validated = false;

                    if ($type === 'SA_ID') {
                        if (!$this->validateSaId($value)) {
                            continue;
                        }
                        $validated = true;
                    }

                    // The comment here used to claim "Luhn-eligible patterns"
                    // while no Luhn ran, so any four groups of four digits was
                    // scored critical on shape alone. It runs now.
                    if ($type === 'CREDIT_CARD') {
                        if (!self::passesLuhn($value)) {
                            continue;
                        }
                        $validated = true;
                    }

                    if ($type === 'EMAIL') {
                        $validated = (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
                    }

                    // Checksum-backed jurisdiction identifiers: a value that
                    // fails its own check digit is not a finding at all.
                    $checksums = [
                        'UK_NHS' => 'passesNhsCheck',
                        'CA_SIN' => 'passesLuhn',
                        'BR_CPF' => 'passesCpfCheck',
                        'AU_TFN' => 'passesTfnCheck',
                        'IBAN' => 'passesIbanCheck',
                    ];
                    if (isset($checksums[$type])) {
                        if (!self::{$checksums[$type]}($value)) {
                            continue;
                        }
                        $validated = true;
                    }

                    // Structural rules only, so deliberately not "validated".
                    if ($type === 'US_SSN' && !self::isPlausibleSsn($value)) {
                        continue;
                    }

                    // Skip if looks like a generic number (not PII)
                    if (in_array($type, ['BANK_ACCOUNT', 'TAX_NUMBER']) && !$this->looksLikeFinancial($text, $position, $value)) {
                        continue;
                    }

                    // Bare-shape identity patterns need the same treatment: an
                    // eleven-digit run and a letter-plus-digits run are the shape
                    // of half the reference codes in an archive, and were being
                    // emitted at 'high' with nothing to back them.
                    if (in_array($type, ['NG_NIN', 'PASSPORT', 'KE_ID', 'KE_KRA_PIN'], true)
                        && !$this->looksLikeIdentityDocument($text, $position, $type)) {
                        continue;
                    }

                    $risk = self::$riskLevels[$type] ?? 'medium';
                    $category = self::$categoryMap[$type] ?? 'personal';

                    $candidates[] = [
                        'type' => $type,
                        'value' => $this->maskPii($value, $type),
                        'raw_value' => $value,
                        'position' => $position,
                        'length' => strlen($value),
                        'risk_level' => $risk,
                        'category' => $category,
                        'validated' => $validated,
                        'confidence' => $this->calculateConfidence($type, $value, $text, $position),
                    ];
                }
            }
        }

        // Two detectors claiming the same characters is one finding, not two.
        foreach (self::resolveOverlaps($candidates) as $entity) {
            $results['entities'][] = $entity;
            $results['summary'] = self::countEntity($results['summary'], $entity['risk_level']);

            $category = $entity['category'];
            if (!isset($results['categories'][$category])) {
                $results['categories'][$category] = 0;
            }
            $results['categories'][$category]++;
        }

        return $results;
    }

    /**
     * Full PII scan combining NER + regex detection
     */
    public function fullScan(string $text): array
    {
        // Get NER entities (PERSON, ORG, GPE, DATE)
        $nerEntities = $this->callNerApi($text);

        // Get regex-based PII
        $piiEntities = $this->detectPii($text);

        // Merge results
        $allEntities = $piiEntities['entities'];

        // Add NER entities
        foreach ($nerEntities as $type => $values) {
            foreach ($values as $value) {
                $risk = self::$riskLevels[$type] ?? 'low';
                $category = self::$categoryMap[$type] ?? 'personal';

                $allEntities[] = [
                    'type' => $type,
                    'value' => $value,
                    'raw_value' => $value,
                    'position' => strpos($text, $value) ?: 0,
                    'risk_level' => $risk,
                    'category' => $category,
                    'confidence' => 0.85, // NER confidence
                    // Statistical, not checksum-confirmed: surfaced for review,
                    // but never on its own the basis of a compliance assertion.
                    'validated' => false,
                    'source' => 'ner',
                ];

                $piiEntities['summary'] = self::countEntity($piiEntities['summary'], $risk);

                if (!isset($piiEntities['categories'][$category])) {
                    $piiEntities['categories'][$category] = 0;
                }
                $piiEntities['categories'][$category]++;
            }
        }

        // Sort by position
        usort($allEntities, fn($a, $b) => $a['position'] <=> $b['position']);

        return [
            'entities' => $allEntities,
            'summary' => $piiEntities['summary'],
            'categories' => $piiEntities['categories'],
            'has_high_risk' => self::isHighRisk($piiEntities['summary']),
        ];
    }

    /**
     * Scan an information object for PII
     */
    public function scanObject(int $objectId, bool $includeDigitalObjects = true): array
    {
        $results = [
            'object_id' => $objectId,
            'scanned_at' => date('Y-m-d H:i:s'),
            'fields_scanned' => [],
            'entities' => [],
            'summary' => self::emptySummary(),
            'categories' => [],
            'risk_score' => 0,
        ];

        // Get object metadata
        $object = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function($j) {
                $j->on('i18n.id', '=', 'io.id')->where('i18n.culture', '=', 'en');
            })
            ->where('io.id', $objectId)
            ->select(['io.id', 'i18n.title', 'i18n.scope_and_content', 'i18n.arrangement',
                      'i18n.appraisal', 'i18n.archival_history', 'i18n.physical_characteristics'])
            ->first();

        if (!$object) {
            return $results;
        }

        // Scan each text field
        $fieldsToScan = [
            'title' => $object->title,
            'scope_and_content' => $object->scope_and_content,
            'arrangement' => $object->arrangement,
            'appraisal' => $object->appraisal,
            'archival_history' => $object->archival_history,
            'physical_characteristics' => $object->physical_characteristics,
        ];

        foreach ($fieldsToScan as $fieldName => $fieldValue) {
            if (empty($fieldValue)) {
                continue;
            }

            $results['fields_scanned'][] = $fieldName;
            $scan = $this->fullScan($fieldValue);

            foreach ($scan['entities'] as $entity) {
                $entity['field'] = $fieldName;
                $results['entities'][] = $entity;
            }

            // Aggregate every band, not a hardcoded subset of them.
            $results['summary'] = self::mergeSummary($results['summary'], $scan['summary']);

            foreach ($scan['categories'] as $cat => $count) {
                if (!isset($results['categories'][$cat])) {
                    $results['categories'][$cat] = 0;
                }
                $results['categories'][$cat] += $count;
            }
        }

        // Scan digital object (PDF) if requested
        if ($includeDigitalObjects) {
            $pdfScan = $this->scanDigitalObject($objectId);
            if ($pdfScan) {
                $results['fields_scanned'][] = 'digital_object';
                foreach ($pdfScan['entities'] as $entity) {
                    $entity['field'] = 'digital_object';
                    $results['entities'][] = $entity;
                }
                $results['summary'] = self::mergeSummary($results['summary'], $pdfScan['summary']);
            }
        }

        // Include ISAD access points (Subject, Places, Names, Dates)
        $isadEntities = $this->convertAccessPointsToEntities($objectId);
        if (!empty($isadEntities)) {
            $results['fields_scanned'][] = 'isad_access_points';
            foreach ($isadEntities as $entity) {
                $results['entities'][] = $entity;
                $results['summary'] = self::countEntity($results['summary'], (string) $entity['risk_level']);
            }
        }

        // Calculate risk score (0-100)
        $results['risk_score'] = self::calculateRiskScore($results['summary']);

        return $results;
    }

    /**
     * Scan digital object (PDF) for PII
     */
    public function scanDigitalObject(int $objectId): ?array
    {
        $digitalObject = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->first();

        if (!$digitalObject || $digitalObject->mime_type !== 'application/pdf') {
            return null;
        }

        // Use NER PDF extraction endpoint
        $filePath = $this->getDigitalObjectPath($digitalObject);
        if (!$filePath || !file_exists($filePath)) {
            return null;
        }

        try {
            $nerService = new \ahgNerService();
            $pdfResult = $nerService->extractFromPdf($filePath);

            if (!$pdfResult || !($pdfResult['success'] ?? false)) {
                return null;
            }

            // Get extracted text and scan for PII
            $text = $pdfResult['text'] ?? '';
            if (empty($text)) {
                return null;
            }

            return $this->fullScan($text);
        } catch (\Exception $e) {
            error_log('PII PDF scan error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get ISAD access points for an information object
     *
     * Returns subjects, places, names, and dates from ISAD access point fields
     *
     * @param int $objectId
     * @return array
     */
    public function getIsadAccessPoints(int $objectId): array
    {
        $accessPoints = [
            'subjects' => [],
            'places' => [],
            'names' => [],
            'dates' => [],
        ];

        // Get subject access points (taxonomy 35)
        $subjects = DB::table('object_term_relation as otr')
            ->join('term as t', 't.id', '=', 'otr.term_id')
            ->join('term_i18n as ti', function ($j) {
                $j->on('ti.id', '=', 't.id')->where('ti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('otr.object_id', $objectId)
            ->where('t.taxonomy_id', self::getSubjectsTaxonomyId())
            ->pluck('ti.name')
            ->toArray();
        $accessPoints['subjects'] = array_unique(array_filter($subjects));

        // Get place access points (taxonomy 42)
        $places = DB::table('object_term_relation as otr')
            ->join('term as t', 't.id', '=', 'otr.term_id')
            ->join('term_i18n as ti', function ($j) {
                $j->on('ti.id', '=', 't.id')->where('ti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('otr.object_id', $objectId)
            ->where('t.taxonomy_id', self::getPlacesTaxonomyId())
            ->pluck('ti.name')
            ->toArray();
        $accessPoints['places'] = array_unique(array_filter($places));

        // Get name access points (via events - creators, subjects of, etc.)
        $names = DB::table('event as ev')
            ->join('actor as a', 'a.id', '=', 'ev.actor_id')
            ->join('actor_i18n as ai', function ($j) {
                $j->on('ai.id', '=', 'a.id')->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('ev.object_id', $objectId)
            ->whereNotNull('ev.actor_id')
            ->pluck('ai.authorized_form_of_name')
            ->toArray();
        $accessPoints['names'] = array_unique(array_filter($names));

        // Get date access points (from events)
        $dates = DB::table('event')
            ->where('object_id', $objectId)
            ->whereNotNull('start_date')
            ->select(['start_date', 'end_date'])
            ->get();

        foreach ($dates as $date) {
            if ($date->start_date) {
                $accessPoints['dates'][] = $date->start_date;
            }
            if ($date->end_date && $date->end_date !== $date->start_date) {
                $accessPoints['dates'][] = $date->end_date;
            }
        }
        $accessPoints['dates'] = array_unique(array_filter($accessPoints['dates']));

        return $accessPoints;
    }

    /**
     * Convert ISAD access points to PII entity format
     *
     * @param int $objectId
     * @return array
     */
    public function convertAccessPointsToEntities(int $objectId): array
    {
        $accessPoints = $this->getIsadAccessPoints($objectId);
        $entities = [];

        // Convert subjects
        foreach ($accessPoints['subjects'] as $subject) {
            $entities[] = [
                'type' => 'ISAD_SUBJECT',
                'value' => $subject,
                'raw_value' => $subject,
                'position' => 0,
                'risk_level' => self::$riskLevels['ISAD_SUBJECT'],
                'category' => 'personal',
                'confidence' => 1.0, // Access points are definitive
                'source' => 'isad_access_point',
                'validated' => true,
                'field' => 'subject_access_points',
            ];
        }

        // Convert places
        foreach ($accessPoints['places'] as $place) {
            $entities[] = [
                'type' => 'ISAD_PLACE',
                'value' => $place,
                'raw_value' => $place,
                'position' => 0,
                'risk_level' => self::$riskLevels['ISAD_PLACE'],
                'category' => 'personal',
                'confidence' => 1.0,
                'source' => 'isad_access_point',
                'validated' => true,
                'field' => 'place_access_points',
            ];
        }

        // Convert names
        foreach ($accessPoints['names'] as $name) {
            $entities[] = [
                'type' => 'ISAD_NAME',
                'value' => $name,
                'raw_value' => $name,
                'position' => 0,
                'risk_level' => self::$riskLevels['ISAD_NAME'],
                'category' => 'personal',
                'confidence' => 1.0,
                'source' => 'isad_access_point',
                'validated' => true,
                'field' => 'name_access_points',
            ];
        }

        // Convert dates
        foreach ($accessPoints['dates'] as $date) {
            $entities[] = [
                'type' => 'ISAD_DATE',
                'value' => $date,
                'raw_value' => $date,
                'position' => 0,
                'risk_level' => self::$riskLevels['ISAD_DATE'],
                'category' => 'personal',
                'confidence' => 1.0,
                'source' => 'isad_access_point',
                'validated' => true,
                'field' => 'date_access_points',
            ];
        }

        return $entities;
    }

    /**
     * Save PII scan results to database
     */
    public function saveScanResults(int $objectId, array $results, ?int $userId = null): int
    {
        // Create extraction record
        $extractionId = DB::table('ahg_ner_extraction')->insertGetId([
            'object_id' => $objectId,
            'backend_used' => 'pii_detector',
            'status' => 'completed',
            'entity_count' => $results['summary']['total'],
            'extracted_at' => date('Y-m-d H:i:s'),
        ]);

        // Save each entity
        foreach ($results['entities'] as $entity) {
            DB::table('ahg_ner_entity')->insert([
                'extraction_id' => $extractionId,
                'object_id' => $objectId,
                'entity_type' => $entity['type'],
                'entity_value' => $entity['value'],
                // original_value is deliberately NOT written here.
                //
                // The column belongs to ahgAIPlugin and means "the value before a
                // human corrected it": the review UI sets it from entity_value on
                // an edit, and NerTrainingSync exports it as original_value paired
                // with corrected_value, pushing both to the AHG Central training
                // server. Writing the unmasked scan value into it was wrong twice
                // over. It recorded a correction that never happened, and it made
                // ahg_ner_entity a cleartext copy of the very data the scan exists
                // to control - one that leaves the site as soon as a reviewer
                // approves or rejects the finding, because that sets
                // correction_type and reviewed_at and makes the row export
                // eligible.
                //
                // A fresh scan has no correction, so NULL is the correct value.
                // NerTrainingSync already falls back to entity_value, which is the
                // masked form, so nothing downstream breaks.
                'confidence' => $entity['confidence'],
                'status' => $entity['risk_level'] === 'high' || $entity['risk_level'] === 'critical'
                    ? 'flagged' : 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // A privacy data inventory row is a COMPLIANCE ASSERTION: it declares
        // that this record holds a category of personal data. It previously fired
        // on high_risk > 0, so an unvalidated pattern hit - an accession number
        // shaped like a NIN - could manufacture that declaration. It now requires
        // at least one severe finding that something other than its shape
        // confirms. Unvalidated findings still reach the reviewer via the entity
        // rows written above.
        if (self::hasReportableFinding($results['entities'] ?? [])) {
            $this->createDataInventoryEntry($objectId, $results);
        }

        return $extractionId;
    }

    /**
     * Create privacy data inventory entry for high-risk PII
     */
    protected function createDataInventoryEntry(int $objectId, array $results): void
    {
        // Check if entry already exists
        $existing = DB::table('privacy_data_inventory')
            ->where('name', 'LIKE', "PII-{$objectId}-%")
            ->first();

        if ($existing) {
            // Update existing entry
            DB::table('privacy_data_inventory')
                ->where('id', $existing->id)
                ->update([
                    'description' => $this->buildInventoryDescription($results),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            return;
        }

        // Get object title for reference
        $object = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->first();

        $title = $object->title ?? "Object #{$objectId}";
        $dataType = $this->determineDataType($results['categories']);

        DB::table('privacy_data_inventory')->insert([
            'name' => "PII-{$objectId}-" . date('Ymd'),
            'description' => $this->buildInventoryDescription($results),
            'data_type' => $dataType,
            'storage_location' => "information_object:{$objectId}",
            'storage_format' => 'electronic',
            'encryption' => 0,
            'access_controls' => json_encode(['requires_review' => true]),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Batch scan multiple objects
     */
    public function batchScan(array $filters = [], int $limit = 100): array
    {
        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function($j) {
                $j->on('i18n.id', '=', 'io.id')->where('i18n.culture', '=', 'en');
            })
            ->whereNotNull('i18n.scope_and_content')
            ->where('i18n.scope_and_content', '!=', '');

        if (!empty($filters['repository_id'])) {
            $query->where('io.repository_id', $filters['repository_id']);
        }

        if (!empty($filters['level_of_description_id'])) {
            $query->where('io.level_of_description_id', $filters['level_of_description_id']);
        }

        // Skip already scanned objects
        if (empty($filters['rescan'])) {
            $scannedIds = DB::table('ahg_ner_extraction')
                ->where('backend_used', 'pii_detector')
                ->pluck('object_id')
                ->toArray();

            if (!empty($scannedIds)) {
                $query->whereNotIn('io.id', $scannedIds);
            }
        }

        $objects = $query->select(['io.id', 'i18n.title'])
            ->limit($limit)
            ->get();

        $results = [
            'scanned' => 0,
            'with_pii' => 0,
            'high_risk' => 0,
            'objects' => [],
        ];

        foreach ($objects as $obj) {
            $scan = $this->scanObject($obj->id);
            $results['scanned']++;

            if ($scan['summary']['total'] > 0) {
                $results['with_pii']++;
                $results['objects'][] = [
                    'id' => $obj->id,
                    'title' => $obj->title,
                    'pii_count' => $scan['summary']['total'],
                    'risk_score' => $scan['risk_score'],
                ];
            }

            if (self::isHighRisk($scan['summary'])) {
                $results['high_risk']++;
            }

            // Save results
            $this->saveScanResults($obj->id, $scan);
        }

        // Sort by risk score
        usort($results['objects'], fn($a, $b) => $b['risk_score'] <=> $a['risk_score']);

        return $results;
    }

    /**
     * Get PII scan statistics
     */
    public function getStatistics(): array
    {
        $totalScanned = DB::table('ahg_ner_extraction')
            ->where('backend_used', 'pii_detector')
            ->count();

        $withPii = DB::table('ahg_ner_extraction as e')
            ->where('e.backend_used', 'pii_detector')
            ->where('e.entity_count', '>', 0)
            ->count();

        $highRiskCount = DB::table('ahg_ner_entity')
            ->where('status', 'flagged')
            ->count();

        $byType = DB::table('ahg_ner_entity as e')
            ->join('ahg_ner_extraction as ex', 'ex.id', '=', 'e.extraction_id')
            ->where('ex.backend_used', 'pii_detector')
            ->select('e.entity_type', DB::raw('COUNT(*) as count'))
            ->groupBy('e.entity_type')
            ->pluck('count', 'entity_type')
            ->toArray();

        $pendingReview = DB::table('ahg_ner_entity')
            ->whereIn('status', ['pending', 'flagged'])
            ->count();

        return [
            'total_scanned' => $totalScanned,
            'with_pii' => $withPii,
            'high_risk_entities' => $highRiskCount,
            'pending_review' => $pendingReview,
            'by_type' => $byType,
            'coverage_percent' => $this->calculateCoverage(),
        ];
    }

    /**
     * Validate South African ID number using Luhn algorithm
     */
    protected function validateSaId(string $id): bool
    {
        if (strlen($id) !== 13 || !ctype_digit($id)) {
            return false;
        }

        // Check date validity
        $year = (int)substr($id, 0, 2);
        $month = (int)substr($id, 2, 2);
        $day = (int)substr($id, 4, 2);

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return false;
        }

        // Luhn checksum validation
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $digit = (int)$id[$i];
            if ($i % 2 === 0) {
                $sum += $digit;
            } else {
                $doubled = $digit * 2;
                $sum += $doubled > 9 ? $doubled - 9 : $doubled;
            }
        }

        return $sum % 10 === 0;
    }

    /**
     * Check if a number looks like financial data based on context
     */
    protected function looksLikeFinancial(string $text, int $position, string $value): bool
    {
        return self::contextMentions($text, $position, ['account', 'bank', 'tax', 'vat', 'payment', 'invoice', 'ref']);
    }

    /**
     * Context gate for the bare-shape identity patterns (#1). Without it, NG_NIN
     * ('\b\d{11}\b') and PASSPORT ('[A-Z]{1,2}\d{6,9}') match reference codes,
     * box numbers and accession numbers throughout ordinary archival metadata,
     * and were scored 'high' on that basis alone.
     */
    protected function looksLikeIdentityDocument(string $text, int $position, string $type): bool
    {
        switch ($type) {
            case 'PASSPORT':
                $keywords = ['passport', 'travel document', 'travel doc'];

                break;

            case 'KE_KRA_PIN':
                $keywords = ['kra', 'pin', 'tax', 'taxpayer'];

                break;

            case 'KE_ID':
                $keywords = ['id', 'identity', 'national id', 'id number', 'id no', 'huduma'];

                break;

            default:
                $keywords = ['nin', 'national identity', 'national id', 'identity number', 'identity no', 'id number', 'id no'];
        }

        return self::contextMentions($text, $position, $keywords);
    }

    /**
     * True when any keyword appears as a WHOLE WORD within +/-50 characters of
     * $position.
     *
     * Whole-word matching is the point. These gates previously used strpos(), so
     * the keyword 'ref' matched inside 'reference' - a word present in very nearly
     * every archival description. The gate that existed specifically to suppress
     * false positives was therefore open almost always.
     *
     * @param array<int,string> $keywords
     */
    private static function contextMentions(string $text, int $position, array $keywords): bool
    {
        $context = strtolower(substr($text, max(0, $position - 50), 100));

        foreach ($keywords as $keyword) {
            if (preg_match('/\b' . preg_quote(strtolower($keyword), '/') . '\b/u', $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mask PII value for display
     */
    protected function maskPii(string $value, string $type): string
    {
        switch ($type) {
            case 'SA_ID':
            case 'NG_NIN':
            case 'PASSPORT':
                return substr($value, 0, 4) . str_repeat('*', strlen($value) - 6) . substr($value, -2);
            case 'EMAIL':
                $parts = explode('@', $value);
                return substr($parts[0], 0, 2) . '***@' . $parts[1];
            case 'PHONE_SA':
            case 'PHONE_INTL':
                return substr($value, 0, 4) . '***' . substr($value, -3);
            case 'CREDIT_CARD':
                return '****-****-****-' . substr(preg_replace('/\D/', '', $value), -4);
            case 'BANK_ACCOUNT':
            case 'TAX_NUMBER':
            case 'UK_NHS':
            case 'CA_SIN':
            case 'US_SSN':
            case 'KE_ID':
            case 'KE_KRA_PIN':
            case 'BR_CPF':
            case 'AU_TFN':
            case 'UK_NINO':
            case 'SG_NRIC':
            case 'IBAN':
                return strlen($value) > 5
                    ? substr($value, 0, 3) . str_repeat('*', strlen($value) - 5) . substr($value, -2)
                    : str_repeat('*', strlen($value));
            default:
                return $value;
        }
    }

    /**
     * Calculate confidence score for detection
     */
    protected function calculateConfidence(string $type, string $value, string $text, ?int $position = null): float
    {
        $baseConfidence = 0.7;

        // Higher confidence for validated patterns
        if ($type === 'SA_ID' && $this->validateSaId($value)) {
            return 0.95;
        }

        if ($type === 'EMAIL' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 0.95;
        }

        // Context-based confidence boost, scored against THIS occurrence. The
        // offset is supplied by the caller, which already holds it from
        // PREG_OFFSET_CAPTURE; re-deriving it with strpos() scored every later
        // occurrence of a repeated value against the first one's surroundings.
        $offset = $position ?? strpos($text, $value);
        $context = strtolower(substr($text, max(0, (int) $offset - 30), 60));
        $contextKeywords = [
            'SA_ID' => ['id', 'identity', 'number', 'document'],
            'EMAIL' => ['email', 'contact', 'mail', '@'],
            'PHONE_SA' => ['phone', 'tel', 'call', 'contact', 'mobile', 'cell'],
        ];

        if (isset($contextKeywords[$type])) {
            foreach ($contextKeywords[$type] as $keyword) {
                if (strpos($context, $keyword) !== false) {
                    $baseConfidence += 0.1;
                }
            }
        }

        return min($baseConfidence, 0.95);
    }

    /**
     * Determine primary data type for inventory
     */
    protected function determineDataType(array $categories): string
    {
        if (!empty($categories['financial'])) {
            return 'financial';
        }
        if (!empty($categories['health'])) {
            return 'health';
        }
        return 'personal';
    }

    /**
     * Build description for data inventory entry
     */
    protected function buildInventoryDescription(array $results): string
    {
        $parts = ["PII scan detected {$results['summary']['total']} entities."];

        if ($results['summary']['high_risk'] > 0) {
            $parts[] = "High-risk: {$results['summary']['high_risk']}";
        }

        $types = [];
        foreach ($results['entities'] as $entity) {
            $types[$entity['type']] = ($types[$entity['type']] ?? 0) + 1;
        }

        $typeList = [];
        foreach ($types as $type => $count) {
            $typeList[] = "{$type}: {$count}";
        }

        if (!empty($typeList)) {
            $parts[] = "Types: " . implode(', ', $typeList);
        }

        return implode(' ', $parts);
    }

    /**
     * Calculate scan coverage percentage
     */
    protected function calculateCoverage(): float
    {
        $totalObjects = DB::table('information_object')
            ->where('id', '!=', 1) // Skip root
            ->count();

        if ($totalObjects === 0) {
            return 0;
        }

        $scannedObjects = DB::table('ahg_ner_extraction')
            ->where('backend_used', 'pii_detector')
            ->distinct('object_id')
            ->count('object_id');

        return round(($scannedObjects / $totalObjects) * 100, 2);
    }

    /**
     * Call NER API
     */
    protected function callNerApi(string $text): array
    {
        try {
            $nerService = new \ahgNerService();
            $result = $nerService->extract($text);

            if ($result && ($result['success'] ?? false)) {
                return $result['entities'] ?? [];
            }
        } catch (\Exception $e) {
            error_log('NER API error: ' . $e->getMessage());
        }

        return ['PERSON' => [], 'ORG' => [], 'GPE' => [], 'DATE' => []];
    }

    /**
     * Get digital object file path
     */
    protected function getDigitalObjectPath($digitalObject): ?string
    {
        $path = $digitalObject->path ?? '';
        $name = $digitalObject->name ?? '';

        if (!$path || !$name) {
            return null;
        }

        $webDir = \sfConfig::get('sf_web_dir');
        $fullPath = $webDir . '/uploads/' . trim($path, '/') . '/' . $name;

        return file_exists($fullPath) ? $fullPath : null;
    }
}
