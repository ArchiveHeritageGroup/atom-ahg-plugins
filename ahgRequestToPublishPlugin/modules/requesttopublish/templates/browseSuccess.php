<?php use_helper('Date'); ?>
<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-paper-plane me-2"></i><?php echo __('Request To Publish'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<!-- Filter Tabs -->
<div class="mb-4">
  <ul class="nav nav-tabs" id="rtp-tabs" role="tablist">
    <li class="nav-item" role="presentation">
      <?php echo link_to(
        '<i class="fas fa-list me-1"></i>' . __('All Requests'),
        ['filter' => 'all'] + $sf_data->getRaw('sf_request')->getParameterHolder()->getAll(),
        [
          'class' => 'nav-link' . ('all' === $filter ? ' active' : ''),
          'role' => 'tab'
        ]
      ); ?>
    </li>
    <li class="nav-item" role="presentation">
      <?php echo link_to(
        '<i class="fas fa-clock me-1"></i>' . __('In Review'),
        ['filter' => 'pending'] + $sf_data->getRaw('sf_request')->getParameterHolder()->getAll(),
        [
          'class' => 'nav-link' . ('pending' === $filter ? ' active' : ''),
          'role' => 'tab'
        ]
      ); ?>
    </li>
    <li class="nav-item" role="presentation">
      <?php echo link_to(
        '<i class="fas fa-times-circle me-1"></i>' . __('Rejected'),
        ['filter' => 'rejected'] + $sf_data->getRaw('sf_request')->getParameterHolder()->getAll(),
        [
          'class' => 'nav-link' . ('rejected' === $filter ? ' active' : ''),
          'role' => 'tab'
        ]
      ); ?>
    </li>
    <li class="nav-item" role="presentation">
      <?php echo link_to(
        '<i class="fas fa-check-circle me-1"></i>' . __('Approved'),
        ['filter' => 'approved'] + $sf_data->getRaw('sf_request')->getParameterHolder()->getAll(),
        [
          'class' => 'nav-link' . ('approved' === $filter ? ' active' : ''),
          'role' => 'tab'
        ]
      ); ?>
    </li>
  </ul>
</div>

<!-- Results -->
<div class="card mb-4">
  <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
    <span><i class="fas fa-inbox me-2"></i><?php echo __('Requests'); ?></span>
    <?php if ($pager->getNbResults() > 0): ?>
      <span class="badge bg-light text-dark"><?php echo $pager->getNbResults(); ?> <?php echo __('total'); ?></span>
    <?php endif; ?>
  </div>
  <div class="card-body p-0">
    <?php if ($pager->getNbResults() == 0): ?>
      <div class="alert alert-info m-3">
        <i class="fas fa-info-circle me-2"></i><?php echo __('No requests found.'); ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 100px;"><?php echo __('Status'); ?></th>
              <th><?php echo __('Archival Description'); ?></th>
              <th><?php echo __('Requester'); ?></th>
              <th><?php echo __('Contact'); ?></th>
              <th><?php echo __('Institution'); ?></th>
              <th><?php echo __('Need By'); ?></th>
              <th><?php echo __('Created'); ?></th>
              <th style="width: 80px;"><?php echo __('Action'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pager->getResults() as $item): ?>
              <tr>
                <td>
                  <?php if ($item->statusId == QubitTerm::IN_REVIEW_ID): ?>
                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i><?php echo __('In Review'); ?></span>
                  <?php elseif ($item->statusId == QubitTerm::REJECTED_ID): ?>
                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i><?php echo __('Rejected'); ?></span>
                  <?php else: ?>
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo __('Approved'); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php $informationObjectsRequestToPublish = QubitInformationObject::getById($item->object_id); ?>
                  <?php if ($informationObjectsRequestToPublish && isset($informationObjectsRequestToPublish->identifier)): ?>
                    <i class="fas fa-file-alt me-1 text-muted"></i>
                    <?php echo link_to(render_title($informationObjectsRequestToPublish), [$informationObjectsRequestToPublish, 'module' => 'informationobject']); ?>
                  <?php endif; ?>
                </td>
                <td>
                  <strong><?php echo esc_entities($item->rtp_name . ' ' . $item->rtp_surname); ?></strong>
                </td>
                <td>
                  <?php if ($item->rtp_email): ?>
                    <a href="mailto:<?php echo esc_entities($item->rtp_email); ?>" title="<?php echo esc_entities($item->rtp_email); ?>">
                      <i class="fas fa-envelope me-1"></i>
                    </a>
                  <?php endif; ?>
                  <?php if ($item->rtp_phone): ?>
                    <a href="tel:<?php echo esc_entities($item->rtp_phone); ?>" title="<?php echo esc_entities($item->rtp_phone); ?>">
                      <i class="fas fa-phone me-1"></i>
                    </a>
                  <?php endif; ?>
                </td>
                <td><?php echo esc_entities($item->rtp_institution); ?></td>
                <td><?php echo esc_entities($item->rtp_need_image_by); ?></td>
                <td>
                  <small class="text-muted"><?php echo $item->createdAt; ?></small>
                </td>
                <td class="text-center">
                  <?php echo link_to('<i class="fas fa-edit"></i>', [$item, 'module' => 'requesttopublish', 'action' => 'editRequestToPublish'], ['class' => 'btn btn-sm btn-outline-primary', 'title' => __('Review')]); ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($pager->getNbResults() > 0): ?>
  <?php echo get_partial('default/pager', ['pager' => $pager]); ?>
<?php endif; ?>

<!-- Expandable Details (for mobile/detailed view) -->
<?php if ($pager->getNbResults() > 0): ?>
<div class="accordion mb-4" id="requestDetails">
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#detailsTable">
        <i class="fas fa-table me-2"></i><?php echo __('View Full Details'); ?>
      </button>
    </h2>
    <div id="detailsTable" class="accordion-collapse collapse" data-bs-parent="#requestDetails">
      <div class="accordion-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Name'); ?></th>
                <th><?php echo __('Surname'); ?></th>
                <th><?php echo __('Phone'); ?></th>
                <th><?php echo __('Email'); ?></th>
                <th><?php echo __('Institution'); ?></th>
                <th><?php echo __('Planned Use'); ?></th>
                <th><?php echo __('Motivation'); ?></th>
                <th><?php echo __('Need By'); ?></th>
                <th><?php echo __('Completed'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pager->getResults() as $item): ?>
                <tr>
                  <td><?php echo esc_entities($item->rtp_name); ?></td>
                  <td><?php echo esc_entities($item->rtp_surname); ?></td>
                  <td><?php echo esc_entities($item->rtp_phone); ?></td>
                  <td><?php echo esc_entities($item->rtp_email); ?></td>
                  <td><?php echo esc_entities($item->rtp_institution); ?></td>
                  <td><?php echo esc_entities($item->rtp_planned_use); ?></td>
                  <td><?php echo esc_entities($item->rtp_motivation); ?></td>
                  <td><?php echo esc_entities($item->rtp_need_image_by); ?></td>
                  <td><?php echo $item->completedAt; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Actions -->
<section class="actions">
  <ul class="list-unstyled d-flex flex-wrap gap-2">
    <li>
      <a href="javascript:history.back();" class="btn atom-btn-outline-light">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back'); ?>
      </a>
    </li>
  </ul>
</section>

<?php end_slot(); ?>
