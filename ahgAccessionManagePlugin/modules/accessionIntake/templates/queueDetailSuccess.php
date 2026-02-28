<?php decorate_with('layout_1col'); ?>

<?php
  $rawAccession        = $sf_data->getRaw('accession');
  $rawV2               = $sf_data->getRaw('v2');
  $rawChecklist        = $sf_data->getRaw('checklist');
  $rawChecklistProgress = $sf_data->getRaw('checklistProgress');
  $rawChecklistTemplates = $sf_data->getRaw('checklistTemplates');
  $rawAttachments      = $sf_data->getRaw('attachments');
  $rawTimeline         = $sf_data->getRaw('timeline');
  $rawUsers            = $sf_data->getRaw('users');

  $accId      = $rawAccession->id ?? $rawAccession->accession_id ?? 0;
  $identifier = $rawAccession->identifier ?? '--';
  $title      = $rawAccession->title ?? $identifier;
  $status     = $rawV2->status ?? 'draft';
  $priority   = $rawV2->priority ?? 'normal';

  $statusBadges = [
      'draft'        => 'secondary',
      'submitted'    => 'primary',
      'under_review' => 'info',
      'accepted'     => 'success',
      'rejected'     => 'danger',
      'returned'     => 'warning',
  ];
  $priorityBadges = [
      'low'    => 'secondary',
      'normal' => 'info',
      'high'   => 'warning',
      'urgent' => 'danger',
  ];

  $checklistArr  = is_array($rawChecklist) ? $rawChecklist : [];
  $progressArr   = is_array($rawChecklistProgress) ? $rawChecklistProgress : (array) $rawChecklistProgress;
  $templatesArr  = is_array($rawChecklistTemplates) ? $rawChecklistTemplates : [];
  $attachArr     = is_array($rawAttachments) ? $rawAttachments : [];
  $timelineArr   = is_array($rawTimeline) ? $rawTimeline : [];
?>

<?php slot('title'); ?>
  <div class="d-flex justify-content-between align-items-start flex-wrap">
    <div>
      <h1>
        <?php echo htmlspecialchars($identifier); ?>
        <span class="badge bg-<?php echo $statusBadges[$status] ?? 'secondary'; ?> fs-6 align-middle">
          <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
        </span>
        <span class="badge bg-<?php echo $priorityBadges[$priority] ?? 'info'; ?> fs-6 align-middle">
          <?php echo ucfirst($priority); ?>
        </span>
      </h1>
      <p class="text-muted mb-0"><?php echo htmlspecialchars($title); ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
      <?php if ($status === 'draft'): ?>
        <form method="post" action="<?php echo url_for('@accession_intake_submit?id=' . $accId); ?>" class="d-inline">
          <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('<?php echo __('Submit this accession for review?'); ?>');">
            <i class="fas fa-paper-plane me-1"></i><?php echo __('Submit'); ?>
          </button>
        </form>
      <?php endif; ?>

      <?php if ($status === 'submitted'): ?>
        <form method="post" action="<?php echo url_for('@accession_intake_review?id=' . $accId); ?>" class="d-inline">
          <button type="submit" class="btn btn-info btn-sm text-white">
            <i class="fas fa-search me-1"></i><?php echo __('Start review'); ?>
          </button>
        </form>
      <?php endif; ?>

      <?php if ($status === 'under_review'): ?>
        <form method="post" action="<?php echo url_for('@accession_intake_accept?id=' . $accId); ?>" class="d-inline">
          <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('<?php echo __('Accept this accession?'); ?>');">
            <i class="fas fa-check me-1"></i><?php echo __('Accept'); ?>
          </button>
        </form>

        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
          <i class="fas fa-times me-1"></i><?php echo __('Reject'); ?>
        </button>

        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#returnModal">
          <i class="fas fa-undo me-1"></i><?php echo __('Return'); ?>
        </button>
      <?php endif; ?>

      <a href="<?php echo url_for('@accession_intake_queue'); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to queue'); ?>
      </a>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <!-- Tabs -->
  <ul class="nav nav-tabs mb-3" id="intakeTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="overview-tab" data-bs-toggle="tab"
              data-bs-target="#tab-overview" type="button" role="tab">
        <i class="fas fa-info-circle me-1"></i><?php echo __('Overview'); ?>
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <?php $clPct = $progressArr['percent'] ?? 0; ?>
      <button class="nav-link" id="checklist-tab" data-bs-toggle="tab"
              data-bs-target="#tab-checklist" type="button" role="tab">
        <i class="fas fa-tasks me-1"></i><?php echo __('Checklist'); ?>
        <?php if ($clPct > 0): ?>
          <span class="badge bg-<?php echo $clPct >= 100 ? 'success' : 'warning'; ?> ms-1"><?php echo $clPct; ?>%</span>
        <?php endif; ?>
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="attachments-tab" data-bs-toggle="tab"
              data-bs-target="#tab-attachments" type="button" role="tab">
        <i class="fas fa-paperclip me-1"></i><?php echo __('Attachments'); ?>
        <?php if (count($attachArr) > 0): ?>
          <span class="badge bg-secondary ms-1"><?php echo count($attachArr); ?></span>
        <?php endif; ?>
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="timeline-tab" data-bs-toggle="tab"
              data-bs-target="#tab-timeline" type="button" role="tab">
        <i class="fas fa-history me-1"></i><?php echo __('Timeline'); ?>
      </button>
    </li>
  </ul>

  <div class="tab-content" id="intakeTabContent">

    <!-- OVERVIEW TAB -->
    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
      <div class="row">
        <div class="col-md-8">
          <div class="card mb-3">
            <div class="card-header">
              <i class="fas fa-clipboard me-1"></i><?php echo __('Intake details'); ?>
            </div>
            <div class="card-body">
              <table class="table table-sm mb-0">
                <tr>
                  <th class="w-25"><?php echo __('Status'); ?></th>
                  <td>
                    <span class="badge bg-<?php echo $statusBadges[$status] ?? 'secondary'; ?>">
                      <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                    </span>
                  </td>
                </tr>
                <tr>
                  <th><?php echo __('Priority'); ?></th>
                  <td>
                    <span class="badge bg-<?php echo $priorityBadges[$priority] ?? 'info'; ?>">
                      <?php echo ucfirst($priority); ?>
                    </span>
                  </td>
                </tr>
                <tr>
                  <th><?php echo __('Assigned to'); ?></th>
                  <td><?php echo htmlspecialchars($rawV2->assigned_to_name ?? '--'); ?></td>
                </tr>
                <tr>
                  <th><?php echo __('Submitted'); ?></th>
                  <td>
                    <?php if (!empty($rawV2->submitted_at)): ?>
                      <?php echo date('d M Y H:i', strtotime($rawV2->submitted_at)); ?>
                    <?php else: ?>
                      <span class="text-muted">--</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <th><?php echo __('Reviewed'); ?></th>
                  <td>
                    <?php if (!empty($rawV2->reviewed_at)): ?>
                      <?php echo date('d M Y H:i', strtotime($rawV2->reviewed_at)); ?>
                    <?php else: ?>
                      <span class="text-muted">--</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <th><?php echo __('Completed'); ?></th>
                  <td>
                    <?php if (!empty($rawV2->completed_at)): ?>
                      <?php echo date('d M Y H:i', strtotime($rawV2->completed_at)); ?>
                    <?php else: ?>
                      <span class="text-muted">--</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <th><?php echo __('Due date'); ?></th>
                  <td>
                    <?php if (!empty($rawV2->due_date)): ?>
                      <?php $overdue = strtotime($rawV2->due_date) < time() && !in_array($status, ['accepted', 'rejected']); ?>
                      <span class="<?php echo $overdue ? 'text-danger fw-bold' : ''; ?>">
                        <?php echo date('d M Y', strtotime($rawV2->due_date)); ?>
                        <?php if ($overdue): ?>
                          <span class="badge bg-danger ms-1"><?php echo __('Overdue'); ?></span>
                        <?php endif; ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">--</span>
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <!-- Notes -->
          <div class="card mb-3">
            <div class="card-header">
              <i class="fas fa-sticky-note me-1"></i><?php echo __('Notes'); ?>
            </div>
            <div class="card-body">
              <?php if (!empty($rawV2->notes)): ?>
                <?php echo nl2br(htmlspecialchars($rawV2->notes)); ?>
              <?php else: ?>
                <p class="text-muted mb-0"><?php echo __('No notes.'); ?></p>
              <?php endif; ?>
            </div>
          </div>

          <!-- Rejection reason -->
          <?php if ($status === 'rejected' && !empty($rawV2->rejection_reason)): ?>
            <div class="card mb-3 border-danger">
              <div class="card-header bg-danger text-white">
                <i class="fas fa-ban me-1"></i><?php echo __('Rejection reason'); ?>
              </div>
              <div class="card-body">
                <?php echo nl2br(htmlspecialchars($rawV2->rejection_reason)); ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Return notes -->
          <?php if ($status === 'returned' && !empty($rawV2->return_notes)): ?>
            <div class="card mb-3 border-warning">
              <div class="card-header bg-warning text-dark">
                <i class="fas fa-undo me-1"></i><?php echo __('Return notes'); ?>
              </div>
              <div class="card-body">
                <?php echo nl2br(htmlspecialchars($rawV2->return_notes)); ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Quick links -->
          <div class="card mb-3">
            <div class="card-header">
              <i class="fas fa-link me-1"></i><?php echo __('Quick links'); ?>
            </div>
            <div class="card-body">
              <div class="d-grid gap-2">
                <a href="<?php echo url_for('@accession_intake_timeline?id=' . $accId); ?>" class="btn btn-outline-secondary btn-sm">
                  <i class="fas fa-history me-1"></i><?php echo __('Full timeline'); ?>
                </a>
                <a href="<?php echo url_for('@accession_intake_checklist?id=' . $accId); ?>" class="btn btn-outline-secondary btn-sm">
                  <i class="fas fa-tasks me-1"></i><?php echo __('Checklist page'); ?>
                </a>
                <a href="<?php echo url_for('@accession_intake_attachments?id=' . $accId); ?>" class="btn btn-outline-secondary btn-sm">
                  <i class="fas fa-paperclip me-1"></i><?php echo __('Attachments page'); ?>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CHECKLIST TAB -->
    <div class="tab-pane fade" id="tab-checklist" role="tabpanel">
      <?php
        $clTotal     = $progressArr['total'] ?? 0;
        $clCompleted = $progressArr['completed'] ?? 0;
        $clPct       = $progressArr['percent'] ?? 0;
      ?>

      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span><?php echo __('Progress'); ?>: <?php echo $clCompleted; ?>/<?php echo $clTotal; ?></span>
          <span><?php echo $clPct; ?>%</span>
        </div>
        <div class="progress" style="height: 20px;">
          <?php $barColor = $clPct >= 100 ? 'success' : ($clPct >= 50 ? 'info' : 'warning'); ?>
          <div class="progress-bar bg-<?php echo $barColor; ?>"
               role="progressbar"
               style="width: <?php echo $clPct; ?>%"
               aria-valuenow="<?php echo $clPct; ?>"
               aria-valuemin="0"
               aria-valuemax="100">
            <?php echo $clPct; ?>%
          </div>
        </div>
      </div>

      <!-- Apply template -->
      <?php if (count($templatesArr) > 0): ?>
        <div class="d-flex gap-2 mb-3">
          <select id="checklist-template-select" class="form-select form-select-sm" style="max-width: 300px;">
            <option value=""><?php echo __('Select a checklist template...'); ?></option>
            <?php foreach ($templatesArr as $tpl): ?>
              <option value="<?php echo htmlspecialchars($tpl->id); ?>"><?php echo htmlspecialchars($tpl->name); ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" id="apply-template-btn" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-copy me-1"></i><?php echo __('Apply template'); ?>
          </button>
        </div>
      <?php endif; ?>

      <!-- Checklist items -->
      <?php if (count($checklistArr) > 0): ?>
        <div class="list-group">
          <?php foreach ($checklistArr as $item): ?>
            <?php $completed = !empty($item->is_completed); ?>
            <div class="list-group-item d-flex align-items-center">
              <div class="form-check me-3">
                <input class="form-check-input checklist-toggle"
                       type="checkbox"
                       data-item-id="<?php echo htmlspecialchars($item->id); ?>"
                       <?php echo $completed ? 'checked' : ''; ?>>
              </div>
              <div class="flex-grow-1">
                <span class="<?php echo $completed ? 'text-decoration-line-through text-muted' : ''; ?>">
                  <?php echo htmlspecialchars($item->label ?? $item->item_label ?? ''); ?>
                </span>
                <?php if (!empty($item->completed_by_name)): ?>
                  <br>
                  <small class="text-muted">
                    <?php echo __('Completed by'); ?> <?php echo htmlspecialchars($item->completed_by_name); ?>
                    <?php if (!empty($item->completed_at)): ?>
                      <?php echo __('on'); ?> <?php echo date('d M Y H:i', strtotime($item->completed_at)); ?>
                    <?php endif; ?>
                  </small>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-center py-4 text-muted">
          <i class="fas fa-tasks fa-2x mb-2"></i>
          <p class="mb-0"><?php echo __('No checklist items. Apply a template to get started.'); ?></p>
        </div>
      <?php endif; ?>
    </div>

    <!-- ATTACHMENTS TAB -->
    <div class="tab-pane fade" id="tab-attachments" role="tabpanel">
      <!-- Upload form -->
      <div class="card mb-3">
        <div class="card-body">
          <form id="attachment-upload-form" enctype="multipart/form-data">
            <div class="row g-2 align-items-end">
              <div class="col-md-4">
                <label class="form-label form-label-sm"><?php echo __('File'); ?></label>
                <input type="file" name="file" id="attachment-file" class="form-control form-control-sm" required>
              </div>
              <div class="col-md-3">
                <label class="form-label form-label-sm"><?php echo __('Category'); ?></label>
                <select name="category" id="attachment-category" class="form-select form-select-sm">
                  <option value="general"><?php echo __('General'); ?></option>
                  <option value="deed_of_gift"><?php echo __('Deed of gift'); ?></option>
                  <option value="photo"><?php echo __('Photo'); ?></option>
                  <option value="correspondence"><?php echo __('Correspondence'); ?></option>
                  <option value="inventory"><?php echo __('Inventory'); ?></option>
                  <option value="other"><?php echo __('Other'); ?></option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label form-label-sm"><?php echo __('Description'); ?></label>
                <input type="text" name="description" id="attachment-description" class="form-control form-control-sm"
                       placeholder="<?php echo __('Optional description'); ?>">
              </div>
              <div class="col-md-auto">
                <button type="submit" class="btn btn-sm btn-primary">
                  <i class="fas fa-upload me-1"></i><?php echo __('Upload'); ?>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Attachments list -->
      <?php if (count($attachArr) > 0): ?>
        <?php
          $catBadges = [
              'general'        => 'secondary',
              'deed_of_gift'   => 'success',
              'photo'          => 'info',
              'correspondence' => 'primary',
              'inventory'      => 'warning',
              'other'          => 'dark',
          ];
        ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Filename'); ?></th>
                <th><?php echo __('Category'); ?></th>
                <th><?php echo __('Size'); ?></th>
                <th><?php echo __('Uploaded by'); ?></th>
                <th><?php echo __('Date'); ?></th>
                <th class="text-end"><?php echo __('Actions'); ?></th>
              </tr>
            </thead>
            <tbody id="attachments-table-body">
              <?php foreach ($attachArr as $att): ?>
                <?php $cat = $att->category ?? 'general'; ?>
                <tr id="attachment-row-<?php echo htmlspecialchars($att->id); ?>">
                  <td>
                    <?php if (!empty($att->file_path)): ?>
                      <a href="<?php echo htmlspecialchars($att->file_path); ?>" target="_blank">
                        <i class="fas fa-file me-1"></i><?php echo htmlspecialchars($att->filename ?? $att->original_filename ?? 'Download'); ?>
                      </a>
                    <?php else: ?>
                      <i class="fas fa-file me-1"></i><?php echo htmlspecialchars($att->filename ?? $att->original_filename ?? '--'); ?>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge bg-<?php echo $catBadges[$cat] ?? 'secondary'; ?>">
                      <?php echo ucfirst(str_replace('_', ' ', $cat)); ?>
                    </span>
                  </td>
                  <td>
                    <?php if (!empty($att->file_size)):
                      $bytes = (int) $att->file_size;
                      if ($bytes >= 1048576) { $sizeStr = round($bytes / 1048576, 1) . ' MB'; }
                      elseif ($bytes >= 1024) { $sizeStr = round($bytes / 1024, 1) . ' KB'; }
                      else { $sizeStr = $bytes . ' B'; }
                      echo $sizeStr;
                    else:
                      echo '--';
                    endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($att->uploaded_by_name ?? '--'); ?></td>
                  <td>
                    <?php if (!empty($att->created_at)): ?>
                      <?php echo date('d M Y H:i', strtotime($att->created_at)); ?>
                    <?php else: ?>
                      --
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger attachment-delete-btn"
                            data-attachment-id="<?php echo htmlspecialchars($att->id); ?>"
                            title="<?php echo __('Delete'); ?>">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-center py-4 text-muted">
          <i class="fas fa-paperclip fa-2x mb-2"></i>
          <p class="mb-0"><?php echo __('No attachments yet.'); ?></p>
        </div>
      <?php endif; ?>
    </div>

    <!-- TIMELINE TAB -->
    <div class="tab-pane fade" id="tab-timeline" role="tabpanel">
      <?php
        $eventIcons = [
            'created'      => ['icon' => 'fas fa-plus-circle',   'color' => 'success'],
            'submitted'    => ['icon' => 'fas fa-paper-plane',   'color' => 'primary'],
            'under_review' => ['icon' => 'fas fa-search',        'color' => 'info'],
            'accepted'     => ['icon' => 'fas fa-check-circle',  'color' => 'success'],
            'rejected'     => ['icon' => 'fas fa-times-circle',  'color' => 'danger'],
            'returned'     => ['icon' => 'fas fa-undo',          'color' => 'warning'],
            'assigned'     => ['icon' => 'fas fa-user-plus',     'color' => 'info'],
            'commented'    => ['icon' => 'fas fa-comment',       'color' => 'secondary'],
            'checklist'    => ['icon' => 'fas fa-check-square',  'color' => 'success'],
            'attachment'   => ['icon' => 'fas fa-paperclip',     'color' => 'primary'],
        ];
      ?>

      <?php if (count($timelineArr) > 0): ?>
        <div class="timeline-list">
          <?php foreach ($timelineArr as $event): ?>
            <?php
              $evType  = $event->event_type ?? 'created';
              $evStyle = $eventIcons[$evType] ?? ['icon' => 'fas fa-circle', 'color' => 'secondary'];
            ?>
            <div class="d-flex mb-3">
              <div class="flex-shrink-0 me-3 text-center" style="width: 40px;">
                <span class="text-<?php echo $evStyle['color']; ?>">
                  <i class="<?php echo $evStyle['icon']; ?> fa-lg"></i>
                </span>
              </div>
              <div class="flex-grow-1">
                <div class="card">
                  <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <span class="badge bg-<?php echo $evStyle['color']; ?> me-1">
                          <?php echo ucfirst(str_replace('_', ' ', $evType)); ?>
                        </span>
                        <strong><?php echo htmlspecialchars($event->actor_name ?? '--'); ?></strong>
                      </div>
                      <small class="text-muted">
                        <?php if (!empty($event->created_at)): ?>
                          <?php echo date('d M Y H:i', strtotime($event->created_at)); ?>
                        <?php endif; ?>
                      </small>
                    </div>
                    <?php if (!empty($event->description)): ?>
                      <p class="mb-0 mt-1 small"><?php echo htmlspecialchars($event->description); ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-center py-4 text-muted">
          <i class="fas fa-history fa-2x mb-2"></i>
          <p class="mb-0"><?php echo __('No timeline events yet.'); ?></p>
        </div>
      <?php endif; ?>
    </div>

  </div>
<?php end_slot(); ?>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?php echo url_for('@accession_intake_reject?id=' . $accId); ?>">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="fas fa-times-circle me-1"></i><?php echo __('Reject accession'); ?></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Reason for rejection'); ?></label>
            <textarea name="rejection_reason" class="form-control" rows="4"
                      placeholder="<?php echo __('Provide a reason for rejecting this accession...'); ?>" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-danger"><?php echo __('Reject'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Return for Revision Modal -->
<div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?php echo url_for('@accession_intake_return?id=' . $accId); ?>">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title"><i class="fas fa-undo me-1"></i><?php echo __('Return for revision'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Notes for revision'); ?></label>
            <textarea name="return_notes" class="form-control" rows="4"
                      placeholder="<?php echo __('Describe what needs to be revised...'); ?>" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-warning"><?php echo __('Return for revision'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  // Checklist toggle
  document.querySelectorAll('.checklist-toggle').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
      var itemId = this.dataset.itemId;
      fetch('<?php echo url_for("@accession_api_checklist_toggle?id="); ?>' + itemId, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function(resp) { return resp.json(); })
      .then(function(data) {
        if (!data.success) {
          alert('<?php echo __("Failed to toggle checklist item."); ?>');
          checkbox.checked = !checkbox.checked;
        } else {
          location.reload();
        }
      })
      .catch(function() {
        alert('<?php echo __("An error occurred."); ?>');
        checkbox.checked = !checkbox.checked;
      });
    });
  });

  // Apply checklist template
  var applyBtn = document.getElementById('apply-template-btn');
  if (applyBtn) {
    applyBtn.addEventListener('click', function() {
      var templateId = document.getElementById('checklist-template-select').value;
      if (!templateId) {
        alert('<?php echo __("Please select a template."); ?>');
        return;
      }
      fetch('<?php echo url_for("@accession_api_checklist_apply"); ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'accession_id=<?php echo $accId; ?>&template_id=' + templateId
      })
      .then(function(resp) { return resp.json(); })
      .then(function(data) {
        if (data.success) {
          location.reload();
        } else {
          alert('<?php echo __("Failed to apply template."); ?>');
        }
      })
      .catch(function() {
        alert('<?php echo __("An error occurred."); ?>');
      });
    });
  }

  // Attachment upload
  var uploadForm = document.getElementById('attachment-upload-form');
  if (uploadForm) {
    uploadForm.addEventListener('submit', function(e) {
      e.preventDefault();
      var formData = new FormData();
      var fileInput = document.getElementById('attachment-file');
      if (!fileInput.files.length) {
        alert('<?php echo __("Please select a file."); ?>');
        return;
      }
      formData.append('file', fileInput.files[0]);
      formData.append('accession_id', '<?php echo $accId; ?>');
      formData.append('category', document.getElementById('attachment-category').value);

      fetch('<?php echo url_for("@accession_api_attachment_upload"); ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
      })
      .then(function(resp) { return resp.json(); })
      .then(function(data) {
        if (data.success) {
          location.reload();
        } else {
          alert(data.error || '<?php echo __("Upload failed."); ?>');
        }
      })
      .catch(function() {
        alert('<?php echo __("An error occurred."); ?>');
      });
    });
  }

  // Attachment delete
  document.querySelectorAll('.attachment-delete-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm('<?php echo __("Delete this attachment?"); ?>')) return;
      var attId = this.dataset.attachmentId;
      fetch('<?php echo url_for("@accession_api_attachment_delete?id="); ?>' + attId, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function(resp) { return resp.json(); })
      .then(function(data) {
        if (data.success) {
          var row = document.getElementById('attachment-row-' + attId);
          if (row) row.remove();
        } else {
          alert('<?php echo __("Failed to delete attachment."); ?>');
        }
      })
      .catch(function() {
        alert('<?php echo __("An error occurred."); ?>');
      });
    });
  });
});
</script>
