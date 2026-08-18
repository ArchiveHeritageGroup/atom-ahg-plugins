# Wits archaeology production: cloning archaeology.theahg.co.za

**Date:** 18 August 2026
**Instances:** Wits production (146.141.9.111 / `ubuntu`), 131 (archaeology.theahg.co.za)
**Plugins at:** v3.103.30 on both

## Getting in

The Wits box is unreachable from 112: `146.141.9.111` drops ICMP and every port.
Johan reaches it from Windows over **Cisco Secure Client**. Route used:

    laptop (VPN up):  ssh -R 2223:146.141.9.111:22 johanpiet@192.168.0.112
    112:              ssh -p 2223 -i /root/.ssh/wits_access a0088012@127.0.0.1

Four things blocked that, each of which looked like a different problem:

- **112 had `AllowTcpForwarding no`.** The SSH session connects and the `-R` forward
  is silently refused - a working login with a tunnel that carries nothing. Changed
  to `AllowTcpForwarding remote` (reverse only). ⚠️ **Insert global directives BEFORE
  the first `Match` block**: a `sed` that matched the first occurrence edited the line
  inside `Match User ftpuser`, loosening forwarding for a chrooted SFTP account and
  doing nothing for the global scope.
- **`systemctl reload sshd` fails on this host** - the unit is `ssh`.
- **`PermitRootLogin no` on 112.** The laptop key authenticated and was then refused:
  `ROOT LOGIN REFUSED`. Diagnosed from `/var/log/auth.log`, which named the exact
  fingerprint. ⚠️ **Check `PermitRootLogin` before concluding a key is missing** - the
  key added to root's `authorized_keys` was useless because root logins are refused
  regardless. The tunnel needs no privilege at all: `johanpiet` already had the
  laptop's default key authorised.
- **The key is passphrase-protected**, so `BatchMode` cannot use it. Loaded into an
  `ssh-agent` at `/tmp/wits-agent.sock` by Johan in a real TTY - interactive prompts
  do not reach `ssh-add` through this harness.

## Privilege

`a0088012` already had `ALL=(ALL:ALL) ALL` - full root with a password. Added
`/etc/sudoers.d/a0088012-atom` granting **password-free** access only to what the
install needs:

    a0088012 ALL=(www-data) NOPASSWD: ALL
    a0088012 ALL=(root) NOPASSWD: /usr/bin/systemctl reload|restart php8.3-fpm, reload nginx

That is a narrowing, not a widening. Verified: `sudo -n -u www-data` works, `sudo -n id`
still demands a password.

⚠️ **`cd` runs as the calling user.** `/usr/share/nginx/atom` is `www-data:www-data`
0750, so `cd /usr/share/nginx/atom && sudo -u www-data php symfony ...` fails on the
`cd` and every subsequent command runs from the wrong directory - 15 plugin enables
reported as failures. Put the whole sequence inside `sudo -u www-data bash -c "..."`.

## What was already there

Johan had installed AtoM 2.10: Ubuntu 24.04.4, PHP 8.3.6 with **gd present** (the
package that blocked the earlier attempt), nginx/php-fpm/mysql 8.0.46/elasticsearch
7.10.2 all active, AtoM answering in 0.05s. Both AHG repos cloned (root-owned, so
`git pull` needs root), 21 plugins enabled, 1 description.

The four `ahg*Plugin` entries at the AtoM root are **symlinks to `plugins/<name>/web`** -
AtoM's asset publishing, not stray directories.

## The clone

15 plugins symlinked, schemas installed, enabled. Then:

- **ahgReports / ahgMirador / ahgSeadragon have no `install.sql`** - rc=2 is "nothing
  to install", not a failure.
- **ahgResearchPlugin's `install.sql` uses `DELIMITER //` and a stored procedure.**
  `DELIMITER` is a mysql *client* directive, not SQL, so PDO cannot execute it and the
  upgrade procedure never runs. Verified harmless on a fresh install by comparing
  `information_schema.columns` against 131: **278 research columns, identical**. The
  procedure only matters when upgrading an older install.
- **Theme bundles must be published.** ahgThemeB5Plugin looks in the web root's
  `dist/`; they ship in `plugins/ahgThemeB5Plugin/web/dist/`. Nothing copies them on
  enable. 12 files copied.

## Two faults that exist on 131 as well

- **`watermark_type` doesn't exist → 500 on `/informationobject/add`.** ahgCorePlugin
  queries watermark tables that **ahgDAMPlugin** creates, and DAM is not enabled on
  either box. There are **30 unguarded call sites** across `WatermarkRepository`,
  `WatermarkSettingsService` and `WatermarkService`, so guarding one is not a fix -
  installed ahgDAMPlugin's **schema only**, plugin left disabled. A guard was added to
  `WatermarkRepository::getTypes()` as partial defence.
- **"This descriptive standard is not available"** for isad. `IoFormHelper::MODULE_MAP`
  leaves isad in `ioManage`, which is provided by **ahgInformationObjectManagePlugin** -
  not enabled on either box, so **ISAD descriptions could not be created on
  archaeology.theahg.co.za either**. Installed on both.

## 131 realigned as the Wits dev site

131 was at v3.103.12 with **66 locally modified files** (today's deployments plus the
stray-SQL cleanup, all already in origin). Backed up to
`/root/atom-ahg-plugins-pre-reset-2026-08-18.tgz` (182MB), then `git reset --hard
origin/main` to v3.103.30. ⚠️ Ownership must be restored afterwards: `chown -R
www-data:www-data`.

## Branding recovered from the original instance

Both boxes showed stock titles - 131 said "AtoM 2.10 clean", Wits said "AtoM". The real
branding was on the ORIGINAL archaeology instance, `/usr/share/nginx/archeology` on 112,
which still holds its 133 descriptions and is untouched by the hostname move.

| Setting | Value |
|---|---|
| siteTitle [en] | Wits Archaeological Collection |
| siteTitle [af] | Wits Argeologiese Versameling |
| siteDescription [en] | Collections, fieldwork records and research documentation from Wits Archaeology |
| siteDescription [af] | Versamelings, veldwerkrekords en navorsingsdokumentasie van die Wits-departement van Argeologie |

⚠️ **Read every culture.** A query taking the first `setting_i18n` row returned the
Afrikaans values and made it look as though no English existed. Both cultures were
copied verbatim rather than retyped.

`siteBaseUrl` was `http://127.0.0.1` on Wits - which would have put localhost into every
generated email link and export URL. Set to the real host on both.

## The catalogue

4,682 site records imported on Wits (3,221 with coordinates, 16 held), same figures as
131. Import ran with indexing disabled; `search:populate` afterwards.

## State at hand-over

Both boxes: v3.103.30, 37 plugins, ahgThemeB5 theme, heritage landing page,
ioManage installed, DAM tables present, archaeology branding, 4,682 sites.

## Open

- `/user/<slug>` returns "page not found" - not yet investigated.
- `arDominionB5Plugin` still enabled on Wits, absent on 131.
- 112 still has `AllowTcpForwarding remote` and an unusable root key to remove.
- The 16 coordinate conflicts still need a human decision.
