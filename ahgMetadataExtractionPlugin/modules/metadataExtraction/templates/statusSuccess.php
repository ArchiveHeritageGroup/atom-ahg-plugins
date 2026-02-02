<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
  <h1><?php echo __('Metadata Extraction Status'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><?php echo __('System Status'); ?></h5>
    <a href="<?php echo url_for(['module' => 'metadataExtraction', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i><?php echo __('Back'); ?>
    </a>
  </div>
  <div class="card-body">

    <!-- ExifTool Status -->
    <div class="row mb-4">
      <div class="col-md-6">
        <h6><?php echo __('ExifTool Status'); ?></h6>
        <table class="table table-sm">
          <tr>
            <th class="w-50"><?php echo __('Installed'); ?></th>
            <td>
              <?php if ($exifToolAvailable): ?>
                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i><?php echo __('Yes'); ?></span>
              <?php else: ?>
                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i><?php echo __('No'); ?></span>
              <?php endif ?>
            </td>
          </tr>
          <?php if ($exifToolAvailable): ?>
            <tr>
              <th><?php echo __('Version'); ?></th>
              <td><code><?php echo htmlspecialchars($exifToolVersion) ?></code></td>
            </tr>
          <?php endif ?>
        </table>

        <?php if (!$exifToolAvailable): ?>
          <div class="alert alert-warning">
            <h6><?php echo __('Installation Instructions'); ?></h6>
            <p class="mb-2"><?php echo __('ExifTool is required for metadata extraction. Install it with:'); ?></p>
            <code>sudo apt install exiftool</code>
            <p class="mt-2 mb-0 small text-muted"><?php echo __('For other operating systems, visit: https://exiftool.org/install.html'); ?></p>
          </div>
        <?php endif ?>
      </div>

      <div class="col-md-6">
        <h6><?php echo __('Extraction Statistics'); ?></h6>
        <table class="table table-sm">
          <tr>
            <th class="w-50"><?php echo __('Total Digital Objects'); ?></th>
            <td><strong><?php echo number_format($totalDigitalObjects) ?></strong></td>
          </tr>
          <tr>
            <th><?php echo __('Objects with Metadata'); ?></th>
            <td>
              <strong><?php echo number_format($objectsWithMetadata) ?></strong>
              <?php if ($totalDigitalObjects > 0): ?>
                <small class="text-muted">(<?php echo round($objectsWithMetadata / $totalDigitalObjects * 100, 1) ?>%)</small>
              <?php endif ?>
            </td>
          </tr>
          <tr>
            <th><?php echo __('Total Metadata Fields'); ?></th>
            <td><strong><?php echo number_format($totalMetadataFields) ?></strong></td>
          </tr>
          <tr>
            <th><?php echo __('Average Fields per Object'); ?></th>
            <td>
              <strong>
                <?php echo $objectsWithMetadata > 0 ? round($totalMetadataFields / $objectsWithMetadata, 1) : 0 ?>
              </strong>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <hr>

    <!-- MIME Type Breakdown -->
    <h6><?php echo __('MIME Type Breakdown'); ?></h6>
    <p class="text-muted small"><?php echo __('Top 10 file types in your repository'); ?></p>

    <?php if (count($mimeTypeBreakdown) > 0): ?>
      <div class="table-responsive">
        <table class="table table-sm table-striped">
          <thead>
            <tr>
              <th><?php echo __('MIME Type'); ?></th>
              <th><?php echo __('Count'); ?></th>
              <th><?php echo __('Supported'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php
            $supportedTypes = [
                'image/jpeg', 'image/png', 'image/tiff', 'image/gif', 'image/bmp', 'image/webp',
                'application/pdf', 'video/mp4', 'video/avi', 'audio/mpeg',
            ];
            ?>
            <?php foreach ($mimeTypeBreakdown as $item): ?>
              <tr>
                <td><code><?php echo htmlspecialchars($item->mime_type) ?></code></td>
                <td><?php echo number_format($item->count) ?></td>
                <td>
                  <?php if (in_array($item->mime_type, $supportedTypes)): ?>
                    <span class="badge bg-success"><?php echo __('Yes'); ?></span>
                  <?php else: ?>
                    <span class="badge bg-secondary"><?php echo __('Limited'); ?></span>
                  <?php endif ?>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info">
        <?php echo __('No digital objects found in the repository.'); ?>
      </div>
    <?php endif ?>

    <hr>

    <!-- Supported Formats -->
    <h6><?php echo __('Supported Formats'); ?></h6>
    <p class="text-muted small"><?php echo __('ExifTool can extract metadata from the following file types:'); ?></p>

    <div class="row">
      <div class="col-md-3">
        <h6 class="text-muted small"><?php echo __('Images'); ?></h6>
        <ul class="small">
          <li>JPEG/JPG</li>
          <li>PNG</li>
          <li>TIFF</li>
          <li>GIF</li>
          <li>BMP</li>
          <li>WebP</li>
          <li>RAW formats</li>
        </ul>
      </div>
      <div class="col-md-3">
        <h6 class="text-muted small"><?php echo __('Documents'); ?></h6>
        <ul class="small">
          <li>PDF</li>
          <li>Office documents</li>
          <li>OpenDocument</li>
        </ul>
      </div>
      <div class="col-md-3">
        <h6 class="text-muted small"><?php echo __('Video'); ?></h6>
        <ul class="small">
          <li>MP4</li>
          <li>AVI</li>
          <li>MOV</li>
          <li>MKV</li>
        </ul>
      </div>
      <div class="col-md-3">
        <h6 class="text-muted small"><?php echo __('Audio'); ?></h6>
        <ul class="small">
          <li>MP3</li>
          <li>WAV</li>
          <li>FLAC</li>
          <li>OGG</li>
        </ul>
      </div>
    </div>

  </div>
</div>

<?php end_slot() ?>
