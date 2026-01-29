<?php
/**
 * Condition Photos Template
 * 
 * Displays and manages photos for condition reports
 */

$title = __('Condition Report Photos');
slot('title', $title);

// Include photo gallery CSS/JS
use_stylesheet('/plugins/ahgSpectrumPlugin/web/css/condition-photos.css?v=' . time());
use_javascript('/plugins/ahgSpectrumPlugin/web/js/condition-photos.js');
?>

<style>
/* Inline styles for photo grid - compact thumbnails */
.photo-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)) !important;
    gap: 0.5rem !important;
}
.photo-image {
    position: relative;
    width: 100%;
    height: 100px !important;
    overflow: hidden;
    background: #f8f9fa;
}
.photo-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
}
.photo-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    overflow: hidden;
}
.photo-info {
    padding: 0.25rem;
    font-size: 0.6rem;
}
.photo-meta small {
    font-size: 0.55rem;
}
.photo-actions {
    padding: 0.2rem;
    border-top: 1px solid #eee;
}
.photo-actions .btn {
    padding: 0.1rem 0.25rem;
    font-size: 0.6rem;
}
.photo-badge, .photo-type-badge {
    font-size: 0.5rem !important;
    padding: 1px 3px !important;
}
</style>

<div class="spectrum-condition-photos">
    
    <!-- Header -->
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1><i class="fas fa-camera"></i> <?php echo $title; ?></h1>
            <p class="text-muted mb-0">
                <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'show', 'slug' => $resource->slug]); ?>">
                    <?php echo esc_entities($resource->title ?? $resource->slug); ?>
                </a>
                &raquo;
                Condition Check: <?php echo $conditionCheck['condition_check_reference']; ?>
                (<?php echo date('d M Y', strtotime($conditionCheck['check_date'])); ?>)
            </p>
        </div>
        <div class="btn-group">
            <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'index', 'slug' => $resource->slug]); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> <?php echo __('Back'); ?>
            </a>
        </div>
    </div>
    
    <!-- Photo Upload Section -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-upload"></i> <?php echo __('Upload Photos'); ?></h5>
        </div>
        <div class="card-body">
            <form action="<?php echo url_for(['module' => 'spectrum', 'action' => 'conditionPhotos', 'slug' => $resource->slug]) . '?photo_action=upload&condition_id=' . $conditionCheckId; ?>" 
                  method="post" 
                  enctype="multipart/form-data" 
                  id="photo-upload-form"
                  class="dropzone-container">
                
                <div class="row">
                    <div class="col-md-8">
                        <!-- Dropzone area -->
                        <div id="photo-dropzone" class="dropzone-area">
                            <div class="dropzone-content">
                                <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                                <p class="mb-2"><strong><?php echo __('Drag & drop photos here'); ?></strong></p>
                                <p class="text-muted mb-3"><?php echo __('or'); ?></p>
                                <label class="btn btn-outline-primary">
                                    <i class="fas fa-folder-open"></i> <?php echo __('Browse Files'); ?>
                                    <input type="file" name="photos[]" multiple accept="image/*" class="d-none" id="photo-input">
                                </label>
                                <p class="text-muted mt-3 small">
                                    <?php echo __('Allowed: JPEG, PNG, WebP, TIFF • Max 10MB per file'); ?>
                                </p>
                            </div>
                            <!-- Preview area -->
                            <div id="photo-preview" class="row mt-3" style="display: none;"></div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Upload metadata -->
                        <div class="form-group">
                            <label for="photo_type"><?php echo __('Photo Type'); ?></label>
                            <select name="photo_type" id="photo_type" class="form-control">
                                <?php foreach ($photoTypes as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"><?php echo __($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="photographer"><?php echo __('Photographer'); ?></label>
                            <input type="text" name="photographer" id="photographer" class="form-control" 
                                   value="<?php echo $sf_user->getAttribute('username'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="photo_date"><?php echo __('Photo Date'); ?></label>
                            <input type="date" name="photo_date" id="photo_date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="location_on_object"><?php echo __('Location on Object'); ?></label>
                            <input type="text" name="location_on_object" id="location_on_object" class="form-control" 
                                   placeholder="<?php echo __('e.g., Front, Back, Top left corner'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="caption"><?php echo __('Caption'); ?></label>
                            <textarea name="caption" id="caption" class="form-control" rows="2" 
                                      placeholder="<?php echo __('Brief description of the photo'); ?>"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-block" id="upload-btn" disabled>
                            <i class="fas fa-upload"></i> <?php echo __('Upload Photos'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Photo Gallery -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-images"></i> <?php echo __('Photo Gallery'); ?></h5>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary active" data-view="grid">
                    <i class="fas fa-th"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" data-view="list">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($photos)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-camera fa-3x mb-3"></i>
                    <p><?php echo __('No photos uploaded yet.'); ?></p>
                </div>
            <?php else: ?>
                
                <!-- Filter by type -->
                <div class="mb-3">
                    <div class="btn-group btn-group-sm" id="photo-type-filter">
                        <button type="button" class="btn btn-outline-primary active" data-type="all">
                            <?php echo __('All'); ?> (<?php echo count($photos); ?>)
                        </button>
                        <?php foreach ($photosByType as $type => $typePhotos): ?>
                            <button type="button" class="btn btn-outline-primary" data-type="<?php echo $type; ?>">
                                <?php echo __($photoTypes[$type]); ?> (<?php echo count($typePhotos); ?>)
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Photo grid -->
                <div class="photo-grid" id="photo-grid">
                    <?php foreach ($photos as $photo): ?>
                        <div class="photo-item" data-type="<?php echo $photo['photo_type']; ?>" data-id="<?php echo $photo['id']; ?>">
                            <div class="photo-card">
                                <div class="photo-image">
                                    <img src="<?php echo $photo['file_path']; ?>" 
                                         alt="<?php echo htmlspecialchars($photo['caption'] ?? ''); ?>"
                                         loading="lazy"
                                         onclick="openLightbox(<?php echo $photo['id']; ?>)">
                                    
                                    <?php if ($photo['is_primary']): ?>
                                        <span class="badge badge-primary photo-badge"><?php echo __('Primary'); ?></span>
                                    <?php endif; ?>
                                    
                                    <span class="badge badge-<?php echo $photo['photo_type']; ?> photo-type-badge">
                                        <?php echo __($photoTypes[$photo['photo_type']]); ?>
                                    </span>
                                </div>
                                
                                <div class="photo-info">
                                    <?php if ($photo['caption']): ?>
                                        <p class="caption"><?php echo htmlspecialchars($photo['caption']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="photo-meta">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($photo['photo_date'] ?? $photo['created_at'])); ?>
                                            <?php if ($photo['photographer']): ?>
                                                | <i class="fas fa-user"></i> <?php echo htmlspecialchars($photo['photographer']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="photo-actions">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" onclick="editPhoto(<?php echo $photo['id']; ?>)" title="<?php echo __('Edit'); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="rotatePhoto(<?php echo $photo['id']; ?>, 90)" title="<?php echo __('Rotate'); ?>">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <?php if (!$photo['is_primary']): ?>
                                            <button type="button" class="btn btn-outline-success" onclick="setPrimary(<?php echo $photo['id']; ?>)" title="<?php echo __('Set as Primary'); ?>">
                                                <i class="fas fa-star"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline-danger" onclick="deletePhoto(<?php echo $photo['id']; ?>)" title="<?php echo __('Delete'); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Before/After Comparisons -->
    <?php if (!empty($comparisons)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-columns"></i> <?php echo __('Before/After Comparisons'); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($comparisons as $comparison): ?>
                    <div class="col-md-6 mb-4">
                        <div class="comparison-card">
                            <?php if ($comparison['comparison_title']): ?>
                                <h6><?php echo htmlspecialchars($comparison['comparison_title']); ?></h6>
                            <?php endif; ?>
                            
                            <div class="comparison-slider" id="comparison-<?php echo $comparison['id']; ?>">
                                <div class="before-image">
                                    <img src="/uploads/<?php echo $comparison['before_file_path']; ?>" alt="Before">
                                    <span class="label"><?php echo __('Before'); ?></span>
                                </div>
                                <div class="after-image">
                                    <img src="/uploads/<?php echo $comparison['after_file_path']; ?>" alt="After">
                                    <span class="label"><?php echo __('After'); ?></span>
                                </div>
                                <input type="range" class="comparison-range" min="0" max="100" value="50">
                            </div>
                            
                            <?php if ($comparison['comparison_notes']): ?>
                                <p class="mt-2 small text-muted"><?php echo htmlspecialchars($comparison['comparison_notes']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Create Comparison Button -->
    <?php 
    $beforePhotos = $photosByType['before'] ?? [];
    $afterPhotos = $photosByType['after'] ?? [];
    if (!empty($beforePhotos) && !empty($afterPhotos)): 
    ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-plus-circle"></i> <?php echo __('Create New Comparison'); ?></h5>
        </div>
        <div class="card-body">
            <form action="<?php echo url_for(['module' => 'spectrum', 'action' => 'conditionPhotos', 'slug' => $objectSlug, 'condition_id' => $conditionCheckId, 'photo_action' => 'create_comparison']); ?>" method="post">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?php echo __('Before Photo'); ?></label>
                            <select name="before_photo_id" class="form-control" required>
                                <option value=""><?php echo __('Select before photo...'); ?></option>
                                <?php foreach ($beforePhotos as $photo): ?>
                                    <option value="<?php echo $photo['id']; ?>">
                                        <?php echo $photo['caption'] ?: $photo['original_filename']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?php echo __('After Photo'); ?></label>
                            <select name="after_photo_id" class="form-control" required>
                                <option value=""><?php echo __('Select after photo...'); ?></option>
                                <?php foreach ($afterPhotos as $photo): ?>
                                    <option value="<?php echo $photo['id']; ?>">
                                        <?php echo $photo['caption'] ?: $photo['original_filename']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?php echo __('Comparison Title'); ?></label>
                            <input type="text" name="comparison_title" class="form-control" placeholder="<?php echo __('Optional title'); ?>">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo __('Notes'); ?></label>
                    <textarea name="comparison_notes" class="form-control" rows="2" placeholder="<?php echo __('Optional notes about the comparison'); ?>"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-columns"></i> <?php echo __('Create Comparison'); ?>
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<!-- Lightbox Modal -->
<div class="modal fade" id="photo-lightbox" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white" id="lightbox-title"></h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="lightbox-image" src="" alt="" class="img-fluid">
            </div>
            <div class="modal-footer border-0 justify-content-between">
                <button type="button" class="btn btn-secondary" id="lightbox-prev">
                    <i class="fas fa-chevron-left"></i> <?php echo __('Previous'); ?>
                </button>
                <div id="lightbox-info" class="text-white"></div>
                <button type="button" class="btn btn-secondary" id="lightbox-next">
                    <?php echo __('Next'); ?> <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Photo Modal -->
<div class="modal fade" id="edit-photo-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('Edit Photo'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="edit-photo-form">
                <div class="modal-body">
                    <input type="hidden" name="photo_id" id="edit-photo-id">
                    
                    <div class="form-group">
                        <label><?php echo __('Photo Type'); ?></label>
                        <select name="photo_type" id="edit-photo-type" class="form-control">
                            <?php foreach ($photoTypes as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo __($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo __('Caption'); ?></label>
                        <input type="text" name="caption" id="edit-caption" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo __('Description'); ?></label>
                        <textarea name="description" id="edit-description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo __('Location on Object'); ?></label>
                        <input type="text" name="location_on_object" id="edit-location" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo __('Photographer'); ?></label>
                        <input type="text" name="photographer" id="edit-photographer" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo __('Photo Date'); ?></label>
                        <input type="date" name="photo_date" id="edit-photo-date" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('Save Changes'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
// Photo data for lightbox
var photos = <?php echo json_encode($photos); ?>;
var currentPhotoIndex = 0;
var baseUrl = '<?php echo url_for(['module' => 'spectrum', 'action' => 'conditionPhotos', 'slug' => $objectSlug, 'condition_id' => $conditionCheckId]); ?>';

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initDropzone();
    initPhotoFilters();
    initComparisonSliders();
    initSortable();
});

function initDropzone() {
    var dropzone = document.getElementById('photo-dropzone');
    var input = document.getElementById('photo-input');
    var preview = document.getElementById('photo-preview');
    var uploadBtn = document.getElementById('upload-btn');
    
    // Drag and drop events
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) {
        dropzone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(function(eventName) {
        dropzone.addEventListener(eventName, function() {
            dropzone.classList.add('dragover');
        }, false);
    });
    
    ['dragleave', 'drop'].forEach(function(eventName) {
        dropzone.addEventListener(eventName, function() {
            dropzone.classList.remove('dragover');
        }, false);
    });
    
    dropzone.addEventListener('drop', function(e) {
        input.files = e.dataTransfer.files;
        showPreviews(input.files);
    });
    
    input.addEventListener('change', function() {
        showPreviews(this.files);
    });
    
    function showPreviews(files) {
        preview.innerHTML = '';
        preview.style.display = 'flex';
        uploadBtn.disabled = files.length === 0;

        Array.from(files).forEach(function(file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var col = document.createElement('div');
                col.className = 'col-md-3 mb-2';
                col.innerHTML = '<div class="preview-thumb"><img src="' + e.target.result + '" class="img-thumbnail"><small>' + file.name + '</small></div>';
                preview.appendChild(col);
            };
            reader.readAsDataURL(file);
        });
    }
}

function initPhotoFilters() {
    var filterBtns = document.querySelectorAll('#photo-type-filter button');
    var photoItems = document.querySelectorAll('.photo-item');

    filterBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var type = this.dataset.type;

            filterBtns.forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');

            photoItems.forEach(function(photo) {
                if (type === 'all' || photo.dataset.type === type) {
                    photo.style.display = '';
                } else {
                    photo.style.display = 'none';
                }
            });
        });
    });
}

function initComparisonSliders() {
    document.querySelectorAll('.comparison-slider').forEach(function(slider) {
        var range = slider.querySelector('.comparison-range');
        var afterImage = slider.querySelector('.after-image');

        range.addEventListener('input', function() {
            afterImage.style.clipPath = 'inset(0 0 0 ' + this.value + '%)';
        });
    });
}

function initSortable() {
    var grid = document.getElementById('photo-grid');
    if (grid && typeof Sortable !== 'undefined') {
        new Sortable(grid, {
            animation: 150,
            onEnd: function() {
                var order = Array.from(grid.querySelectorAll('.photo-item')).map(function(el) {
                    return el.dataset.id;
                });

                fetch(baseUrl + '&photo_action=reorder', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({order: order})
                });
            }
        });
    }
}

function openLightbox(photoId) {
    currentPhotoIndex = photos.findIndex(function(p) { return p.id == photoId; });
    updateLightbox();
    $('#photo-lightbox').modal('show');
}

function updateLightbox() {
    var photo = photos[currentPhotoIndex];
    document.getElementById('lightbox-image').src = photo.file_path;
    document.getElementById('lightbox-title').textContent = photo.caption || photo.original_filename;
    document.getElementById('lightbox-info').textContent = (currentPhotoIndex + 1) + ' / ' + photos.length;
}

document.getElementById('lightbox-prev').addEventListener('click', function() {
    currentPhotoIndex = (currentPhotoIndex - 1 + photos.length) % photos.length;
    updateLightbox();
});

document.getElementById('lightbox-next').addEventListener('click', function() {
    currentPhotoIndex = (currentPhotoIndex + 1) % photos.length;
    updateLightbox();
});

function editPhoto(photoId) {
    var photo = photos.find(function(p) { return p.id == photoId; });
    if (!photo) return;

    document.getElementById('edit-photo-id').value = photo.id;
    document.getElementById('edit-photo-type').value = photo.photo_type;
    document.getElementById('edit-caption').value = photo.caption || '';
    document.getElementById('edit-description').value = photo.description || '';
    document.getElementById('edit-location').value = photo.location_on_object || '';
    document.getElementById('edit-photographer').value = photo.photographer || '';
    document.getElementById('edit-photo-date').value = photo.photo_date || '';

    $('#edit-photo-modal').modal('show');
}

document.getElementById('edit-photo-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);

    fetch(baseUrl + '&photo_action=edit&photo_id=' + formData.get('photo_id'), {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            location.reload();
        }
    });
});

function rotatePhoto(photoId, degrees) {
    fetch(baseUrl + '&photo_action=rotate&photo_id=' + photoId + '&degrees=' + degrees, {
        method: 'POST'
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            var img = document.querySelector('.photo-item[data-id="' + photoId + '"] img');
            if (img) {
                img.src = data.thumbnail_url;
            }
        }
    });
}

function setPrimary(photoId) {
    fetch(baseUrl + '&photo_action=set_primary&photo_id=' + photoId, {
        method: 'POST'
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            location.reload();
        }
    });
}

function deletePhoto(photoId) {
    if (!confirm('<?php echo __('Are you sure you want to delete this photo?'); ?>')) {
        return;
    }

    fetch(baseUrl + '&photo_action=delete&photo_id=' + photoId, {
        method: 'POST'
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            var item = document.querySelector('.photo-item[data-id="' + photoId + '"]');
            if (item) {
                item.remove();
            }
        }
    });
}
</script>

<!-- Annotation Modal -->
<div class="modal fade" id="annotation-modal" tabindex="-1" aria-labelledby="annotation-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="annotation-modal-label">
                    <i class="fas fa-draw-polygon me-2"></i>
                    <?php echo __('Annotate Photo'); ?>
                </h5>
                <div class="ms-auto me-3">
                    <button type="button" class="btn btn-light btn-sm me-2" id="save-annotations-btn">
                        <i class="fas fa-save"></i> <?php echo __('Save'); ?>
                    </button>
                    <button type="button" class="btn btn-success btn-sm me-2" id="export-annotations-btn">
                        <i class="fas fa-download"></i> <?php echo __('Export'); ?>
                    </button>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: calc(100vh - 120px);">
                <div id="annotator-container" style="height: 100%;"></div>
            </div>
            <div class="modal-footer">
                <span id="annotation-status" class="text-muted me-auto"></span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
// Annotation functionality
var currentAnnotator = null;
var currentPhotoId = null;

function openAnnotator(photoId) {
    currentPhotoId = photoId;
    var photo = photos.find(function(p) { return p.id == photoId; });
    if (!photo) {
        console.error('Photo not found:', photoId);
        return;
    }
    
    // Update modal title
    document.getElementById('annotation-modal-label').innerHTML = 
        '<i class="fas fa-draw-polygon me-2"></i> Annotate: ' + (photo.caption || photo.original_filename);
    
    // Show modal first
    var modal = new bootstrap.Modal(document.getElementById('annotation-modal'));
    modal.show();
    
    // Initialize annotator after modal is shown
    document.getElementById('annotation-modal').addEventListener('shown.bs.modal', function handler() {
        this.removeEventListener('shown.bs.modal', handler);
        initAnnotator(photo);
    });
}

function initAnnotator(photo) {
    // Destroy previous instance
    if (currentAnnotator) {
        currentAnnotator.destroy();
    }
    
    var imageUrl = photo.file_path;
    
    // Check if Fabric is loaded
    if (typeof fabric === 'undefined') {
        console.error('Fabric.js not loaded!');
        document.getElementById('annotator-container').innerHTML = 
            '<div class="alert alert-danger m-4">Error: Fabric.js library not loaded. Please refresh the page.</div>';
        return;
    }
    
    // Check if ConditionAnnotator is loaded
    if (typeof ConditionAnnotator === 'undefined') {
        console.error('ConditionAnnotator not loaded!');
        document.getElementById('annotator-container').innerHTML = 
            '<div class="alert alert-danger m-4">Error: Annotation library not loaded. Please refresh the page.</div>';
        return;
    }
    
    try {
        currentAnnotator = new ConditionAnnotator('annotator-container', {
            photoId: photo.id,
            imageUrl: imageUrl,
            readonly: false,
            showToolbar: true,
            saveUrl: '<?php echo url_for(["module" => "spectrum", "action" => "saveAnnotations"]); ?>',
            getUrl: '<?php echo url_for(["module" => "spectrum", "action" => "getAnnotations"]); ?>'
        });
        
        document.getElementById('annotation-status').textContent = 'Annotator initialized';
    } catch (e) {
        console.error('Failed to initialize annotator:', e);
        document.getElementById('annotator-container').innerHTML = 
            '<div class="alert alert-danger m-4">Error initializing annotator: ' + e.message + '</div>';
    }
}

// Save button handler
document.getElementById('save-annotations-btn').addEventListener('click', function() {
    if (currentAnnotator) {
        currentAnnotator.save().then(function() {
            document.getElementById('annotation-status').textContent = 'Annotations saved!';
            // Update photo card to show annotation indicator
            var photoItem = document.querySelector('.photo-item[data-id="' + currentPhotoId + '"]');
            if (photoItem && !photoItem.querySelector('.annotation-badge')) {
                var badge = document.createElement('span');
                badge.className = 'badge bg-info annotation-badge position-absolute';
                badge.style.cssText = 'top: 5px; right: 5px;';
                badge.innerHTML = '<i class="fas fa-draw-polygon"></i>';
                photoItem.querySelector('.photo-image').appendChild(badge);
            }
        }).catch(function(e) {
            document.getElementById('annotation-status').textContent = 'Save failed: ' + e;
        });
    }
});

// Export button handler
document.getElementById('export-annotations-btn').addEventListener('click', function() {
    if (currentPhotoId) {
        window.open('<?php echo url_for(["module" => "spectrum", "action" => "exportAnnotatedPhoto"]); ?>?photo_id=' + currentPhotoId + '&format=png', '_blank');
    }
});

// Cleanup when modal closes
document.getElementById('annotation-modal').addEventListener('hidden.bs.modal', function() {
    if (currentAnnotator) {
        // Prompt to save if dirty
        if (currentAnnotator.isDirty && currentAnnotator.isDirty()) {
            if (confirm('You have unsaved annotations. Save before closing?')) {
                currentAnnotator.save();
            }
        }
        currentAnnotator.destroy();
        currentAnnotator = null;
    }
});
</script>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
/* Annotation badge on photos */
.photo-item .annotation-badge {
    z-index: 10;
}

/* Annotator modal fullscreen adjustments */
#annotation-modal .modal-body {
    overflow: hidden;
}

#annotator-container {
    background: #1a1a1a;
}

/* Tool buttons in annotator */
.annotator-toolbar .tool-btn.active {
    background-color: var(--ahg-primary, #005837) !important;
    border-color: var(--ahg-primary, #005837) !important;
    color: white !important;
}
</style>
