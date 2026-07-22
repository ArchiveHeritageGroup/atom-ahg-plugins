# Archaeology demonstration collection

Rebuilds the Kranskop Shelter demonstration set used to show the archaeology
cataloguing model. **Every record is fictional.** The site does not exist, and
each generated photograph is stamped `DEMONSTRATION - NOT A REAL RECORD`.

This is demo data, not configuration. The configuration it depends on -
vocabularies, custom field definitions, UI labels, the Browse menu entry - lives
in `ahgCustomFieldsPlugin/database/seeds/archaeology_profile.sql` and is
installed separately.

## What it builds

Two hierarchies in one instance, joined on the site and context identifier:

```
KRK        Kranskop Shelter                    Series      <- the site
  KRK-T1     Trench 1                          Subseries   <- the trench
    KRK-1001   Context 1001, upper ashy layer  File        <- the context
      KRK-SF214  Glass beads, small find 214   Item        <- the find
      ...
KRK-ARCH   Kranskop Shelter excavation archive Fonds       <- the site archive
  KRK-ARCH-1 Context sheets                    Series
    ...                                        File
```

Standard ISAD(G) levels throughout. Series is the site, Subseries the trench,
File the stratigraphic context. Phase is **not** a level - it is a vocabulary
applied as a term, because phase cuts across the spatial hierarchy: a context in
Trench 1 and one in Trench 2 routinely belong to the same phase, which a tree
cannot express when each context already has one parent.

Also creates 4 storage boxes across 3 locations with the finds located in them,
and 7 placeholder object photographs with thumbnail and reference derivatives.

## Running it

Order matters. Run from the instance root (paths inside assume
`/usr/share/nginx/archeology` - edit the `require` lines for another instance).

```bash
# 0. clear existing content. DESTRUCTIVE - take a mysqldump first.
#    Keeps users, terms, taxonomies, settings and the i18n catalogues.
php 00-clear-content.php --apply

# 1. the collection: repository, hierarchy, vocabularies, accession
php 01-seed-collection.php --apply

# 2. object description into Extent and medium, condition into
#    Physical characteristics, written to a fixed convention
php 02-enrich-finds.php --apply

# 3. placeholder photographs (needs ImageMagick)
python3 03-make-placeholder-photos.py /tmp/arch-photos

# 4. load them through AtoM's own loader so derivatives are real
#    build a CSV of slug,filename then:
#    sudo -u www-data php symfony digitalobject:load --index /tmp/arch-do.csv

# 5. storage boxes and locations, and clear any orphaned location rows
php 04-seed-storage.php --apply

# 6. rebuild
sudo -u www-data php symfony propel:build-nested-set
sudo -u www-data php symfony search:populate

# Facets ("Narrow your results by:") read a pre-computed cache, NOT live
# aggregations. Without these two the browse facet sidebar is EMPTY however
# much data exists: auto-detect assigns each record its GLAM type, then the
# facet cache is rebuilt from the current data.
sudo -u www-data php symfony display:auto-detect
sudo -u www-data php symfony ahg:refresh-facet-cache

sudo -u www-data php symfony cc && sudo systemctl reload php8.3-fpm
```

Every script supports a dry run - omit `--apply` to see what it would do. Each
wraps its writes in a transaction and rolls back rather than half-applying.

## Gotchas found the hard way

- `actor` has **no** `lft`/`rgt` columns; `information_object` does.
- Publication status lives in the `status` table, not on `information_object`.
- `accession` has no default for `created_at`/`updated_at` - pass them.
- `entity_type_id` **131** is Corporate body. 132 is Person - typing a
  repository as a person renders wrongly.
- Physical Object Type is taxonomy **48**; Box is term **223**.
- Reload with `systemctl reload php8.3-fpm`, not `restart` - restart drops the
  socket and briefly 502s the live site.
