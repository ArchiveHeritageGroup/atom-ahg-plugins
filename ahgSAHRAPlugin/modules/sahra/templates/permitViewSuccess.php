<?php use_helper('Date'); ?>
<?php $p = $permit; ?>

<div class="container">
  <div class="row">
    <div class="col-lg-10 mx-auto">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for($isApplicant ? '@sahra_my' : '@sahra_permits'); ?>">Permits</a></li>
          <li class="breadcrumb-item active"><?php echo htmlspecialchars($p->application_ref); ?></li>
        </ol>
      </nav>

      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-landmark me-2"></i><?php echo htmlspecialchars($p->project_title); ?></h4>
          <?php echo include_partial('sahra/statusBadge', ['status' => $p->status]); ?>
        </div>
        <div class="card-body">

          <!-- workflow progress -->
          <?php
            $steps = ['pending_supervisor' => 'Applied', 'supervisor_approved' => 'Endorsed', 'submitted_to_sahra' => 'With SAHRA', 'active' => 'Issued'];
            $order = ['pending_supervisor', 'supervisor_approved', 'submitted_to_sahra', 'active'];
            $curIdx = array_search($p->status, $order, true);
            if ($p->status === 'sahra_issued') { $curIdx = 3; }
            if ($curIdx === false) { $curIdx = ($p->status === 'supervisor_rejected' || $p->status === 'sahra_rejected' || $p->status === 'revoked' || $p->status === 'closed' || $p->status === 'expired') ? -1 : 0; }
          ?>
          <div class="d-flex justify-content-between text-center mb-4">
            <?php $i = 0; foreach ($steps as $key => $lbl): ?>
              <div class="flex-fill">
                <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center <?php echo ($curIdx >= $i) ? 'bg-success text-white' : 'bg-light text-muted'; ?>" style="width:38px;height:38px;">
                  <?php echo ($curIdx > $i) ? '<i class="fas fa-check"></i>' : ($i + 1); ?>
                </div>
                <small class="<?php echo ($curIdx >= $i) ? 'fw-bold' : 'text-muted'; ?>"><?php echo $lbl; ?></small>
              </div>
              <?php if ($i < count($steps) - 1): ?><div class="align-self-center flex-fill border-top mx-1" style="height:1px;"></div><?php endif; ?>
            <?php $i++; endforeach; ?>
          </div>

          <?php if (in_array($p->status, ['supervisor_rejected', 'sahra_rejected', 'revoked', 'closed'], true)): ?>
            <div class="alert alert-danger py-2"><i class="fas fa-info-circle me-1"></i>
              This application is <strong><?php echo htmlspecialchars(\AhgSAHRA\Services\SahraPermitService::STATUS_LABELS[$p->status] ?? $p->status); ?></strong>.
            </div>
          <?php endif; ?>

          <!-- details -->
          <dl class="row mb-0">
            <dt class="col-sm-3">Application ref</dt><dd class="col-sm-9"><?php echo htmlspecialchars($p->application_ref); ?></dd>
            <?php if ($p->sahra_permit_number): ?><dt class="col-sm-3">SAHRA permit no.</dt><dd class="col-sm-9"><strong><?php echo htmlspecialchars($p->sahra_permit_number); ?></strong></dd><?php endif; ?>
            <dt class="col-sm-3">Permit type</dt><dd class="col-sm-9"><?php echo htmlspecialchars($sections[$p->nhra_section] ?? $p->nhra_section); ?></dd>
            <dt class="col-sm-3">Issuing authority</dt><dd class="col-sm-9"><?php echo htmlspecialchars($p->issuing_authority); ?></dd>
            <dt class="col-sm-3">Applicant</dt><dd class="col-sm-9"><?php echo htmlspecialchars($p->applicant_name ?? $p->applicant_username ?? '-'); ?><?php echo $p->applicant_email ? ' &lt;' . htmlspecialchars($p->applicant_email) . '&gt;' : ''; ?><?php echo $p->institution ? ' - ' . htmlspecialchars($p->institution) : ''; ?></dd>
            <dt class="col-sm-3">Supervisor</dt><dd class="col-sm-9"><?php echo htmlspecialchars($p->supervisor_name ?? $p->supervisor_username ?? '-'); ?></dd>
            <?php if ($p->site_name || $p->site_location || $p->province || $p->linked_object_id): ?>
              <dt class="col-sm-3">Site</dt>
              <dd class="col-sm-9">
                <?php if ($p->linked_object_id && !empty($siteSlug)): ?>
                  <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $siteSlug]); ?>"><?php echo htmlspecialchars($p->site_name ?? ('Record #' . $p->linked_object_id)); ?></a>
                <?php elseif ($p->site_name): ?>
                  <?php echo htmlspecialchars($p->site_name); ?>
                <?php endif; ?>
                <?php $extra = array_filter([$p->site_location, $p->province]); ?>
                <?php if ($extra): ?><span class="text-muted"> - <?php echo htmlspecialchars(implode(' - ', $extra)); ?></span><?php endif; ?>
              </dd>
              <?php if (!empty($areas)): ?>
                <dt class="col-sm-3">Dig areas</dt>
                <dd class="col-sm-9">
                  <?php foreach ($areas as $a): ?>
                    <span class="badge bg-light text-dark border me-1 mb-1"><i class="fas fa-map-pin me-1 text-muted"></i><?php echo htmlspecialchars($a->object_title ?? ('#' . $a->object_id)); ?></span>
                  <?php endforeach; ?>
                </dd>
              <?php endif; ?>
            <?php endif; ?>
            <?php if ($p->start_date || $p->end_date): ?>
              <dt class="col-sm-3">Validity</dt><dd class="col-sm-9"><?php echo $p->start_date ? date('j M Y', strtotime($p->start_date)) : '?'; ?> &ndash; <?php echo $p->end_date ? date('j M Y', strtotime($p->end_date)) : '?'; ?></dd>
            <?php endif; ?>
            <?php if ($p->project_description): ?>
              <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($p->project_description)); ?></dd>
            <?php endif; ?>
            <?php if ($p->conditions): ?>
              <dt class="col-sm-3">Conditions</dt><dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($p->conditions)); ?></dd>
            <?php endif; ?>
            <?php if ($p->sahra_reference): ?>
              <dt class="col-sm-3">SAHRA reference</dt><dd class="col-sm-9"><?php echo htmlspecialchars($p->sahra_reference); ?></dd>
            <?php endif; ?>
            <?php if ($p->supervisor_notes): ?>
              <dt class="col-sm-3">Supervisor notes</dt><dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($p->supervisor_notes)); ?></dd>
            <?php endif; ?>
            <?php if ($p->sahra_notes): ?>
              <dt class="col-sm-3">SAHRA notes</dt><dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($p->sahra_notes)); ?></dd>
            <?php endif; ?>
          </dl>
        </div>

        <!-- workflow actions -->
        <?php if ($canEndorse || $canSubmit || $canDecide || ($isApplicant && in_array($p->status, ['pending_supervisor', 'supervisor_rejected'], true))): ?>
        <div class="card-footer bg-light">

          <?php if ($canEndorse): ?>
            <div class="row g-2">
              <div class="col-md-6">
                <form method="post" action="<?php echo url_for(['module' => 'sahra', 'action' => 'endorse', 'id' => $p->id]); ?>">
                  <div class="input-group">
                    <input type="text" name="notes" class="form-control" placeholder="Endorsement notes (optional)">
                    <button class="btn btn-success" onclick="return confirm('Endorse this application?');"><i class="fas fa-check me-1"></i> Endorse</button>
                  </div>
                </form>
              </div>
              <div class="col-md-6">
                <form method="post" action="<?php echo url_for(['module' => 'sahra', 'action' => 'reject', 'id' => $p->id]); ?>">
                  <div class="input-group">
                    <input type="text" name="notes" class="form-control" placeholder="Reason for returning (required)" required>
                    <button class="btn btn-outline-danger"><i class="fas fa-times me-1"></i> Return</button>
                  </div>
                </form>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($canSubmit): ?>
            <form method="post" action="<?php echo url_for(['module' => 'sahra', 'action' => 'submitToSahra', 'id' => $p->id]); ?>">
              <label class="form-label mb-1"><strong>Lodge with SAHRA</strong></label>
              <div class="input-group">
                <input type="text" name="sahra_reference" class="form-control" placeholder="SAHRA case / reference no. (if known)">
                <input type="text" name="notes" class="form-control" placeholder="Notes (optional)">
                <button class="btn btn-primary" onclick="return confirm('Mark this application as lodged with SAHRA?');"><i class="fas fa-paper-plane me-1"></i> Mark as submitted</button>
              </div>
            </form>
          <?php endif; ?>

          <?php if ($canDecide): ?>
            <form method="post" action="<?php echo url_for(['module' => 'sahra', 'action' => 'recordDecision', 'id' => $p->id]); ?>" data-sahra-decision>
              <label class="form-label mb-1"><strong>Record SAHRA's decision</strong></label>
              <div class="mb-2">
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="outcome" id="outcome_issued" value="issued" data-decision-outcome checked>
                  <label class="form-check-label" for="outcome_issued">Permit issued</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="outcome" id="outcome_rejected" value="rejected" data-decision-outcome>
                  <label class="form-check-label" for="outcome_rejected">Declined</label>
                </div>
              </div>
              <div data-decision-issued>
                <div class="row g-2 mb-2">
                  <div class="col-md-4"><input type="text" name="sahra_permit_number" class="form-control" placeholder="SAHRA permit number"></div>
                  <div class="col-md-4"><input type="date" name="start_date" class="form-control" title="Valid from"></div>
                  <div class="col-md-4"><input type="date" name="end_date" class="form-control" title="Valid until"></div>
                </div>
                <textarea name="conditions" class="form-control mb-2" rows="2" placeholder="Permit conditions imposed by SAHRA"></textarea>
              </div>
              <textarea name="sahra_notes" class="form-control mb-2" rows="2" placeholder="Notes / reason"></textarea>
              <button class="btn btn-success"><i class="fas fa-save me-1"></i> Record decision</button>
            </form>
          <?php endif; ?>

          <?php if ($isApplicant && in_array($p->status, ['pending_supervisor', 'supervisor_rejected'], true)): ?>
            <form method="post" action="<?php echo url_for(['module' => 'sahra', 'action' => 'cancel', 'id' => $p->id]); ?>" class="mt-2">
              <button class="btn btn-sm btn-outline-secondary" onclick="return confirm('Cancel this application?');"><i class="fas fa-ban me-1"></i> Cancel application</button>
            </form>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- reporting obligations -->
      <?php if (in_array($p->status, ['sahra_issued', 'active', 'expired', 'closed'], true) || !empty($reports)): ?>
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong><i class="fas fa-file-alt me-1"></i> Reporting obligations</strong>
        </div>
        <div class="card-body">
          <?php if (empty($reports)): ?>
            <p class="text-muted mb-3">No reporting obligations recorded.</p>
          <?php else: ?>
            <table class="table table-sm">
              <thead><tr><th>Type</th><th>Due</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($reports as $r): ?>
                  <?php $overdue = ($r->status === 'pending' && $r->due_date && strtotime($r->due_date) < time()); ?>
                  <tr class="<?php echo $overdue ? 'table-danger' : ''; ?>">
                    <td><?php echo ucfirst(htmlspecialchars($r->report_type)); ?></td>
                    <td><?php echo $r->due_date ? date('j M Y', strtotime($r->due_date)) : '-'; ?></td>
                    <td><span class="badge bg-<?php echo $r->status === 'submitted' || $r->status === 'accepted' ? 'success' : ($overdue ? 'danger' : 'warning text-dark'); ?>"><?php echo $overdue ? 'Overdue' : ucfirst($r->status); ?></span></td>
                    <td><?php echo $r->submitted_date ? date('j M Y', strtotime($r->submitted_date)) : '-'; ?></td>
                    <td>
                      <?php if ($r->status === 'pending'): ?>
                        <form method="post" action="<?php echo url_for(['module' => 'sahra', 'action' => 'reportSubmit', 'id' => $r->id]); ?>" class="d-inline">
                          <button class="btn btn-sm btn-outline-success" onclick="return confirm('Mark this report as submitted?');">Mark submitted</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>

          <?php if ($canDecide || $canSubmit || (isset($p->status) && in_array($p->status, ['sahra_issued', 'active'], true))): ?>
            <form method="post" action="<?php echo url_for(['module' => 'sahra', 'action' => 'reportAdd', 'id' => $p->id]); ?>" class="row g-2 align-items-end mt-1">
              <div class="col-md-3">
                <label class="form-label mb-0 small">Add obligation</label>
                <select name="report_type" class="form-select form-select-sm">
                  <option value="interim">Interim report</option>
                  <option value="final">Final report</option>
                  <option value="annual">Annual report</option>
                  <option value="fieldwork">Fieldwork report</option>
                </select>
              </div>
              <div class="col-md-3"><input type="date" name="due_date" class="form-control form-control-sm" title="Due date"></div>
              <div class="col-md-4"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
              <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Add</button></div>
            </form>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- admin: revoke -->
      <?php if ($canDecide === false && in_array($p->status, ['sahra_issued', 'active'], true) && $sf_user->hasCredential('administrator')): ?>
        <form method="post" action="<?php echo url_for(['module' => 'sahra', 'action' => 'revoke', 'id' => $p->id]); ?>" class="mb-4">
          <div class="input-group">
            <input type="text" name="notes" class="form-control" placeholder="Reason for revocation">
            <button class="btn btn-outline-danger" onclick="return confirm('Revoke this permit?');"><i class="fas fa-ban me-1"></i> Revoke permit</button>
          </div>
        </form>
      <?php endif; ?>

      <!-- documents -->
      <div class="card mb-4">
        <div class="card-header"><strong><i class="fas fa-paperclip me-1"></i> Documents</strong></div>
        <div class="card-body">
          <?php if (empty($documents)): ?>
            <p class="text-muted mb-3">No documents attached.</p>
          <?php else: ?>
            <ul class="list-group mb-3">
              <?php foreach ($documents as $d): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <a href="<?php echo url_for(['module' => 'sahra', 'action' => 'documentDownload', 'id' => $d->id]); ?>"><i class="fas fa-file me-1"></i><?php echo htmlspecialchars($d->original_name); ?></a>
                    <small class="text-muted ms-2"><?php echo ucfirst(str_replace('_', ' ', $d->doc_type)); ?> &middot; <?php echo number_format(max(1, $d->size_bytes / 1024), 0); ?> KB</small>
                  </div>
                  <?php if ($isApplicant || $sf_user->hasCredential('administrator')): ?>
                    <form method="post" action="<?php echo url_for(['module' => 'sahra', 'action' => 'documentDelete', 'id' => $d->id]); ?>" class="d-inline">
                      <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this document?');"><i class="fas fa-trash"></i></button>
                    </form>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <?php if ($canUpload): ?>
            <form method="post" action="<?php echo url_for(['module' => 'sahra', 'action' => 'documentUpload', 'id' => $p->id]); ?>" enctype="multipart/form-data" class="row g-2 align-items-end">
              <div class="col-md-4">
                <label class="form-label mb-0 small">Type</label>
                <select name="doc_type" class="form-select form-select-sm">
                  <option value="supporting">Supporting</option>
                  <option value="application">Application form</option>
                  <option value="method_statement">Method statement</option>
                  <option value="cv">CV</option>
                  <option value="permit_certificate">Permit certificate</option>
                  <option value="report">Report</option>
                  <option value="correspondence">Correspondence</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="col-md-6"><input type="file" name="documents[]" class="form-control form-control-sm" multiple required></div>
              <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-upload me-1"></i>Upload</button></div>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- workflow log -->
      <?php if (!empty($log)): ?>
      <div class="card">
        <div class="card-header"><strong><i class="fas fa-history me-1"></i> Workflow history</strong></div>
        <ul class="list-group list-group-flush">
          <?php foreach ($log as $entry): ?>
            <li class="list-group-item d-flex justify-content-between">
              <div>
                <span class="badge bg-secondary me-2"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $entry->action))); ?></span>
                <?php if ($entry->notes): ?><span class="text-muted"><?php echo htmlspecialchars($entry->notes); ?></span><?php endif; ?>
              </div>
              <small class="text-muted"><?php echo htmlspecialchars($entry->actor_username ?? 'System'); ?> - <?php echo date('j M Y H:i', strtotime($entry->created_at)); ?></small>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
