<?php
$f = $folder ?? null;
$s = $session ?? null;
$action = $isNew
    ? url_for(['module' => 'scanManage', 'action' => 'create'])
    : url_for(['module' => 'scanManage', 'action' => 'update', 'id' => $f->id]);
$val = function ($field, $default = '') use ($f) {
    return $f && isset($f->$field) && $f->$field !== null ? htmlspecialchars((string) $f->$field) : $default;
};
$sval = function ($field, $default = '') use ($s) {
    return $s && isset($s->$field) && $s->$field !== null ? htmlspecialchars((string) $s->$field) : $default;
};
$chk = function ($field, $default = 0) use ($f) {
    $v = $f && isset($f->$field) ? (int) $f->$field : $default;
    return $v ? 'checked' : '';
};
$schk = function ($field, $default = 0) use ($s) {
    $v = $s && isset($s->$field) ? (int) $s->$field : $default;
    return $v ? 'checked' : '';
};
$sel = function ($field, $option, $src) use ($val, $sval) {
    $cur = $src === 'folder' ? $val($field) : $sval($field);
    return $cur === $option ? 'selected' : '';
};
?>
<div class="container-fluid">
    <h1><i class="fas fa-folder-tree me-2"></i><?php echo $isNew ? 'New watched folder' : 'Edit: ' . htmlspecialchars($f->code) ?></h1>

    <form method="post" action="<?php echo $action ?>" class="mt-3">
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3"><div class="card-header">Folder</div><div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="<?php echo $val('code') ?>" <?php echo $isNew ? '' : 'readonly' ?> required>
                        <div class="form-text">Unique identifier (e.g. <code>incoming-archive</code>).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Label</label>
                        <input type="text" name="label" class="form-control" value="<?php echo $val('label') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Watched path <span class="text-danger">*</span></label>
                        <input type="text" name="path" class="form-control" value="<?php echo $val('path') ?>" placeholder="/srv/scan/incoming" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Layout</label>
                        <select name="layout" class="form-select">
                            <option value="flat" <?php echo $sel('layout', 'flat', 'folder') ?>>flat (all files = items under parent)</option>
                            <option value="path" <?php echo $sel('layout', 'path', 'folder') ?>>path (first sub-dir = parent identifier)</option>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="enabled" value="1" <?php echo $f ? $chk('enabled') : 'checked' ?>>
                        <label class="form-check-label">Enabled</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="auto_commit" value="1" <?php echo $f ? $chk('auto_commit', 1) : 'checked' ?>>
                        <label class="form-check-label">Auto-commit (launch ingest immediately)</label>
                    </div>
                </div></div>

                <div class="card mb-3"><div class="card-header">Disposition</div><div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">On success</label>
                        <select name="disposition_success" class="form-select">
                            <option value="move" <?php echo $sel('disposition_success', 'move', 'folder') ?>>move to processed dir</option>
                            <option value="delete" <?php echo $sel('disposition_success', 'delete', 'folder') ?>>delete source</option>
                            <option value="leave" <?php echo $sel('disposition_success', 'leave', 'folder') ?>>leave in place</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">On failure</label>
                        <select name="disposition_failure" class="form-select">
                            <option value="quarantine" <?php echo $sel('disposition_failure', 'quarantine', 'folder') ?>>move to failed dir (quarantine)</option>
                            <option value="leave" <?php echo $sel('disposition_failure', 'leave', 'folder') ?>>leave in place</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Processed dir (optional)</label>
                        <input type="text" name="processed_path" class="form-control" value="<?php echo $val('processed_path') ?>" placeholder="default: &lt;path&gt;/.processed">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Failed dir (optional)</label>
                        <input type="text" name="failed_path" class="form-control" value="<?php echo $val('failed_path') ?>" placeholder="default: &lt;path&gt;/.failed">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quiet period (seconds)</label>
                        <input type="number" name="min_quiet_seconds" class="form-control" value="<?php echo $val('min_quiet_seconds', '10') ?>" min="1">
                        <div class="form-text">File must be idle this long before it is ingested (avoids partial uploads).</div>
                    </div>
                </div></div>
            </div>

            <div class="col-md-6">
                <div class="card mb-3"><div class="card-header">Processing (backing ingest session)</div><div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Sector</label>
                        <select name="sector" class="form-select">
                            <?php foreach (['archive', 'museum', 'library', 'gallery', 'dam'] as $opt): ?>
                                <option value="<?php echo $opt ?>" <?php echo $sel('sector', $opt, 'session') ?>><?php echo $opt ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descriptive standard</label>
                        <select name="standard" class="form-select">
                            <?php foreach (['isadg', 'dc', 'spectrum', 'cco', 'rad', 'dacs'] as $opt): ?>
                                <option value="<?php echo $opt ?>" <?php echo $sel('standard', $opt, 'session') ?>><?php echo $opt ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent information object id</label>
                        <input type="number" name="parent_id" class="form-control" value="<?php echo $sval('parent_id') ?>" placeholder="top level if blank">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Repository id (optional)</label>
                        <input type="number" name="repository_id" class="form-control" value="<?php echo $sval('repository_id') ?>">
                    </div>
                    <hr>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="derivative_thumbnails" value="1" <?php echo $s ? $schk('derivative_thumbnails', 1) : 'checked' ?>>
                        <label class="form-check-label">Generate thumbnails</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="derivative_reference" value="1" <?php echo $s ? $schk('derivative_reference', 1) : 'checked' ?>>
                        <label class="form-check-label">Generate reference copies</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="process_virus_scan" value="1" <?php echo $s ? $schk('process_virus_scan', 1) : 'checked' ?>>
                        <label class="form-check-label">Virus scan (ClamAV)</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="process_ocr" value="1" <?php echo $schk('process_ocr') ?>>
                        <label class="form-check-label">OCR</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="process_ner" value="1" <?php echo $schk('process_ner') ?>>
                        <label class="form-check-label">Named-entity recognition</label>
                    </div>
                </div></div>
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            <a href="<?php echo url_for(['module' => 'scanManage', 'action' => 'index']) ?>" class="btn btn-link">Cancel</a>
        </div>
    </form>
</div>
