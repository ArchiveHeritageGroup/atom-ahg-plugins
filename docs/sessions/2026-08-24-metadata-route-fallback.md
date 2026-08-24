# A description in a standard the instance cannot render returned 500 on its own permalink

Date: 2026-08-24
Releases: atom-framework v2.18.13, atom-ahg-plugins v3.106.21
Reported by: Stefan du Toit, from the Wits archaeology instance (146.141.9.111)

## Symptom

Creating an archival description with the Photo/DAM (IPTC/XMP) display standard,
saved as a draft, produced `500 Internal Server Error` when opening the record.
The record itself saved correctly - the slug existed. What failed was rendering it.

## Cause

A description carries its descriptive standard in `information_object.display_standard_id`,
and `QubitMetadataRoute` turns that code into the symfony module that renders it.
Two ways that returns 500, both reachable from the UI:

1. Base AtoM checks the code against a hardcoded whitelist and throws
   `sfConfigurationException` when it is not on the list. The framework ships a
   patch adding `museum/dam/gallery/library/ric`, applied by `bin/install`, but an
   instance running stock base routing stops at `dacs`.
   Signature: `The metadata code "dam" is not valid.`

2. The code is whitelisted but the plugin owning the module is not enabled, so
   symfony resolves the template directory to `""`.
   Signature: `The template "indexSuccess.php" does not exist or is unreadable in "".`

The display-standard picker offered every term in taxonomy 70 regardless of what
was installed, so an editor could choose a standard the instance could not render
and make the record unreachable at its own URL.

Reproduced on archaeology, which runs stock base routing:

```
;isad 200   ;dam 500   ;museum 500   ;library 500
```

## Fix

**Read side** - `atom-framework/src/Routing/AhgSafeMetadataRoute.class.php` (new).
Resolves the first standard the instance actually has a module for: the one
requested, then the instance default, then the allowed codes in order. Module
presence is tested against `getPluginPaths()`, which lists enabled plugins only.
Hands back to the parent if nothing resolves, so behaviour is unchanged where
nothing is missing. Also uses `static::` on the legacy template map, which the
parent reads with `self::` - silently ignoring every subclass map.

`ahgCorePlugin` installs it at `routing.load_configuration` by swapping the class
on the existing routes in place, under their existing names, carrying each route's
own pattern, defaults, requirements and options across. Deliberately not
`prependRoute`: a second `/:slug` would sit in front of every single-segment
plugin route registered earlier.

**Write side** - the picker now offers only standards this instance can render,
in both places that build it (`InformationObjectCrudService::getDisplayStandards()`
for the AHG form, `editAction.class.php` for the base-style form). Both keep the
record's current standard on the list unconditionally; dropping it would make an
ordinary edit silently save a different standard than the record has. Both fail
open - without the routing class every standard is offered, as before.

## Verified

PSIS (all sector plugins present): every probe 200, sector records still render in
their own module (`body class` reads `dam index`, `museum index`, `gallery index`),
all ten standards survive the filter, no new `ahg_error_log` rows.

Archaeology (no sector plugins): `;dam`, `;museum`, `;library`, `;gallery` and an
invented code all 200, error log clean, and the picker now offers exactly the five
archival standards whose modules exist - `isad`, `dc`, `dacs`, `mods`, `rad`.

## Notes

- `ric` resolves to `sfIsadPlugin` on PSIS and is filtered out on archaeology.
  Both match existing behaviour: PSIS's patched base already maps it that way.
- `symfony cc` before the fpm workers respawn leaves old workers running new event
  wiring against a stale class in opcache - six errors in a four-second window on
  the first PSIS reload. Reload first, then clear.
- The Wits instance still needs this deployed; it was diagnosed entirely from
  archaeology's error log, without reaching 146.141.9.111.
