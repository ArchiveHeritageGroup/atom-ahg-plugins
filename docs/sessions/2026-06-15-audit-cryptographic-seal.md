# Audit log cryptographic seal — 2026-06-15

**Source:** DB-audit archive plan build-order #5. **Plugin:** ahgAuditTrailPlugin (user explicitly unlocked this DO-NOT-MODIFY stable plugin for this work). **Status:** built + crypto-verified, lint-clean. NOT activated; ALTER + keygen are Johan's. Unreleased.

## Correction to the audit plan
The plan claimed archive's `ahg_audit_log` lacks hash-chaining. **Wrong** — PSIS already has `prev_hash` + `entry_hash` (the #126 ChainedAuditWriter, sealed & active: chain_state sealed_from_id 460778, ~650k rows). The genuine gap vs Heratio is only the cryptographic **seal** columns: `kid`, `seq`, `signature`, `tenant_id`.

## What the seal adds
Hash chaining = tamper-EVIDENT (any edit/delete/insert breaks SHA-256 linkage). Ed25519-signing each entry's `entry_hash` = tamper-PROOF: an attacker who rewrites the chain to stay internally consistent still can't forge the per-entry signatures without the private key. `seq` = monotonic gap-detectable ordinal; `tenant_id` = forward-looking multi-tenant scoping (PSIS MT disabled → null).

## Delivered
- `database/add_audit_seal.sql` — ALTER `ahg_audit_log` ADD kid/seq/signature/tenant_id (all nullable/additive; MySQL-8 INSTANT) + ALTER `ahg_audit_chain_state` ADD last_seq.
- `lib/Services/AuditSigner.php` (NEW, ns AtoM\Framework\Plugins\AuditTrail\Services) — self-contained Ed25519 (sodium): generateKeypair/isEnabled/keyId/publicKey/sign(entryHash)/verify. Key lives in `data/ahg-audit-signing/` (outside any repo, +.gitignore), private never in DB/git. Opt-in: sign() null until keygen. **Self-contained by design — does NOT reuse ahgProvenancePlugin's signer (plugin autonomy).**
- `ChainedAuditWriter::append` — inside the existing locked txn: seq = last_seq+1, Ed25519-sign entry_hash → signature+kid, resolve tenant_id. **entry_hash definition UNCHANGED** (seal cols are NOT hashed) so all pre-seal rows verify unchanged. **Column-tolerant** (`hasSealColumns`/`hasStateSeqColumn`, cached) → safe to deploy before or after the ALTER; audit logging never breaks. Fallback path also unsets seal cols.
- `verifyChain` — additionally verifies each row's signature against the current key; reports signed/sig_verified/sig_failed (+first_sig_fail_id). A rotated-key mismatch is reported as sig_failed, NOT a chain break (no false integrity alarm).
- `auditChainTask` — `--keygen [--force]` to mint the key; verify output now prints seal status.

## Verified
- All `php -l` clean.
- AuditSigner crypto self-test (temp keydir, no DB): opt-in null before keygen; kid `ed25519:…` 24c (fits varchar 32); signature 88c b64 (fits varchar 128); verify good=true; tampered-entry=false; tampered-sig=false.
- Live DB append smoke deferred to post-ALTER (columns are Johan's to add).

## Activation (Johan — ORDERED)
1. **ALTER** (DB-protection — you run): `mysql archive < ahgAuditTrailPlugin/database/add_audit_seal.sql`
2. **Keygen as www-data** (so php-fpm can read the private key — root-owned keys silently break signing):
   `cd /usr/share/nginx/archive && sudo -u www-data php symfony audit:chain --keygen`
3. **Activate:** `sudo rm -rf cache/qubit/prod/* && sudo systemctl restart php8.3-fpm`
4. **Verify:** `php symfony audit:chain` → expect "chain intact" + "Seal: N signed, N verified, 0 failed".
5. **Release:** `cd atom-ahg-plugins && ./bin/release patch "ahgAuditTrailPlugin: Ed25519 cryptographic seal for the audit hash chain (kid/seq/signature/tenant_id)"`
