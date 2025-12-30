<?php
use_helper('Text');
$baseUrl = sfContext::getInstance()->getRequest()->getRelativeUrlRoot() . '/index.php';

// Photo types (must match enum values)
$photoTypes = [
    'overall' => 'Overall View',
    'detail' => 'Detail',
    'damage' => 'Damage',
    'before' => 'Before Treatment',
    'after' => 'After Treatment',
    'other' => 'Other',
];
?>

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
                                    <img src="/uploads/condition_photos/<?php echo $photo->filename; ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo esc_entities($photo->caption ?? ''); ?>"
                                         style="height: 200px; object-fit: cover; cursor: pointer;"
                                         onclick="openAnnotator(<?php echo $photo->id; ?>, '/uploads/condition_photos/<?php echo $photo->filename; ?>')">
                                    
                                    <span class="badge bg-info position-absolute top-0 end-0 m-2">
                                        <?php echo __($photoTypes[$photo->photo_type] ?? 'Other'); ?>
                                    </span>
                                    
                                    <?php if ($photo->annotations): 
                                        $annCount = count(json_decode($photo->annotations, true) ?: []);
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
                                        <button class="btn btn-outline-info" onclick="openAnnotator(<?php echo $photo->id; ?>, '/uploads/condition_photos/<?php echo $photo->filename; ?>')" title="<?php echo __('Annotate'); ?>">
                                            <i class="fas fa-draw-polygon"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deletePhoto(<?php echo $photo->id; ?>)" title="<?php echo __('Delete'); ?>">
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
                <div id="annotator-container" style="min-height: 600px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Close'); ?></button>
                <button type="button" class="btn btn-primary" onclick="saveAnnotations()">
                    <i class="fas fa-save me-1"></i><?php echo __('Save Annotations'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/plugins/ahgConditionPlugin/css/condition-annotator.css">
<script src="/plugins/sfMuseumPlugin/js/fabric.min.js"></script>
<script src="/plugins/sfMuseumPlugin/js/condition-annotator.js?v=1765295470"></script>

<script>
var currentAnnotator = null;
var annotatorModal = null;

document.addEventListener('DOMContentLoaded', function() {
    annotatorModal = new bootstrap.Modal(document.getElementById('annotatorModal'));
    
    // Upload form
    var uploadForm = document.getElementById('upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            uploadPhoto();
        });
    }

    // Dropzone
    var dropzone = document.getElementById('dropzone');
    var fileInput = document.getElementById('photo-file');
    
    if (dropzone && fileInput) {
        dropzone.addEventListener('click', function() {
            fileInput.click();
        });

        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.classList.add('bg-light');
        });

        dropzone.addEventListener('dragleave', function() {
            dropzone.classList.remove('bg-light');
        });

        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.classList.remove('bg-light');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
            }
        });
    }
});

function uploadPhoto() {
    var form = document.getElementById('upload-form');
    var formData = new FormData(form);
    var fileInput = document.getElementById('photo-file');
    
    if (!fileInput.files.length) {
        alert('Please select a photo');
        return;
    }

    formData.append('photo', fileInput.files[0]);

    fetch('/condition/check/<?php echo $checkId; ?>/upload', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Upload failed: ' + error.message);
    });
}

function openAnnotator(photoId, imageSrc) {
    if (currentAnnotator) {
        currentAnnotator.destroy();
        currentAnnotator = null;
    }

    // Show modal first, then initialize annotator after it's visible
    var modal = document.getElementById('annotatorModal');
    
    var initAnnotator = function() {
        // Remove the event listener so it only fires once
        modal.removeEventListener('shown.bs.modal', initAnnotator);
        
        currentAnnotator = new ConditionAnnotator('annotator-container', {
            photoId: photoId,
            imageUrl: imageSrc,
            readonly: false,
            showToolbar: true,
            saveUrl: '/condition/annotation/save',
            getUrl: '/condition/annotation/get'
        });
    };
    
    modal.addEventListener('shown.bs.modal', initAnnotator);
    annotatorModal.show();
}

function saveAnnotations() {
    if (currentAnnotator) {
        currentAnnotator.save().then(function() {
            annotatorModal.hide();
        });
    }
}

function deletePhoto(photoId) {
    if (!confirm('<?php echo __('Are you sure you want to delete this photo?'); ?>')) {
        return;
    }

    fetch('/condition/photo/' + photoId + '/delete', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Delete failed: ' + (data.error || 'Unknown error'));
        }
    });
}
</script>

<style>
.dropzone-area {
    border: 2px dashed #dee2e6;
    cursor: pointer;
    transition: all 0.2s;
}
.dropzone-area:hover, .dropzone-area.bg-light {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}
.photo-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
</style>
