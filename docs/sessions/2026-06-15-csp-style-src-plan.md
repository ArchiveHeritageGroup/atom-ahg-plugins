# CSP `style-src 'unsafe-inline'` removal — PLAN / decision memo — 2026-06-15

## Current state (measured)
- CSP mode: **Report-Only** (`config/app.yml:68` → `Content-Security-Policy-Report-Only`) — nothing is enforced today.
- `script-src`: `'self' 'nonce' …CDNs… blob:` — **no `'unsafe-inline'`** (the important protection — already in place).
- `style-src`: `'self' 'unsafe-inline' …CDNs…` — the line in question.
- Inline surface in atom-ahg-plugins (excl. backups):
  - **2,834 inline `style="…"` attributes** (~**245 dynamic** = PHP-interpolated values; ~2,589 static).
  - **60 bare `<style>` tags** without a nonce (127 already nonced).

## The CSP mechanics that constrain this (why it's not a quick win)
1. **Inline `style="…"` *attributes* cannot carry a nonce.** They are allowed ONLY by `'unsafe-inline'` or by `'unsafe-hashes'` + a SHA-256 hash of each distinct value.
2. **Adding a nonce to `style-src` *disables* `'unsafe-inline'`** for that directive in modern browsers (CSP3: when a nonce/hash is present, `'unsafe-inline'` is ignored). So you can't "nonce the `<style>` tags AND keep unsafe-inline for attributes" — turning on nonces breaks all 2,834 attributes.
3. **`'unsafe-hashes'` doesn't scale here:** ~2,589 static attributes → potentially hundreds–thousands of unique hashes baked into the CSP header (huge, unmaintainable), and the **245 dynamic** attributes **can't be hashed at all** (value changes per request) → they'd be permanently blocked.

→ Therefore **removing `style-src 'unsafe-inline'` requires refactoring all 2,834 inline style attributes** to CSS classes / external CSS / CSS custom properties. That's a large, cross-plugin program (many templates, several in locked plugins) with **low security ROI**: CSS injection via a style attribute is far less dangerous than script injection, and `script-src` is already locked down.

## Options
- **A — KEEP `style-src 'unsafe-inline'` (recommended).** Document it as an accepted risk. Zero breakage, zero effort. This is the mainstream pragmatic stance; the high-value control (`script-src` no-unsafe-inline) is already enforced-ready.
- **B — Full removal (long-horizon, only if mandated).** Refactor all inline style attributes → classes/CSS-vars, add `'nonce'` to `style-src`, nonce the 60 bare `<style>`, then drop `'unsafe-inline'`. Multi-week+ across all plugins incl. locked ones; high regression risk. Must be incremental + Report-Only throughout.
- **C — Hybrid (not recommended).** `'unsafe-hashes'` for static + refactor dynamic — the hash-list explosion makes it impractical.

## Recommended plan
1. **Decision: adopt Option A** — keep `style-src 'unsafe-inline'`; record it as an accepted, compensated risk (script-src is the real guard). No code change.
2. **Cheap hardening that does NOT require removal (do regardless):**
   - Nonce the **60 bare `<style>` tags** (mechanical, like the script-nonce work) — harmless now, and prerequisite if B is ever pursued.
   - **Stop adding new inline styles**: new templates use CSS classes / a plugin CSS file. (Convention / review rule.)
3. **Only if full removal is later mandated (Option B), in order:**
   a. Refactor the **245 dynamic** style attributes first (→ CSS custom properties set in a nonced `<style>`, or data-attributes + CSS). These are the ones that can never be hashed.
   b. Sweep the ~2,589 static attributes → classes, per-plugin, behind Report-Only.
   c. Add `'nonce'` to `style-src`, nonce all `<style>`, verify zero Report-Only violations, then drop `'unsafe-inline'` and flip nothing else.
4. **Before ANY enforcement flip** (separate from style-src): the missing-nonce `<script>` fixes (done this session) must be deployed, and Report-Only violation reports reviewed.

## ✅ DECISION + hardening done (2026-06-15)
- **Option A adopted:** `style-src 'unsafe-inline'` kept (no app.yml change) — accepted/compensated risk; `script-src` is the real guard.
- **`<style>`-nonce hardening — verify-first collapsed the "60":** most "bare" hits were already nonced via a `$nonceAttr`/`$na` variable (the literal-`csp_nonce` grep missed them), comments, PDF-generator service files (HTML→PDF engine, no browser CSP → pointless), or locked plugins. Genuinely-bare, non-locked, browser-template `<style>` = **2 files, both nonced now:** `ahgExtendedRightsPlugin/templates/_downloadBlocked.php`, `ahgLandingPagePlugin/.../_block_browse_panels.php`. Lint clean; zero non-locked bare template `<style>` remain.
- **Left (flagged):** 3 ahgCorePlugin print-label templates (boxLabel/itemOrFileList/storageLocations — bare but LOCKED) + any other locked-plugin/PDF-generator `<style>` (pointless or need naming). Harmless (style-src still allows inline).

## Bottom line
This is a **decision, not a build**: removing `style-src 'unsafe-inline'` is a large, low-ROI refactor. Recommend keeping it (Option A) + the cheap `<style>`-nonce hardening, and treating full removal as a deferred, incremental program only if a compliance requirement forces it.
