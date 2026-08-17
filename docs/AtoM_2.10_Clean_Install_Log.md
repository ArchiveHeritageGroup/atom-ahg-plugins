# AtoM 2.10 clean install - step by step log

**Machine:** atom210, 192.168.0.131 (KVM guest on 112)
**Started:** 2026-08-17
**Purpose:** a genuine clean-room install. Every step recorded as it ran, with its real
result, so the AHG install instructions can be corrected against what actually happens on
a machine with nothing on it.

Why this exists: the earlier "rehearsal" was run on an instance that already had composer,
the PHP extension set, imagick and a populated `vendor/` directory. It passed, and told us
nothing. The first real bare-metal install then failed at almost every step. Nothing in
this file is written from intention - if a step is recorded here, it ran on this machine
and produced the result shown.

---

## Part 0 - building the machine

### 0.1 Why the VM was rebuilt rather than cleaned

The existing atom210 could not serve as a clean-room. It carried the `ondrej/php` PPA with
PHP **8.3, 8.4 and 8.5** installed simultaneously, and `php` resolved to 8.5. AtoM 2.10
does not run on 8.5, and no machine following the official documentation would ever be in
that state. Stripping packages would have left the PPA and its consequences behind.

It was also **Ubuntu 22.04.5**, while the AtoM 2.10 installation page documents **24.04**.
Following 24.04 instructions on a 22.04 box is not a verbatim test.

### 0.2 Recovering the network configuration first

`192.168.0.131` was **not** a DHCP reservation. It was static, inside the guest, at
`/etc/netplan/60-lan.yaml`, bound to the MAC of the bridged interface:

```yaml
network:
  version: 2
  ethernets:
    br0nic:
      match:
        macaddress: "52:54:00:ed:b8:8a"
      dhcp4: false
      addresses: [192.168.0.131/24]
      routes:
        - to: default
          via: 192.168.0.1
          metric: 100
      nameservers:
        addresses: [1.1.1.1, 8.8.8.8]
```

This was read off the old disk by attaching it read-only over `qemu-nbd` before deleting
it. An earlier rebuild attempt using the Ubuntu cloud image failed precisely because this
was not carried across - the guest came up with no address on the bridged interface and
was unreachable. **Read the netplan off any machine before you destroy it.**

### 0.3 What was destroyed

- `atom210.qcow2` and its two snapshots, `pre-iiif-poc` (4 Aug) and `pre-standalone-test`
  (8 Aug). The snapshots were internal to the qcow2 and are not recoverable. They were not
  known to be there when removal was authorised.
- The old domain definition (kept at `atom210-old-domain.xml` for the MAC addresses).
- Nothing belonging to any other VM. `atom28.qcow2` shares the same directory and was left
  untouched.

### 0.4 The new machine

Built with `virt-install` from `ubuntu-24.04-live-server-amd64.iso`, unattended via a
cloud-init `autoinstall` seed on a second CD-ROM:

| | |
|---|---|
| Memory | 8 GB |
| vCPU | 4 |
| Disk | 60 G qcow2 |
| NIC 1 | libvirt `default` network, MAC `52:54:00:bc:2a:80`, DHCP |
| NIC 2 | bridge `br0`, MAC `52:54:00:ed:b8:8a`, static 192.168.0.131 |

Both MACs were preserved from the old domain, because the netplan matches on MAC - change
the MAC and the static address never applies.

The autoinstall seed sets the hostname, installs the SSH server, adds the operator key for
both `ahgadmin` and `root`, disables password authentication, and writes the netplan
above. Storage layout `direct`, `updates: security`.

---

## Part 1 - AtoM 2.10 installation

Following https://www.accesstomemory.org/en/docs/2.10/admin-manual/installation/ubuntu/

Each step below is run individually with its output visible. Steps are **not** batched and
output is **not** suppressed - an earlier attempt did both, and nginx silently failed to
install without anyone noticing until a binary was missing.

**Result: the documented procedure works.** AtoM 2.10 installed and served HTTP 200 on a
machine that had nothing on it. Two gaps in the official instructions are recorded below;
neither is fatal, and both are one line to fix.

### 1.1 Starting state, proven not assumed

```
Ubuntu 24.04.4 LTS          hostname atom210
br0nic 192.168.0.131/24     enp1s0 192.168.122.130/24
absent: php   absent: mysql   absent: nginx   absent: composer
third-party repos: none (ubuntu.sources only)
php-cli Installed: (none)
```

The absence of third-party repositories is the important line. The previous atom210
carried the `ondrej/php` PPA, which put PHP 8.3, 8.4 and 8.5 on the box with 8.5 as
default - AtoM 2.10 does not run on 8.5. On a clean machine PHP comes from Ubuntu at
**8.3.6**, which is what the documentation assumes.

### 1.2 The steps, and what each produced

| Step | Result |
|---|---|
| `apt install mysql-server` + `/etc/mysql/conf.d/mysqld.cnf` | MySQL 8.0.46, active |
| Java + Elasticsearch OSS 7.x repository | Elasticsearch **7.10.2** answering on :9200, Java 11.0.31 |
| PHP 8.3 and extensions | **PHP 8.3.6** from Ubuntu |
| gearman, fop, libsaxon-java, imagemagick, ghostscript, poppler-utils, ffmpeg | installed; ImageMagick 6.9.12-98 |
| `npm` | Node 18.19.1 |
| `git clone -b stable/2.10.x --depth 1` | AtoM 2.10 source |
| `composer install --no-dev` | **57M vendor, 0 `.git` directories** |
| `npm install && npm run build` | webpack 5.97.1 compiled, 3 warnings |
| database + `atom` user | created, user connects |
| `php symfony tools:install` | **Installation completed** |
| fpm pool, nginx site, atom-worker unit | all five services active |
| HTTP | `/` 200, `/index.php/informationobject/browse` 200 |

### GAP 1 - composer is used but never installed

The package lists never install composer, but the install step runs
`sudo composer install --no-dev`. On a clean machine that fails with
`composer: command not found`. Needs `apt install composer` adding to the package step.

This is worth stating plainly because it is the AtoM documentation's gap, not ours - and
it is the same failure that stopped the archaeology production install today.

### GAP 2 - the installer is fully scriptable and this is not obvious

`tools:install` is presented as an interactive prompt sequence. It accepts every answer as
an option:

```
php symfony tools:install \
  --database-host=localhost --database-port=3306 --database-name=atom \
  --database-user=atom --database-password=<pw> \
  --search-host=localhost --search-port=9200 --search-index=atom \
  --site-title='...' --site-base-url=http://<host> \
  --admin-email=<email> --admin-username=<user> --admin-password=<pw> \
  --no-confirmation
```

That makes an unattended build possible, which matters for any repeatable deployment.

### Correction to an earlier claim of mine

I previously said `--prefer-dist` was needed to stop composer shipping ~500M of git
history inside the runtime plugin. That is wrong as a general statement. AtoM's own
`composer install --no-dev` here produced 57M with **zero** `.git` directories. The bloat
seen on the production box came from GitHub returning HTTP 429 (rate limiting), which
makes composer fall back to source clones. So `--prefer-dist` is a mitigation for
throttling, not a normal requirement - and if it happens, stripping the `.git` directories
afterwards produces the same tree.

### Not part of the official procedure, but needed under /usr/share/nginx

php-fpm ships with `ProtectSystem=full`, mounting `/usr` read-only for the worker. AtoM
installed at `/usr/share/nginx/atom` cannot then write its own cache or logs, and every
page returns 500 with an empty body. Grant the paths in
`/etc/systemd/system/php8.3-fpm.service.d/atom-storage.conf`, and **prefix each path with
`-`**:

```ini
[Service]
ReadWritePaths=-/usr/share/nginx/atom/log
ReadWritePaths=-/usr/share/nginx/atom/cache
ReadWritePaths=-/usr/share/nginx/atom/uploads
ReadWritePaths=-/usr/share/nginx/atom/downloads
ReadWritePaths=-/usr/share/nginx/atom/backups
```

Without the `-`, a path that does not exist yet makes systemd refuse to start php-fpm at
all - `226/NAMESPACE`, web server down. That happened on the production box today because
`backups/` had not been created.

---

## Part 2 - the AHG layer

**Base AtoM was not modified at any point.** No file under `apps/`, `lib/`, `vendor/` or
`config/` was touched, and `config/ProjectConfiguration.class.php` is exactly as upstream
ships it. The plugins load through AtoM's own `plugins` setting, the same list Admin >
Plugins writes.

### 2.1 The one thing that blocks a clean install

```
composer install --no-dev
  atom-framework requires ext-gd -> the requested PHP extension gd is missing
```

**`php8.3-gd` is required by the framework and is not in AtoM 2.10's package list.** On a
machine installed exactly per the AtoM documentation, the AHG install stops here. This is
the same failure that stopped the archaeology production install, and it is one line:

```
apt install php8.3-gd
```

With gd present, composer completed: **53M vendor, 0 `.git` directories**. No other
extension was missing - gd was the only one.

### 2.2 The steps, and what each produced

| Step | Result |
|---|---|
| clone atom-framework, atom-ahg-plugins (HTTPS, public repos) | v2.18.3, v3.103.9 |
| `composer install --no-dev --optimize-autoloader` | fails without gd; 53M / 0 `.git` with it |
| `bin/build-runtime-plugin` | **58M, 487 php files, 3 tables** |
| symlink 10 plugins into `plugins/` | 10 symlinks |
| `install-plugin-schema.php` per plugin | see below |
| enable in the `plugins` setting | 12 -> 21 entries |
| `browse` | **200** |
| `user/register` | **200** - base AtoM has no such action, so this proves ahgCorePlugin is loaded |

Schema results:

```
ahgRuntimePlugin              executed 3    unresolved 0
ahgCorePlugin                 executed 81   unresolved 0
ahgContactPlugin              executed 1    unresolved 0
ahgSecurityClearancePlugin    executed 28   unresolved 0   (2 passes)
ahgDisplayPlugin              executed 136  unresolved 0   (2 passes)
ahgSettingsPlugin             executed 5    unresolved 0
ahgUiOverridesPlugin          no database/install.sql      (correct - it has no tables)
ahgAuditTrailPlugin           executed 6    unresolved 0
ahgBackupPlugin               executed 3    unresolved 0
ahgThemeB5Plugin              executed 1    unresolved 0
```

`ahgUiOverridesPlugin` reporting an error for having no schema is noise - the installer
should treat "no tables declared and no install.sql" as informational.

### 2.3 ahgRuntimePlugin is generated, not cloned

It is not in the plugins repository. `atom-framework/bin/build-runtime-plugin` produces it
from the framework, which is how the framework installs on instances that want everything
shaped as ordinary plugins. A `git pull` will never produce it, and its absence is not
obvious.

### 2.4 Enabling without the interface

Admin > Plugins is the normal route. Where that is not available, the same list can be
written directly - but **generate the serialised value on the server**. It is
length-prefixed PHP serialisation, and passing it through a terminal corrupts it: a
wrapped paste took the archaeology production site down on 2026-08-17 with
`array_diff(): Argument #1 must be of type array, false given`, which is what AtoM does
when that value will not unserialise.

### 2.5 ahgThemeB5Plugin will never appear in Admin > Plugins

Base AtoM filters the plugin list:

```php
if (isset($class::$summary) && 0 === preg_match('/theme/i', $class::$summary)) {
```

The theme's summary contains the word "Theme", so it is excluded by design and belongs
under **Admin > Themes**. Do not go looking for it in the plugin list.

Related and more serious: `pluginsSuccess.php` renders `$plugin::$version`, and in PHP 8
reading an undeclared static property is a **fatal Error**. A plugin with `$summary` but
no `$version` kills that page part way down, taking the save button with it - so no plugin
on the instance can be enabled through the interface, with a 200 response and nothing in
any log. Ten AHG plugins had this and were fixed in v3.103.9. **Every plugin
configuration class must declare both `$summary` and `$version`.**

### 2.6 Both themes work

| | stock `arDominionB5Plugin` | with `ahgThemeB5Plugin` |
|---|---|---|
| `informationobject/browse` | 200, body `display browse` | 200, body `display browse` |
| `user/register` | 200 | 200 |
| `actor/browse` | 200 | 200 |
| AHG admin pages | correctly refuse anonymous | correctly refuse anonymous |

`display browse` on the stock theme is the point: ahgDisplayPlugin serves its interface
without the AHG theme. No bootstrap-icon references appeared on the pages tested, so the
icon concern does not apply to them - it may still apply elsewhere, but it was not
reproduced here and should not be repeated as fact without evidence.

### 2.7 CLI tasks do not exist without a base change

With base AtoM untouched, `php symfony` sees **no AHG tasks at all** - the `display:` and
`ahg:` namespaces are absent, even with the plugins enabled and ordered correctly.

The cause, read off the clean install: stock `config/ProjectConfiguration.class.php` builds
a hardcoded `$plugins = [...]` array and calls `enablePlugins($plugins)`. It contains
**zero** references to the `plugins` setting. That setting is consumed later, by the
application configuration, at web-request time. Symfony discovers tasks at project level,
so plugin tasks are invisible to the CLI.

Consequences, and they matter for anyone following these instructions:

- Web functionality is unaffected. Everything served over HTTP works.
- Any documentation that says to run `php symfony display:auto-detect`,
  `php symfony ahg:refresh-facet-cache` or similar **will fail on a stock install**.
- Plugin management is available through the framework's own CLI,
  `php atom-framework/bin/atom` - discover, install, enable, disable, update, migrate.

Either those symfony tasks move under `bin/atom`, or the instructions stop referencing
them. This is the open design question this install surfaced.

### 2.8 Two of my earlier claims that this install disproved

- **`ProtectSystem`**: stock Ubuntu 24.04 ships php-fpm with `ProtectSystem=no`. The
  read-only `/usr` problem is specific to how server 112 is configured. It was documented
  as universal; it is not.
- **`--prefer-dist`**: unthrottled, composer produced 53M with zero `.git` directories.
  The 548M seen on production came from GitHub returning HTTP 429 and composer falling
  back to source clones. It is a mitigation for rate limiting, not a requirement.

Both were stated with more confidence than the evidence supported. They are corrected here
because a clean machine is the only thing that could settle them.
