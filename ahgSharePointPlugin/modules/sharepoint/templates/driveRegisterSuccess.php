<?php
$tenants = $sf_data->getRaw('tenants');
?>
<h1><?php echo __('Register SharePoint drive') ?></h1>

<p class="text-muted"><?php echo __('Pick a tenant, then a site, then the document library to register. AtoM stores the IDs so cron-driven auto-ingest and the wizard picker can reach the drive.') ?></p>

<?php if (empty($tenants) || count($tenants) === 0): ?>
    <div class="alert alert-warning"><?php echo __('No SharePoint tenants configured yet. Configure a tenant first at /sharepoint/tenants.') ?></div>
<?php else: ?>

<form method="post" action="<?php echo url_for(['module' => 'sharepoint', 'action' => 'driveSave']) ?>" id="drive-register-form">

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Source') ?></h5></div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label"><?php echo __('Tenant') ?></label>
                <select name="tenant_id" class="form-select" id="tenant-pick" required>
                    <option value="">— <?php echo __('Select tenant') ?> —</option>
                    <?php foreach ($tenants as $t): ?>
                        <option value="<?php echo (int) $t->id ?>"><?php echo esc_entities($t->name) ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label"><?php echo __('Site') ?></label>
                <select id="site-pick" class="form-select" disabled>
                    <option value="">— <?php echo __('Select tenant first') ?> —</option>
                </select>
                <input type="hidden" name="site_id" id="site_id">
                <input type="hidden" name="site_url" id="site_url">
                <input type="hidden" name="site_title" id="site_title">
            </div>

            <div class="mb-3">
                <label class="form-label"><?php echo __('Drive (document library)') ?></label>
                <select id="drive-pick" class="form-select" disabled>
                    <option value="">— <?php echo __('Select site first') ?> —</option>
                </select>
                <input type="hidden" name="drive_id" id="drive_id">
                <input type="hidden" name="drive_name" id="drive_name">
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Defaults for ingest sessions') ?></h5></div>
        <div class="card-body row">
            <div class="col-md-4 mb-3">
                <label class="form-label"><?php echo __('Sector') ?></label>
                <select name="sector" class="form-select">
                    <option value="archive" selected>Archive</option>
                    <option value="library">Library</option>
                    <option value="museum">Museum</option>
                    <option value="gallery">Gallery</option>
                    <option value="dam">DAM</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label"><?php echo __('Default placement') ?></label>
                <select name="default_parent_placement" class="form-select">
                    <option value="top_level" selected><?php echo __('Top level') ?></option>
                    <option value="existing"><?php echo __('Under existing') ?></option>
                    <option value="new"><?php echo __('Create new parent') ?></option>
                </select>
            </div>
            <div class="col-md-4 mb-3 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="ingest_enabled" value="1" id="ingest_enabled">
                    <label class="form-check-label" for="ingest_enabled"><?php echo __('Drive available for ingest') ?></label>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'drives']) ?>" class="btn btn-outline-secondary"><?php echo __('Cancel') ?></a>
        <button type="submit" class="btn btn-primary" id="save-btn" disabled><?php echo __('Register drive') ?></button>
    </div>
</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function () {
    var browseUrl = '<?php echo url_for(['module' => 'sharepoint', 'action' => 'driveBrowse']) ?>';
    var tenantPick = document.getElementById('tenant-pick');
    var sitePick = document.getElementById('site-pick');
    var drivePick = document.getElementById('drive-pick');
    var siteId = document.getElementById('site_id');
    var siteUrl = document.getElementById('site_url');
    var siteTitle = document.getElementById('site_title');
    var driveId = document.getElementById('drive_id');
    var driveName = document.getElementById('drive_name');
    var saveBtn = document.getElementById('save-btn');

    function api(params) {
        var qs = Object.keys(params).map(function (k) { return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]); }).join('&');
        return fetch(browseUrl + (browseUrl.indexOf('?') === -1 ? '?' : '&') + qs, {
            credentials: 'same-origin', headers: { 'Accept': 'application/json' },
        }).then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); });
    }

    tenantPick.addEventListener('change', function () {
        sitePick.disabled = true; drivePick.disabled = true; saveBtn.disabled = true;
        siteId.value = ''; siteUrl.value = ''; siteTitle.value = ''; driveId.value = ''; driveName.value = '';
        if (!tenantPick.value) return;
        sitePick.innerHTML = '<option value="">— loading… —</option>';
        api({ op: 'sites', tenant_id: tenantPick.value }).then(function (json) {
            sitePick.innerHTML = '<option value="">— select site —</option>';
            (json.sites || []).forEach(function (s) {
                var o = document.createElement('option');
                o.value = s.id;
                o.dataset.url = s.webUrl;
                o.dataset.title = s.displayName;
                o.textContent = s.displayName + '  —  ' + s.webUrl;
                sitePick.appendChild(o);
            });
            sitePick.disabled = false;
        }).catch(function (e) {
            sitePick.innerHTML = '<option value="">' + e.message + '</option>';
        });
    });

    sitePick.addEventListener('change', function () {
        drivePick.disabled = true; saveBtn.disabled = true;
        driveId.value = ''; driveName.value = '';
        if (!sitePick.value) return;
        var opt = sitePick.options[sitePick.selectedIndex];
        siteId.value = sitePick.value;
        siteUrl.value = opt.dataset.url;
        siteTitle.value = opt.dataset.title;
        drivePick.innerHTML = '<option value="">— loading… —</option>';
        api({ op: 'drives', tenant_id: tenantPick.value, site_id: sitePick.value }).then(function (json) {
            drivePick.innerHTML = '<option value="">— select drive —</option>';
            (json.drives || []).forEach(function (d) {
                var o = document.createElement('option');
                o.value = d.id;
                o.dataset.name = d.name;
                o.textContent = d.name + ' (' + (d.driveType || 'documentLibrary') + ')';
                drivePick.appendChild(o);
            });
            drivePick.disabled = false;
        });
    });

    drivePick.addEventListener('change', function () {
        saveBtn.disabled = !drivePick.value;
        if (drivePick.value) {
            driveId.value = drivePick.value;
            driveName.value = drivePick.options[drivePick.selectedIndex].dataset.name || '';
        }
    });
})();
</script>

<?php endif ?>
