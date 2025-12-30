<?php
/**
 * Condition Photo Annotation View
 */
$photoTypes = [
    'general' => 'General',
    'detail' => 'Detail',
    'damage' => 'Damage',
    'before' => 'Before Treatment',
    'after' => 'After Treatment',
    'raking' => 'Raking Light',
    'uv' => 'UV Light',
    'ir' => 'Infrared',
    'xray' => 'X-Ray',
];
?>

<div class="condition-check-header">
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb mb-0" style="background: transparent; padding: 0;">
            <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage') ?>" style="color: rgba(255,255,255,0.8);">Home</a></li>
            <?php if ($conditionCheck->slug): ?>
            <li class="breadcrumb-item"><a href="/<?php echo $conditionCheck->slug ?>" style="color: rgba(255,255,255,0.8);"><?php echo $conditionCheck->identifier ?></a></li>
            <?php endif ?>
            <li class="breadcrumb-item"><a href="<?php echo url_for('@condition_photos?id=' . $photo->condition_check_id) ?>" style="color: rgba(255,255,255,0.8);">Condition Photos</a></li>
            <li class="breadcrumb-item active" style="color: #fff;">Annotate</li>
        </ol>
    </nav>
    
    <h1><i class="fas fa-draw-polygon me-2"></i><?php echo __('Annotate Condition Photo') ?></h1>
    
    <div class="object-info">
        <strong><?php echo $conditionCheck->identifier ?></strong> - 
        <?php echo $conditionCheck->object_title ?>
    </div>
    
    <div class="check-meta">
        <div class="meta-item">
            <span class="meta-label"><?php echo __('Photo Type') ?></span>
            <span class="meta-value"><span class="photo-type <?php echo $photo->photo_type ?>"><?php echo $photoTypes[$photo->photo_type] ?? $photo->photo_type ?></span></span>
        </div>
        <div class="meta-item">
            <span class="meta-label"><?php echo __('Caption') ?></span>
            <span class="meta-value"><?php echo $photo->caption ?: __('No caption') ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label"><?php echo __('Uploaded') ?></span>
            <span class="meta-value"><?php echo date('j M Y H:i', strtotime($photo->created_at)) ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label"><?php echo __('Annotations') ?></span>
            <span class="meta-value"><?php echo count($annotations) ?></span>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col">
        <a href="<?php echo url_for('@condition_photos?id=' . $photo->condition_check_id) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Photos') ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-9">
        <!-- Annotation Canvas -->
        <div id="annotator-container" style="min-height: 500px;"></div>
    </div>
    
    <div class="col-lg-3">
        <!-- Annotation List -->
        <div class="annotation-list-panel">
            <div class="panel-header">
                <i class="fas fa-list me-1"></i> <?php echo __('Annotations') ?>
            </div>
            <div id="annotation-list">
                <?php if (empty($annotations)): ?>
                <div class="p-3 text-muted text-center">
                    <?php echo __('No annotations yet') ?>
                </div>
                <?php else: ?>
                <?php foreach ($annotations as $ann): ?>
                <div class="annotation-list-item" data-id="<?php echo $ann['id'] ?? '' ?>">
                    <span class="ann-color" style="background: <?php echo $ann['fabricData']['stroke'] ?? $ann['stroke'] ?? '#FF0000' ?>;"></span>
                    <div class="ann-info">
                        <div class="ann-label">
                            <?php echo esc_entities($ann['label'] ?? 'Annotation') ?>
                            <?php if (!empty($ann['ai_generated'])): ?>
                            <span class="ann-ai">AI</span>
                            <?php endif ?>
                        </div>
                        <?php if (!empty($ann['notes'])): ?>
                        <div class="ann-notes"><?php echo esc_entities($ann['notes']) ?></div>
                        <?php endif ?>
                    </div>
                </div>
                <?php endforeach ?>
                <?php endif ?>
            </div>
        </div>
        
        <!-- Photo Info -->
        <div class="card mt-3">
            <div class="card-header">
                <i class="fas fa-info-circle me-1"></i> <?php echo __('Photo Details') ?>
            </div>
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form id="photoMetaForm">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Caption') ?></label>
                        <input type="text" name="caption" class="form-control form-control-sm" value="<?php echo esc_entities($photo->caption) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Photo Type') ?></label>
                        <select name="photo_type" class="form-select form-select-sm">
                            <?php foreach ($photoTypes as $key => $label): ?>
                            <option value="<?php echo $key ?>" <?php echo $photo->photo_type == $key ? 'selected' : '' ?>><?php echo $label ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-save me-1"></i> <?php echo __('Update Info') ?>
                    </button>
                </form>
                <?php else: ?>
                <p><strong><?php echo __('Filename:') ?></strong><br><?php echo $photo->original_name ?></p>
                <p><strong><?php echo __('Size:') ?></strong><br><?php echo number_format($photo->file_size / 1024, 1) ?> KB</p>
                <p><strong><?php echo __('Type:') ?></strong><br><?php echo $photo->mime_type ?></p>
                <?php endif ?>
            </div>
        </div>
        
        <!-- Help -->
        <div class="card mt-3">
            <div class="card-header">
                <i class="fas fa-question-circle me-1"></i> <?php echo __('Help') ?>
            </div>
            <div class="card-body small">
                <p><strong><?php echo __('Tools:') ?></strong></p>
                <ul class="mb-2">
                    <li><i class="fas fa-mouse-pointer"></i> - Select/move annotations</li>
                    <li><i class="far fa-square"></i> - Draw rectangle</li>
                    <li><i class="far fa-circle"></i> - Draw ellipse</li>
                    <li><i class="fas fa-long-arrow-alt-right"></i> - Draw arrow</li>
                    <li><i class="fas fa-pencil-alt"></i> - Freehand drawing</li>
                    <li><i class="fas fa-font"></i> - Add text label</li>
                    <li><i class="fas fa-map-marker-alt"></i> - Add numbered marker</li>
                </ul>
                <p><strong><?php echo __('Shortcuts:') ?></strong></p>
                <ul class="mb-0">
                    <li>Delete - Remove selected</li>
                    <li>Ctrl+Z - Undo</li>
                    <li>Ctrl+S - Save</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Load Fabric.js from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>

<!-- Load our annotator -->
<script src="/plugins/ahgConditionPlugin/js/condition-annotator.js"></script>

<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize annotator
    var annotator = new ConditionAnnotator('annotator-container', {
        photoId: <?php echo $photoId ?>,
        imageUrl: '<?php echo $imageUrl ?>',
        readonly: <?php echo $canEdit ? 'false' : 'true' ?>,
        showToolbar: true,
        onSave: function(annotations) {
            // Update annotation list
            updateAnnotationList(annotations);
        },
        onAnnotationAdded: function(ann) {
            console.log('Added:', ann);
        },
        onAnnotationRemoved: function(ann) {
            console.log('Removed:', ann);
        }
    });
    
    // Load existing annotations
    <?php if (!empty($annotations)): ?>
    annotator.fromJSON(<?php echo json_encode($annotations) ?>);
    <?php endif ?>
    
    function updateAnnotationList(annotations) {
        var listEl = document.getElementById('annotation-list');
        
        if (annotations.length === 0) {
            listEl.innerHTML = '<div class="p-3 text-muted text-center">No annotations yet</div>';
            return;
        }
        
        var html = '';
        annotations.forEach(function(ann) {
            var color = (ann.fabricData && ann.fabricData.stroke) || ann.stroke || '#FF0000';
            var aiTag = ann.ai_generated ? '<span class="ann-ai">AI</span>' : '';
            
            html += '<div class="annotation-list-item" data-id="' + (ann.id || '') + '">' +
                '<span class="ann-color" style="background: ' + color + ';"></span>' +
                '<div class="ann-info">' +
                '<div class="ann-label">' + (ann.label || 'Annotation') + ' ' + aiTag + '</div>' +
                (ann.notes ? '<div class="ann-notes">' + ann.notes + '</div>' : '') +
                '</div></div>';
        });
        
        listEl.innerHTML = html;
    }
    
    // Photo meta form
    var metaForm = document.getElementById('photoMetaForm');
    if (metaForm) {
        metaForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(metaForm);
            
            fetch('/condition/photo/<?php echo $photoId ?>/update-meta', {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    alert('Photo info updated');
                }
            });
        });
    }
});
</script>
