<?php use_helper('Text'); ?>
<h1 class="h3 mb-4"><i class="fas fa-file-alt me-2"></i><?php echo __('Privacy Template Library'); ?></h1>

<div class="mb-3">
    <a href="/admin/privacy/manage" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i><?php echo __('Back'); ?></a>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadTemplateModal">
        <i class="fas fa-upload me-1"></i><?php echo __('Upload Template'); ?>
    </button>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.template-card .card-header { color: #ffffff !important; font-weight: 600; }
.template-card .card-header h6 { color: #ffffff !important; }
.template-card .card-header i { color: #ffffff !important; }
</style>

<?php 
$templateArray = [];
if (isset($templates)) {
    foreach ($templates as $t) {
        $templateArray[] = (object)[
            'id' => $t->id,
            'category' => $t->category,
            'name' => $t->name,
            'content' => $t->content ?? '',
            'file_path' => $t->file_path ?? null,
            'file_name' => $t->file_name ?? null,
            'file_size' => $t->file_size ?? 0,
        ];
    }
}

$categories = [
    'paia_manual' => ['icon' => 'fa-book', 'color' => '#1a5f2a', 'label' => 'PAIA Manuals'],
    'privacy_notice' => ['icon' => 'fa-shield-alt', 'color' => '#17a2b8', 'label' => 'Privacy Notices'],
    'dsar_response' => ['icon' => 'fa-reply', 'color' => '#d4a200', 'label' => 'DSAR Responses'],
    'breach_notification' => ['icon' => 'fa-exclamation-triangle', 'color' => '#dc3545', 'label' => 'Breach Notifications'],
    'consent_form' => ['icon' => 'fa-check-square', 'color' => '#28a745', 'label' => 'Consent Forms'],
    'retention_schedule' => ['icon' => 'fa-calendar-alt', 'color' => '#6c757d', 'label' => 'Retention Schedules'],
];
?>

<div class="row">
<?php foreach ($categories as $cat => $info): 
    $catTemplates = [];
    foreach ($templateArray as $t) {
        if ($t->category === $cat) {
            $catTemplates[] = $t;
        }
    }
?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 template-card">
            <div class="card-header" style="background-color: <?php echo $info['color']; ?>;">
                <h6 class="mb-0" style="color: #fff;"><i class="fas <?php echo $info['icon']; ?> me-2" style="color: #fff;"></i><?php echo __($info['label']); ?></h6>
            </div>
            <ul class="list-group list-group-flush">
            <?php if (count($catTemplates) > 0): ?>
                <?php foreach ($catTemplates as $t): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?php echo esc_entities($t->name); ?></strong>
                            <?php if ($t->file_name): ?>
                                <br><small class="text-muted">
                                    <i class="fas fa-file-word text-primary me-1"></i>
                                    <?php echo esc_entities($t->file_name); ?>
                                    (<?php echo round(($t->file_size ?? 0) / 1024); ?> KB)
                                </small>
                            <?php endif; ?>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <?php if ($t->file_path): ?>
                                <a href="/admin/privacy/templates/download?id=<?php echo $t->id; ?>" class="btn btn-outline-success" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                            <?php endif; ?>
                            <button class="btn btn-outline-warning" onclick="replaceTemplate(<?php echo $t->id; ?>, '<?php echo esc_entities(addslashes($t->name)); ?>')" title="Replace">
                                <i class="fas fa-sync"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteTemplate(<?php echo $t->id; ?>)" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="list-group-item text-muted text-center"><small>No templates</small></li>
            <?php endif; ?>
            </ul>
        </div>
    </div>
<?php endforeach; ?>
</div>

<!-- Upload Template Modal -->
<div class="modal fade" id="uploadTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="/admin/privacy/templates" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <div class="modal-header" style="background-color: #1a5f2a;">
                    <h5 class="modal-title" style="color: #fff;"><i class="fas fa-upload me-2"></i>Upload Template</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select name="category" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option value="paia_manual">PAIA Manual</option>
                            <option value="privacy_notice">Privacy Notice</option>
                            <option value="dsar_response">DSAR Response</option>
                            <option value="breach_notification">Breach Notification</option>
                            <option value="consent_form">Consent Form</option>
                            <option value="retention_schedule">Retention Schedule</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Template Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., PAIA Manual - Standard">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Word Document (.docx) *</label>
                        <input type="file" name="template_file" class="form-control" accept=".docx,.doc" required>
                        <small class="text-muted">Upload a .docx file</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i>Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Replace Template Modal -->
<div class="modal fade" id="replaceTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="/admin/privacy/templates" enctype="multipart/form-data">
                <input type="hidden" name="action" value="replace">
                <input type="hidden" name="id" id="replace_id">
                <div class="modal-header" style="background-color: #d4a200;">
                    <h5 class="modal-title" style="color: #fff;"><i class="fas fa-sync me-2"></i>Replace Template</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Replacing: <strong id="replace_name"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">New Word Document (.docx) *</label>
                        <input type="file" name="template_file" class="form-control" accept=".docx,.doc" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-sync me-1"></i>Replace</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function replaceTemplate(id, name) {
    document.getElementById('replace_id').value = id;
    document.getElementById('replace_name').textContent = name;
    new bootstrap.Modal(document.getElementById('replaceTemplateModal')).show();
}

function deleteTemplate(id) {
    if (confirm('Delete this template?')) {
        window.location.href = '/admin/privacy/templates/delete?id=' + id;
    }
}
</script>
