# AHG plugins for AtoM

Extensions for [Access to Memory](https://accesstomemory.org), installable one at
a time on a standard AtoM installation. Each is a separate download. Take the ones
you want and leave the rest.

Nothing in base AtoM is modified. No file under `apps/`, `lib/`, `vendor/` or
`config/` is touched, and `ProjectConfiguration.class.php` stays exactly as
upstream ships it. Removing a plugin is deleting its directory and disabling it.

All plugins are AGPL-3.0-or-later.

## Before you start

    AtoM     2.9 or 2.10
    PHP      8.1 or later
    MySQL    8.0

AtoM 2.9 and 2.10 both require PHP 8 already, so on a supported AtoM there is
nothing to prepare.

**AtoM 2.8 is not supported.** It is the last release that runs on PHP 7.4, and
these plugins are built on Laravel 10, which is PHP 8.1+ by construction. That is
a property of the dependency tree rather than a coding style, so it is not
something a compatibility pass could fix. AtoM 2.8 itself runs perfectly well on
PHP 8.3, so a 2.8 site that wants these can move its FPM pool rather than upgrade
AtoM.

### Install the runtime first, once

Every plugin needs the shared runtime. It is a separate download because it is
identical for all of them, and shipping it inside each bundle would mean carrying
the same 56 MB a dozen times and re-releasing everything whenever it changed.

[**Download ahgRuntimePlugin 2.14.0**](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/releases/tag/ahgRuntimePlugin-v2.14.0)

From your AtoM root:

    unzip ahgRuntimePlugin-2.14.0.zip -d plugins/
    chown -R www-data:www-data plugins/ahgRuntimePlugin
    mysql -u <user> -p <database> < plugins/ahgRuntimePlugin/database/install.sql

Enable **ahgRuntimePlugin** in Admin > Plugins, then:

    rm -rf cache/*
    php symfony cc
    systemctl reload php8.3-fpm

## The plugins

### Provenance Tracking

Chain of custody and ownership history for archival records, museum objects and
library materials. Records acquisition type, certainty level and gaps in custody,
flags material needing Nazi-era provenance checking, tracks cultural property
status, and signs manifests with Ed25519. Also records the provenance of AI
inference where that is used to generate description.

![Provenance management](screenshots/ahgProvenancePlugin/provenance.png)

[Download 1.2.2](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/releases/tag/ahgProvenancePlugin-v1.2.2) - 10 tables

### Backup and Restore

Database and file backup with scheduling, restore, upload and retention
management. Backs up the database, uploads, plugins and framework independently,
keeps a configurable number of copies, and can be driven from cron for unattended
operation.

![Backups and schedules](screenshots/ahgBackupPlugin/backup.png)

[Download 1.1.1](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/releases/tag/ahgBackupPlugin-v1.1.1) - 3 tables

### Digital Preservation

Checksums, fixity checking, PREMIS event recording, format identification against
PRONOM, and OAIS packaging with SIP, AIP and DIP. Includes TIFF and PDF merge for
assembling multi-page objects from scanned pages.

![Preservation packages](screenshots/ahgPreservationPlugin/packages.png)

[Download 1.0.5](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/releases/tag/ahgPreservationPlugin-v1.0.5) - 30 tables

### Favourites

Per-user favourites and folders, with sharing, export, and optional integration
with research projects and bibliographies where those are installed. Each user
keeps their own list; no particular permission is needed beyond being signed in.

![Favourites and folders](screenshots/ahgFavoritesPlugin/favorites.png)

[Download 2.0.0](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/releases/tag/ahgFavoritesPlugin-v2.0.0) - 3 tables

### IIIF, with a viewer of your choice

IIIF manifests, collections, annotations, OCR export and media streaming.
Manifests are publicly fetchable so external viewers can use them, while the media
processing queue and validation tools require an editor or administrator.

The viewer is a separate, optional install. Take one, both or neither:

- **[Seadragon 1.0.0](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/releases/tag/ahgSeadragonPlugin-v1.0.0)** - OpenSeadragon, deep zoom for single images
- **[Mirador 1.0.0](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/releases/tag/ahgMiradorPlugin-v1.0.0)** - side-by-side comparison across collections

Each registers itself with the IIIF renderer registry rather than being named by
the theme, so installing one does not require the other and removing one leaves
the rest working.

**Tiled deep zoom needs an image server, and that stays yours.** The plugins do not
install one, do not configure one, and do not assume you are without one. Manifests,
the viewer and OCR export work as they are; tiling is what an image server adds. If
you already run Cantaloupe, IIPImage or anything else that speaks IIIF, point it at
your uploads and carry on.

Whichever server you use, one thing has to be right. It reads files straight off
disk and knows nothing about AtoM's access control, so left open, every master under
`uploads/r/` is retrievable through the IIIF endpoint by anyone who can form the
path, and those paths appear in every manifest. The nginx rules protecting
`/uploads/r/` do not help: it is a different route to the same bytes.

For Cantaloupe, the authorisation hook that closes this ships in the bundle at
`config/cantaloupe/delegates.rb`, with a README covering three ways it can appear
configured while doing nothing at all. For anything else, the endpoint your check
should call is documented in the guides below.

Two standalone guides, both optional, neither required to install a plugin:

- **[Setting up an IIIF image server](../infrastructure/iiif-image-server.md)** -
  requirements, Cantaloupe configuration, authorisation, and how to prove it refuses
- **[nginx](../infrastructure/nginx.md)** - proxying the image server, serving
  derivatives directly, the `ProtectSystem=full` trap, and CSP

[Download IIIF 1.0.2](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/releases/tag/ahgIiifPlugin-v1.0.2)

### Feedback

Collect and manage feedback from users against archival descriptions. Readers can
report a correction or add context from the record page; staff triage the queue
by status.

![Feedback management](screenshots/ahgFeedbackPlugin/feedback.png)

[Download 1.0.5](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/releases/tag/ahgFeedbackPlugin-v1.0.5) - 2 tables

## Installing a plugin

The same four steps for every one. From your AtoM root, with the runtime already
installed:

    unzip <plugin>-<version>.zip -d plugins/
    chown -R www-data:www-data plugins/
    mysql -u <user> -p <database> < plugins/<plugin>/database/install.sql
    mysql -u <user> -p <database> < plugins/ahgCorePlugin/database/install.sql

Enable in Admin > Plugins, in this order:

    1. ahgRuntimePlugin
    2. ahgCorePlugin
    3. the plugin itself

Then clear the cache and reload PHP as above. Each zip contains an `INSTALL.md`
with the exact commands for that plugin, including the tables it creates.

Once enabled, the plugin adds itself to the navigation. On a stock AtoM that is
the **Quick links** menu; AtoM has no Manage menu of its own.

## If something does not work

Read the AtoM log rather than the rendered page:

    tail -50 log/qubit_prod.log

AtoM returns HTTP 200 for pages that are not successes, its login page and its
error page among them, so a status code on its own proves very little. An empty
body with a 200 usually means a fatal error.

### If your AtoM lives under /usr/share/nginx

php-fpm ships with `ProtectSystem=full`, which mounts `/usr` read-only for the
worker. The site then cannot write its own cache or logs and every page returns
500 with an empty body, which looks nothing like a permissions problem. Grant the
paths in `/etc/systemd/system/php8.3-fpm.service.d/<site>-storage.conf`:

    [Service]
    ReadWritePaths=/usr/share/nginx/<site>/log
    ReadWritePaths=/usr/share/nginx/<site>/cache
    ReadWritePaths=/usr/share/nginx/<site>/uploads
    ReadWritePaths=/usr/share/nginx/<site>/downloads
    ReadWritePaths=/usr/share/nginx/<site>/backups

Then `systemctl daemon-reload && systemctl restart php8.3-fpm`.

## Removing a plugin

Disable it in Admin > Plugins, delete its directory from `plugins/`, and clear the
cache. The tables are left in place; drop them by hand if the data is genuinely no
longer wanted, because nothing else will.

## How these were tested

Each was installed on a wiped AtoM 2.9 from the steps above and then used, rather
than inspected. Every page is exercised in a real browser and checked on four
things: the status code, the content, JavaScript console errors, and any failed
request the page itself issued. The last two matter more than they sound - a page
can be flawless at the HTTP level and broken the moment someone presses a button.

Reports and issues:
[github.com/ArchiveHeritageGroup/atom-ahg-plugins/issues](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/issues)

The Archive and Heritage Group (Pty) Ltd.
