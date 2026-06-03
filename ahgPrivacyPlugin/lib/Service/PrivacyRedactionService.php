<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * PrivacyRedactionService — field-level structured redaction for archival
 * description metadata (#130). Holds the redaction ENGINE (full / partial /
 * pseudonymised) + the per-IO profile/field management + audit. The display
 * layer calls applyRedaction() for public/unauthorised viewers only; an
 * authorised viewer is served the original values untouched.
 *
 * @package ahgPrivacyPlugin
 */
class PrivacyRedactionService
{
    public const FULL_PLACEHOLDER = '[REDACTED — personal data removed]';
    public const PARTIAL_PLACEHOLDER = '[PARTIALLY REDACTED]';

    // ── Profile / field management ──────────────────────────────────────

    public function getProfile(int $ioId): ?object
    {
        return DB::table('information_object_privacy')->where('information_object_id', $ioId)->first();
    }

    /** @return array<int,object> field rules for an IO (empty if none) */
    public function getFields(int $ioId): array
    {
        $profile = $this->getProfile($ioId);
        if (!$profile) {
            return [];
        }

        return DB::table('information_object_privacy_field')->where('privacy_id', $profile->id)
            ->orderBy('field_name')->get()->all();
    }

    public function getReasons(): array
    {
        return DB::table('privacy_reason')->orderBy('sort_order')->get()->all();
    }

    /** Upsert the per-IO privacy profile; returns information_object_privacy.id. */
    public function setProfile(int $ioId, array $data, ?int $userId = null): int
    {
        $row = [
            'privacy_reason_id' => $data['privacy_reason_id'] ?? null ?: null,
            'redaction_status' => in_array($data['redaction_status'] ?? '', ['none', 'partial', 'full', 'pending'], true)
                ? $data['redaction_status'] : 'partial',
            'legal_basis_reference' => $data['legal_basis_reference'] ?? null ?: null,
            'notes' => $data['notes'] ?? null ?: null,
            'applied_by' => $userId,
            'applied_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $existing = $this->getProfile($ioId);
        if ($existing) {
            DB::table('information_object_privacy')->where('id', $existing->id)->update($row);
            $id = (int) $existing->id;
        } else {
            $row['information_object_id'] = $ioId;
            $row['created_at'] = date('Y-m-d H:i:s');
            $id = (int) DB::table('information_object_privacy')->insertGetId($row);
        }
        $this->log($ioId, $userId, 'profile_set', null);

        return $id;
    }

    /** Add/update a per-field redaction rule. */
    public function setField(int $ioId, array $data, ?int $userId = null): int
    {
        $privacyId = $this->setProfileIfMissing($ioId, $userId);
        $field = trim((string) ($data['field_name'] ?? ''));
        $row = [
            'privacy_id' => $privacyId,
            'field_name' => $field,
            'redaction_type' => in_array($data['redaction_type'] ?? '', ['full', 'partial', 'pseudonymised'], true)
                ? $data['redaction_type'] : 'full',
            'redaction_pattern' => $data['redaction_pattern'] ?? null ?: null,
            'reason' => trim((string) ($data['reason'] ?? 'Personal data')),
            'is_sensitive' => !empty($data['is_sensitive']) ? 1 : 0,
            'reviewed_by' => $userId,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ];
        $existing = DB::table('information_object_privacy_field')
            ->where('privacy_id', $privacyId)->where('field_name', $field)->first();
        if ($existing) {
            DB::table('information_object_privacy_field')->where('id', $existing->id)->update($row);
            $id = (int) $existing->id;
        } else {
            $row['created_at'] = date('Y-m-d H:i:s');
            $id = (int) DB::table('information_object_privacy_field')->insertGetId($row);
        }
        $this->log($ioId, $userId, 'field_added', $field);

        return $id;
    }

    public function removeField(int $fieldId, ?int $userId = null): bool
    {
        $f = DB::table('information_object_privacy_field as f')
            ->join('information_object_privacy as p', 'f.privacy_id', '=', 'p.id')
            ->where('f.id', $fieldId)->select('f.field_name', 'p.information_object_id as io')->first();
        $ok = DB::table('information_object_privacy_field')->where('id', $fieldId)->delete() > 0;
        if ($f) {
            $this->log((int) $f->io, $userId, 'field_removed', $f->field_name);
        }

        return $ok;
    }

    private function setProfileIfMissing(int $ioId, ?int $userId): int
    {
        $p = $this->getProfile($ioId);

        return $p ? (int) $p->id : $this->setProfile($ioId, ['redaction_status' => 'partial'], $userId);
    }

    /**
     * #130 AC#5 - DSAR integration. Pre-populate a privacy profile for an IO
     * that is in scope for a data subject access request so the officer can
     * mark fields for redaction as part of the response. Idempotent: an
     * existing profile is left untouched. New profiles start at status
     * 'pending' with the "access request" reason.
     *
     * @return int information_object_privacy.id
     */
    public function prepopulateForDsar(int $ioId, ?int $userId = null): int
    {
        $existing = $this->getProfile($ioId);
        if ($existing) {
            return (int) $existing->id;
        }

        $reasonId = DB::table('privacy_reason')->where('code', 'access_request')->value('id');

        $id = (int) DB::table('information_object_privacy')->insertGetId([
            'information_object_id' => $ioId,
            'privacy_reason_id' => $reasonId !== null ? (int) $reasonId : null,
            'redaction_status' => 'pending',
            'legal_basis_reference' => 'DSAR / data subject access request',
            'applied_by' => $userId,
            'applied_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->log($ioId, $userId, 'dsar_prepopulated', null);

        return $id;
    }

    // ── Redaction engine (apply to public/unauthorised viewers only) ────

    /**
     * Return a copy of $values with this IO's redacted fields replaced.
     * $values is a field_name => value map. Fields with no rule pass through.
     */
    public function applyRedaction(int $ioId, array $values, ?int $userId = null, bool $audit = false): array
    {
        $fields = $this->getFields($ioId);
        if (empty($fields)) {
            return $values;
        }
        foreach ($fields as $rule) {
            if (!array_key_exists($rule->field_name, $values) || $values[$rule->field_name] === null) {
                continue;
            }
            $values[$rule->field_name] = $this->applyType(
                (string) $values[$rule->field_name], $rule->redaction_type, $rule->redaction_pattern
            );
        }
        if ($audit) {
            $this->log($ioId, $userId, 'served_redacted', null);
        }

        return $values;
    }

    public function applyType(string $value, string $type, ?string $pattern): string
    {
        switch ($type) {
            case 'partial':
                return $pattern ? $this->applyPattern($value, $pattern) : self::PARTIAL_PLACEHOLDER;
            case 'pseudonymised':
                return $this->pseudonymise($value);
            case 'full':
            default:
                return self::FULL_PLACEHOLDER;
        }
    }

    private function applyPattern(string $value, string $pattern): string
    {
        switch ($pattern) {
            case 'email_partial':
                return preg_replace('/^(.).*@.*$/', '$1***@***.***', $value) ?: self::PARTIAL_PLACEHOLDER;
            case 'phone_partial':
                return preg_replace('/\d(?=\d{4})/', '*', $value) ?: self::PARTIAL_PLACEHOLDER;
            case 'id_last4':
                return strlen($value) > 4 ? str_repeat('*', strlen($value) - 4) . substr($value, -4) : self::PARTIAL_PLACEHOLDER;
            default:
                return self::PARTIAL_PLACEHOLDER;
        }
    }

    /** Deterministic, non-reversible pseudonym (same input → same pseudonym). */
    private function pseudonymise(string $value): string
    {
        return 'Subject-' . strtoupper(substr(hash('sha256', $value), 0, 8));
    }

    // ── Audit ───────────────────────────────────────────────────────────

    public function log(int $ioId, ?int $userId, string $action, ?string $field): void
    {
        try {
            DB::table('information_object_privacy_log')->insert([
                'information_object_id' => $ioId,
                'user_id' => $userId,
                'action' => $action,
                'field_name' => $field,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // logging must never block redaction
        }
    }

    /** All IOs that have a privacy profile (for the admin list). */
    public function listProfiledObjects(): array
    {
        return DB::table('information_object_privacy as p')
            ->leftJoin('information_object_i18n as i', function ($j) {
                $j->on('i.id', '=', 'p.information_object_id')->where('i.culture', '=', 'en');
            })
            ->orderByDesc('p.updated_at')
            ->select('p.*', 'i.title')
            ->get()->all();
    }
}
