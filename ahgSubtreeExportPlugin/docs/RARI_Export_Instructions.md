# Exporting images and metadata from RARI

How to export a run of catalogue records, with their master images and reference
copies, to a folder or an external drive. Written for an operator with shell access
to the RARI server.

Prepared by The Archive and Heritage Group (Pty) Ltd.

## What this does

It takes a starting record, walks forward through the catalogue in the order the
records appear in the tree, and copies the chosen number of records together with
their image files.

It also writes a **lookup page** you can open straight from the drive to search what
was exported and click through to the images. No internet, no software to install.

## Before you begin

You need shell access to the RARI server and `sudo` for two of the steps. The AtoM
installation is at `/usr/share/nginx/atom`.

⚠️ **Do not export into the AtoM folder or onto the system disk.** The system disk
has roughly 32 GB free and a thousand records is about 21 GB, which would leave the
server dangerously short. Use `/data.old`, or a mounted external drive. The tool
checks free space and refuses to start if the destination is too small, but choosing
properly is better than relying on that.

## One-time installation

Only needed once. Skip to "Running an export" if it is already installed.

1. Copy the `ahgSubtreeExportPlugin` folder into `/usr/share/nginx/atom/plugins/`.

2. Create its two database tables:

   ```bash
   sudo mysql atom < /usr/share/nginx/atom/plugins/ahgSubtreeExportPlugin/database/install.sql
   ```

3. Make sure the files are readable:

   ```bash
   sudo chmod -R a+rX /usr/share/nginx/atom/plugins/ahgSubtreeExportPlugin
   ```

4. Add `'ahgSubtreeExportPlugin',` to the plugin list in
   `/usr/share/nginx/atom/config/ProjectConfiguration.class.php`, then clear the
   cache:

   ```bash
   cd /usr/share/nginx/atom && php symfony cc
   ```

5. Check it is available:

   ```bash
   php symfony | grep subtree
   ```

   You should see `subtree`. If nothing appears, step 4 did not take effect.

## Running an export

### Step 1: always look first

This reports what would be exported and writes nothing at all.

```bash
cd /usr/share/nginx/atom
php symfony subtree:export --slug=smt-les-hab1-1 --limit=1000 \
  --output=/data.old/rari-export --estimate-only
```

Expected output:

```
start smt-les-hab1-1 (lft 276585), bounded by smits-lucas-3 (253322-284497)
1000 records, 2000 files, 20.7 GB
Estimate only - nothing written.
```

Check the record count and the size look right before going further. If the count is
lower than you asked for, the run reached the end of the collection - the message
says so.

### Step 2: try a few records

Prove the destination works before moving twenty gigabytes.

```bash
php symfony subtree:export --slug=smt-les-hab1-1 --limit=5 \
  --clean --output=/data.old/rari-export
```

You should see `5 records, 10 files` and `0 missing`. Then open
`/data.old/rari-export/lookup.html` in a browser and confirm the records are listed
and the image links work.

### Step 3: the full export

```bash
php symfony subtree:export --slug=smt-les-hab1-1 --limit=1000 --batch-size=100 \
  --clean --output=/data.old/rari-export
```

`--batch-size=100` does a hundred records and stops, so you can run it in stages.
Continue with the job number it printed:

```bash
php symfony subtree:export --resume=7
```

Leave off `--batch-size` to do it all in one go. That takes a while, so if your
connection might drop:

```bash
nohup php symfony subtree:export --slug=smt-les-hab1-1 --limit=1000 \
  --output=/data.old/rari-export > ~/export.log 2>&1 &
tail -f ~/export.log
```

## Exporting to an external drive

Plug the drive in, then find where it mounted:

```bash
lsblk -o NAME,SIZE,FSTYPE,LABEL,MOUNTPOINT
df -h | grep -E "media|mnt"
```

You are looking for a line such as `/media/a0088012/RARI-2026` or `/mnt/usb`. That
path is what goes after `--output`.

Check you can write to it, because a drive mounted read-only or owned by another
user is the most common reason an export produces nothing:

```bash
touch /media/a0088012/RARI-2026/write-test && rm /media/a0088012/RARI-2026/write-test \
  && echo "writable"
```

If that fails:

```bash
sudo chown $USER /media/a0088012/RARI-2026
```

Then export as normal, pointing at the drive:

```bash
cd /usr/share/nginx/atom

# 1. Check it will fit
php symfony subtree:export --slug=smt-les-hab1-1 --limit=1000 \
  --output=/media/a0088012/RARI-2026/smits-lucas --estimate-only

# 2. Five records, to prove the drive works
php symfony subtree:export --slug=smt-les-hab1-1 --limit=5 --clean \
  --output=/media/a0088012/RARI-2026/smits-lucas

# 3. The full run, a hundred at a time
php symfony subtree:export --slug=smt-les-hab1-1 --limit=1000 --batch-size=100 \
  --clean --output=/media/a0088012/RARI-2026/smits-lucas
```

When it finishes, flush everything to the disk before unplugging. A large export can
still be sitting in memory when the command returns:

```bash
sync
udisksctl unmount -b /dev/sdb1
```

⚠️ **Check the drive's filesystem before a large export.** `lsblk` shows it under
FSTYPE. A drive formatted **FAT32** cannot hold a file larger than 4 GB, and refuses
it part-way through rather than up front. **exFAT** or **NTFS** work on Windows and
Mac as well as Linux; **ext4** is fine if the drive will only ever be read on Linux.

⚠️ **USB drives are slow.** Twenty gigabytes over USB 2 takes hours. Use
`--batch-size=100` so the work is done in stages that can be stopped and resumed,
rather than one run you dare not interrupt.

## What you get

```
manifest.json     every record and file, for machines
manifest.js       the same data, for the lookup page
lookup.html       open this in a browser
metadata/         one file per batch
objects/          the images, in the same folder structure as the server
```

Open **lookup.html** by double-clicking it. Search by title, identifier, place,
photographer, shelf mark, genre or file name. Every file is linked, so clicking opens
the image.

## The options in detail

### Choosing what to export

**`--slug`** (required)

The record the export starts from. It is the last part of the record's web address:
`https://rari.wits.ac.za/index.php/smt-les-hab1-1` gives `--slug=smt-les-hab1-1`.

The export starts at that record and works forward. The starting record is always
included.

**`--limit`** (default 1000)

How many records to take, counting from the start. If fewer are available before the
end of the collection, it exports what there is and tells you:

```
NOTE: only 412 records available before the bound ends - asked for 1000.
```

That is not an error. It means the collection ran out.

**`--bound`** (defaults to the starting record's parent)

The collection the export may not leave.

This matters more than it looks. Records sit in one long sequence, so walking forward
from the last record of one collection carries straight on into the next. Without a
bound you would quietly export material from a different collection and only discover
it later.

By default the bound is the starting record's parent, which is usually what you want.
Set it explicitly to widen the range, for example `--bound=rari-slide-archive` to let
the export run across a whole archive rather than one site.

### Choosing which files

Each catalogue record can have three kinds of file. The defaults suit an archival
copy: full quality, plus something usable on screen.

| Kind | Included by default | Typical size here |
| --- | --- | --- |
| Master | yes | about 22 MB each |
| Reference | yes | about 40 KB each |
| Thumbnail | no | about 10 KB each |

**`--no-masters`** leaves out the full-quality images. The export becomes tiny, a few
megabytes for a thousand records, and is useful when you only want the catalogue and
something to look at.

**`--no-references`** leaves out the screen-sized copies. Saves very little, and the
lookup page becomes less useful because the reference copy is what opens quickly in a
browser. Rarely worth it.

**`--thumbnails`** adds the thumbnails. Costs almost nothing.

Switching everything off is refused, with `Nothing to export`.

### Where it goes

**`--output`** (required)

The folder to write into. It is created if it does not exist. See the external drive
section above for finding a drive's path.

**`--clean`**

Empties a previous export from that folder first. Without it, a new export writes
alongside whatever is already there, which leaves files from an earlier run mixed in
with the new one.

It removes **only** the five things this tool creates: `objects/`, `metadata/`,
`manifest.json`, `manifest.js` and `lookup.html`. Anything else in the folder is left
alone, so it is safe on a drive holding other material.

It refuses outright to clean the AtoM folder, anything inside it, anything containing
it, a system folder, or the root of the filesystem. A mistyped `--output` stops the
export rather than deleting something it should not.

**`--force`**

Proceeds when the destination looks too small. The check requires the estimated size
plus half again as working room, so `--force` is reasonable when you know the figure
is conservative. It does not make more space appear: if the drive genuinely fills, the
export stops part-way and reports the files it could not write.

### Running it in stages

**`--batch-size`** (default 0, meaning all at once)

Records per run. With `--batch-size=100` the tool does a hundred records and stops,
reporting the job number. Nothing is lost between batches.

Use it for anything large, and for anything going to a USB drive. If something goes
wrong you lose one batch rather than the whole transfer.

**`--resume`**

Continues a job by its number:

```bash
php symfony subtree:export --resume=7
```

It picks up exactly where the last batch stopped. Files already copied are not copied
again and are not double counted. You do not repeat the other options; the job
remembers them.

### Checking without doing

**`--estimate-only`**

Reports what would be exported and stops. Writes nothing, creates nothing, changes
nothing. Always worth running first, and safe to run at any time.

## If something goes wrong

**"Destination has N GB free; M GB wanted"** - not enough room. The tool wants the
estimated size plus half again, as working room. Choose a bigger destination.

**"cannot create ... check the destination is writable"** - the folder belongs to
another user. Fix it with:

```bash
sudo mkdir -p /data.old/rari-export && sudo chown $USER /data.old/rari-export
```

**"JOB FAILED - files were expected and none were written"** - nothing copied,
almost always the problem above.

**"N MISSING on disk"** - those records have catalogue entries but the image files
are not on the server. The export continues and records which ones. They are listed
in `manifest.json` with `"status": "missing"`.

**"N SIZE MISMATCH"** - a copied file is a different size from what the catalogue
says. Worth investigating: it can mean a file was changed after cataloguing, or that
a copy was cut short. The export continues.

**"REFUSING to clean ..."** - `--clean` will not touch the AtoM folder, anything
inside it, or a system folder. Check your `--output` for a typo.

## Notes

`--clean` only removes what this tool creates: `objects/`, `metadata/`,
`manifest.json`, `manifest.js` and `lookup.html`. Anything else in the destination is
left alone, so it is safe to use a drive that holds other material.

Nothing in the catalogue or on the server is changed by an export. It only reads the
database and copies files out.
