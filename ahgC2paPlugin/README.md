# ahgC2paPlugin

C2PA (Coalition for Content Provenance and Authenticity) 2.1 content credentials
for AtoM-AHG. PSIS twin of Heratio's `ahg/c2pa` package.

Generate, Ed25519-sign, embed and verify signed provenance manifests on
digital-object derivatives and AI outputs:

- **Build** a C2PA manifest from assertions + a host asset (file or text).
- **Sign** it with the shared Ed25519 key chain (`ahg/inference-receipts`,
  the same key `ahgAiCompliancePlugin` installs).
- **Embed** the manifest as JUMBF inside a JPEG via the `c2patool` binary, or
  fall back to a `.c2pa.json` sidecar when `c2patool` is absent.
- **Surface** embedded EXIF/IPTC/XMP as C2PA Standard Metadata Assertions
  (`stds.exif`, `stds.iptc`, `stds.xmp`) read from `digital_object_metadata`,
  `dam_iptc_metadata`, `media_metadata`.
- **Declare** the claimer's AI training-mining stance (`c2pa.training-mining`).
- **Verify** a manifest end-to-end: re-hash every assertion against its
  claim-pinned hash, then verify the Ed25519 claim signature.
- **Persist** every signed manifest to `ahg_c2pa_manifest` for audit + reissue.

## Architecture

```
lib/
  c2pa_bootstrap.php          require_once loader (no PSR-4 for plugin classes)
  Manifest/
    Assertion.php             one provenance assertion + convenience builders
    Claim.php                 the signed claim structure
    ManifestBuilder.php       assemble manifest, JCS/CBOR encoders
    CborEncoder.php           deterministic CTAP2 CBOR (pure PHP)
    C2paSigner.php            Ed25519 sign/verify over SHA-256(JCS(claim))
    StandardMetadataLoader.php  EXIF/IPTC/XMP -> stds.* assertions
  Services/
    C2paService.php           build/sign/sidecar/embed/persist/verify
  Commands/
    C2paVerifyCommand.php     php bin/atom c2pa:verify
    C2paSmokeCommand.php      php bin/atom c2pa:smoke
modules/c2pa/actions/actions.class.php   HTTP endpoints
config/ahgC2paPluginConfiguration.class.php  module + route registration
database/install.sql          ahg_c2pa_manifest
```

The crypto layer is shared with `ahgAiCompliancePlugin`: the framework composer
dependency `ahg/inference-receipts` (`AhgInferenceReceipts\{JcsEncoder,Signer,
KeyPair}`) provides the RFC 8785 JCS encoder + Ed25519 primitives, and the
signing key lives at `data/ai-keys/inference-signing.sk` with its public half
registered in `ai_inference_key`. C2PA claims signed here cross-verify against
Heratio claims byte-for-byte.

## Endpoints

| Method | Path                     | Action      | Purpose |
|--------|--------------------------|-------------|---------|
| GET    | `/.well-known/c2pa-info` | wellKnown   | Capability discovery (signing/embed/library availability, active kid) |
| ANY    | `/c2pa/verify`           | verify      | Verify a posted manifest JSON (raw body or `manifest` field) |
| GET    | `/c2pa/manifest/:id`     | manifest    | One stored manifest (full canonical JSON) by row id |
| GET    | `/c2pa/manifests/:id`    | manifests   | All stored manifests for an information object id |

## CLI

```bash
# Build + sign a manifest for a hypothetical AI suggestion (deployment check)
php bin/atom c2pa:smoke 1234
php bin/atom c2pa:smoke 1234 "Custom text" --action=ai-assisted --no-write

# Verify a sidecar / signed-manifest JSON file
php bin/atom c2pa:verify /path/to/photo.jpg.c2pa.json
php bin/atom c2pa:verify manifest.json --public-key=<64-hex>
```

## External dependencies

- **`c2patool`** (optional) - enables JUMBF embedding into JPEGs. Install at
  `/usr/local/bin/c2patool` or on `PATH`. When absent, `C2paService::embedInJpeg()`
  transparently falls back to a `.c2pa.json` sidecar. Auto-detected at runtime.
- **`ext-sodium`** (required for signing/verifying) - provides Ed25519. Present in
  PHP 8.3 by default.
- **`ahg/inference-receipts`** (composer) - already installed as a framework
  dependency; used for JCS + Ed25519.

## Install

```bash
# 1. Symlink + enable (standard AHG plugin flow)
php bin/atom extension:enable ahgC2paPlugin

# 2. Create the manifest + provenance stores (an existing ahg_c2pa_manifest
#    created by ahgMetadataExportPlugin also needs upgrade_heratio_parity.sql once)
mysql -u root archive < atom-ahg-plugins/ahgC2paPlugin/database/install.sql

# 3. Ensure a signing key exists (shared with ahgAiCompliancePlugin)
sudo -u www-data php symfony ai-compliance:install-key

# 4. Clear cache + restart
rm -rf cache/* && sudo systemctl restart php8.3-fpm
```

Signing degrades gracefully: with no key installed, manifests are still built and
sidecar'd (unsigned); verification of unsigned manifests reports the missing
signature.

## Provenance, AI outputs and public verification (Heratio parity)

- **Auto-provenance.** When a request creates digital objects (any POST, or a CLI
  task such as `ingest:commit`), every new master gets a signed digitisation
  provenance record at the end of that request: sidecar next to the master, plus
  an embedded `<stem>.c2pa.<ext>` copy for JPEG/PNG/TIFF/MP4 (c2patool) or PDF.
  Encrypted-at-rest masters are signed as plaintext and keep the sidecar only.
  Nothing is written when no signing key is readable, so the backfill can sign later.
- **`sudo -u www-data php symfony c2pa:provenance-backfill [--id=] [--limit=] [--dry-run]`**
  records the masters that have none. Idempotent.
- **`sudo -u www-data php symfony c2pa:embed [--id=] [--limit=] [--dry-run]`** (re)embeds
  signed provenance manifests into master copies.
- **AI outputs.** ahgAIPlugin signs an `ai-generated` manifest for NER, summaries,
  translations, HTR, LLM descriptions and suggestions, and `ai-assisted` when a
  human edits a suggestion before approving it. Best effort; never breaks the AI call.
- **Staff pages:** `/admin/c2pa/coverage`, `/admin/c2pa/object/:id` (records + manifests),
  `/admin/c2pa/object/:id/record` (record a digitisation).
- **Public pages:** `/c2pa/record/:id` (the target of the Content Credentials badge on
  record pages), `/c2pa/object/:id`, `/c2pa/object/:id/download`,
  `/c2pa/object/:id/credentials`, `/c2pa/object/:id/badge`, `/c2pa/object/:id/badge-json`
  and the drop-in checker `/c2pa/check`. Record pages respect the read ACL; the download
  and sidecar require readMaster.
