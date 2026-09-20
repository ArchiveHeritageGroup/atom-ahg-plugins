# Cart and favourites - unauthenticated writes on GET, and three dead ends

**Date:** 2026-09-20
**Plugins:** ahgThemeB5Plugin, ahgDisplayPlugin (8 files)
**Calls:** none. This was found by inspection, not reported - see the closing note.

## What was wrong

`informationobject/addCart`, `addFavorites`, `removeCart` and `removeFavorites` exist
in **both** ahgThemeB5Plugin and ahgDisplayPlugin. All eight had:

- no authentication check
- no CSRF token
- no POST requirement
- no class guard

Nothing links to them. The working cart and favourites features live in
**ahgCartPlugin** (`cart/add`, `browse`, `clear`, `checkout`) and **ahgFavoritesPlugin**
(`favorites/add`, `ajaxToggle`, `bulk`). These eight are vestigial duplicates, reachable
only through the catch-all route `/:slug/:module/:action`.

⚠️ **So an anonymous GET could write to the database.** `addCart` and `addFavorites`
insert a row into `object` **and** into `cart`/`favorites`, with a null `user_id` that
nobody can ever retrieve. A crawler walking slug x module x action - which Bingbot
demonstrably does on this site, and which is how the whole CH-000066 arc started -
could create unbounded rows. The `cart` table already holds 1855 rows from the real
feature, so this is not a dormant table.

### The SQL

`removeFavorites` built its DELETE by string concatenation via `DB::statement()`.
`removeCart` was worse:

```php
$sql = 'DELETE FROM cart WHERE id = "'.$this->resource->id.';';
```

Two faults in one line. The string literal is **unterminated**, so the statement always
errored - the only reason it never deleted anything. And `cart.id` is the cart row's own
object id, not the information object's, so had it parsed it would have deleted an
arbitrary row belonging to **any** user.

## What was changed

Each of the eight now has, before any work:

```php
if (!$this->resource instanceof QubitInformationObject) {
    $this->forward404();
}

if (!$this->getUser()->isAuthenticated()) {
    $this->redirect(['module' => 'user', 'action' => 'login']);
}
```

The redirect-to-login idiom is copied from
`ahgFavoritesPlugin/modules/favorites/actions/addAction`, the canonical implementation.
The class guard matters independently: `addCart` calls
`QubitInformationObject::getById($this->resource->id)` and then `getTitle()` on the
result, so a non-information-object slug whose id is not an information object returned
null and fatalled.

Both DELETEs now use the Illuminate query builder that these files **already import**,
so values are bound rather than interpolated, and `removeCart` is scoped by
`user_id` + `archival_description_id` the way `removeFavorites` is.

### Verified

Anonymous GET on `addCart`, `addFavorites` and `removeFavorites` returns 302 to
`/user/login/`. A wrong-class slug returns 404. Row counts before and after the probes
are identical - cart 1855, favorites 15, object 6821 - so nothing was written.

## Left deliberately unfixed

**`removeCart` has never been loadable.** Both copies declare `class removeCartAction`
where Symfony expects `InformationObjectRemoveCartAction`; the other three are named
correctly. Invoking it logs `Class "informationobjectremoveCartAction" not found` and
returns HTTP 200.

So `removeCart` had **three** independent reasons for never deleting anything: an
unloadable class name, an unterminated SQL literal, and a WHERE clause against the
wrong column.

⚠️ **The name was not corrected.** Fixing it would revive a vestigial destructive
action that nothing links to, while ahgCartPlugin already provides the real remove and
clear. The hardening was applied to it anyway, so that if anyone ever does fix the name
they do not simultaneously open an unauthenticated delete.

## Still open

CSRF and a POST requirement. Both actions are still GET-triggered writes for an
authenticated user. Requiring POST means changing whatever would link to them, and
nothing does today - so it was flagged rather than done. The authentication check
removes the anonymous vector, which was the whole of the exposure a crawler could reach.

## Closing note

⚠️ **None of this had a CallHub call and none of it would ever have got one.** The
actions are unlinked, so no user hits them; `removeCart` returns HTTP 200 while
failing; and an anonymous INSERT succeeds silently. It was found by reading code after
the CH-000066 arc, not by anything reporting it - the same point as the rest of that
work, in its sharpest form: the call queue measures which broken URLs received traffic,
not what is broken.

## Outcome - deleted (2026-09-20, Johan approved)

The hardening above was the right immediate step but the wrong end state. All eight
actions, plus three orphaned `*Success.php` templates in ahgThemeB5Plugin, were
**deleted**. Nothing referenced them anywhere in the codebase - no templates, no routes,
no JavaScript - and ahgCartPlugin and ahgFavoritesPlugin ship the working features.

This closes CH-000112's siblings together:

- **CH-000113** - the `removeCart` class-name mismatch. Deleting the file resolves it
  without reviving a destructive action nobody links to.
- **CH-000114** - CSRF and the POST requirement. An action that no longer exists needs
  neither. Had the duplicates been kept, this would have meant inventing a form for
  eight actions nothing reaches.

⚠️ **Deciding CH-000113 first made CH-000114 disappear rather than be solved.** Working
them in the order they were raised would have produced CSRF tokens on code that was
about to be deleted.

Verified after deletion: all four URLs return 404; `cart/browse` and `favorites/browse`
return 200; the information-object page and fullWidthTreeView return 200; cart and
favorites row counts unchanged at 1855 and 15; no new error-log rows.

## The general lesson

Four actions existed in two plugins, duplicating a feature that already had two
dedicated plugins of its own. They were unreachable by navigation, unreferenced by
anything, and accepted unauthenticated writes. **Nothing in the estate would ever have
surfaced them** - not the call queue, not monitoring, not a user - because nothing
pointed at them in the first place. They were found by reading code outward from an
unrelated bug.
