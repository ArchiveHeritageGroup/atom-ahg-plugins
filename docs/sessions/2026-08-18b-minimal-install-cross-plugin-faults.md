# A minimal install breaks in ways a full one never shows

**Date:** 2026-08-18 (later session)
**Releases:** atom-ahg-plugins v3.103.16 to v3.103.18, atom-framework v2.18.4 and v2.18.5
**Machine:** atom210 (192.168.0.131), clean Ubuntu 24.04 + AtoM 2.10 + 34 plugins

## The theme build was erasing its own head block

`_layout_start.php` is a **build artefact**. AtoM's webpack creates an
HtmlWebpackPlugin for every plugin that has `webpack.entry.js` and
`templates/_layout_start_webpack.php`, and writes the result to
`templates/_layout_start.php`.

All of ahgThemeB5Plugin's head customisations had been written into the *generated*
file: the CSRF meta tag, the script that auto-attaches `X-CSRF-TOKEN` to every unsafe
AJAX request, the bundle discovery, and the Bootstrap Icons stylesheet. Any
`npm run build` erased all four.

PSIS and archaeology were unaffected only because nobody had re-run the build since
those edits. **Any fresh install following AtoM's documented steps lost CSRF
protection silently**, with nothing in any log. Fixed by promoting the working
template to be the source (v3.103.16); verified by building twice.

⚠️ On any instance that has not yet pulled v3.103.16, running `npm run build` will
strip CSRF. Pull first.

Related: the theme ships no prebuilt CSS. Symlink the theme **before** `npm run build`
or `dist/css/ahgThemeB5Plugin.bundle.*.css` is never produced and the site renders
unstyled with no error - the template globs for it and finds nothing.

## Four cross-plugin faults that a full install cannot reveal

A minimal install is the only place these appear, because every instance we own has
the plugins that were missing.

| Fault | Missing thing belongs to | Fixed in |
|---|---|---|
| `Unable to load "MediaHelper.php"` - every description page 500 | ahgIiifPlugin | ahgUiOverridesPlugin, try/catch |
| `object_rights_holder doesn't exist` - every description page 500 | ahgExtendedRightsPlugin | framework `AccessFilterService::checkDonorRestrictions` |
| `extended_rights doesn't exist` - every description page 500 | ahgExtendedRightsPlugin | framework `checkEmbargo` and `applyEmbargoFilter` |
| `favorites doesn't exist` - error logged on every record view | ahgFavoritesPlugin / ahgCartPlugin | theme record template |

**The framework one is the serious one.** `AccessFilterService` ships inside
`ahgRuntimePlugin` to every install. Without ahgExtendedRightsPlugin, **every archival
description page returned HTTP 500** - an archive where no record can be opened. This
is precisely the standalone scenario the packaging work exists to support.

All four guards **fail open**: a missing optional plugin means "no restriction
recorded", not "deny access". Classification is the real access control and is
untouched.

## Menus and settings inferred from schema rather than enablement

`$hasExhibitionSpace` asked `hasTable('ahg_exhibition_space')` while every other flag
in the same file asked `ahgIsPluginEnabled()`. Tables outlive enablement, so a plugin
whose schema was ever loaded kept its menu entry for good and offered a link into a
module that is not loaded.

This matters more after the schema merge: every plugin's `install.sql` now creates its
**full** table set, so schema-based inference lights up far more often than before.

Also gated (v3.103.17): Media Processing on ahgIiifPlugin, Semantic Search on
ahgSemanticSearchPlugin, and Watermark Settings on **ahgDAMPlugin** - the page is
served by ahgSecurityClearancePlugin but reads DAM's tables, so it is gated on the
plugin that owns the data, not the one serving the page. `$sectionPluginMap` went from
16 entries to 21.

## ahgRuntimePlugin's manifest is now derived

`build-runtime-plugin` wrote an extension.json with no `tables` key at all, so the
installer had nothing to verify and reported success without checking anything. It now
derives the list from the generated `install.sql`, stripping comments and allowing
optional backticks - a line reading `-- CREATE TABLE IF NOT EXISTS, safe to re-run`
otherwise matched and produced a table called "IF". Now reports
`verified all 3 declared tables exist`.

## Mistakes made and corrected

- Guarded the donor query in **ahgSecurityClearancePlugin** first. Real code path, but
  not the live one - the caller was the framework. The plugin guard was kept; the
  framework guard is what fixed it.
- Reported "three tiles gated" when the edit had not applied: an assertion had been
  disabled and nothing changed. Same false-success pattern being removed everywhere
  else. Verify the file, not the script's own claim.
- Instructed "symlink the theme, then npm run build" without realising the build would
  strip the theme's head block. Correct ordering, incomplete understanding.

## State

atom210 runs 34 plugins including ahgThemeB5Plugin, ahgHeritagePlugin, ahgIiifPlugin,
ahgMiradorPlugin and ahgSeadragonPlugin. Record pages, browse, actor browse and
heritage all return 200 with **zero PHP errors**. It matches archaeology's look and
feel - same theme bundle, icons loading, `/heritage` serving.
