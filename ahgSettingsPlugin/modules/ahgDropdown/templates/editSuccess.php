<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-cog me-2"></i><?php echo __('Actions') ?></h5>
        </div>
        <div class="card-body">
            <a href="<?php echo url_for(['module' => 'ahgDropdown', 'action' => 'index']) ?>" class="btn btn-outline-secondary w-100 mb-2">
                <i class="fas fa-arrow-left me-2"></i><?php echo __('Back to List') ?>
            </a>
            <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addTermModal">
                <i class="fas fa-plus me-2"></i><?php echo __('Add Term') ?>
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Info') ?></h6>
        </div>
        <div class="card-body small">
            <dl class="mb-0">
                <dt><?php echo __('Code') ?></dt>
                <dd><code><?php echo esc_entities($taxonomy) ?></code></dd>
                <dt><?php echo __('Terms') ?></dt>
                <dd><?php echo count($terms) ?></dd>
            </dl>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ahgDropdown', 'action' => 'index']) ?>"><?php echo __('Dropdown Manager') ?></a></li>
        <li class="breadcrumb-item active"><?php echo esc_entities($taxonomyLabel) ?></li>
    </ol>
</nav>
<h1><i class="fas fa-list me-2"></i><?php echo esc_entities($taxonomyLabel) ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php $termsRaw = $sf_data->getRaw('terms'); ?>
<div class="taxonomy-editor">
    <?php if (empty($terms)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <?php echo __('No terms in this taxonomy.') ?>
        <button type="button" class="btn btn-sm btn-success ms-2" data-bs-toggle="modal" data-bs-target="#addTermModal">
            <?php echo __('Add your first term') ?>
        </button>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <span><i class="fas fa-grip-lines me-2"></i><?php echo __('Drag to reorder') ?></span>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="showInactive" onchange="toggleInactive()">
                <label class="form-check-label" for="showInactive"><?php echo __('Show inactive') ?></label>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th><?php echo __('Label') ?></th>
                        <th><?php echo __('Code') ?></th>
                        <th style="width: 80px;"><?php echo __('Color') ?></th>
                        <th style="width: 80px;"><?php echo __('Default') ?></th>
                        <th style="width: 80px;"><?php echo __('Active') ?></th>
                        <th style="width: 120px;"><?php echo __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody id="termsTable">
                    <?php foreach ($termsRaw as $term): ?>
                    <tr data-id="<?php echo $term->id ?>" class="term-row <?php echo !$term->is_active ? 'table-secondary inactive-row d-none' : '' ?>">
                        <td class="drag-handle text-center text-muted"><i class="fas fa-grip-vertical"></i></td>
                        <td>
                            <input type="text" class="form-control form-control-sm border-0 bg-transparent" value="<?php echo htmlspecialchars($term->label, ENT_QUOTES, 'UTF-8', false) ?>" onchange="updateTerm(<?php echo $term->id ?>, 'label', this.value)">
                        </td>
                        <td><code class="small"><?php echo htmlspecialchars($term->code, ENT_QUOTES, 'UTF-8', false) ?></code></td>
                        <td>
                            <input type="color" class="form-control form-control-color form-control-sm" value="<?php echo $term->color ?: '#6c757d' ?>" onchange="updateTerm(<?php echo $term->id ?>, 'color', this.value)" title="<?php echo __('Choose color') ?>">
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input type="radio" name="default_term" class="form-check-input" <?php echo $term->is_default ? 'checked' : '' ?> onchange="setDefault(<?php echo $term->id ?>)">
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input type="checkbox" class="form-check-input" <?php echo $term->is_active ? 'checked' : '' ?> onchange="updateTerm(<?php echo $term->id ?>, 'is_active', this.checked ? 1 : 0)">
                            </div>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteTerm(<?php echo $term->id ?>)" title="<?php echo __('Delete') ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif ?>
</div>

<!-- Add Term Modal -->
<div class="modal fade" id="addTermModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i><?php echo __('Add Term') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Label') ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newTermLabel" placeholder="e.g., Approved">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Code') ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newTermCode" placeholder="e.g., approved">
                    <div class="form-text"><?php echo __('Lowercase letters, numbers, and underscores only') ?></div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <label class="form-label"><?php echo __('Color') ?></label>
                        <input type="color" class="form-control form-control-color w-100" id="newTermColor" value="#6c757d">
                    </div>
                    <div class="col-6">
                        <label class="form-label"><?php echo __('Icon') ?></label>
                        <input type="text" class="form-control" id="newTermIcon" placeholder="fa-check">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
                <button type="button" class="btn btn-success" onclick="addTerm()"><?php echo __('Add') ?></button>
            </div>
        </div>
    </div>
</div>

<script src="/plugins/ahgCorePlugin/web/js/vendor/sortable.min.js" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
var taxonomy = '<?php echo esc_entities($taxonomy) ?>';
var taxonomyLabel = '<?php echo esc_entities($taxonomyLabel) ?>';

document.addEventListener('DOMContentLoaded', function() {
    var tbody = document.getElementById('termsTable');
    if (tbody) {
        new Sortable(tbody, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function() {
                var order = Array.from(tbody.querySelectorAll('tr')).map(r => r.dataset.id);
                fetch('<?php echo url_for(['module' => 'ahgDropdown', 'action' => 'reorder']) ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'order[]=' + order.join('&order[]=')
                });
            }
        });
    }
});

function updateTerm(id, field, value) {
    fetch('<?php echo url_for(['module' => 'ahgDropdown', 'action' => 'updateTerm']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id + '&' + field + '=' + encodeURIComponent(value)
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert('<?php echo __('Error updating term') ?>');
        }
    });
}

function setDefault(id) {
    fetch('<?php echo url_for(['module' => 'ahgDropdown', 'action' => 'setDefault']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id + '&taxonomy=' + encodeURIComponent(taxonomy)
    });
}

function deleteTerm(id) {
    if (!confirm('<?php echo __('Delete this term?') ?>')) return;

    fetch('<?php echo url_for(['module' => 'ahgDropdown', 'action' => 'deleteTerm']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id + '&hard_delete=1'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.querySelector('tr[data-id="' + id + '"]').remove();
        } else {
            alert('<?php echo __('Error deleting term') ?>');
        }
    });
}

function addTerm() {
    var label = document.getElementById('newTermLabel').value.trim();
    var code = document.getElementById('newTermCode').value.trim();
    var color = document.getElementById('newTermColor').value;
    var icon = document.getElementById('newTermIcon').value.trim();

    if (!label || !code) {
        alert('<?php echo __('Label and code are required') ?>');
        return;
    }

    fetch('<?php echo url_for(['module' => 'ahgDropdown', 'action' => 'addTerm']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'taxonomy=' + encodeURIComponent(taxonomy) +
              '&taxonomy_label=' + encodeURIComponent(taxonomyLabel) +
              '&code=' + encodeURIComponent(code) +
              '&label=' + encodeURIComponent(label) +
              '&color=' + encodeURIComponent(color) +
              '&icon=' + encodeURIComponent(icon)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || '<?php echo __('Error adding term') ?>');
        }
    });
}

function toggleInactive() {
    var show = document.getElementById('showInactive').checked;
    document.querySelectorAll('.inactive-row').forEach(function(row) {
        row.classList.toggle('d-none', !show);
    });
}

// Auto-generate code from label
document.getElementById('newTermLabel').addEventListener('input', function() {
    var code = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
    document.getElementById('newTermCode').value = code;
});
</script>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.drag-handle { cursor: grab; }
.drag-handle:active { cursor: grabbing; }
.sortable-ghost { opacity: 0.4; background: #f8f9fa; }
.form-control-color { height: 31px; padding: 2px; }
</style>
<?php end_slot() ?>
