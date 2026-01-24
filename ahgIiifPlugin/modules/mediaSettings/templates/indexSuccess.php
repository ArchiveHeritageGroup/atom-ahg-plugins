<?php use_helper('Text') ?>
<?php use_stylesheet("/plugins/ahgCorePlugin/web/css/vendor/tom-select.bootstrap5.min.css") ?>
<?php use_javascript("/plugins/ahgCorePlugin/web/js/vendor/tom-select.complete.min.js") ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="mb-0"><?php echo __('Media Processing Settings') ?></h1>
  <a href="<?php echo url_for(['module' => 'settings', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to AHG Settings') ?>
  </a>
</div>

<div class="row mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><?php echo __('System Tools') ?></h5>
      </div>
      <div class="card-body">
        <div class="row">
          <?php foreach ($tools as $tool => $available): ?>
            <div class="col-md-2 col-sm-4 col-6 mb-2">
              <div class="d-flex align-items-center">
                <?php if ($available): ?>
                  <span class="badge bg-success me-2">✓</span>
                <?php else: ?>
                  <span class="badge bg-danger me-2">✗</span>
                <?php endif; ?>
                <span><?php echo ucfirst($tool) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if (!$tools['ffmpeg'] || !$tools['ffprobe']): ?>
          <div class="alert alert-warning mt-3 mb-0">
            <strong><?php echo __('Warning:') ?></strong>
            <?php echo __('FFmpeg/FFprobe are required for media processing. Install with: sudo apt install ffmpeg') ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<form method="post" action="<?php echo url_for(['module' => 'mediaSettings', 'action' => 'save']) ?>">

<?php 
$groupLabels = [
    'thumbnail' => __('Thumbnail Generation'),
    'preview' => __('Preview Clips'),
    'waveform' => __('Audio Waveform'),
    'poster' => __('Video Posters'),
    'audio' => __('Audio Processing'),
    'transcription' => __('Speech Transcription'),
];
?>

<?php foreach ($grouped as $group => $groupSettings): ?>
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><?php echo $groupLabels[$group] ?? ucfirst($group) ?></h5>
    </div>
    <div class="card-body">
      <?php foreach ($groupSettings as $key => $setting): ?>
        <div class="row mb-3 align-items-center">
          <label class="col-md-4 col-form-label">
            <?php echo $setting['description'] ?? $key ?>
          </label>
          <div class="col-md-8">
            <?php if ($setting['type'] === 'boolean'): ?>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" 
                       name="settings[<?php echo $key ?>]" value="1"
                       id="setting_<?php echo $key ?>"
                       <?php echo $setting['value'] ? 'checked' : '' ?>>
              </div>
            <?php elseif ($setting['type'] === 'integer' || $setting['type'] === 'float'): ?>
              <input type="number" class="form-control" 
                     name="settings[<?php echo $key ?>]"
                     value="<?php echo $setting['value'] ?>"
                     <?php echo $setting['type'] === 'float' ? 'step="0.1"' : '' ?>>
            <?php elseif ($setting['type'] === 'json'): ?>
              <input type="text" class="form-control font-monospace" 
                     name="settings[<?php echo $key ?>]"
                     value="<?php echo htmlspecialchars(json_encode($setting['value'])) ?>">
              <small class="text-muted"><?php echo __('JSON format') ?></small>
            <?php else: ?>
              <input type="text" class="form-control" 
                     name="settings[<?php echo $key ?>]"
                     value="<?php echo htmlspecialchars($setting['value']) ?>">
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>

<div class="actions mb-4">
  <button type="submit" class="btn btn-primary">
    <i class="fas fa-save me-1"></i>
    <?php echo __('Save Settings') ?>
  </button>
  <a href="<?php echo url_for(['module' => 'mediaSettings', 'action' => 'queue']) ?>" class="btn btn-outline-secondary">
    <i class="fas fa-list me-1"></i>
    <?php echo __('View Queue') ?>
  </a>
</div>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><?php echo __('Test Processing') ?></h5>
  </div>
  <div class="card-body">
    <p class="text-muted"><?php echo __('Test media processing on a specific digital object.') ?></p>
    <form method="get" action="<?php echo url_for(['module' => 'mediaSettings', 'action' => 'test']) ?>" class="row g-2 align-items-end" id="media-test-form">
      <div class="col-md-6">
        <label class="form-label"><?php echo __('Search Archival Description') ?></label>
        <div class="position-relative">
          <input type="text" id="media-search-input" class="form-control" placeholder="<?php echo __('Type to search (min 2 characters)...') ?>" autocomplete="off">
          <input type="hidden" name="slug" id="media-search-slug">
          <div id="media-search-results" class="position-absolute w-100 bg-white border rounded shadow-sm" style="display:none; z-index:1000; max-height:300px; overflow-y:auto;"></div>
        </div>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary" id="media-test-btn" disabled>
          <i class="fas fa-play me-1"></i>
          <?php echo __('Test') ?>
        </button>
      </div>
    </form>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
    var input = document.getElementById('media-search-input');
    var slugInput = document.getElementById('media-search-slug');
    var results = document.getElementById('media-search-results');
    var btn = document.getElementById('media-test-btn');
    var timer = null;
    
    input.addEventListener('input', function() {
        var query = this.value.trim();
        clearTimeout(timer);
        
        if (query.length < 2) {
            results.style.display = 'none';
            slugInput.value = '';
            btn.disabled = true;
            return;
        }
        
        timer = setTimeout(function() {
            fetch('<?php echo url_for(['module' => 'mediaSettings', 'action' => 'autocomplete']) ?>?query=' + encodeURIComponent(query))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.length === 0) {
                        results.innerHTML = '<div class="p-2 text-muted">No results found</div>';
                    } else {
                        var html = '';
                        data.forEach(function(item) {
                            html += '<div class="p-2 border-bottom media-result-item" style="cursor:pointer" data-slug="' + item.slug + '">';
                            html += '<strong>' + (item.title || '(Untitled)') + '</strong>';
                            if (item.identifier) html += ' <span class="text-muted">(' + item.identifier + ')</span>';
                            html += '</div>';
                        });
                        results.innerHTML = html;
                    }
                    results.style.display = 'block';
                })
                .catch(function() {
                    results.innerHTML = '<div class="p-2 text-danger">Error searching</div>';
                    results.style.display = 'block';
                });
        }, 300);
    });
    
    results.addEventListener('click', function(e) {
        var item = e.target.closest('.media-result-item');
        if (item) {
            input.value = item.querySelector('strong').textContent;
            slugInput.value = item.dataset.slug;
            results.style.display = 'none';
            btn.disabled = false;
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#media-search-input') && !e.target.closest('#media-search-results')) {
            results.style.display = 'none';
        }
    });
})();
</script>
