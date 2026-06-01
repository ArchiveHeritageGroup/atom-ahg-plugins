# ahgResourceSyncPlugin

ResourceSync 1.1 (NISO Z39.99-2017) **Source**-role endpoints for AtoM / PSIS.

Ported from the Heratio `ahg-resourcesync` Laravel package to Symfony 1.4 +
atom-framework, mirroring its data model and behaviour.

## Endpoints

All endpoints are public, read-only, sitemap-formatted XML.

| Capability         | URL                                    | Notes                          |
|--------------------|----------------------------------------|--------------------------------|
| Source Description | `/.well-known/resourcesync`            | Discovery file -> CapabilityList |
| Capability List    | `/resourcesync/capabilitylist.xml`     | Advertises ResourceList + ChangeList |
| Resource List      | `/resourcesync/resourcelist.xml`       | Full inventory, paged via `?page=N` |
| Change List        | `/resourcesync/changelist.xml`         | Updates + tombstones, paged via `?page=N` |

Documents carry the sitemap namespace plus the ResourceSync `xmlns:rs`
extension, with `rs:md` / `rs:ln` supplying protocol metadata.

## Publication filter (OAI parity)

The published-record query mirrors the OAI-PMH `ListRecords` shape exactly:

- `information_object` joined to `object` (for `updated_at`)
- joined to `status` where `type_id = 158` and `status_id = 160` (published)
- `parent_id` non-null and non-zero (excludes the synthetic root)

Tombstones are read from the shared `oai_deleted_record` table, so ResourceSync
ChangeList and OAI ListRecords report the **same** deletion set.

## Tombstones

Record a tombstone so harvesters clean up a removed/unpublished record:

```bash
php bin/atom resourcesync:mark-deleted 1234
php bin/atom resourcesync:mark-deleted 1234 --reason="Withdrawn"
php bin/atom resourcesync:mark-deleted --all-unpublished
php bin/atom resourcesync:mark-deleted --list
```

## Settings

Optional overrides via the `ahg_settings` table (`setting_group = 'resourcesync'`):

| key               | default | meaning                                     |
|-------------------|---------|---------------------------------------------|
| `page_size`       | 1000    | entries per ResourceList / ChangeList page  |
| `changelist_days` | 30      | ChangeList horizon in days                  |

The page size also honours the OAI `resumption_token_limit` setting when set,
so operators tune one knob across both federation surfaces.

## Install

1. Symlink the plugin into `plugins/` (handled by `bin/install`).
2. Load `database/install.sql` (creates `oai_deleted_record` if absent).
3. Enable the plugin:

   ```bash
   php bin/atom extension:enable ahgResourceSyncPlugin
   ```

4. Clear cache and restart php-fpm.

## Graceful degradation

All schema lookups are guarded:

- Missing `oai_local_identifier` column -> records fall back to slug-based `loc`.
- Missing `oai_deleted_record` table -> ChangeList simply omits tombstones.
- Missing `ahg_settings` -> built-in defaults apply.
