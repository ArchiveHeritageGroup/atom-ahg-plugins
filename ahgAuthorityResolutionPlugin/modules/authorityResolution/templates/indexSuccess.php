<?php
/**
 * Authority Resolution - pending queue list (Task 5).
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */
?>
<?php decorate_with('layout_1col'); ?>

<?php
  $rows           = $sf_data->getRaw('rows');
  $total          = (int) $sf_data->getRaw('total');
  $filters        = $sf_data->getRaw('filters');
  $lastPage       = (int) $sf_data->getRaw('lastPage');
  $stateCounts    = $sf_data->getRaw('stateCounts');
  $typeCounts     = $sf_data->getRaw('typeCounts');
  $archivists     = $sf_data->getRaw('archivists');
  $allMatchingIds = $sf_data->getRaw('allMatchingIds');

  // Keyed count map so every state KPI renders even when its count is zero.
  $countsByState = [];
  foreach ($stateCounts as $sc) {
      $countsByState[$sc->state] = (int) $sc->c;
  }

  $stateBadges = [
    'pending'             => 'warning',
    'linked'              => 'success',
    'parked'              => 'info',
    'rejected'            => 'secondary',
    'new_record_created'  => 'primary',
  ];

  $typeBadges = [
    'PERSON'      => 'primary',
    'ORG'         => 'info',
    'GPE'         => 'success',
    'LOC'         => 'success',
    'PLACE'       => 'success',
    'ISAD_PLACE'  => 'success',
  ];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-balance-scale me-2"></i><?php echo __('Authority Resolution Queue'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item active"><?php echo __('Authority Resolution'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <!-- Header actions -->
  <div class="d-flex justify-content-end mb-3">
    <div class="btn-group">
      <a href="<?php echo url_for('@ar_auth_res_park_list'); ?>" class="btn btn-outline-info">
        <i class="fas fa-pause-circle me-1"></i><?php echo __('Parked'); ?>
      </a>
      <a href="<?php echo url_for('@ar_auth_res_lookup_settings'); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-sliders-h me-1"></i><?php echo __('Lookup settings'); ?>
      </a>
    </div>
  </div>

  <!-- State KPIs -->
  <div class="row g-2 mb-3">
    <?php foreach (['pending', 'linked', 'parked', 'rejected', 'new_record_created'] as $state): ?>
      <div class="col-md col-sm-4">
        <a href="<?php echo url_for('@ar_auth_res_index?state=' . $state); ?>" class="text-decoration-none">
          <div class="card text-center border-<?php echo $stateBadges[$state] ?? 'secondary'; ?>">
            <div class="card-body py-2">
              <h4 class="mb-0"><?php echo number_format($countsByState[$state] ?? 0); ?></h4>
              <small class="text-muted"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $state))); ?></small>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body">
      <form method="get" action="<?php echo url_for('@ar_auth_res_index'); ?>" class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="form-label small mb-1"><?php echo __('Entity type'); ?></label>
          <select name="entity_type" class="form-select form-select-sm">
            <option value=""><?php echo __('All types'); ?></option>
            <?php foreach (['PERSON', 'ORG', 'GPE', 'LOC', 'PLACE'] as $t): ?>
              <option value="<?php echo $t; ?>"<?php echo ($filters['entity_type'] ?? '') === $t ? ' selected' : ''; ?>>
                <?php echo $t; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1"><?php echo __('State'); ?></label>
          <select name="state" class="form-select form-select-sm">
            <option value="pending"<?php echo ($filters['state'] ?? '') === 'pending' ? ' selected' : ''; ?>>pending</option>
            <option value="linked"<?php echo ($filters['state'] ?? '') === 'linked' ? ' selected' : ''; ?>>linked</option>
            <option value="parked"<?php echo ($filters['state'] ?? '') === 'parked' ? ' selected' : ''; ?>>parked</option>
            <option value="rejected"<?php echo ($filters['state'] ?? '') === 'rejected' ? ' selected' : ''; ?>>rejected</option>
            <option value="new_record_created"<?php echo ($filters['state'] ?? '') === 'new_record_created' ? ' selected' : ''; ?>>new_record_created</option>
            <option value="any"<?php echo ($filters['state'] ?? '') === 'any' ? ' selected' : ''; ?>><?php echo __('Any'); ?></option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1"><?php echo __('Object ID'); ?></label>
          <input type="number" name="object_id" class="form-control form-control-sm"
                 value="<?php echo $filters['object_id'] > 0 ? (int) $filters['object_id'] : ''; ?>" min="0">
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1"><?php echo __('Per page'); ?></label>
          <input type="number" name="limit" class="form-control form-control-sm"
                 value="<?php echo (int) $filters['limit']; ?>" min="10" max="200">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="fas fa-filter me-1"></i><?php echo __('Filter'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Queue -->
  <form id="ar-batch-form" method="post" action="<?php echo url_for('@ar_auth_res_batch_assign'); ?>">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><?php echo __('%1% mention(s)', ['%1%' => number_format($total)]); ?></span>
      <small class="text-muted"><?php echo __('Sorted by candidate count then id'); ?></small>
    </div>

    <!-- Select-all-matching-filter notice -->
    <div id="ar-select-all-matching" class="alert alert-secondary border-0 rounded-0 mb-0 py-2 small d-none">
      <span id="ar-select-all-text"></span>
      <a href="#" id="ar-select-all-link" class="alert-link"><?php
        echo __('Select all %1% mentions matching the current filter', ['%1%' => number_format($total)]); ?></a>
      <a href="#" id="ar-clear-all-link" class="alert-link ms-2 d-none"><?php echo __('Clear selection'); ?></a>
    </div>

    <div class="card-body p-0">
      <table class="table table-hover table-striped mb-0 align-middle">
        <thead>
          <tr>
            <th style="width:1%;">
              <input type="checkbox" class="form-check-input" id="ar-check-all"
                     title="<?php echo __('Select all on this page'); ?>">
            </th>
            <th><?php echo __('ID'); ?></th>
            <th><?php echo __('Mention'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Source IO'); ?></th>
            <th class="text-center"><?php echo __('Candidates'); ?></th>
            <th><?php echo __('State'); ?></th>
            <th><?php echo __('Assigned to'); ?></th>
            <th><?php echo __('Promoted'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows) || count($rows) === 0): ?>
            <tr><td colspan="10" class="text-center text-muted py-4"><?php echo __('No mentions match.'); ?></td></tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td>
                  <input type="checkbox" class="form-check-input ar-row-check" name="mention_ids[]"
                         value="<?php echo (int) $row->id; ?>">
                </td>
                <td class="text-muted small">#<?php echo (int) $row->id; ?></td>
                <td><strong><?php echo htmlspecialchars((string) $row->entity_value); ?></strong></td>
                <td>
                  <span class="badge bg-<?php echo $typeBadges[$row->entity_type] ?? 'secondary'; ?>">
                    <?php echo htmlspecialchars($row->entity_type); ?>
                  </span>
                </td>
                <td>
                  <?php
                    // Digital-object indicator: classify the master DO attached
                    // to the source IO (if any) as pdf / image / other so the
                    // archivist can see at a glance what is behind the record.
                    $doMime  = isset($row->do_mime_type) ? strtolower((string) $row->do_mime_type) : '';
                    $doName  = isset($row->do_name) ? (string) $row->do_name : '';
                    $doIcon  = '';
                    $doTitle = '';
                    if ($doMime !== '' || $doName !== '') {
                        $isPdf = (strpos($doMime, 'pdf') !== false)
                              || (strtolower(pathinfo($doName, PATHINFO_EXTENSION)) === 'pdf');
                        $isImg = (strpos($doMime, 'image/') === 0)
                              || in_array(strtolower(pathinfo($doName, PATHINFO_EXTENSION)),
                                          ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tif', 'tiff', 'bmp', 'svg'], true);
                        if ($isPdf) {
                            $doIcon  = 'fas fa-file-pdf text-danger';
                            $doTitle = __('PDF attached');
                        } elseif ($isImg) {
                            $doIcon  = 'fas fa-file-image text-primary';
                            $doTitle = __('Image attached');
                        } else {
                            $doIcon  = 'fas fa-file text-muted';
                            $doTitle = __('Digital object attached');
                        }
                        if ($doMime !== '') {
                            $doTitle .= ' (' . htmlspecialchars($doMime) . ')';
                        }
                    }
                  ?>
                  <?php if (!empty($row->io_slug)): ?>
                    <a href="/<?php echo htmlspecialchars((string) $row->io_slug); ?>" target="_blank" rel="noopener">
                      <?php echo htmlspecialchars($row->io_title ?: ('Object #' . (int) $row->object_id)); ?>
                      <i class="fas fa-external-link-alt fa-xs ms-1 text-muted"></i>
                    </a>
                  <?php else: ?>
                    <span class="text-muted"><?php echo htmlspecialchars($row->io_title ?: ('Object #' . (int) $row->object_id)); ?></span>
                  <?php endif; ?>
                  <?php if ($doIcon !== ''): ?>
                    <i class="<?php echo $doIcon; ?> ms-1" title="<?php echo $doTitle; ?>"
                       aria-label="<?php echo $doTitle; ?>"></i>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <span class="badge bg-<?php echo ((int) $row->candidate_count) > 0 ? 'dark' : 'light text-dark border'; ?>">
                    <?php echo (int) $row->candidate_count; ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-<?php echo $stateBadges[$row->state] ?? 'secondary'; ?>">
                    <?php echo htmlspecialchars($row->state); ?>
                  </span>
                </td>
                <td class="small">
                  <?php if (!empty($row->assigned_to_username)): ?>
                    <span class="badge bg-primary">
                      <i class="fas fa-user me-1"></i><?php echo htmlspecialchars((string) $row->assigned_to_username); ?>
                    </span>
                    <?php if (!empty($row->workflow_task_id)): ?>
                      <span class="text-muted">#<?php echo (int) $row->workflow_task_id; ?></span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-muted"><?php echo __('Unassigned'); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-muted small"><?php echo htmlspecialchars((string) $row->promoted_at); ?></td>
                <td class="text-nowrap">
                  <a href="<?php echo url_for('@ar_auth_res_review?id=' . (int) $row->id); ?>"
                     class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-search me-1"></i><?php echo __('Review'); ?>
                  </a>
                  <button type="button" class="btn btn-sm btn-outline-primary ar-row-assign"
                          data-bs-toggle="modal" data-bs-target="#ar-queue-assign-modal"
                          data-mention-id="<?php echo (int) $row->id; ?>"
                          data-mention-label="#<?php echo (int) $row->id; ?> <?php echo htmlspecialchars((string) $row->entity_value, ENT_QUOTES); ?>">
                    <i class="fas fa-user-plus me-1"></i><?php echo __('Assign'); ?>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($lastPage > 1): ?>
      <div class="card-footer">
        <nav>
          <ul class="pagination pagination-sm mb-0 justify-content-center">
            <?php for ($p = 1; $p <= $lastPage; $p++): ?>
              <li class="page-item<?php echo $p === (int) $filters['page'] ? ' active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for('@ar_auth_res_index?page=' . $p
                  . '&state=' . urlencode((string) $filters['state'])
                  . '&entity_type=' . urlencode((string) $filters['entity_type'])
                  . '&object_id=' . (int) $filters['object_id']
                  . '&limit=' . (int) $filters['limit']); ?>"><?php echo $p; ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      </div>
    <?php endif; ?>
  </div>

  <!-- Hidden inputs for "select all matching filter" - populated by JS -->
  <div id="ar-matching-ids-host" class="d-none"></div>

  </form><!-- /#ar-batch-form -->

  <!-- Sticky batch-assign bar -->
  <div id="ar-batch-bar" class="d-none" style="position:sticky;bottom:0;z-index:1020;">
    <div class="card border-primary shadow mt-2">
      <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
        <span class="fw-bold">
          <i class="fas fa-check-square me-1"></i>
          <span id="ar-batch-count">0</span> <?php echo __('selected'); ?>
        </span>
        <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
          <label for="ar-batch-archivist" class="form-label mb-0 small"><?php echo __('Assign to'); ?>:</label>
          <select id="ar-batch-archivist" class="form-select form-select-sm" style="width:auto;">
            <option value=""><?php echo __('Select an archivist...'); ?></option>
            <?php foreach (($archivists ?: []) as $a): ?>
              <option value="<?php echo (int) $a['id']; ?>"><?php echo htmlspecialchars((string) $a['display']); ?></option>
            <?php endforeach; ?>
          </select>
          <input type="text" id="ar-batch-reason" class="form-control form-control-sm" style="width:16rem;"
                 placeholder="<?php echo __('Reason / message (optional)'); ?>"
                 aria-label="<?php echo __('Reason / message (optional)'); ?>">
          <button type="button" id="ar-batch-submit" class="btn btn-sm btn-primary">
            <i class="fas fa-user-plus me-1"></i><?php echo __('Assign selected'); ?>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Single-row assign modal (queue) -->
  <div class="modal fade" id="ar-queue-assign-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="ar-queue-assign-form" method="post" action="">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-user-plus me-2"></i><?php echo __('Assign mention'); ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo __('Close'); ?>"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small mb-2">
              <i class="fas fa-tag me-1"></i><span id="ar-queue-assign-label"></span>
            </p>
            <p class="text-muted small">
              <?php echo __('Assigning routes this mention through the Workflow plugin and notifies the chosen archivist.'); ?>
            </p>
            <div class="mb-2">
              <label for="ar-queue-assign-archivist" class="form-label"><?php echo __('Archivist'); ?></label>
              <select name="archivist_user_id" id="ar-queue-assign-archivist" class="form-select" required>
                <option value=""><?php echo __('Select an archivist...'); ?></option>
                <?php foreach (($archivists ?: []) as $a): ?>
                  <option value="<?php echo (int) $a['id']; ?>"><?php echo htmlspecialchars((string) $a['display']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2">
              <label for="ar-queue-assign-reason" class="form-label"><?php echo __('Reason / message (optional)'); ?></label>
              <textarea name="reason" id="ar-queue-assign-reason" class="form-control" rows="3"
                        placeholder="<?php echo __('Add a note for the archivist...'); ?>"></textarea>
              <div class="form-text"><?php echo __('Recorded on the workflow task as the assignment comment.'); ?></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-user-plus me-1"></i><?php echo __('Assign'); ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

<?php
  // CSP nonce for the inline script (AtoM CSP pattern).
  $n = sfConfig::get('csp_nonce', '');
  $nonceAttr = $n ? ' nonce="' . preg_replace('/^nonce=/', '', $n) . '"' : '';
  // Per-mention assign URL template - the route requires a numeric :id so
  // url_for() cannot take the __ID__ token; build the path explicitly using a
  // real id then swap it for the token. The id token is replaced client-side.
  $assignUrlSample = url_for('@ar_auth_res_assign?id=0');
  $assignUrlTpl    = preg_replace('#/0/assign$#', '/__ID__/assign', $assignUrlSample);
  $matchingIds     = is_array($allMatchingIds) ? $allMatchingIds : [];
?>
<script<?php echo $nonceAttr; ?>>
(function () {
  var matchingIds = <?php echo json_encode(array_values($matchingIds)); ?>;
  var assignUrlTpl = <?php echo json_encode($assignUrlTpl); ?>;

  var form        = document.getElementById('ar-batch-form');
  var checkAll    = document.getElementById('ar-check-all');
  var rowChecks   = function () { return Array.prototype.slice.call(document.querySelectorAll('.ar-row-check')); };
  var bar         = document.getElementById('ar-batch-bar');
  var countEl     = document.getElementById('ar-batch-count');
  var batchSelect = document.getElementById('ar-batch-archivist');
  var batchSubmit = document.getElementById('ar-batch-submit');
  var notice      = document.getElementById('ar-select-all-matching');
  var noticeText  = document.getElementById('ar-select-all-text');
  var selectAll   = document.getElementById('ar-select-all-link');
  var clearAll    = document.getElementById('ar-clear-all-link');
  var idsHost     = document.getElementById('ar-matching-ids-host');

  var wholeFilterSelected = false;

  function selectedPageCount() {
    return rowChecks().filter(function (c) { return c.checked; }).length;
  }

  function clearMatchingHost() {
    if (idsHost) { idsHost.innerHTML = ''; }
  }

  function fillMatchingHost() {
    clearMatchingHost();
    if (!idsHost) { return; }
    matchingIds.forEach(function (id) {
      var i = document.createElement('input');
      i.type = 'hidden';
      i.name = 'mention_ids[]';
      i.value = String(id);
      idsHost.appendChild(i);
    });
  }

  function refresh() {
    var pageSel = selectedPageCount();
    var total   = wholeFilterSelected ? matchingIds.length : pageSel;

    if (countEl) { countEl.textContent = String(total); }
    if (bar) { bar.classList.toggle('d-none', total === 0); }

    // The "select all matching filter" prompt appears once a full page is
    // checked AND there are more matching rows than the current page.
    var pageRows = rowChecks().length;
    if (notice) {
      if (!wholeFilterSelected && pageSel === pageRows && pageRows > 0 && matchingIds.length > pageRows) {
        notice.classList.remove('d-none');
        if (noticeText) {
          noticeText.textContent = pageSel + ' <?php echo __('on this page selected.'); ?> ';
        }
        if (selectAll) { selectAll.classList.remove('d-none'); }
        if (clearAll) { clearAll.classList.add('d-none'); }
      } else if (wholeFilterSelected) {
        notice.classList.remove('d-none');
        if (noticeText) {
          noticeText.textContent = matchingIds.length + ' <?php echo __('mentions across all pages selected.'); ?> ';
        }
        if (selectAll) { selectAll.classList.add('d-none'); }
        if (clearAll) { clearAll.classList.remove('d-none'); }
      } else {
        notice.classList.add('d-none');
      }
    }
  }

  if (checkAll) {
    checkAll.addEventListener('change', function () {
      wholeFilterSelected = false;
      clearMatchingHost();
      rowChecks().forEach(function (c) { c.checked = checkAll.checked; });
      refresh();
    });
  }

  rowChecks().forEach(function (c) {
    c.addEventListener('change', function () {
      wholeFilterSelected = false;
      clearMatchingHost();
      if (!c.checked && checkAll) { checkAll.checked = false; }
      refresh();
    });
  });

  if (selectAll) {
    selectAll.addEventListener('click', function (e) {
      e.preventDefault();
      wholeFilterSelected = true;
      // Uncheck the per-page boxes so they are not double-submitted alongside
      // the hidden whole-filter inputs.
      rowChecks().forEach(function (c) { c.checked = false; });
      if (checkAll) { checkAll.checked = false; }
      fillMatchingHost();
      refresh();
    });
  }

  if (clearAll) {
    clearAll.addEventListener('click', function (e) {
      e.preventDefault();
      wholeFilterSelected = false;
      clearMatchingHost();
      rowChecks().forEach(function (c) { c.checked = false; });
      if (checkAll) { checkAll.checked = false; }
      refresh();
    });
  }

  if (batchSubmit) {
    batchSubmit.addEventListener('click', function () {
      var total = wholeFilterSelected ? matchingIds.length : selectedPageCount();
      if (total === 0) { window.alert('<?php echo __('Select at least one mention.'); ?>'); return; }
      if (!batchSelect || !batchSelect.value) {
        window.alert('<?php echo __('Choose an archivist first.'); ?>'); return;
      }
      // Mirror the chosen archivist into the form before submit.
      var hidden = document.getElementById('ar-batch-archivist-hidden');
      if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'archivist_user_id';
        hidden.id = 'ar-batch-archivist-hidden';
        form.appendChild(hidden);
      }
      hidden.value = batchSelect.value;

      // Mirror the optional reason / message (one reason for the whole batch).
      var batchReason = document.getElementById('ar-batch-reason');
      var reasonHidden = document.getElementById('ar-batch-reason-hidden');
      if (!reasonHidden) {
        reasonHidden = document.createElement('input');
        reasonHidden.type = 'hidden';
        reasonHidden.name = 'reason';
        reasonHidden.id = 'ar-batch-reason-hidden';
        form.appendChild(reasonHidden);
      }
      reasonHidden.value = batchReason ? batchReason.value : '';

      form.submit();
    });
  }

  // Per-row assign modal wiring.
  //
  // The .ar-row-assign buttons carry data-bs-toggle="modal" so Bootstrap's own
  // delegated listener opens #ar-queue-assign-modal - this works whether or not
  // window.bootstrap is exposed as a global. We only need to POPULATE the modal
  // form when it opens: the show.bs.modal event hands us event.relatedTarget,
  // the button that triggered the open, and we copy its data-attributes onto
  // the form action + label.
  var queueModalEl = document.getElementById('ar-queue-assign-modal');
  var queueForm    = document.getElementById('ar-queue-assign-form');
  var queueLabel   = document.getElementById('ar-queue-assign-label');

  if (queueModalEl) {
    queueModalEl.addEventListener('show.bs.modal', function (event) {
      var btn = event.relatedTarget;
      if (!btn) { return; }
      var id = btn.getAttribute('data-mention-id');
      if (queueForm && id) {
        queueForm.setAttribute('action', assignUrlTpl.replace('__ID__', id));
      }
      if (queueLabel) {
        queueLabel.textContent = btn.getAttribute('data-mention-label') || ('#' + id);
      }
    });
  }

  refresh();
})();
</script>

<?php end_slot(); ?>
