<?php
$doId = (int) $sf_data->getRaw('doId');
$r = $sf_data->getRaw('record');
$do = $sf_data->getRaw('digitalObject');
$postUrl = '/index.php/media/audio-description/' . $doId . '/edit';
$sample = "WEBVTT\n\n00:00:00.000 --> 00:00:05.000\nWide shot of the archive reading room; a researcher sits at a long oak table.\n\n00:00:05.000 --> 00:00:09.000\nClose-up of a leather-bound ledger being opened.";
?>
<div class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><i class="fas fa-audio-description me-2"></i><?php echo __('Audio description'); ?></h1>
  </div>
  <p class="text-muted">
    <?php echo __('Author a WebVTT audio-description track for this video. It is exposed to assistive technology as a'); ?>
    <code>kind="descriptions"</code> <?php echo __('track on the player.'); ?>
    <?php if ($do && !empty($do->name)): ?><br><strong><?php echo htmlspecialchars((string) $do->name); ?></strong> (DO #<?php echo $doId; ?>)<?php endif; ?>
  </p>

  <form method="post" action="<?php echo $postUrl; ?>">
    <div class="row">
      <div class="col-md-3 mb-3"><label class="form-label"><?php echo __('Language'); ?></label>
        <input class="form-control" name="language" value="<?php echo htmlspecialchars((string) ($r->language ?? 'en')); ?>"></div>
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Label'); ?></label>
        <input class="form-control" name="label" value="<?php echo htmlspecialchars((string) ($r->label ?? 'Audio description')); ?>"></div>
    </div>
    <div class="mb-3">
      <label class="form-label"><?php echo __('WebVTT content'); ?></label>
      <textarea class="form-control font-monospace" name="vtt_content" rows="16" placeholder="<?php echo htmlspecialchars($sample); ?>"><?php echo htmlspecialchars((string) ($r->vtt_content ?? '')); ?></textarea>
      <div class="form-text"><?php echo __('Must be valid WebVTT. "WEBVTT" header is added automatically if omitted.'); ?></div>
    </div>
    <button class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save'); ?></button>
    <?php if ($r): ?>
      <a class="btn btn-outline-secondary" target="_blank" href="/index.php/media/audio-description/<?php echo $doId; ?>"><?php echo __('Preview .vtt'); ?></a>
    <?php endif; ?>
  </form>
</div>
