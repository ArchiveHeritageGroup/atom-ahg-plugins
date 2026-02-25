<?php
  $fileIcons = [
    'image' => 'fas fa-file-image text-success',
    'document' => 'fas fa-file-alt text-primary',
    'pdf' => 'fas fa-file-pdf text-danger',
    'spreadsheet' => 'fas fa-file-excel text-success',
    'presentation' => 'fas fa-file-powerpoint text-warning',
    'archive' => 'fas fa-file-archive text-secondary',
    'video' => 'fas fa-file-video text-info',
    'audio' => 'fas fa-file-audio text-purple',
    'code' => 'fas fa-file-code text-dark',
  ];

  /**
   * Determine file type icon based on extension or mime type
   */
  function _registryFileIcon($attachment, $fileIcons) {
    $ext = strtolower(pathinfo($attachment->file_name ?? $attachment->filename ?? '', PATHINFO_EXTENSION));
    $mime = $attachment->mime_type ?? '';

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'tiff']) || stripos($mime, 'image/') === 0) {
      return $fileIcons['image'];
    }
    if (in_array($ext, ['pdf']) || stripos($mime, 'pdf') !== false) {
      return $fileIcons['pdf'];
    }
    if (in_array($ext, ['xls', 'xlsx', 'csv', 'ods'])) {
      return $fileIcons['spreadsheet'];
    }
    if (in_array($ext, ['ppt', 'pptx', 'odp'])) {
      return $fileIcons['presentation'];
    }
    if (in_array($ext, ['zip', 'tar', 'gz', 'rar', '7z'])) {
      return $fileIcons['archive'];
    }
    if (in_array($ext, ['mp4', 'avi', 'mov', 'mkv', 'webm']) || stripos($mime, 'video/') === 0) {
      return $fileIcons['video'];
    }
    if (in_array($ext, ['mp3', 'wav', 'ogg', 'flac', 'aac']) || stripos($mime, 'audio/') === 0) {
      return $fileIcons['audio'];
    }
    if (in_array($ext, ['py', 'php', 'js', 'html', 'css', 'json', 'xml'])) {
      return $fileIcons['code'];
    }
    return $fileIcons['document'];
  }

  /**
   * Format file size in human-readable format
   */
  function _registryFormatSize($bytes) {
    $bytes = (int) $bytes;
    if ($bytes >= 1073741824) {
      return number_format($bytes / 1073741824, 1) . ' GB';
    }
    if ($bytes >= 1048576) {
      return number_format($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
      return number_format($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
  }
?>
<?php if (!empty($attachments)): ?>
<div class="list-group list-group-flush">
  <?php foreach ($attachments as $att): ?>
    <?php
      $icon = _registryFileIcon($att, $fileIcons);
      $fileName = $att->file_name ?? $att->filename ?? __('Unnamed file');
      $fileUrl = $att->file_url ?? $att->url ?? '#';
      $fileSize = $att->file_size ?? $att->size ?? 0;
      $downloads = (int) ($att->download_count ?? 0);
    ?>
    <div class="list-group-item d-flex align-items-center py-2">
      <i class="<?php echo $icon; ?> me-3 fa-lg flex-shrink-0"></i>
      <div class="flex-grow-1 min-width-0">
        <a href="<?php echo htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none small fw-semibold" target="_blank" rel="noopener">
          <?php echo htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8'); ?>
        </a>
        <br>
        <small class="text-muted">
          <?php if ($fileSize > 0): ?>
            <?php echo _registryFormatSize($fileSize); ?>
          <?php endif; ?>
          <?php if ($downloads > 0): ?>
            <span class="ms-2"><i class="fas fa-download me-1"></i><?php echo number_format($downloads); ?></span>
          <?php endif; ?>
        </small>
      </div>
      <a href="<?php echo htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-secondary flex-shrink-0" download title="<?php echo __('Download'); ?>">
        <i class="fas fa-download"></i>
      </a>
    </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<p class="text-muted small mb-0"><?php echo __('No attachments.'); ?></p>
<?php endif; ?>
