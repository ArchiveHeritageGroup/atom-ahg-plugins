# nginx — RDM protected-file serving (#176)

Approach B for issue #176: restricted/embargoed RDM dataset files are **moved out
of the public `/uploads` tree** into a non-web-served protected directory, and
served only through the authed download controller via **`X-Accel-Redirect`**.

This requires **one nginx `internal` location** + a protected directory. Both are
operational (outside the plugin); apply on the PSIS server.

## 1. Protected directory (one-time)
```bash
sudo mkdir -p /mnt/nas/heratio/rdm-protected
sudo chown www-data:www-data /mnt/nas/heratio/rdm-protected
sudo chmod 750 /mnt/nas/heratio/rdm-protected
```
(Override the path with `app_rdm_protected_dir` in config/app.yml if a different
location is wanted; keep it OUTSIDE `/usr/share/nginx/archive/uploads`.)

## 2. nginx internal location
Add inside the PSIS `server { }` block in
`/etc/nginx/sites-enabled/psis.theahg.co.za.conf` (anywhere among the other
`location` blocks):

```nginx
    # RDM protected files (#176). internal => only reachable via the
    # X-Accel-Redirect the authed RDM download controller emits; a direct
    # browser request to /rdm-protected/... returns 404.
    location /rdm-protected/ {
        internal;
        alias /mnt/nas/heratio/rdm-protected/;
    }
```
Then:
```bash
sudo nginx -t && sudo systemctl reload nginx
```

If `app_rdm_protected_url` is changed from the default, match the `location`
prefix to it.

## 3. Fallback (no nginx change)
If the `internal` location is not added, set `app_rdm_xaccel: false` in
config/app.yml — the controller then streams protected files itself with
`readfile()` (works, but uses a PHP-FPM worker for the whole transfer; X-Accel is
strongly preferred for large files).

## How it behaves
- **restrict / embargo / de-identify**: `DatasetFileGuardService::protect()` moves
  every `digital_object` file of the dataset's IOs into
  `/mnt/nas/heratio/rdm-protected/<do_id>/<file>` and records the move in
  `rdm_protected_object`. The old `/uploads/r/...` path now **404s** (bytes gone)
  — the raw-URL bypass is closed.
- **open release**: `release()` moves the files back to `/uploads`.
- **download**: `/research/datasets/:id/file/:fid` (auth) evaluates ODRL
  (`evaluateAccess('archival_description', ioId, researcherId, 'use')`); admins
  bypass; restricted → **403**; permitted → `X-Accel-Redirect` to the internal
  location.

## Trade-off (accepted, approach B)
While a dataset is restricted, its master also disappears from the base AtoM
catalogue IO viewer (it serves from `/uploads`, which is now empty for that file).
For *restricted/POPIA* data that is the intended posture; access is exclusively
through the authed RDM download controller. Open-released datasets are unaffected.
