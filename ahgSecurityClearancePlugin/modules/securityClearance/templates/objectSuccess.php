<?php use_helper('Date'); ?>

<?php decorate_with('layout_3col'); ?>

<?php slot('sidebar'); ?>
  <?php include_component('informationobject', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1 class="multiline">
    <?php echo __('Security classification'); ?>
    <span class="sub"><?php echo render_title($resource); ?></span>
  </h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if ($sf_request->getParameter('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?php if ('classified' === $sf_request->getParameter('success')): ?>
        <?php echo __('Security classification has been applied successfully.'); ?>
      <?php elseif ('declassified' === $sf_request->getParameter('success')): ?>
        <?php echo __('Security classification has been removed.'); ?>
      <?php endif; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <section id="content">

    <!-- Current Classification -->
    <div class="card mb-4">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          <i class="fas fa-shield-alt me-2"></i><?php echo __('Security Classification'); ?>
        </h5>
        <?php echo link_to(
            '<i class="fas fa-edit me-1"></i>'.($classification ? __('Reclassify') : __('Classify')),
            [$resource, 'module' => 'securityClearance', 'action' => 'classify'],
            ['class' => 'btn btn-sm btn-primary']
        ); ?>
      </div>
      <div class="card-body">

        <?php if ($classification): ?>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label text-muted small"><?php echo __('Classification Level'); ?></label>
              <p class="mb-0">
                <span class="badge fs-6" style="background-color: <?php echo $classification->classificationColor; ?>;">
                  <i class="<?php echo $classification->classificationIcon ?? 'fa-lock'; ?> me-1"></i>
                  <?php echo $classification->classificationName; ?>
                </span>
              </p>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label text-muted small"><?php echo __('Classified By'); ?></label>
              <p class="mb-0"><?php echo $classification->classifiedByUsername ?? __('System'); ?></p>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label text-muted small"><?php echo __('Classification Date'); ?></label>
              <p class="mb-0"><?php echo $classification->classifiedAt ? format_date($classification->classifiedAt, 'f') : '-'; ?></p>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label text-muted small"><?php echo __('Review Date'); ?></label>
              <p class="mb-0">
                <?php if ($classification->reviewDate): ?>
                  <?php echo format_date($classification->reviewDate, 'f'); ?>
                  <?php if (strtotime($classification->reviewDate) <= time()): ?>
                    <span class="badge bg-warning text-dark ms-1"><?php echo __('Due'); ?></span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted"><?php echo __('Not set'); ?></span>
                <?php endif; ?>
              </p>
            </div>
            <?php if ($classification->declassifyDate): ?>
              <div class="col-md-6 mb-3">
                <label class="form-label text-muted small"><?php echo __('Auto-declassify Date'); ?></label>
                <p class="mb-0">
                  <?php echo format_date($classification->declassifyDate, 'f'); ?>
                  <?php if (strtotime($classification->declassifyDate) <= time()): ?>
                    <span class="badge bg-info ms-1"><?php echo __('Due'); ?></span>
                  <?php endif; ?>
                </p>
              </div>
            <?php endif; ?>
            <?php if ($classification->reason): ?>
              <div class="col-12 mb-3">
                <label class="form-label text-muted small"><?php echo __('Classification Reason'); ?></label>
                <p class="mb-0"><?php echo $classification->reason; ?></p>
              </div>
            <?php endif; ?>
            <?php if ($classification->handlingInstructions): ?>
              <div class="col-12">
                <label class="form-label text-muted small"><?php echo __('Handling Instructions'); ?></label>
                <div class="alert alert-warning mb-0">
                  <i class="fas fa-exclamation-triangle me-2"></i>
                  <?php echo $classification->handlingInstructions; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Declassify Button -->
          <div class="border-top mt-3 pt-3">
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#declassifyModal">
              <i class="fas fa-unlock me-1"></i><?php echo __('Remove Classification'); ?>
            </button>
          </div>

        <?php else: ?>
          <div class="text-center py-4">
            <i class="fas fa-globe fa-3x text-success mb-3"></i>
            <h5><?php echo __('This record is publicly accessible'); ?></h5>
            <p class="text-muted"><?php echo __('No security classification has been applied to this record.'); ?></p>
            <?php echo link_to(
                '<i class="fas fa-lock me-1"></i>'.__('Apply Classification'),
                [$resource, 'module' => 'securityClearance', 'action' => 'classify'],
                ['class' => 'btn btn-primary']
            ); ?>
          </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- Classification History -->
    <?php if (!empty($history)): ?>
      <div class="card">
        <div class="card-header bg-light">
          <h5 class="mb-0">
            <i class="fas fa-history me-2"></i><?php echo __('Classification History'); ?>
          </h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th><?php echo __('Date'); ?></th>
                  <th><?php echo __('Action'); ?></th>
                  <th><?php echo __('From'); ?></th>
                  <th><?php echo __('To'); ?></th>
                  <th><?php echo __('By'); ?></th>
                  <th><?php echo __('Reason'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($history as $record): ?>
                  <tr>
                    <td><?php echo date('Y-m-d H:i', strtotime($record->created_at)); ?></td>
                    <td>
                      <span class="badge <?php echo 'declassified' === $record->action ? 'bg-success' : ('reclassified' === $record->action ? 'bg-info' : 'bg-warning text-dark'); ?>">
                        <?php echo ucfirst($record->action); ?>
                      </span>
                    </td>
                    <td><?php echo $record->previous_name ?? '-'; ?></td>
                    <td><?php echo $record->new_name ?? '-'; ?></td>
                    <td><?php echo $record->changed_by_username ?? __('System'); ?></td>
                    <td><?php echo $record->reason ?? '-'; ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </section>

  <!-- Declassify Modal -->
  <?php if ($classification): ?>
  <div class="modal fade" id="declassifyModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" action="<?php echo url_for([$resource, 'module' => 'securityClearance', 'action' => 'declassify']); ?>">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title"><i class="fas fa-unlock me-2"></i><?php echo __('Remove Classification'); ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p><?php echo __('Are you sure you want to remove the security classification from this record?'); ?></p>
            <p class="text-success"><i class="fas fa-globe me-1"></i><?php echo __('This record will become publicly accessible.'); ?></p>
            <div class="mb-3">
              <label class="form-label"><?php echo __('Reason'); ?> <span class="text-danger">*</span></label>
              <textarea name="reason" class="form-control" rows="2" required placeholder="<?php echo __('Enter reason for declassification...'); ?>"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
            <button type="submit" class="btn btn-success"><i class="fas fa-unlock me-1"></i><?php echo __('Remove Classification'); ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <section class="actions">
    <ul>
      <li><?php echo link_to(__('Edit'), [$resource, 'module' => 'informationobject', 'action' => 'edit'], ['class' => 'c-btn']); ?></li>
      <li><?php echo link_to(__('View record'), [$resource, 'module' => 'informationobject'], ['class' => 'c-btn']); ?></li>
    </ul>
  </section>
<?php end_slot(); ?>
