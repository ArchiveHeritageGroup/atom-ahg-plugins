<h1><?php echo __('Media Processing Test Result') ?></h1>

<div class="mb-4">
  <a href="<?php echo url_for(['module' => 'mediaSettings', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i>
    <?php echo __('Back to Settings') ?>
  </a>
</div>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0">
      <?php echo __('Digital Object ID:') ?> <?php echo $digitalObjectId ?>
    </h5>
  </div>
  <div class="card-body">
    <?php if ($result['success'] ?? false): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo __('Processing completed successfully!') ?>
      </div>
      
      <h6><?php echo __('Media Type') ?></h6>
      <p><?php echo ucfirst($result['media_type'] ?? 'Unknown') ?></p>
      
      <?php if (!empty($result['derivatives'])): ?>
        <h6><?php echo __('Generated Derivatives') ?></h6>
        <ul class="list-group mb-3">
          <?php foreach ($result['derivatives'] as $type => $data): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span><?php echo ucfirst($type) ?></span>
              <?php if (is_array($data) && isset($data[0])): ?>
                <span class="badge bg-primary"><?php echo count($data) ?> items</span>
              <?php elseif (is_array($data) && isset($data['path'])): ?>
                <a href="<?php echo $data['path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                  <?php echo __('View') ?>
                </a>
              <?php elseif (is_string($data)): ?>
                <a href="<?php echo $data ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                  <?php echo __('View') ?>
                </a>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      
      <?php if ($result['metadata'] ?? false): ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          <?php echo __('Metadata extracted successfully') ?>
        </div>
      <?php endif; ?>
      
    <?php else: ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo __('Processing failed') ?>
      </div>
      
      <?php if (isset($result['error'])): ?>
        <h6><?php echo __('Error') ?></h6>
        <pre class="bg-light p-3 rounded"><?php echo htmlspecialchars($result['error']) ?></pre>
      <?php endif; ?>
      
      <?php if (isset($result['reason'])): ?>
        <h6><?php echo __('Reason') ?></h6>
        <p><?php echo htmlspecialchars($result['reason']) ?></p>
      <?php endif; ?>
    <?php endif; ?>
    
    <h6 class="mt-4"><?php echo __('Full Result') ?></h6>
    <pre class="bg-light p-3 rounded" style="max-height:400px;overflow:auto;"><?php echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) ?></pre>
  </div>
</div>
