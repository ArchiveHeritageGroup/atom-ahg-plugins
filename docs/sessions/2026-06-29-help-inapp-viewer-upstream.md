# 2026-06-29 — Port in-app help-viewer links upstream from archaeology (v3.79.22)

## Context
While syncing archaeology (own checkout, v3.79.11) against canonical, a three-way compare
found archaeology was AHEAD of canonical on two ahgHelpPlugin templates — it wired the
help buttons to the in-app help viewer instead of external URLs. Per "sync only the newest
code", these were NOT overwritten downward; instead ported upstream to canonical.

## Change (canonical, ported from archaeology)
`ahgHelpPlugin/modules/help/templates/articleSuccess.php`
- User/Tech manual buttons now link to the in-app help viewer
  (`url_for('@help_article_view?slug=...')`) via a `$toHelpSlug()` filename→slug builder,
  instead of external GitHub catalog URLs
  (`github.com/ArchiveHeritageGroup/atom-extensions-catalog/blob/main/docs/...`).
- Dropped `target="_blank"` so help opens in-app, same tab.

`ahgHelpPlugin/modules/help/templates/indexSuccess.php`
- "Documentation Portal" → "Instance Documentation"; the external "Open Documentation"
  link (docs.theahg.co.za) → in-app `@help_category?category=User Manual` "Open User Manual".

## Safety check
Confirmed canonical already has both target routes before porting (no broken links):
- `help_article_view` → `/help/article/:slug` (action: article)
- `help_category` → `/help/category/:category` (action: category)
Both lint clean. Released as v3.79.22 (pushed origin/main + tag).

## Sync context (this turn)
Compared archaeology vs canonical (v3.79.11 → v3.79.21): 49 already in sync, 9 canonical-
newer (5 code + 4 docs synced down to archaeology), 0 conflicts. The remaining
archaeology-local files were left as-is with reasons:
- per-instance/generated: `ahg-generated.css`, `ahgSettingsPlugin` self-symlink,
  4× install.sql (only inline column COMMENTs differ; live DB already built — cosmetic);
- archaeology-ahead: the 2 help templates (ported here) + `ahgHeritagePlugin` homepage
  config (lands everyone on /heritage — left as an archaeology-instance UX decision, not
  upstreamed).
