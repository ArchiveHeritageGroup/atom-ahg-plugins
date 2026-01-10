<h1 class="mb-4"><i class="fas fa-layer-group me-2"></i><?php echo __('Batch Rights Assignment'); ?></h1>

<?php if ($message): ?>
    <div class="alert <?php echo $failed > 0 ? 'alert-warning' : 'alert-success'; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong><?php echo __('Errors'); ?>:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" id="batch-rights-form" action="<?php echo url_for(['module' => 'right', 'action' => 'batch']); ?>">

    <!-- 1. Action Selection -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><?php echo __('1. Select Action'); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="batch_action" id="action_assign" value="assign" checked>
                        <label class="form-check-label" for="action_assign">
                            <strong><?php echo __('Assign Rights'); ?></strong><br>
                            <small class="text-muted"><?php echo __('Apply rights to selected objects'); ?></small>
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="batch_action" id="action_embargo" value="embargo">
                        <label class="form-check-label" for="action_embargo">
                            <strong><?php echo __('Apply Embargo'); ?></strong><br>
                            <small class="text-muted"><?php echo __('Restrict access to selected objects'); ?></small>
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="batch_action" id="action_clear" value="clear">
                        <label class="form-check-label" for="action_clear">
                            <strong><?php echo __('Clear Rights'); ?></strong><br>
                            <small class="text-muted"><?php echo __('Remove rights from selected objects'); ?></small>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Object Selection -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><?php echo __('2. Select Objects'); ?></h5>
        </div>
        <div class="card-body">
            <!-- Option A: Dropdown -->
            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label fw-bold"><?php echo __('Option A: Select a Fonds/Collection'); ?></label>
                    <select name="information_object_id" id="information_object_id" class="form-select">
                        <option value="">-- <?php echo __('Select a record'); ?> --</option>
                        <?php foreach ($records as $record): ?>
                            <?php 
                                $title = $record->title ?: '[Untitled]';
                                $identifier = $record->identifier ?: 'No ID';
                                $level = $record->level ?: '';
                            ?>
                            <option value="<?php echo $record->id; ?>">
                                [<?php echo $identifier; ?>] <?php echo $title; ?><?php echo $level ? ' ('.$level.')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold"><?php echo __('Apply to'); ?></label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="scope" id="scope_selected" value="selected" checked>
                        <label class="form-check-label" for="scope_selected"><?php echo __('Selected only'); ?></label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="scope" id="scope_children" value="children">
                        <label class="form-check-label" for="scope_children"><?php echo __('Direct children'); ?></label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="scope" id="scope_all" value="all_descendants">
                        <label class="form-check-label" for="scope_all"><?php echo __('All descendants'); ?></label>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="include_parent" id="include_parent" value="1" checked>
                        <label class="form-check-label" for="include_parent"><?php echo __('Include the parent record itself'); ?></label>
                    </div>
                </div>
            </div>

            <hr>

            <!-- Option B: Manual IDs -->
            <div class="mb-3">
                <label class="form-label fw-bold"><?php echo __('Option B: Enter Object IDs (comma-separated)'); ?></label>
                <textarea name="object_ids" id="object_ids" class="form-control" rows="2" 
                          placeholder="<?php echo __('e.g., 12345, 12346, 12347'); ?>"></textarea>
            </div>

            <div class="form-check">
                <input type="checkbox" name="overwrite" id="overwrite" value="1" class="form-check-input">
                <label class="form-check-label" for="overwrite"><?php echo __('Overwrite existing rights'); ?></label>
            </div>
        </div>
    </div>

    <!-- 3. Rights Details -->
    <div id="assign_options" class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><?php echo __('3. Rights Details'); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="rights_statement_id" class="form-label"><?php echo __('Rights Statement'); ?></label>
                    <select name="rights_statement_id" id="rights_statement_id" class="form-select">
                        <option value=""><?php echo __('-- Select --'); ?></option>
                        <?php foreach ($rightsStatements as $rs): ?>
                        <option value="<?php echo $rs->id; ?>">[<?php echo $rs->code; ?>] <?php echo $rs->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="creative_commons_id" class="form-label"><?php echo __('Creative Commons License'); ?></label>
                    <select name="creative_commons_id" id="creative_commons_id" class="form-select">
                        <option value=""><?php echo __('-- Select --'); ?></option>
                        <?php foreach ($ccLicenses as $cc): ?>
                        <option value="<?php echo $cc->id; ?>">[<?php echo $cc->code; ?>] <?php echo $cc->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="rights_holder" class="form-label"><?php echo __('Rights Holder'); ?></label>
                    <input type="text" name="rights_holder" id="rights_holder" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="copyright_notice" class="form-label"><?php echo __('Copyright Notice'); ?></label>
                    <input type="text" name="copyright_notice" id="copyright_notice" class="form-control" placeholder="© 2024 ...">
                </div>
            </div>

            <!-- TK Labels -->
            <?php if (count($tkLabels) > 0): ?>
            <div class="mb-3">
                <label class="form-label fw-bold"><?php echo __('Traditional Knowledge Labels'); ?></label>
                <div class="row">
                    <?php foreach ($tkLabels as $tk): ?>
                    <div class="col-md-4 mb-2">
                        <div class="form-check">
                            <input type="checkbox" name="tk_label_ids[]" value="<?php echo $tk->id; ?>" class="form-check-input" id="tk_<?php echo $tk->id; ?>">
                            <label class="form-check-label" for="tk_<?php echo $tk->id; ?>">
                                <?php if ($tk->icon_url): ?>
                                <img src="<?php echo $tk->icon_url; ?>" alt="" style="width:20px;height:20px;" class="me-1">
                                <?php endif; ?>
                                <?php echo $tk->name; ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Submit -->
    <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'right', 'action' => 'manage']); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Cancel'); ?>
        </a>
        <button type="submit" class="btn btn-success">
            <i class="fas fa-check me-1"></i><?php echo __('Execute Batch Operation'); ?>
        </button>
    </div>
</form>

<!-- Tom Select for dropdown -->
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var select = document.getElementById('information_object_id');
    if (select && typeof TomSelect !== 'undefined') {
        new TomSelect(select, {
            maxOptions: null,
            allowEmptyOption: true,
            render: {
                option: function(data, escape) {
                    var text = data.text;
                    if (!text || text.indexOf('Select') !== -1) return '<div>' + escape(text) + '</div>';
                    
                    var identifier = '', title = text, level = '';
                    var idMatch = text.match(/^\[([^\]]+)\]\s*/);
                    if (idMatch) { identifier = idMatch[1]; title = text.substring(idMatch[0].length); }
                    var lvlMatch = title.match(/\s*\(([^)]+)\)$/);
                    if (lvlMatch) { level = lvlMatch[1]; title = title.substring(0, title.length - lvlMatch[0].length).trim(); }
                    
                    var html = '<div><span style="font-weight:500">' + escape(title) + '</span>';
                    if (identifier) html += ' <span style="color:#6c757d;font-size:0.9em">(' + escape(identifier) + ')</span>';
                    if (level) html += ' <span style="background:#e9ecef;padding:2px 6px;border-radius:3px;font-size:0.75em;margin-left:6px">' + escape(level) + '</span>';
                    html += '</div>';
                    return html;
                },
                item: function(data, escape) {
                    var text = data.text;
                    if (!text || text.indexOf('Select') !== -1) return '<div>' + escape(text) + '</div>';
                    var identifier = '', title = text;
                    var idMatch = text.match(/^\[([^\]]+)\]\s*/);
                    if (idMatch) { identifier = idMatch[1]; title = text.substring(idMatch[0].length); }
                    var lvlMatch = title.match(/\s*\(([^)]+)\)$/);
                    if (lvlMatch) { title = title.substring(0, title.length - lvlMatch[0].length).trim(); }
                    return '<div>' + escape(title) + (identifier ? ' (' + escape(identifier) + ')' : '') + '</div>';
                }
            }
        });
    }

    // Toggle rights options based on action
    document.querySelectorAll('input[name="batch_action"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var assignOptions = document.getElementById('assign_options');
            if (this.value === 'clear') {
                assignOptions.style.display = 'none';
            } else {
                assignOptions.style.display = 'block';
            }
        });
    });
});
</script>
