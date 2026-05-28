<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Bulk Import'); ?></h1>
<?php end_slot(); ?>

<div class="row">
  <div class="col-md-8">

    <?php /* ============================================================
         ERROR DISPLAY
         ============================================================ */ ?>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo esc_entities($error); ?>
      </div>
    <?php endif; ?>

    <?php /* ============================================================
         RESULT DISPLAY
         ============================================================ */ ?>
    <?php
      $result = $sf_data->getRaw('result');
      $format = $sf_data->getRaw('format');
      $dryRun = $sf_data->getRaw('dryRun');
    ?>
    <?php if (!empty($result)): ?>
      <?php
        $imported = (int) ($result['imported'] ?? 0);
        $skipped  = (int) ($result['skipped'] ?? 0);
        $errors   = $result['errors'] ?? [];
        $rows     = $result['results'] ?? [];
        $file     = $result['file'] ?? '';
        $isDryRun = !empty($result['dry_run']);
      ?>

      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">
            <?php echo __('Import complete'); ?>
            <?php if ($isDryRun): ?>
              <span class="badge bg-warning text-dark ms-2"><?php echo __('Dry run — no changes saved'); ?></span>
            <?php endif; ?>
          </h5>
        </div>
        <div class="card-body">
          <div class="row text-center mb-3">
            <div class="col">
              <div class="fs-3 fw-bold text-success"><?php echo $imported; ?></div>
              <div class="text-muted small"><?php echo __('Imported'); ?></div>
            </div>
            <div class="col">
              <div class="fs-3 fw-bold text-warning"><?php echo $skipped; ?></div>
              <div class="text-muted small"><?php echo __('Skipped / Errored'); ?></div>
            </div>
            <div class="col">
              <div class="fs-3 fw-bold"><?php echo $imported + $skipped; ?></div>
              <div class="text-muted small"><?php echo __('Total rows'); ?></div>
            </div>
          </div>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-warning mb-3">
              <strong><?php echo __('Warnings / Errors'); ?></strong>
              <ul class="mb-0 small">
                <?php foreach (array_slice($errors, 0, 50) as $err): ?>
                  <li><?php echo esc_entities($err); ?></li>
                <?php endforeach; ?>
                <?php if (count($errors) > 50): ?>
                  <li class="text-muted">... and <?php echo count($errors) - 50; ?> more</li>
                <?php endif; ?>
              </ul>
            </div>
          <?php endif; ?>

          <?php if (!empty($rows)): ?>
            <details class="mb-3">
              <summary class="text-primary" style="cursor:pointer;"><?php echo __('Preview results (%1%)', ['%1%' => count($rows)]); ?></summary>
              <div class="table-responsive mt-2">
                <table class="table table-sm table-hover">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th><?php echo __('Row'); ?></th>
                      <th><?php echo __('Title'); ?></th>
                      <th><?php echo __('ISBN'); ?></th>
                      <th><?php echo __('Status'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach (array_slice($rows, 0, 100) as $i => $r): ?>
                      <tr class="<?php echo ($r['action'] ?? '') === 'error' ? 'table-danger' : ''; ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo esc_entities($r['row'] ?? ''); ?></td>
                        <td><?php echo esc_entities(mb_strimwidth($r['title'] ?? '', 0, 60, '...')); ?></td>
                        <td><code><?php echo esc_entities($r['isbn'] ?? ''); ?></code></td>
                        <td>
                          <?php if (($r['action'] ?? '') === 'error'): ?>
                            <span class="badge bg-danger"><?php echo __('Error'); ?></span>
                          <?php elseif ($isDryRun): ?>
                            <span class="badge bg-warning text-dark"><?php echo __('Would import'); ?></span>
                          <?php else: ?>
                            <span class="badge bg-success"><?php echo __('OK'); ?></span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (count($rows) > 100): ?>
                      <tr><td colspan="5" class="text-center text-muted">
                        <?php echo __('Showing 100 of %1% results', ['%1%' => count($rows)]); ?>
                      </td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </details>
          <?php endif; ?>

          <a href="<?php echo url_for(['module' => 'acquisition', 'action' => 'bulk-import']); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Import more'); ?>
          </a>
          <?php if (!$isDryRun && $imported > 0): ?>
            <a href="<?php echo url_for(['module' => 'library', 'action' => 'browse']); ?>" class="btn btn-primary ms-2">
              <i class="fas fa-book me-1"></i><?php echo __('View catalogue'); ?>
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php /* ============================================================
         UPLOAD FORM
         ============================================================ */ ?>
    <?php if (empty($result)): ?>
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><?php echo __('Upload file for bulk import'); ?></h5>
        </div>
        <div class="card-body">
          <form method="post" enctype="multipart/form-data" id="bulk-import-form">

            <div class="mb-3">
              <label for="import-file" class="form-label">
                <?php echo __('File'); ?> <span class="text-danger">*</span>
              </label>
              <input type="file"
                     name="file"
                     id="import-file"
                     class="form-control"
                     accept=".csv,.xml,.txt"
                     required>
              <div class="form-text">
                <?php echo __('Supported formats: CSV (semicolon-delimited), MARCXML (.xml).'); ?>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-6">
                <label for="import-format" class="form-label"><?php echo __('Format'); ?></label>
                <select name="format" id="import-format" class="form-select">
                  <option value=""><?php echo __('Auto-detect from extension'); ?></option>
                  <option value="csv">CSV</option>
                  <option value="marcxml">MARCXML</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="delimiter" class="form-label"><?php echo __('CSV delimiter'); ?></label>
                <select name="delimiter" id="delimiter" class="form-select">
                  <option value=";">Semicolon (;) — default</option>
                  <option value=",">Comma (,)</option>
                  <option value="\t">Tab</option>
                </select>
              </div>
            </div>

            <div class="alert alert-info small mb-3">
              <strong><?php echo __('CSV expected columns'); ?></strong>:
              title, author, isbn, issn, doi, lccn, oclc_number,
              publisher, publication_date, publication_place, edition_statement,
              material_type, language, call_number, dewey_decimal,
              pagination, description, subjects (semicolon-separated),
              barcode, copy_count, location
            </div>

            <div class="mb-3 form-check">
              <input type="checkbox"
                     name="dry_run"
                     id="dry-run"
                     value="1"
                     class="form-check-input">
              <label for="dry-run" class="form-check-label">
                <?php echo __('Dry run — preview changes without saving'); ?>
              </label>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary" id="submit-btn">
                <i class="fas fa-upload me-1"></i>
                <?php echo __('Start Import'); ?>
              </button>
              <a href="<?php echo url_for(['module' => 'acquisition', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
                <?php echo __('Cancel'); ?>
              </a>
            </div>

          </form>
        </div>
      </div>

      <?php /* Download sample */ ?>
      <div class="card mt-3">
        <div class="card-body">
          <h6 class="mb-2"><?php echo __('Sample CSV template'); ?></h6>
          <p class="text-muted small mb-2">
            <?php echo __('Download a sample CSV file with the required column headers to use as a template.'); ?>
          </p>
          <a href="<?php echo url_for(['module' => 'acquisition', 'action' => 'bulk-import-sample']); ?>"
             class="btn btn-sm btn-outline-info">
            <i class="fas fa-download me-1"></i><?php echo __('Download sample CSV'); ?>
          </a>
        </div>
      </div>
    <?php endif; ?>

  </div>

  <?php /* ============================================================
       SIDEBAR — help
       ============================================================ */ ?>
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><?php echo __('Import help'); ?></h6>
      </div>
      <div class="card-body small">
        <h6><?php echo __('CSV format'); ?></h6>
        <ul>
          <li>First row must be column headers</li>
          <li>Semicolon (<code>;</code>) is the default delimiter</li>
          <li><code>title</code> is required; rows without a title are skipped</li>
          <li><code>subjects</code> accepts semicolon-separated values</li>
          <li>Multiple authors separated by semicolons</li>
          <li>ISBN-13 preferred for books</li>
        </ul>

        <h6 class="mt-3"><?php echo __('MARCXML format'); ?></h6>
        <ul>
          <li>Standard MARC 21 XML (LOC namespace)</li>
          <li>Each <code>&lt;record&gt;</code> becomes one item</li>
          <li>Leader and control fields mapped automatically</li>
        </ul>

        <h6 class="mt-3"><?php echo __('Behaviour'); ?></h6>
        <ul>
          <li>If ISBN matches an existing item, the item is updated (not duplicated)</li>
          <li>Use <strong>Dry run</strong> to preview before committing changes</li>
          <li>Copies are created automatically if <code>copy_count</code> column is present</li>
        </ul>
      </div>
    </div>
  </div>
</div>
