<?php use_helper('Url'); ?>

<div class="container-fluid py-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for('workflow/dashboard'); ?>">Workflow</a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for("workflow/publish-readiness/{$objectId}"); ?>">Publish Readiness</a></li>
      <li class="breadcrumb-item active">Preview Public View</li>
    </ol>
  </nav>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-eye me-2"></i>Publish Preview — What the Public Will See</h1>
    <a href="<?php echo url_for("workflow/publish-readiness/{$objectId}"); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i>Back to Readiness
    </a>
  </div>

  <?php if (isset($preview['error'])): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($preview['error']); ?></div>
  <?php else: ?>

    <div class="alert alert-info">
      <i class="fas fa-info-circle me-2"></i>
      This is a simulation of how this record would appear to public users after publishing.
    </div>

    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <h4 class="mb-0"><?php echo htmlspecialchars($preview['title'] ?? 'Untitled'); ?></h4>
      </div>
      <div class="card-body">

        <div class="row mb-3">
          <?php if (!empty($preview['identifier'])): ?>
            <div class="col-md-4">
              <strong>Identifier</strong><br>
              <?php echo htmlspecialchars($preview['identifier']); ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($preview['level'])): ?>
            <div class="col-md-4">
              <strong>Level of Description</strong><br>
              <?php echo htmlspecialchars($preview['level']); ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($preview['repository'])): ?>
            <div class="col-md-4">
              <strong>Repository</strong><br>
              <?php echo htmlspecialchars($preview['repository']); ?>
            </div>
          <?php endif; ?>
        </div>

        <?php if (!empty($preview['scope_and_content'])): ?>
          <div class="mb-3">
            <strong>Scope and Content</strong>
            <div class="border rounded p-3 bg-light mt-1"><?php echo $preview['scope_and_content']; ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($preview['extent_and_medium'])): ?>
          <div class="mb-3">
            <strong>Extent and Medium</strong><br>
            <?php echo htmlspecialchars($preview['extent_and_medium']); ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($preview['archival_history'])): ?>
          <div class="mb-3">
            <strong>Archival History</strong>
            <div class="border rounded p-3 bg-light mt-1"><?php echo $preview['archival_history']; ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($preview['arrangement'])): ?>
          <div class="mb-3">
            <strong>Arrangement</strong>
            <div class="border rounded p-3 bg-light mt-1"><?php echo $preview['arrangement']; ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($preview['access_conditions'])): ?>
          <div class="mb-3">
            <strong>Conditions Governing Access</strong><br>
            <?php echo htmlspecialchars($preview['access_conditions']); ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($preview['reproduction_conditions'])): ?>
          <div class="mb-3">
            <strong>Conditions Governing Reproduction</strong><br>
            <?php echo htmlspecialchars($preview['reproduction_conditions']); ?>
          </div>
        <?php endif; ?>

        <!-- Dates -->
        <?php if (!empty($preview['dates'])): ?>
          <div class="mb-3">
            <strong>Dates</strong>
            <ul class="list-unstyled mt-1">
              <?php foreach ($preview['dates'] as $d): ?>
                <li>
                  <?php echo htmlspecialchars($d->date ?? ''); ?>
                  <?php if (!empty($d->start_date)): ?>
                    (<?php echo htmlspecialchars($d->start_date); ?>
                    <?php if (!empty($d->end_date)): ?> — <?php echo htmlspecialchars($d->end_date); ?><?php endif; ?>)
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- Digital Objects -->
        <?php if (!empty($preview['digital_objects'])): ?>
          <div class="mb-3">
            <strong>Digital Objects</strong>
            <span class="badge bg-info"><?php echo count($preview['digital_objects']); ?></span>
            <div class="table-responsive mt-1">
              <table class="table table-sm table-bordered">
                <thead class="table-light">
                  <tr><th>Name</th><th>Type</th><th>Size</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($preview['digital_objects'] as $do): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($do->name ?? 'Unknown'); ?></td>
                      <td><?php echo htmlspecialchars($do->mime_type ?? ''); ?></td>
                      <td><?php echo $do->byte_size ? number_format($do->byte_size / 1024, 1) . ' KB' : ''; ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php else: ?>
          <div class="mb-3">
            <strong>Digital Objects</strong>
            <p class="text-muted">No digital objects attached</p>
          </div>
        <?php endif; ?>

        <!-- Rights -->
        <?php if (!empty($preview['rights'])): ?>
          <div class="mb-3">
            <strong>Rights Statements</strong>
            <span class="badge bg-info"><?php echo count($preview['rights']); ?></span>
            <ul class="list-group mt-1">
              <?php foreach ($preview['rights'] as $right): ?>
                <li class="list-group-item">
                  <?php echo htmlspecialchars($right->rights_note ?? $right->copyright_note ?? $right->license_note ?? 'Rights statement'); ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php else: ?>
          <div class="mb-3">
            <strong>Rights Statements</strong>
            <p class="text-muted">No rights statements assigned</p>
          </div>
        <?php endif; ?>

      </div>
    </div>

  <?php endif; ?>
</div>
