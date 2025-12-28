<?php
/**
 * Review Access Request Template.
 */
?>

<h1><i class="fas fa-search"></i> <?php echo __('Review Access Request') ?> #<?php echo $accessRequest->id ?></h1>

<div class="mb-3">
  <a href="/security/requests" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left"></i> <?php echo __('Back to Pending Requests') ?>
  </a>
</div>

<div class="row">
  <div class="col-md-8">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><?php echo __('Request Details') ?></h5>
      </div>
      <div class="card-body">
        <dl class="row">
          <dt class="col-sm-3"><?php echo __('Requester') ?></dt>
          <dd class="col-sm-9">
            <strong><?php echo esc_entities($accessRequest->username) ?></strong><br>
            <small><?php echo esc_entities($accessRequest->email) ?></small>
          </dd>

          <dt class="col-sm-3"><?php echo __('Resource') ?></dt>
          <dd class="col-sm-9">
            <?php echo esc_entities($accessRequest->object_title ?? 'N/A') ?>
          </dd>

          <dt class="col-sm-3"><?php echo __('Request Type') ?></dt>
          <dd class="col-sm-9">
            <?php echo ucfirst(str_replace('_', ' ', $accessRequest->request_type)) ?>
          </dd>

          <dt class="col-sm-3"><?php echo __('Priority') ?></dt>
          <dd class="col-sm-9">
            <span class="badge bg-<?php echo 'immediate' === $accessRequest->priority ? 'danger' : ('urgent' === $accessRequest->priority ? 'warning' : 'secondary') ?>">
              <?php echo ucfirst($accessRequest->priority) ?>
            </span>
          </dd>

          <dt class="col-sm-3"><?php echo __('Duration Requested') ?></dt>
          <dd class="col-sm-9"><?php echo $accessRequest->duration_hours ?> <?php echo __('hours') ?></dd>

          <dt class="col-sm-3"><?php echo __('Submitted') ?></dt>
          <dd class="col-sm-9"><?php echo date('Y-m-d H:i:s', strtotime($accessRequest->created_at)) ?></dd>
        </dl>

        <hr>

        <h6><?php echo __('Justification') ?></h6>
        <div class="bg-light p-3 rounded">
          <?php echo nl2br(esc_entities($accessRequest->justification)) ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><?php echo __('Review Decision') ?></h5>
      </div>
      <div class="card-body">
        <form method="post" action="">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Access Duration (hours)') ?></label>
            <input type="number" name="duration_hours" class="form-control" 
                   value="<?php echo $accessRequest->duration_hours ?>" min="1" max="720">
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Review Notes') ?></label>
            <textarea name="notes" class="form-control" rows="3"></textarea>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" name="action" value="approve" class="btn btn-success btn-lg">
              <i class="fas fa-check"></i> <?php echo __('Approve') ?>
            </button>
            <button type="submit" name="action" value="deny" class="btn btn-danger">
              <i class="fas fa-times"></i> <?php echo __('Deny') ?>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- User Clearance Info -->
    <div class="card mt-3">
      <div class="card-header">
        <h6 class="mb-0"><?php echo __('User Clearance Info') ?></h6>
      </div>
      <div class="card-body">
        <a href="<?php echo url_for('@security_clearance_view?id=' . $accessRequest->user_id); ?>" class="btn btn-sm btn-outline-primary">
          <?php echo __('View User Clearance') ?>
        </a>
        <a href="/security/audit/user/<?php echo $accessRequest->user_id ?>" class="btn btn-sm btn-outline-secondary">
          <?php echo __('View Access History') ?>
        </a>
      </div>
    </div>
  </div>
</div>
