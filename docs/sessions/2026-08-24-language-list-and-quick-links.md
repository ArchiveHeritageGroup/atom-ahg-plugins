# An empty language select and a Contact link that could only 404

Date: 2026-08-24
Releases: atom-framework v2.18.14, atom-ahg-plugins v3.106.23
Reported by: Stefan du Toit, from the Wits archaeology instance (146.141.9.111)

## Language preference offered nothing

The Language preference select on the authority record contact form showed only
"Select...". Not an instance problem - it was empty on every instance ever built.

`LanguageService::getAll()` queried `taxonomy_id = 12`. AtoM's own taxonomy ids
begin at 30 (`QubitTaxonomy::ROOT_ID`) and it has no language taxonomy at all:
zero terms measured on both PSIS and archaeology, and no taxonomy whose name even
contains "language". The query could only ever return an empty collection.

`getAll()` now returns the service's own 90-entry ISO 639-1 table, sorted by name,
with the ISO code as `id`. The code is also what the consumer wants:
`contact_information_extended.language_preference` is `varchar(16)`, and the one
value stored on PSIS is an ISO code - the old shape would have written a numeric
term id into that column had it ever returned a row.

`findByCode()`, `findByName()` and `getTermIdFromCode()` still query the same
absent taxonomy and always return null. Nothing calls them; flagged in the
docblock rather than left silent.

## The Contact quick link was a guaranteed 404

The "i" dropdown is hardcoded in `_quickLinksMenu.php` and linked to a static page
with slug `contact`. Base AtoM ships `home`, `about` and `privacy` - there is no
contact page - so on any install where nobody had created one by hand the link
404s. Archaeology reproduced it; PSIS happens to have one, which is why it had
never surfaced here.

The menu now lists only pages that exist, and picks up `privacy`, which base ships
and the menu had been ignoring. Fails open on a database error: a link that might
404 beats dropping the menu over an unrelated fault.

## The deploy trap this exposed

Framework v2.18.14 deployed to archaeology cleanly - correct tag, correct version -
and `LanguageService::getAll()` still returned 0.

Where `ahgRuntimePlugin` is enabled, every PSR-4 `AtomFramework\*` class resolves
to that plugin's **generated** copy of `src/`, not to `atom-framework/src/`. The
plugin is not in git, so a pull can never refresh it. Fix:

```bash
cd <root>/atom-framework && sudo -u www-data bin/build-runtime-plugin
```

What makes it easy to miss: classes loaded by explicit `require_once` from
`$rootDir/atom-framework` - the routing classes - come from the fresh framework.
On this same deploy the new `AhgSafeMetadataRoute` worked immediately while
`LanguageService` did not. **A working framework fix does not prove the framework
deployed.** `ReflectionClass::getFileName()` settles it in one line.

## Verified

PSIS: quick links render About, Contact and Privacy, all three resolve 200;
90 languages returned; error log clean.

Archaeology after rebuild: 90 languages, quick links show About and Privacy with
Contact correctly absent, home and browse 200, `;dam` still 200.

## Still open

The "Authorized form of name" dropdown rendering rows of `?` is NOT fixed. The
widget is base AtoM's YUI autocomplete, which builds each row from
`$('td a', this).html()` of the table returned by `/actor/autocomplete`. Running
that action's exact query on archaeology returns correct labels for all 12 actors,
the AHG template is byte-identical to base's, and no actor name in the database
contains a `?`. The fault is either Wits-specific data or the response body not
being the table the parser expects, and the raw response from that instance is
what separates them:

```
http://146.141.9.111/index.php/actor/autocomplete?showOnlyActors=true&query=a
```

The Wits instance also still needs every fix from today deployed.
