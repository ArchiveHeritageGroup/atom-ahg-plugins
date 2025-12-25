<?php use_helper('Text') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="mb-0"><?php echo __('Media Processing Settings') ?></h1>
  <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgSettings']) ?>" class="btn btn-outline-secondary">
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

<form method="post" action="<?php echo url_for(['module' => 'arMediaSettings', 'action' => 'save']) ?>">

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
  <a href="<?php echo url_for(['module' => 'arMediaSettings', 'action' => 'queue']) ?>" class="btn btn-outline-secondary">
    <i class="fas fa-list me-1"></i>
    <?php echo __('View Queue') ?>
  </a>
</div>

</form>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><?php echo __('Test Processing') ?></h5>
  </div>
  <div class="card-body">
    <p class="text-muted"><?php echo __('Test media processing on a specific digital object.') ?></p>
    <form method="get" action="<?php echo url_for(['module' => 'arMediaSettings', 'action' => 'test']) ?>" class="row g-2">
      <div class="col-auto">
        <input type="number" name="id" class="form-control" placeholder="<?php echo __('Digital Object ID') ?>" required>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary">
          <i class="fas fa-play me-1"></i>
          <?php echo __('Test') ?>
        </button>
      </div>
    </form>
  </div>
</div>
