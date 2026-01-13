<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
<div class="d-flex align-items-center mb-3">
  <i class="fas fa-paper-plane fa-2x text-primary me-3"></i>
  <div>
    <h1 class="h3 mb-0"><?php echo __('Review Publication Request') ?></h1>
    <p class="text-muted mb-0">
      <?php echo __('Submitted') ?>: <?php echo date('d M Y H:i', strtotime($resource->created_at)) ?>
    </p>
  </div>
</div>
<?php end_slot() ?>

<?php slot('content') ?>

<?php if ($sf_user->hasFlash('notice') && $sf_user->getFlash('notice')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $sf_user->getFlash('notice') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('error') && $sf_user->getFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $sf_user->getFlash('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<form method="post" action="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'edit', 'slug' => $resource->slug]) ?>">

<div class="row">
  <div class="col-lg-8">
    <!-- Request Details -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="fas fa-info-circle me-2"></i><?php echo __('Request Information') ?>
        </h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label text-muted small"><?php echo __('Requester Name') ?></label>
            <p class="fw-semibold mb-0">
              <?php echo esc_entities(($resource->rtp_name ?? '') . ' ' . ($resource->rtp_surname ?? '')) ?>
            </p>
          </div>
          <div class="col-md-6">
            <label class="form-label text-muted small"><?php echo __('Institution') ?></label>
            <p class="fw-semibold mb-0">
              <?php echo !empty($resource->rtp_institution) ? esc_entities($resource->rtp_institution) : '-' ?>
            </p>
          </div>
          <div class="col-md-6">
            <label class="form-label text-muted small"><?php echo __('Email') ?></label>
            <p class="mb-0">
              <?php if (!empty($resource->rtp_email)): ?>
                <a href="mailto:<?php echo esc_entities($resource->rtp_email) ?>">
                  <i class="fas fa-envelope me-1"></i><?php echo esc_entities($resource->rtp_email) ?>
                </a>
              <?php else: ?>-<?php endif; ?>
            </p>
          </div>
          <div class="col-md-6">
            <label class="form-label text-muted small"><?php echo __('Phone') ?></label>
            <p class="mb-0">
              <?php if (!empty($resource->rtp_phone)): ?>
                <a href="tel:<?php echo esc_entities($resource->rtp_phone) ?>">
                  <i class="fas fa-phone me-1"></i><?php echo esc_entities($resource->rtp_phone) ?>
                </a>
              <?php else: ?>-<?php endif; ?>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Archival Item -->
    <?php if (isset($resource->object) && $resource->object): ?>
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="fas fa-file-alt me-2"></i><?php echo __('Requested Item') ?>
        </h5>
      </div>
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="flex-grow-1">
            <h6 class="mb-1">
              <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $resource->object->slug]) ?>">
                <?php echo esc_entities($resource->object->title ?? $resource->object->slug) ?>
              </a>
            </h6>
            <?php if (!empty($resource->object->identifier)): ?>
              <small class="text-muted"><?php echo __('Identifier') ?>: <?php echo esc_entities($resource->object->identifier) ?></small>
            <?php endif; ?>
          </div>
          <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $resource->object->slug]) ?>" 
             class="btn btn-sm btn-outline-primary" target="_blank">
            <i class="fas fa-external-link-alt me-1"></i><?php echo __('View Item') ?>
          </a>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Planned Use -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="fas fa-clipboard-list me-2"></i><?php echo __('Request Details') ?>
        </h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label text-muted small"><?php echo __('Planned Use') ?></label>
          <p class="mb-0"><?php echo !empty($resource->rtp_planned_use) ? nl2br(esc_entities($resource->rtp_planned_use)) : '-' ?></p>
        </div>
        <?php if (!empty($resource->rtp_motivation)): ?>
        <div class="mb-3">
          <label class="form-label text-muted small"><?php echo __('Motivation') ?></label>
          <p class="mb-0"><?php echo nl2br(esc_entities($resource->rtp_motivation)) ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($resource->rtp_need_image_by)): ?>
        <div>
          <label class="form-label text-muted small"><?php echo __('Need Image By') ?></label>
          <p class="mb-0">
            <span class="badge bg-info text-dark">
              <i class="fas fa-calendar me-1"></i><?php echo date('d M Y', strtotime($resource->rtp_need_image_by)) ?>
            </span>
          </p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Admin Response -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">
          <i class="fas fa-reply me-2"></i><?php echo __('Admin Response') ?>
        </h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label for="rtp_admin_notes" class="form-label fw-semibold"><?php echo __('Admin Notes') ?></label>
          <textarea name="rtp_admin_notes" id="rtp_admin_notes" class="form-control" rows="4" 
                    placeholder="<?php echo __('Add notes for internal reference or to communicate with the requester...') ?>"><?php echo esc_entities($resource->rtp_admin_notes ?? '') ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <!-- Status Card -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="fas fa-flag me-2"></i><?php echo __('Status') ?>
        </h5>
      </div>
      <div class="card-body text-center">
        <span class="badge <?php echo $repository->getStatusBadgeClass($resource->status_id) ?> fs-5 px-4 py-2">
          <?php echo __($repository->getStatusLabel($resource->status_id)) ?>
        </span>
        
        <?php if ($resource->status_id == 220): ?>
        <!-- Pending - Show action buttons -->
        <hr>
        <p class="text-muted small mb-3"><?php echo __('Take action on this request:') ?></p>
        <div class="d-grid gap-2">
          <button type="submit" name="action_type" value="approve" class="btn btn-success">
            <i class="fas fa-check me-1"></i><?php echo __('Approve Request') ?>
          </button>
          <button type="submit" name="action_type" value="reject" class="btn btn-danger">
            <i class="fas fa-times me-1"></i><?php echo __('Reject Request') ?>
          </button>
        </div>
        <?php elseif (!empty($resource->completed_at)): ?>
        <hr>
        <p class="text-muted small mb-0">
          <?php echo __('Completed on') ?>:<br>
          <strong><?php echo date('d M Y H:i', strtotime($resource->completed_at)) ?></strong>
        </p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Actions -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="fas fa-cogs me-2"></i><?php echo __('Actions') ?>
        </h5>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <button type="submit" name="action_type" value="save" class="btn btn-primary">
            <i class="fas fa-save me-1"></i><?php echo __('Save Notes') ?>
          </button>
          <a href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'browse']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to List') ?>
          </a>
          <hr>
          <a href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'delete', 'slug' => $resource->slug]) ?>" 
             class="btn btn-outline-danger"
             onclick="return confirm('<?php echo __('Are you sure you want to delete this request?') ?>');">
            <i class="fas fa-trash me-1"></i><?php echo __('Delete Request') ?>
          </a>
        </div>
      </div>
    </div>

    <!-- Timeline -->
    <div class="card shadow-sm">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="fas fa-history me-2"></i><?php echo __('Timeline') ?>
        </h5>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          <li class="mb-2">
            <i class="fas fa-plus-circle text-primary me-2"></i>
            <small class="text-muted"><?php echo __('Submitted') ?></small><br>
            <strong><?php echo date('d M Y H:i', strtotime($resource->created_at)) ?></strong>
          </li>
          <?php if (!empty($resource->completed_at)): ?>
          <li>
            <i class="fas fa-check-circle text-success me-2"></i>
            <small class="text-muted"><?php echo __('Completed') ?></small><br>
            <strong><?php echo date('d M Y H:i', strtotime($resource->completed_at)) ?></strong>
          </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

</form>

<?php end_slot() ?>
