# #151 — Federated GLAM network: union catalogue surface — 2026-06-15

**Issue:** #151 (twin of Heratio #1203). **Plugin:** ahgFederationPlugin. **Status:** union-catalogue surface built + verified live, unreleased. Issue STAYS OPEN (loan-request flow deferred).

## Delivered (the buildable half — union catalogue)
A harvested record = an `information_object` with a `federation_harvest_log` row attributing it to a source peer; everything else is local. Built on that:
- `lib/UnionCatalogueService.php` (ns AhgFederation): `members()` (peers + contributed-record counts), `counts()` (local/harvested/total/members), `browse()` (unified record list with source attribution + peer/source/q filters + pagination).
- `federationActions::executeUnion` (admin-gated) + route `federation_union` → `/admin/federation/union`.
- `unionSuccess.php` — counts cards, members table (with per-member record counts + harvest link), search/source filter, unified records table with Local/peer source badges, pagination.

## Verified
- All `php -l` clean; `/admin/federation/union` → 200 (admin gate, no 500).
- CLI: counts {local:710, harvested:0, total:710, members:1}; browse total 710 with source attribution. (No harvested records yet — harvest_log empty — so all show Local; peer attribution activates as harvests run.)

## Deferred (rest of #151 — keep issue open)
- **Inter-institution loan request flow** over the federation — a `federation_loan_request` model + workflow on `ahgLoanPlugin` (request a peer's item; status lifecycle). Buildable but the actual cross-institution delivery is external (peer-to-peer); model + status are local.
- **Opt-in membership model** (join/subscribe handshake) and **Europeana/EDM export** (external).
