# 2026-06-09 — Heratio→PSIS forward parity: 4 new plugins (#145–#148)

## Summary
Built the four genuine Heratio→PSIS forward-parity gaps (from the 2026-06-09 functionality audit) as **new, self-contained plugins** in `atom-ahg-plugins` — deliberately avoiding edits to the locked stable plugins (ahgIiifPlugin / ahg3DModelPlugin / ahgHelpPlugin), mirroring Heratio's own per-package structure. All four are **live and web-verified on PSIS**; not yet released, not on ANC/WDB.

## Plugins

### ahgEmailDeliveryPlugin (#145) — email bounce + suppression
- Table `ahg_email_suppression`; `EmailSuppressionService` with a static `isSuppressed()` gate (hard bounce / complaint / manual block immediately; soft bounces only after 3).
- `ingestWebhook()` parses Amazon SES/SNS, Mailgun, SendGrid event arrays, and a plain `{email,type}` body.
- Routes: `POST /email/bounce` (provider webhook, optional shared secret `app_email_webhook_secret`) and `/admin/email/suppressions` (admin list/add/remove).
- Other AHG send paths can gate with `EmailSuppressionService::isSuppressed($email)` / `filterDeliverable($list)`.
- **Deploy note:** add `/email/bounce` to the nginx bot-blocker `$api_bypass` so empty-UA provider POSTs aren't blocked (same as `/sharepoint/webhook`).

### ahgAnnotationsPlugin (#146) — standalone W3C Web Annotation backend
- Table `ahg_web_annotation` (full W3C JSON-LD in `body_json`, `target_hash` = sha1(target) for query); `WebAnnotationService` (uuid4 ids, create/get/update/delete/container).
- W3C Web Annotation **Protocol**: `GET /annotations?target=` (AnnotationCollection), `POST /annotations` (create → 201 + `Location`), `GET|PUT|DELETE /annotations/:uuid`. CORS + OPTIONS preflight.
- Verified end-to-end over the web: create 201 + Location + JSON-LD body, read, query-by-target, auth-reject, not-found, OPTIONS.

### ahgImageArPlugin (#147) — 2D image in augmented reality
- `/imagear/:slug` renders a WebXR AR viewer (three.js 0.160 core via jsdelivr importmap, hit-test surface placement of a textured plane). Graceful fallback when WebXR unavailable.
- `ImageArService` resolves the REFERENCE (142) derivative, falling back to the master. TIFF-master-only objects need a web JPG reference to display in-browser.

### ahgFunctionsDocsPlugin (#148) — system catalogue
- `/admin/docs/catalogue` (admin-only): browsable, filterable tabs of routes / CLI tasks / services, auto-generated from the live routing table + a source scan (live counts: 3829 routes, 159 tasks, 350 services).

## Deploy applied on PSIS (replicate on ANC/WDB at end-of-cycle)
- 4 symlinks in `plugins/`; DDL for `ahg_email_suppression` + `ahg_web_annotation`; `atom_plugin` rows (load_order 216–219, is_enabled=1); cache clear + php-fpm restart.

## AtoM gotchas hit (documented for reuse)
1. A `profile="..."` parameter on the response content-type **blanks POST bodies** in AtoM (GET tolerates it). Use plain `application/ld+json` + advertise the context via a `Link` header.
2. Setting any **4xx status** triggers AtoM's themed error page (`error_404_module`). For a JSON API, keep HTTP 200 and put the code in the body (as `AhgController::renderJsonError` does).
3. Overriding an `AhgController` method (`requireAuth(): void`) with a different signature is a hard fatal.
4. Never run a `debug=true` CLI dispatch against the shared `cache/qubit/prod` tree — it corrupts the prod config cache (site-wide 500: `arOpenSearchConfigHandler not found`). Recover with a full `cache/qubit/prod/*` clear + fpm restart + a real web request.

## Status
Built + live + web-verified on PSIS. Pending: release (`./bin/release minor`), nginx `/email/bounce` api_bypass, ANC/WDB deploy, close #145–#148.
