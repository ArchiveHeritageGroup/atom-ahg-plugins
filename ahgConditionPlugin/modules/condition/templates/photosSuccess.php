<?php
use_helper('Text');

$baseUrl = sfContext::getInstance()->getRequest()->getRelativeUrlRoot() . '/index.php';

// Photo types (must match enum values)
$photoTypes = [
    'overall' => 'Overall View',
    'detail'  => 'Detail',
    'damage'  => 'Damage',
    'before'  => 'Before Treatment',
    'after'   => 'After Treatment',
    'other'   => 'Other',
];

// CSP nonce attribute helper (AtoM uses sfConfig('csp_nonce') like "nonce=XYZ")
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
$nonceVal  = $n ? preg_replace('/^nonce=/', '', $n) : '';
?>

<meta name="csp-nonce" content="<?php echo esc_entities($nonceVal); ?>">

<div class="condition-photos-page">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="/<?php echo $conditionCheck->slug; ?>"><?php echo esc_entities($conditionCheck->object_title ?: 'Object'); ?></a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="/<?php echo $conditionCheck->slug; ?>/condition"><?php echo __('Condition'); ?></a>
                    </li>
                    <li class="breadcrumb-item active"><?php echo __('Photos'); ?></li>
                </ol>
            </nav>

            <h1 class="h3 mb-3">
                <i class="fas fa-images me-2"></i>
                <?php echo __('Condition Photos'); ?>
            </h1>

            <div class="mb-3">
                <a href="/<?php echo $conditionCheck->slug; ?>/condition" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back'); ?>
                </a>
            </div>
        </div>
    </div>

    <?php if ($canEdit): ?>
    <!-- Upload Section -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-upload me-2"></i><?php echo __('Upload Photos'); ?></h5>
        </div>
        <div class="card-body">
            <form id="upload-form" enctype="multipart/form-data">
                <input type="hidden" name="condition_check_id" value="<?php echo $checkId; ?>">

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Photo Type'); ?></label>
                            <select name="photo_type" id="photo_type" class="form-select">
                                <?php foreach ($photoTypes as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"><?php echo __($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Caption'); ?></label>
                            <input type="text" name="caption" id="caption" class="form-control"
                                   placeholder="<?php echo __('Brief description of the photo'); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Select Photo'); ?></label>
                            <input type="file" name="photo" id="photo-file" class="form-control" accept="image/*" required>
                        </div>
                    </div>
                </div>

                <div class="dropzone-area text-center p-4 border border-dashed rounded mb-3" id="dropzone">
                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                    <p class="mb-0"><?php echo __('Drag & drop a photo here, or click to select'); ?></p>
                </div>

                <button type="submit" class="btn btn-success" id="upload-btn">
                    <i class="fas fa-upload me-1"></i> <?php echo __('Upload Photo'); ?>
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Photos Grid -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-th me-2"></i><?php echo __('Photos'); ?></h5>
            <span class="badge bg-secondary"><?php echo count($photos); ?></span>
        </div>
        <div class="card-body">
            <?php if (empty($photos)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-camera fa-4x text-muted mb-3"></i>
                    <p class="text-muted"><?php echo __('No photos uploaded yet.'); ?></p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($photos as $photo): ?>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="card h-100 photo-card">
                                <div class="photo-image position-relative">
                                    <img
                                        src="/uploads/condition_photos/<?php echo $photo->filename; ?>"
                                        class="card-img-top condition-photo-thumb"
                                        alt="<?php echo esc_entities($photo->caption ?? ''); ?>"
                                        data-action="annotate"
                                        data-photo-id="<?php echo $photo->id; ?>"
                                        data-image-src="/uploads/condition_photos/<?php echo $photo->filename; ?>"
                                    >

                                    <span class="badge bg-info position-absolute top-0 end-0 m-2">
                                        <?php echo __($photoTypes[$photo->photo_type] ?? 'Other'); ?>
                                    </span>

                                    <?php if ($photo->annotations):
                                        $annCount = count(json_decode(html_entity_decode($photo->annotations), true) ?: []);
                                        if ($annCount > 0):
                                    ?>
                                        <span class="badge bg-warning position-absolute top-0 start-0 m-2">
                                            <i class="fas fa-draw-polygon"></i> <?php echo $annCount; ?>
                                        </span>
                                    <?php endif; endif; ?>
                                </div>

                                <div class="card-body p-2">
                                    <?php if ($photo->caption): ?>
                                        <p class="card-text small mb-1"><?php echo esc_entities($photo->caption); ?></p>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        <?php echo date('d M Y', strtotime($photo->created_at)); ?>
                                    </small>
                                </div>

                                <?php if ($canEdit): ?>
                                <div class="card-footer p-2">
                                    <div class="btn-group btn-group-sm w-100">
                                        <button class="btn btn-outline-info"
                                                data-action="annotate"
                                                data-photo-id="<?php echo $photo->id; ?>"
                                                data-image-src="/uploads/condition_photos/<?php echo $photo->filename; ?>"
                                                title="<?php echo __('Annotate'); ?>">
                                            <i class="fas fa-draw-polygon"></i>
                                        </button>
                                        <button class="btn btn-outline-success"
                                                data-action="ai-scan"
                                                data-photo-id="<?php echo $photo->id; ?>"
                                                data-image-src="/uploads/condition_photos/<?php echo $photo->filename; ?>"
                                                title="<?php echo __('AI Scan'); ?>">
                                            <i class="fas fa-robot"></i>
                                        </button>
                                        <?php
                                        $hasAnnotations = false;
                                        if ($photo->annotations) {
                                            $annData = json_decode(html_entity_decode($photo->annotations), true);
                                            $hasAnnotations = !empty($annData);
                                        }
                                        if ($hasAnnotations): ?>
                                        <button class="btn btn-outline-warning"
                                                data-action="contribute-training"
                                                data-photo-id="<?php echo $photo->id; ?>"
                                                data-image-src="/uploads/condition_photos/<?php echo $photo->filename; ?>"
                                                title="<?php echo __('Contribute to Training'); ?>">
                                            <i class="fas fa-graduation-cap"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn btn-outline-danger"
                                                data-action="delete"
                                                data-photo-id="<?php echo $photo->id; ?>"
                                                title="<?php echo __('Delete'); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Annotation Modal -->
<div class="modal fade" id="annotatorModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-draw-polygon me-2"></i><?php echo __('Annotate Photo'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="annotator-container" class="condition-annotator-container"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Close'); ?></button>
                <button type="button" class="btn btn-primary" data-action="save-annotations">
                    <i class="fas fa-save me-1"></i><?php echo __('Save Annotations'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CSS -->
<link rel="stylesheet" href="/plugins/ahgConditionPlugin/web/css/condition-annotator.css">
<link rel="stylesheet" href="/plugins/ahgConditionPlugin/web/css/condition-photos.css">

<!-- External JS -->
<script src="/plugins/ahgCorePlugin/web/js/vendor/fabric.min.js"></script>
<script src="/plugins/ahgConditionPlugin/web/js/condition-annotator.js?v=<?php echo time(); ?>"></script>
<script src="/plugins/ahgConditionPlugin/web/js/condition-photos.js?v=<?php echo time(); ?>"></script>

<!-- AI Scan Result Modal -->
<div class="modal fade" id="aiScanModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-robot me-2"></i><?php echo __('AI Condition Scan'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="aiScanBody">
                <div class="text-center py-4" id="aiScanLoading">
                    <i class="fas fa-spinner fa-spin fa-2x text-success mb-3 d-block"></i>
                    <p class="text-muted"><?php echo __('Analyzing image for damage...'); ?></p>
                </div>
                <div id="aiScanResult" style="display:none"></div>
            </div>
            <div class="modal-footer" id="aiScanFooter" style="display:none">
                <a href="#" id="aiScanViewFull" class="btn btn-primary btn-sm" target="_blank">
                    <i class="fas fa-eye me-1"></i><?php echo __('View Full Report'); ?>
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo __('Close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Minimal inline config (CSP nonce applied) -->
<script <?php echo $nonceAttr; ?>>
window.AHG_CONDITION = window.AHG_CONDITION || {};
window.AHG_CONDITION.checkId = <?php echo (int)$checkId; ?>;
window.AHG_CONDITION.objectId = <?php echo (int)($conditionCheck->object_id ?? 0); ?>;
window.AHG_CONDITION.confirmDelete = <?php echo json_encode(__('Are you sure you want to delete this photo?')); ?>;

// --- AI Scan Handler ---
document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-action="ai-scan"]');
    if (!btn) return;
    e.preventDefault();

    var imgSrc = btn.dataset.imageSrc;
    var modal = new bootstrap.Modal(document.getElementById('aiScanModal'));
    document.getElementById('aiScanLoading').style.display = '';
    document.getElementById('aiScanResult').style.display = 'none';
    document.getElementById('aiScanFooter').style.display = 'none';
    modal.show();

    // Load image and convert to base64
    fetch(imgSrc)
    .then(function(r) { return r.blob(); })
    .then(function(blob) {
        return new Promise(function(resolve) {
            var reader = new FileReader();
            reader.onload = function() { resolve(reader.result.split(',')[1]); };
            reader.readAsDataURL(blob);
        });
    })
    .then(function(base64) {
        return fetch('<?php echo $baseUrl; ?>/aiCondition/apiSubmit', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                image_base64: base64,
                object_id: window.AHG_CONDITION.objectId || null,
                confidence: 0.25
            })
        });
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        document.getElementById('aiScanLoading').style.display = 'none';
        var resultEl = document.getElementById('aiScanResult');
        resultEl.style.display = '';

        if (!data.success) {
            resultEl.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times me-1"></i>' + (data.error || 'Scan failed') + '</div>';
            return;
        }

        var gradeColors = {excellent:'success',good:'info',fair:'warning',poor:'danger',critical:'dark'};
        var grade = data.condition_grade || 'unknown';
        var score = data.overall_score != null ? parseFloat(data.overall_score).toFixed(1) : '--';

        var html = '<div class="text-center mb-3">';
        html += '<span class="badge bg-' + (gradeColors[grade]||'secondary') + ' fs-5 me-2">' + grade.charAt(0).toUpperCase() + grade.slice(1) + '</span>';
        html += '<span class="badge bg-primary fs-5">Score: ' + score + '/100</span>';
        html += '</div>';

        if (data.overlay_base64) {
            html += '<div class="text-center mb-3"><img src="data:image/jpeg;base64,' + data.overlay_base64 + '" class="img-fluid rounded border" style="max-height:400px"></div>';
        }

        if (data.damages && data.damages.length) {
            html += '<h6><?php echo __('Damages Detected'); ?> (' + data.damages.length + ')</h6><ul class="list-group list-group-flush">';
            data.damages.forEach(function(d) {
                html += '<li class="list-group-item py-1 d-flex justify-content-between"><span><span class="badge" style="background:' + (d.color||'#6c757d') + '">' + (d.damage_type||'unknown').replace(/_/g,' ') + '</span></span><span class="small text-muted">' + Math.round((d.confidence||0)*100) + '% conf</span></li>';
            });
            html += '</ul>';
        } else {
            html += '<div class="alert alert-success py-2"><i class="fas fa-check me-1"></i><?php echo __('No damage detected'); ?></div>';
        }

        if (data.recommendations) {
            var recs = Array.isArray(data.recommendations) ? data.recommendations.join('<br>') : data.recommendations;
            html += '<div class="mt-3"><h6><?php echo __('Recommendations'); ?></h6><p class="small text-muted">' + recs + '</p></div>';
        }

        resultEl.innerHTML = html;
        document.getElementById('aiScanFooter').style.display = '';

        if (data.assessment_id) {
            document.getElementById('aiScanViewFull').href = '<?php echo $baseUrl; ?>/ai-condition/view/' + data.assessment_id;
        }
    })
    .catch(function(err) {
        document.getElementById('aiScanLoading').style.display = 'none';
        document.getElementById('aiScanResult').style.display = '';
        document.getElementById('aiScanResult').innerHTML = '<div class="alert alert-danger"><i class="fas fa-times me-1"></i>Network error: ' + err.message + '</div>';
    });
});

// --- Contribute to Training Handler ---
document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-action="contribute-training"]');
    if (!btn) return;
    e.preventDefault();

    var photoId = btn.dataset.photoId;
    var imgSrc = btn.dataset.imageSrc;

    if (!confirm('<?php echo __('Submit this photo and its annotations as training data for the AI model?'); ?>')) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    // Get the photo annotations from the condition check
    fetch('<?php echo $baseUrl; ?>/condition/api/annotation/' + photoId)
    .then(function(r) { return r.json(); })
    .then(function(annData) {
        // Load image as base64
        return fetch(imgSrc)
        .then(function(r) { return r.blob(); })
        .then(function(blob) {
            return new Promise(function(resolve) {
                var reader = new FileReader();
                reader.onload = function() { resolve({base64: reader.result.split(',')[1], annotations: annData}); };
                reader.readAsDataURL(blob);
            });
        });
    })
    .then(function(result) {
        // Convert fabric.js annotations to damage bounding boxes
        var annotations = [];
        var objects = result.annotations.annotations || result.annotations.objects || [];
        if (typeof objects === 'string') {
            try { objects = JSON.parse(objects); } catch(e) { objects = []; }
        }
        objects.forEach(function(obj) {
            if (obj.left != null && obj.top != null && obj.width != null && obj.height != null) {
                annotations.push({
                    damage_type: obj.damageType || obj.label || 'tear',
                    bbox: {x1: Math.round(obj.left), y1: Math.round(obj.top), x2: Math.round(obj.left + (obj.width * (obj.scaleX || 1))), y2: Math.round(obj.top + (obj.height * (obj.scaleY || 1)))}
                });
            }
        });

        if (!annotations.length) {
            alert('<?php echo __('No valid bounding box annotations found on this photo.'); ?>');
            return Promise.reject('no_annotations');
        }

        return fetch('<?php echo $baseUrl; ?>/aiCondition/apiContribute', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                image_base64: result.base64,
                annotations: annotations,
                source: 'condition_photos',
                object_id: window.AHG_CONDITION.objectId || null
            })
        });
    })
    .then(function(r) { if (r) return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-graduation-cap"></i>';
        if (data && data.success !== false) {
            btn.classList.remove('btn-outline-warning');
            btn.classList.add('btn-warning');
            btn.title = '<?php echo __('Contributed'); ?>';
            alert('<?php echo __('Training data submitted successfully. It will be reviewed by an administrator.'); ?>');
        } else if (data) {
            alert(data.error || '<?php echo __('Failed to submit training data.'); ?>');
        }
    })
    .catch(function(err) {
        if (err === 'no_annotations') return;
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-graduation-cap"></i>';
        alert('<?php echo __('Network error submitting training data.'); ?>');
    });
});
</script>
