<?php
/**
 * Access Request Form Template.
 */
?>

<div class="row justify-content-center">
  <div class="col-md-8">
    <h1><i class="fas fa-hand-paper"></i> <?php echo __('Request Access') ?></h1>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><?php echo __('Requested Resource') ?></h5>
      </div>
      <div class="card-body">
        <p>
          <strong><?php echo __('Title:') ?></strong>
          <?php echo esc_entities($object->title ?? 'Untitled') ?>
        </p>
        <?php if ($object->identifier): ?>
        <p>
          <strong><?php echo __('Identifier:') ?></strong>
          <?php echo esc_entities($object->identifier) ?>
        </p>
        <?php endif ?>
        
        <?php if ($classification): ?>
        <p>
          <strong><?php echo __('Classification:') ?></strong>
          <span class="badge" style="background-color: <?php echo $classification->color ?>">
            <i class="fas <?php echo $classification->icon ?>"></i>
            <?php echo esc_entities($classification->name) ?>
          </span>
        </p>
        <?php endif ?>
      </div>
    </div>

    <?php if ($userClearance): ?>
    <div class="alert alert-info">
      <strong><?php echo __('Your Current Clearance:') ?></strong>
      <span class="badge" style="background-color: <?php echo $userClearance->color ?>">
        <?php echo esc_entities($userClearance->name) ?>
      </span>
      (Level <?php echo $userClearance->level ?>)
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
      <i class="fas fa-exclamation-triangle"></i>
      <?php echo __('You do not currently have a security clearance. Your request will need to be approved by a security officer.') ?>
    </div>
    <?php endif ?>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><?php echo __('Access Request Details') ?></h5>
      </div>
      <div class="card-body">
        <form action="/security/request/submit" method="post">
          <input type="hidden" name="object_id" value="<?php echo $object->id ?>">
          <?php if ($classification): ?>
          <input type="hidden" name="classification_id" value="<?php echo $classification->classification_id ?>">
          <?php endif ?>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Type of Access') ?> *</label>
            <select name="request_type" class="form-select" required>
              <option value="view"><?php echo __('View Only') ?></option>
              <option value="download"><?php echo __('Download') ?></option>
              <option value="print"><?php echo __('Print') ?></option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Priority') ?></label>
            <select name="priority" class="form-select">
              <option value="normal"><?php echo __('Normal') ?></option>
              <option value="urgent"><?php echo __('Urgent') ?></option>
              <option value="immediate"><?php echo __('Immediate') ?></option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Duration Required (hours)') ?></label>
            <select name="duration_hours" class="form-select">
              <option value="1">1 hour</option>
              <option value="4">4 hours</option>
              <option value="8">8 hours (1 day)</option>
              <option value="24" selected>24 hours</option>
              <option value="72">72 hours (3 days)</option>
              <option value="168">168 hours (1 week)</option>
              <option value="720">720 hours (30 days)</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Justification') ?> *</label>
            <textarea name="justification" class="form-control" rows="5" required
                      minlength="20" placeholder="<?php echo __('Please provide a detailed justification for your access request. Include the purpose, project name, and any relevant authorization.') ?>"></textarea>
            <div class="form-text"><?php echo __('Minimum 20 characters required.') ?></div>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fas fa-paper-plane"></i> <?php echo __('Submit Request') ?>
            </button>
            <a href="/security/my-requests" class="btn btn-outline-secondary">
              <?php echo __('View My Requests') ?>
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
