<?php decorate_with('layout_1col'); ?>

<?php
  $rawAccession = $sf_data->getRaw('accession');
  $rawTimeline  = $sf_data->getRaw('timeline');

  $accId      = $rawAccession->id ?? $rawAccession->accession_id ?? 0;
  $identifier = $rawAccession->identifier ?? '--';

  $timelineArr = is_array($rawTimeline) ? $rawTimeline : [];

  $eventIcons = [
      'created'         => ['icon' => 'fas fa-plus-circle',     'color' => 'success',   'bg' => 'success'],
      'submitted'       => ['icon' => 'fas fa-paper-plane',     'color' => 'primary',   'bg' => 'primary'],
      'under_review'    => ['icon' => 'fas fa-search',          'color' => 'info',      'bg' => 'info'],
      'accepted'        => ['icon' => 'fas fa-check-circle',    'color' => 'success',   'bg' => 'success'],
      'rejected'        => ['icon' => 'fas fa-times-circle',    'color' => 'danger',    'bg' => 'danger'],
      'returned'        => ['icon' => 'fas fa-undo',            'color' => 'warning',   'bg' => 'warning'],
      'assigned'        => ['icon' => 'fas fa-user-plus',       'color' => 'info',      'bg' => 'info'],
      'commented'       => ['icon' => 'fas fa-comment',         'color' => 'secondary', 'bg' => 'secondary'],
      'checklist'       => ['icon' => 'fas fa-check-square',    'color' => 'success',   'bg' => 'success'],
      'attachment'      => ['icon' => 'fas fa-paperclip',       'color' => 'primary',   'bg' => 'primary'],
      'updated'         => ['icon' => 'fas fa-edit',            'color' => 'secondary', 'bg' => 'secondary'],
      'priority_change' => ['icon' => 'fas fa-exclamation',     'color' => 'warning',   'bg' => 'warning'],
      'status_change'   => ['icon' => 'fas fa-exchange-alt',    'color' => 'info',      'bg' => 'info'],
  ];
?>

<?php slot('title'); ?>
  <h1>
    <i class="fas fa-history me-2"></i><?php echo __('Timeline'); ?>
    <small class="text-muted fs-5"><?php echo htmlspecialchars($identifier); ?></small>
  </h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@accession_intake_queue'); ?>"><?php echo __('Intake queue'); ?></a>
      </li>
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@accession_intake_detail?id=' . $accId); ?>"><?php echo htmlspecialchars($identifier); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Timeline'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <?php if (count($timelineArr) > 0): ?>
    <style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
      .ahg-timeline {
        position: relative;
        padding-left: 40px;
      }
      .ahg-timeline::before {
        content: '';
        position: absolute;
        left: 19px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
      }
      .ahg-timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
      }
      .ahg-timeline-icon {
        position: absolute;
        left: -40px;
        top: 0;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.9rem;
        z-index: 1;
      }
    </style>

    <div class="ahg-timeline">
      <?php foreach ($timelineArr as $event): ?>
        <?php
          $evType  = $event->event_type ?? 'created';
          $evStyle = $eventIcons[$evType] ?? ['icon' => 'fas fa-circle', 'color' => 'secondary', 'bg' => 'secondary'];
        ?>
        <div class="ahg-timeline-item">
          <div class="ahg-timeline-icon bg-<?php echo $evStyle['bg']; ?>">
            <i class="<?php echo $evStyle['icon']; ?>"></i>
          </div>
          <div class="card">
            <div class="card-body py-2 px-3">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <span class="badge bg-<?php echo $evStyle['bg']; ?> me-1">
                    <?php echo ucfirst(str_replace('_', ' ', $evType)); ?>
                  </span>
                  <?php if (!empty($event->actor_name)): ?>
                    <strong><?php echo htmlspecialchars($event->actor_name); ?></strong>
                  <?php endif; ?>
                </div>
                <small class="text-muted text-nowrap ms-2">
                  <?php if (!empty($event->created_at)): ?>
                    <?php echo date('d M Y H:i', strtotime($event->created_at)); ?>
                  <?php endif; ?>
                </small>
              </div>
              <?php if (!empty($event->description)): ?>
                <p class="mb-0 mt-1"><?php echo htmlspecialchars($event->description); ?></p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center py-5 text-muted">
      <i class="fas fa-history fa-3x mb-3"></i>
      <p class="mb-0"><?php echo __('No timeline events recorded for this accession.'); ?></p>
    </div>
  <?php endif; ?>
<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <section class="actions mb-3">
    <a href="<?php echo url_for('@accession_intake_detail?id=' . $accId); ?>" class="btn atom-btn-outline-light">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to intake detail'); ?>
    </a>
  </section>
<?php end_slot(); ?>
