<?php

namespace AtoM\Framework\Plugins\AuditTrail\Services;

use AtoM\Framework\Plugins\AuditTrail\Models\AuditLog;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Tamper-evident hash chaining for ahg_audit_log (#126).
 *
 * Seal-forward: historical rows (entry_hash IS NULL) are left untouched; every
 * entry from the seal point on is SHA-256 linked to the previous one
 * (entry_hash = SHA-256(prev_hash ‖ canonical(content))). Any later edit,
 * deletion or insertion of a chained row breaks the chain and is reported by
 * verifyChain().
 *
 * Writers are serialised on the single ahg_audit_chain_state row (locked FOR
 * UPDATE) so concurrent inserts can't fork the chain.
 */
class ChainedAuditWriter
{
    private const SEP = "\x1e";

    /** Scalar content columns, hashed in this fixed order. */
    private const SCALAR_FIELDS = [
        'uuid', 'user_id', 'username', 'user_email', 'ip_address', 'user_agent',
        'session_id', 'action', 'entity_type', 'entity_id', 'entity_slug',
        'entity_title', 'module', 'action_name', 'request_method', 'request_uri',
        'security_classification', 'status', 'error_message', 'created_at',
    ];

    /** JSON content columns (deterministically re-encoded before hashing). */
    private const JSON_FIELDS = ['old_values', 'new_values', 'changed_fields', 'metadata'];

    /**
     * Insert an ahg_audit_log row linked to the previous chained entry.
     * Returns the new row id. Fail-open: if chaining errors, the row is still
     * written (unchained) so audit logging and the request never break.
     */
    public static function append(array $data): int
    {
        // Fix uuid + created_at before hashing so they are covered by the chain.
        if (empty($data['uuid'])) {
            $data['uuid'] = self::uuid4();
        }
        $data['created_at'] = self::normalizeTs($data['created_at'] ?? null) ?: date('Y-m-d H:i:s');

        try {
            return (int) DB::connection()->transaction(static function () use ($data) {
                $state = DB::table('ahg_audit_chain_state')->where('id', 1)->lockForUpdate()->first();
                $prev = $state ? $state->last_hash : self::initStateLocked();
                $prevSeq = ($state && isset($state->last_seq)) ? (int) $state->last_seq : 0;

                $data['prev_hash'] = $prev;
                // entry_hash is computed over content only (SCALAR_FIELDS/JSON_FIELDS),
                // exactly as before — the seal columns below are NOT part of the hash,
                // so every pre-seal row still verifies unchanged.
                $data['entry_hash'] = self::entryHash($prev, $data);

                // Cryptographic seal (#5): monotonic seq + Ed25519 signature over
                // the entry_hash. Opt-in — null kid/signature until a key is minted.
                // Column-tolerant: if the seal columns aren't migrated yet, skip
                // them entirely so deploy order (code vs ALTER) can never break
                // audit logging.
                $seq = $prevSeq + 1;
                $sealCols = self::hasSealColumns();
                if ($sealCols) {
                    $data['seq'] = $seq;
                    $sealed = self::sealEntry($data['entry_hash']);
                    $data['signature'] = $sealed['signature'];
                    $data['kid'] = $sealed['kid'];
                    if (!array_key_exists('tenant_id', $data)) {
                        $data['tenant_id'] = self::resolveTenantId();
                    }
                }

                $id = DB::table('ahg_audit_log')->insertGetId(self::forStorage($data));

                $stateUpdate = [
                    'last_hash' => $data['entry_hash'],
                    'last_audit_id' => $id,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                if (self::hasStateSeqColumn()) {
                    $stateUpdate['last_seq'] = $seq;
                }
                DB::table('ahg_audit_chain_state')->where('id', 1)->update($stateUpdate);

                return $id;
            });
        } catch (\Throwable $e) {
            error_log('audit.chain.append_failed: ' . $e->getMessage());
            try {
                unset($data['prev_hash'], $data['entry_hash'], $data['seq'], $data['signature'], $data['kid']);

                return (int) DB::table('ahg_audit_log')->insertGetId(self::forStorage($data));
            } catch (\Throwable $e2) {
                error_log('audit.chain.fallback_failed: ' . $e2->getMessage());

                return 0;
            }
        }
    }

    /** Encode array JSON fields to strings for a raw insert. */
    private static function forStorage(array $data): array
    {
        foreach (self::JSON_FIELDS as $k) {
            if (isset($data[$k]) && is_array($data[$k])) {
                $data[$k] = json_encode($data[$k], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }

        return $data;
    }

    /**
     * Walk the chain and verify every link. Detects content tampering
     * (entry_hash mismatch) and structural tampering — deletion or insertion of
     * a chained row (prev_hash linkage / tip mismatch).
     *
     * @return array{sealed:bool,intact:bool,broken_id:?int,reason:?string,checked:int,total:int}
     */
    public static function verifyChain(): array
    {
        $state = DB::table('ahg_audit_chain_state')->where('id', 1)->first();
        if (!$state) {
            return ['sealed' => false, 'intact' => true, 'broken_id' => null,
                'reason' => 'chain not sealed yet', 'checked' => 0, 'total' => 0];
        }

        $rows = AuditLog::whereNotNull('entry_hash')->orderBy('id')->get();
        $total = count($rows);
        $expectedPrev = $state->genesis_hash;
        $checked = 0;

        // Cryptographic seal verification (#5): verify each row's Ed25519
        // signature over its entry_hash against the current public key. A
        // failure against a rotated key is reported as sig_failed, not as a
        // chain break, so key rotation never trips the integrity gate.
        $signer = null;
        $publicKey = null;
        $currentKid = null;
        try {
            $signer = new AuditSigner();
            $publicKey = $signer->publicKey();
            $currentKid = $signer->keyId();
        } catch (\Throwable $e) {
            // signing not configured — seal counts stay zero.
        }
        $signed = 0;
        $sigVerified = 0;
        $sigFailed = 0;
        $firstSigFailId = null;

        foreach ($rows as $r) {
            if ($r->prev_hash !== $expectedPrev) {
                return self::broken((int) $r->id, 'chain linkage broken (an entry was deleted or inserted)', $checked, $total);
            }
            if (self::entryHash($r->prev_hash, self::rowToData($r)) !== $r->entry_hash) {
                return self::broken((int) $r->id, 'entry content was altered after it was written', $checked, $total);
            }
            if (!empty($r->signature)) {
                ++$signed;
                if (null !== $signer && null !== $publicKey && (string) $r->kid === (string) $currentKid) {
                    if ($signer->verify((string) $r->signature, (string) $r->entry_hash, $publicKey)) {
                        ++$sigVerified;
                    } else {
                        ++$sigFailed;
                        $firstSigFailId = $firstSigFailId ?? (int) $r->id;
                    }
                }
            }
            $expectedPrev = $r->entry_hash;
            ++$checked;
        }

        if ($expectedPrev !== $state->last_hash) {
            return self::broken($state->last_audit_id ? (int) $state->last_audit_id : null,
                'chain tip mismatch (the most recent entries were deleted)', $checked, $total);
        }

        return ['sealed' => true, 'intact' => true, 'broken_id' => null, 'reason' => null,
            'checked' => $checked, 'total' => $total,
            'signed' => $signed, 'sig_verified' => $sigVerified, 'sig_failed' => $sigFailed,
            'first_sig_fail_id' => $firstSigFailId];
    }

    /**
     * (Re)seal the chain from the current head. Existing chained entries before
     * this point keep their hashes; the anchor is reset to "now". Use only to
     * establish the very first seal — re-sealing abandons earlier chain history.
     *
     * @return array{sealed_from_id:int,genesis:string}
     */
    public static function seal(): array
    {
        $maxId = (int) (DB::table('ahg_audit_log')->max('id') ?? 0);
        $now = date('Y-m-d H:i:s');
        $genesis = hash('sha256', 'ahg_audit_log:seal:' . $maxId . ':' . $now);

        DB::table('ahg_audit_chain_state')->updateOrInsert(
            ['id' => 1],
            ['genesis_hash' => $genesis, 'last_hash' => $genesis, 'last_audit_id' => $maxId,
                'sealed_from_id' => $maxId, 'sealed_at' => $now, 'updated_at' => $now]
        );

        return ['sealed_from_id' => $maxId, 'genesis' => $genesis];
    }

    // ─── internals ──────────────────────────────────────────────────────────

    /** Seed the seal anchor inside an append when none exists; returns last_hash. */
    private static function initStateLocked(): string
    {
        $maxId = (int) (DB::table('ahg_audit_log')->max('id') ?? 0);
        $now = date('Y-m-d H:i:s');
        $genesis = hash('sha256', 'ahg_audit_log:seal:' . $maxId . ':' . $now);
        DB::table('ahg_audit_chain_state')->insert([
            'id' => 1, 'genesis_hash' => $genesis, 'last_hash' => $genesis,
            'last_audit_id' => $maxId, 'sealed_from_id' => $maxId, 'sealed_at' => $now, 'updated_at' => $now,
        ]);

        return $genesis;
    }

    private static function broken(?int $id, string $reason, int $checked, int $total): array
    {
        return ['sealed' => true, 'intact' => false, 'broken_id' => $id,
            'reason' => $reason, 'checked' => $checked, 'total' => $total];
    }

    private static function entryHash(string $prevHash, array $row): string
    {
        return hash('sha256', $prevHash . self::SEP . self::canonical($row));
    }

    /** Whether the ahg_audit_log seal columns have been migrated (cached per request). */
    private static function hasSealColumns(): bool
    {
        static $has = null;
        if (null === $has) {
            try {
                $has = DB::getSchemaBuilder()->hasColumn('ahg_audit_log', 'signature');
            } catch (\Throwable $e) {
                $has = false;
            }
        }

        return $has;
    }

    /** Whether ahg_audit_chain_state has the last_seq column (cached per request). */
    private static function hasStateSeqColumn(): bool
    {
        static $has = null;
        if (null === $has) {
            try {
                $has = DB::getSchemaBuilder()->hasColumn('ahg_audit_chain_state', 'last_seq');
            } catch (\Throwable $e) {
                $has = false;
            }
        }

        return $has;
    }

    /**
     * Ed25519-sign an entry hash. Returns ['signature' => ?string, 'kid' => ?string];
     * both null when no signing key is configured (seal is opt-in) or signing errors.
     */
    private static function sealEntry(string $entryHash): array
    {
        try {
            $signer = new AuditSigner();
            if (!$signer->isEnabled()) {
                return ['signature' => null, 'kid' => null];
            }
            $sig = $signer->sign($entryHash);

            return ['signature' => $sig, 'kid' => null === $sig ? null : $signer->keyId()];
        } catch (\Throwable $e) {
            error_log('audit.chain.sign_failed: ' . $e->getMessage());

            return ['signature' => null, 'kid' => null];
        }
    }

    /**
     * Resolve the current tenant id for multi-tenant deployments. PSIS multi-tenancy
     * is disabled, so this returns null; the column exists for parity + forward use.
     */
    private static function resolveTenantId(): ?int
    {
        $tid = class_exists('sfConfig') ? \sfConfig::get('ahg_current_tenant_id') : null;

        return (null === $tid || '' === $tid) ? null : (int) $tid;
    }

    private static function canonical(array $f): string
    {
        $parts = [];
        foreach (self::SCALAR_FIELDS as $k) {
            $v = $f[$k] ?? null;
            $parts[] = (null === $v) ? '' : (string) $v;
        }
        foreach (self::JSON_FIELDS as $k) {
            $parts[] = self::deterministicEncode($f[$k] ?? null);
        }

        return implode('|', $parts);
    }

    /** Stable JSON: decode strings, sort assoc keys recursively, re-encode. */
    private static function deterministicEncode($v): string
    {
        if (null === $v) {
            return 'null';
        }
        if (is_string($v)) {
            $decoded = json_decode($v, true);
            $v = (JSON_ERROR_NONE === json_last_error()) ? $decoded : ['_raw' => $v];
        }
        self::ksortRecursive($v);

        return json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function ksortRecursive(&$v): void
    {
        if (!is_array($v)) {
            return;
        }
        // Sort associative arrays by key; leave lists in order.
        if (array_keys($v) !== range(0, count($v) - 1)) {
            ksort($v);
        }
        foreach ($v as &$child) {
            self::ksortRecursive($child);
        }
    }

    private static function rowToData(AuditLog $r): array
    {
        $d = [];
        foreach (self::SCALAR_FIELDS as $k) {
            $d[$k] = $r->{$k};
        }
        $d['created_at'] = self::normalizeTs($r->created_at);
        foreach (self::JSON_FIELDS as $k) {
            $d[$k] = $r->{$k}; // cast to array by the model
        }

        return $d;
    }

    /** RFC-4122 v4 UUID without the ramsey/uuid dependency. */
    private static function uuid4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    private static function normalizeTs($ts): ?string
    {
        if (null === $ts || '' === $ts) {
            return null;
        }
        if ($ts instanceof \DateTimeInterface) {
            return $ts->format('Y-m-d H:i:s');
        }

        return substr((string) $ts, 0, 19);
    }
}
