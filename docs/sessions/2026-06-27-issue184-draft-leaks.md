# #184 — Class-1 draft leaks fixed: gallery / library / museum / display

**Date:** 2026-06-27 · From the #178 audit.

Public/anon read paths were returning UNPUBLISHED (draft) records because they
built their own IO queries without the published-status filter. Fix pattern: for
unauthenticated visitors, restrict to published records (`status` type_id 158 /
status_id 160); staff (any authed user) keep seeing drafts. Mirrors OpacService /
applyFilters().

## ahgGalleryPlugin (1.2.11)
- `browseAction` — added `whereExists` published filter for guests on the gallery IO query.
- `indexAction` (show) — renamed the by-slug query to `$resourceQuery`, added the guest published `whereExists` so a draft gallery record 404s for anon.
- `dashboardAction` — staff overview (recent items incl. drafts) → `forward('admin','secure')` for anon.

## ahgLibraryPlugin (1.9.15) — LOCKED plugin, modified under explicit #184 authorisation
- `lib/Service/ExportService::fetchItems` — new `published_only` param adds the published `whereExists`; flows through `export()` too.
- `exportAction` — sets `filters['published_only'] = !isAuthenticated`, so an anon bulk CSV/BibTeX/RIS export can't dump drafts.
- `browseAction` — a `$publishedFilter` closure applied via `->where($publishedFilter)` on both the FRBR and flat queries (guests only).

## ahgMuseumPlugin (1.4.18)
- `dashboardAction` — staff overview → `forward('admin','secure')` for anon.

## ahgDisplayPlugin (1.3.1)
- `tryElasticsearchFuzzy()` — the fuzzy-fallback path bypassed `applyFilters()`. For guests, the `multi_match` is now wrapped in a `bool` with `filter: [{term: {publicationStatusId: 160}}]`; staff keep the unfiltered fuzzy search.

## Verified
php -l clean on all 7 files; cache + php-fpm restart. `/gallery/browse` + `/library` → 200 (public reads work, now published-filtered for guests); `/gallery/dashboard` + `/museum/dashboard` → 403 anon. No 500s.

Remaining #178 backlog: #185 (3d-model/spectrum/marketplace IDOR — MEDIUM, all unlocked).
