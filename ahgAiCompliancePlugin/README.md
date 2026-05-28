# ahgAiCompliancePlugin

EU AI Act Article 12 record-keeping for the AtoM-AHG fork (PSIS twin of
the Heratio `ahg-ai-compliance` package).

Every AI inference call (LLM, HTR, NER, OCR, Translate, Guardrail, ...) writes
one immutable row to `ai_inference_log`. Each row links to the previous via a
SHA-256 chain; each row's hash is over the RFC 8785 JCS canonical form of the
row's signing view; each hash is Ed25519-signed under a publisher key.

The protocol primitives live in the framework-agnostic
[`ahg/inference-receipts`](https://packagist.org/packages/ahg/inference-receipts)
Composer library. This plugin is the AtoM integration layer.

## Installation

1. **Add the Composer dependency** to the AtoM root `composer.json`:

   ```json
   "require": {
       "ahg/inference-receipts": "^0.1"
   }
   ```

   Then `composer install --no-scripts` from `/usr/share/nginx/archive`.

2. **Drop the plugin directory** into `plugins/` (or wherever your AtoM
   instance loads AHG plugins from; on this host the canonical path is
   `/usr/share/nginx/archive/atom-ahg-plugins/ahgAiCompliancePlugin/`).

3. **Run the install SQL** against the AtoM database:

   ```bash
   mysqldump archive > /var/backups/archive-before-ai-compliance.sql
   mysql archive < ahgAiCompliancePlugin/database/install.sql
   ```

   Two new tables are created: `ai_inference_log` (the chain) and
   `ai_inference_key` (the per-kid public-key registry). Both are
   InnoDB / utf8mb4_unicode_ci and live alongside the existing `ahg_*`
   sidecar tables - no AtoM/Qubit base tables are touched.

4. **Generate the signing key**:

   ```bash
   sudo -u www-data php symfony ai-compliance:install-key
   ```

   Writes the secret key (raw libsodium 64-byte) to
   `data/ai-keys/inference-signing.sk` (mode 0600) and registers the kid
   in `ai_inference_key`. Run as the same uid that AtoM serves under so
   the web action can read it back if needed.

5. **Clear cache** so Symfony picks up the new module + routes:

   ```bash
   php symfony cc
   ```

6. **Verify the endpoint**:

   ```bash
   curl -i https://psis.theahg.co.za/.well-known/ai-inference-pubkey
   ```

   Should return `200 OK` with `application/json` containing the issuer,
   purpose, spec URL, and a `keys` array with one active Ed25519 key.

## .well-known/* reachability

Some nginx/apache configs strip `/.well-known/` from the request path before
it reaches the Symfony front controller. If `curl` returns 404 even though
`php symfony app:routes | grep ai-inference` shows the route is registered:

- **nginx**: confirm there is no `location ^~ /.well-known/ { ... }` block
  ahead of the AtoM front-controller rewrites. If there is and you cannot
  remove it, add an internal forward:

  ```nginx
  location = /.well-known/ai-inference-pubkey {
      try_files $uri /index.php?module=aiCompliance&action=wellKnownPubkey;
  }
  ```

- **apache**: the AtoM `.htaccess` ships a `RewriteRule ^(.*)$ index.php`
  which is enough; if you've added a `RewriteCond` excluding `/.well-known/`
  (common for ACME challenges) make it specific to `acme-challenge`:

  ```apache
  RewriteCond %{REQUEST_URI} !^/.well-known/acme-challenge/
  ```

## Operator tasks

| Task | Purpose |
|------|---------|
| `php symfony ai-compliance:install-key [--rotate] [--force]` | generate / rotate the Ed25519 signing key |
| `php symfony ai-compliance:verify-inference-log [--from=ISO] [--to=ISO]` | walk the chain, recompute hashes, validate signatures |
| `php symfony ai-compliance:prune [--years=N] [--dry-run]` | null `payload_json` past the retention window (default 7 years) |

`verify-inference-log` is the auditor-facing command - exit code 0 means the
chain is intact, non-zero means tampering at the reported seq.

## Library classes

| Class | Purpose |
|-------|---------|
| `InferenceLogger` | thin wrapper around `\AhgInferenceReceipts\ReceiptChain` that AI services call after each inference (signature matches Heratio's) |
| `PropelChainStore` | implements `\AhgInferenceReceipts\Storage\ChainStore` against `ai_inference_log` via Capsule DB |
| `KeyResolver` | kid -> 32-byte raw public key lookup, with `register()` for the install task |
| `SignerFactory` | loads / generates the Ed25519 key from `data/ai-keys/inference-signing.sk` |

## What this plugin does NOT do (yet)

Wiring the actual AtoM AI services (`ahgAIPlugin/lib/Services/*`) to call
`InferenceLogger::log()` after each inference is a separate phase, tracked
in the same GitHub issue. This phase ships the scaffold, tables, tasks, and
public-key endpoint only.

## See also

- Heratio sibling: `packages/ahg-ai-compliance/`
- Standalone library: <https://packagist.org/packages/ahg/inference-receipts>
- EU AI Act Article 12: enforcement 2 August 2026
- Cross-verifier reference vectors: nobulex (TypeScript/Python)
