# Heritage branding, the CSP attribute trap, and one out-of-sync registry

**Date:** 18 August 2026
**Releases:** atom-ahg-plugins v3.103.31, v3.103.32
**Instances:** Wits production (146.141.9.111), 131 (archaeology.theahg.co.za)

## The archaeology heritage page was never lost - it was on the old instance

The branding and content Johan remembered were on the ORIGINAL archaeology instance,
`/usr/share/nginx/archeology` on 112, which still holds its 133 descriptions and was
untouched by the hostname move to 131.

The heritage landing page is entirely database-driven. Migrated to both boxes
(227 statements, zero errors):

| Table | Content |
|---|---|
| `heritage_hero_slide` | "Wits Archaeological Collection / From the Cradle of Humankind", Sterkfontein image, CTA, photographer credit |
| `heritage_branding_config` | `#043673` navy, `#917248` gold |
| `heritage_landing_config` | tagline, subtext, search placeholder, suggested searches |
| `heritage_explore_category` / `_era` / `_timeline_period` | 12 / 44 / 12 rows |

Plus `uploads/heritage/hero/sterkfontein.jpg` (193,698 bytes).

⚠️ **Read every culture when copying settings.** A query taking the first
`setting_i18n` row returned only Afrikaans and made it look as though no English
existed. Both were copied verbatim: "Wits Archaeological Collection" /
"Wits Argeologiese Versameling".

⚠️ `siteBaseUrl` was `http://127.0.0.1` on Wits, which would have put localhost into
every generated email link and export URL.

## Three symptoms, one cause: atom_plugin out of sync

Johan noticed `/admin/ahg-settings/plugins` showed far fewer plugins than
`/sfPluginAdminPlugin/plugins`. Same root cause as two other faults:

**AtoM loads from the serialised `plugins` setting. `atom_plugin` is the AHG
framework's own registry, and `tools:atom-plugins add` does not touch it.**

| | `plugins` setting | `atom_plugin` |
|---|---|---|
| Wits before | 36 enabled | 4 rows, 3 enabled |
| 131 before | 36 enabled | **table did not exist** |

What that broke:

- `/admin/ahg-settings/plugins` reported plugins as not installed while they ran.
- **The theme's admin menu vanished.** `ahgIsPluginEnabled()` in
  `ahgThemeB5Plugin/templates/_ahgAdminMenu.php` reads `atom_plugin` and wraps it in
  `catch (Exception) { $enabledPlugins = []; }`. On 131 the table was absent, so every
  entry was hidden - and a caught exception is indistinguishable from "nothing
  enabled". This is the guard-that-turns-a-fault-into-silence pattern again.
- Heritage admin was therefore reachable only by typing a URL.

`atom_plugin`, `atom_plugin_audit` and `atom_plugin_menu` are created by
**`atom-framework/database/install.sql`**, not by any plugin - an instance built
without that step has no registry at all. Created on 131, then both boxes reconciled
against the live setting: 36 rows, 36 enabled. Menu verified rendering with a real
login.

⚠️ **AhgNav registration is not enough on a themed instance.** The heritage entries
existed in `ahgHeritagePluginConfiguration` but declared
`'route' => ['module' => ..., 'action' => ...]` - an ARRAY, where
`AhgNav::knownRoute()` is typed `string`. Corrected to route names
(`@heritage_admin_dashboard`) in v3.103.31. But with ahgThemeB5Plugin present the
theme draws its own menu from `_ahgAdminMenu.php`, so the registry fix is what
actually surfaced it.

## The CSP trap that makes a working image look missing

Hero images did not render in the browser - including incognito - while `curl`
fetched them with HTTP 200 and the correct byte count.

`_heroSection.php` and `landingSuccess.php` set the background with
`style="background-image: url(...)"`. **A CSP nonce authorises `<style>` ELEMENTS; it
can never authorise a style ATTRIBUTE** - there is nowhere to put the nonce. With
`style-src 'self' 'nonce-...'` the browser dropped it silently.

Nothing was wrong server-side, which is exactly what made it look like a missing
file: **curl does not enforce CSP**.

The instance being compared against had `style-src 'self' 'unsafe-inline'`, which hid
the problem entirely - so the same code looked fine there and broken here.

Fixed in v3.103.32 by emitting the rules through `ahg_style_block()` (nonce'd
`<style>`, from ahgCorePlugin's `AhgCsp` helper, loaded for every template), keyed on
`data-index`:

    .heritage-hero-bg[data-index="0"]{background-image:url('/uploads/heritage/hero/sterkfontein.jpg');}

`'unsafe-inline'` was NOT added anywhere. Paths containing a quote, paren or backslash
are refused rather than escaped - url() is CSS, not HTML.

## nginx served no heritage uploads

The stock AtoM nginx config serves static files only from
`/(css|dist|js|images|plugins|vendor)/` and `/uploads/r/`. There is no rule for
`/uploads/heritage/`, so the request fell through to `location /`, which does
`if (-f $request_filename) { return 403; }` - **403 for any file that exists on
disk**. Added, after the `/uploads/r/` rules:

    location /uploads/ {
        add_header Access-Control-Allow-Origin "*" always;
        add_header Access-Control-Allow-Methods "GET, OPTIONS" always;
    }

The `/uploads/r/` locations are regex and take precedence over this prefix block, so
masters still route through PHP and keep their ACL check - verified: a master path
returns 404 through PHP, not a file off disk.

## Heritage admin URLs

    /index.php/heritage/admin                      dashboard
    /index.php/heritage/admin/hero-slides          pictures
    /index.php/heritage/admin/branding             colours
    /index.php/heritage/admin/config               landing text
    /index.php/heritage/admin/features             feature toggles
    /index.php/heritage/admin/featured-collections

## State

Both boxes: v3.103.32, 37 plugins, `atom_plugin` reconciled at 36, ahgThemeB5 theme,
heritage landing with the Sterkfontein hero, archaeology branding in both languages,
4,682 sites. Heritage menu verified rendering.

## Open

- 112: `AllowTcpForwarding remote` to revert to `no`; the root `authorized_keys` entry
  added there is unusable (`PermitRootLogin no`) and should be removed.
- `arDominionB5Plugin` enabled on Wits, absent on 131.
- 131's framework schema load reported 32 insert errors against pre-existing tables
  with drifted columns - separate from this work, not investigated.
- The 16 coordinate conflicts still need a human decision.
