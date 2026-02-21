<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header bg-success text-white py-2">
            <h6 class="mb-0"><i class="fas fa-camera me-1"></i><?php echo __('New Assessment') ?></h6>
        </div>
        <div class="card-body py-2 small">
            <p class="text-muted mb-2"><?php echo __('Upload an image or select an archival object to analyze for damage.') ?></p>
            <div id="progressArea" style="display:none">
                <div class="progress mb-2" style="height:4px">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="progressBar" style="width:0%"></div>
                </div>
                <p class="text-center small text-muted" id="progressText"><?php echo __('Analyzing...') ?></p>
            </div>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1 class="h3 mb-0"><i class="fas fa-camera me-2"></i><?php echo __('AI Condition Assessment') ?></h1>
<p class="text-muted small mb-3"><?php echo __('Upload an image for AI-powered damage detection') ?></p>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form id="assessForm" enctype="multipart/form-data">
                    <!-- Object selector (optional) -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Link to Object (optional)') ?></label>
                        <input type="text" class="form-control form-control-sm" id="objectSearch" placeholder="<?php echo __('Search by title...') ?>" autocomplete="off">
                        <input type="hidden" id="objectId" name="information_object_id" value="<?php echo esc_entities($objectId ?? '') ?>">
                        <div id="objectResults" class="list-group position-absolute" style="z-index:1000;display:none"></div>
                    </div>

                    <!-- Image upload -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Image') ?> <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" id="imageFile" name="image_file" accept="image/*" required>
                    </div>

                    <!-- Preview -->
                    <div class="mb-3 text-center" id="previewArea" style="display:none">
                        <img id="previewImg" src="" alt="Preview" class="img-fluid rounded border" style="max-height:300px">
                    </div>

                    <!-- Confidence slider -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Min Confidence') ?>: <span id="confValue">25%</span></label>
                        <input type="range" class="form-range" id="confidence" name="confidence" min="10" max="90" value="25" step="5">
                    </div>

                    <button type="submit" class="btn btn-success w-100" id="submitBtn">
                        <i class="fas fa-search me-1"></i><?php echo __('Analyze Image') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <!-- Results panel (populated by JS) -->
        <div id="resultsPanel" style="display:none">
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-1"></i><?php echo __('Results') ?></h6>
                    <span id="resultScore"></span>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img id="overlayImg" src="" alt="Overlay" class="img-fluid rounded border" style="max-height:400px">
                    </div>
                    <div id="resultGrade" class="text-center mb-3"></div>
                    <h6><?php echo __('Damages Detected') ?></h6>
                    <div id="damageList"></div>
                    <h6 class="mt-3"><?php echo __('Recommendations') ?></h6>
                    <div id="recommendations" class="small text-muted"></div>
                    <div class="mt-3 d-flex gap-2">
                        <a id="viewDetailLink" href="#" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye me-1"></i><?php echo __('View Full Report') ?>
                        </a>
                        <button type="button" class="btn btn-sm btn-success" id="confirmBtn" style="display:none">
                            <i class="fas fa-check me-1"></i><?php echo __('Confirm Assessment') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
// Preview uploaded image
document.getElementById('imageFile').addEventListener('change', function() {
    var file = this.files[0];
    if (file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('previewArea').style.display = '';
        };
        reader.readAsDataURL(file);
    }
});

// Confidence slider
document.getElementById('confidence').addEventListener('input', function() {
    document.getElementById('confValue').textContent = this.value + '%';
});

// Object autocomplete
var searchTimer;
document.getElementById('objectSearch').addEventListener('input', function() {
    clearTimeout(searchTimer);
    var q = this.value.trim();
    if (q.length < 2) { document.getElementById('objectResults').style.display='none'; return; }
    searchTimer = setTimeout(function() {
        fetch('/index.php/object/autocomplete?query=' + encodeURIComponent(q))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var el = document.getElementById('objectResults');
            el.innerHTML = '';
            (data.results || []).forEach(function(item) {
                var a = document.createElement('a');
                a.href = '#';
                a.className = 'list-group-item list-group-item-action small py-1';
                a.textContent = item.title || item.label || item.name;
                a.dataset.id = item.id;
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('objectId').value = this.dataset.id;
                    document.getElementById('objectSearch').value = this.textContent;
                    el.style.display = 'none';
                });
                el.appendChild(a);
            });
            el.style.display = data.results && data.results.length ? '' : 'none';
        });
    }, 300);
});

// Submit assessment
document.getElementById('assessForm').addEventListener('submit', function(e) {
    e.preventDefault();

    var fileInput = document.getElementById('imageFile');
    if (!fileInput.files[0]) { alert('Please select an image'); return; }

    var formData = new FormData();
    formData.append('image_file', fileInput.files[0]);
    formData.append('information_object_id', document.getElementById('objectId').value);
    formData.append('confidence', document.getElementById('confidence').value / 100);

    document.getElementById('submitBtn').disabled = true;
    document.getElementById('progressArea').style.display = '';
    document.getElementById('progressBar').style.width = '30%';
    document.getElementById('progressText').textContent = 'Uploading and analyzing...';

    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiSubmit']) ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        document.getElementById('progressBar').style.width = '100%';
        document.getElementById('submitBtn').disabled = false;

        if (!data.success) {
            document.getElementById('progressText').textContent = 'Error: ' + (data.error || 'Unknown error');
            document.getElementById('progressBar').classList.add('bg-danger');
            return;
        }

        document.getElementById('progressArea').style.display = 'none';
        showResults(data);
    })
    .catch(function(err) {
        document.getElementById('progressText').textContent = 'Network error';
        document.getElementById('submitBtn').disabled = false;
    });
});

function showResults(data) {
    document.getElementById('resultsPanel').style.display = '';

    // Score
    var score = data.overall_score != null ? parseFloat(data.overall_score).toFixed(1) : '--';
    var scoreColor = score >= 80 ? 'success' : score >= 60 ? 'info' : score >= 40 ? 'warning' : 'danger';
    document.getElementById('resultScore').innerHTML = '<span class="badge bg-' + scoreColor + ' fs-6">' + score + '/100</span>';

    // Grade
    var grade = data.condition_grade || 'unknown';
    var gradeColors = {excellent:'success',good:'info',fair:'warning',poor:'danger',critical:'dark'};
    document.getElementById('resultGrade').innerHTML = '<span class="badge bg-' + (gradeColors[grade]||'secondary') + ' fs-5">' + grade.charAt(0).toUpperCase() + grade.slice(1) + '</span>';

    // Overlay image
    if (data.overlay_base64) {
        document.getElementById('overlayImg').src = 'data:image/jpeg;base64,' + data.overlay_base64;
    } else {
        document.getElementById('overlayImg').src = document.getElementById('previewImg').src;
    }

    // Damage list
    var damageHtml = '';
    (data.damages || []).forEach(function(d) {
        damageHtml += '<div class="d-flex justify-content-between align-items-center border-bottom py-1">';
        damageHtml += '<span><span class="badge" style="background-color:' + (d.color || '#6c757d') + '">' + (d.damage_type || 'unknown').replace('_',' ') + '</span></span>';
        damageHtml += '<span class="small text-muted">' + Math.round((d.confidence || 0) * 100) + '% conf</span>';
        damageHtml += '</div>';
    });
    document.getElementById('damageList').innerHTML = damageHtml || '<p class="text-success small">No damage detected</p>';

    // Recommendations
    var recs = data.recommendations;
    if (Array.isArray(recs)) {
        recs = recs.join('<br>');
    }
    document.getElementById('recommendations').innerHTML = recs || 'No specific recommendations.';

    // Links
    if (data.assessment_id) {
        document.getElementById('viewDetailLink').href = '<?php echo url_for(['module' => 'aiCondition', 'action' => 'view', 'id' => '']) ?>' + data.assessment_id;
        document.getElementById('confirmBtn').style.display = '';
        document.getElementById('confirmBtn').onclick = function() {
            fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiConfirm']) ?>?id=' + data.assessment_id, {method:'POST'})
            .then(function(r){return r.json()})
            .then(function(d){
                if(d.success){
                    this.textContent='Confirmed';
                    this.disabled=true;
                    this.classList.replace('btn-success','btn-secondary');
                }
            }.bind(this));
        };
    }
}
</script>
<?php end_slot() ?>
