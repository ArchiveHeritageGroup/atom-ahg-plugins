# ahgTimeLimitedShareLinkPlugin

Time-limited, auditable share links for information_object records. Anonymous bearer-token access (token = credential), HMAC-derived URL-safe identifiers, optional max-access count, expiry caps, classified-record gating, admin revocation, central audit feed integration.

## Status

Phase A — schema only. Service and UI phases land in subsequent build phases.

## Architecture

- `database/install.sql` — schema: `information_object_share_token` + `information_object_share_access` tables, FK to `information_object` and `user`. Base AtoM schema is NOT modified.
- `lib/Services/` (Phase B onwards) — TokenService, IssueService, AccessService, RevokeService.
- `lib/Listeners/` (Phase E) — response-filter listener that injects the "Share link" button on IO view pages.
- `modules/shareLink/` (Phase D+F) — recipient landing controller + admin index.
- `templates/display/` (Phase E) — issue modal partial.

## Base AtoM impact

**Zero schema changes to base AtoM tables.** The share-link tables reference `information_object.id` and `user.id` via FK as read-only relationships.

## Reference

Build plan: `/usr/share/nginx/archive/uploads/F1 Build Plan - Time-Limited Sharing.md`
Design doc: `/usr/share/nginx/archive/uploads/AHG Feature Development Plan - Records Management.md`

## Pattern lineage

The schema and token-lifecycle pattern mirror the proven AHG share-token implementations in Heratio:

- `portable_export_share_token` (offline export catalogue)
- `report_share` (time-limited report sharing)
- `favorites_share`, `research_institutional_share`

This plugin extends the same pattern to archival `information_object` records.
