# ahgSharePointPlugin

Microsoft 365 SharePoint integration for AtoM Heratio. One-way ingest from SharePoint into AtoM with eventual records-handoff via Graph webhooks and federated search across both surfaces.

**Status:** Phase 1 scaffold (v0.1.0). Tables and config skeleton in place; service implementations are TODO.

**Plan:** [`atom-extensions-catalog/docs/technical/ahgSharePointPlugin_Implementation_Plan.md`](../../atom-extensions-catalog/docs/technical/ahgSharePointPlugin_Implementation_Plan.md)

**Heratio counterpart:** [`heratio/packages/ahg-sharepoint/`](../../../heratio/packages/ahg-sharepoint/) — schema and feature parity required.

## Phases

1. **Foundation** (this scaffold) — tenant config, drive registration, manual delta sync (`sharepoint:sync`), settings UI, audit-trail.
2. **Webhooks** — subscription lifecycle, records handoff via existing `IngestCommitService`, Purview retention-label mapping. Gated on a half-day verification spike.
3. **Discovery** — AtoM-side federated search tab (staff-only), M365-side Microsoft Search connector feed.

## Install (once services are implemented)

```bash
cd /usr/share/nginx/archive
php bin/atom extension:enable ahgSharePointPlugin
php symfony sharepoint:install
php symfony sharepoint:test-connection --tenant=1
```

## Tables

`sharepoint_tenant`, `sharepoint_drive`, `sharepoint_mapping`, `sharepoint_sync_state`, `sharepoint_subscription`, `sharepoint_event`. Plus an additive migration on `ingest_session` (adds `source` and `source_id` columns).

## Locked architectural decisions

See plan §2. Highlights:
- Hand-rolled Graph client (no microsoft/microsoft-graph SDK)
- Settings section added to `ahgSettingsPlugin` (precedented edit to locked plugin)
- `firebase/php-jwt` for Phase 3 inbound JWT validation
- Webhook URL: `psis.theahg.co.za/sharepoint/webhook` direct
- Federated search gated to AtoM staff (editor/admin) only
