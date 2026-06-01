<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Export Library Catalogue'); ?></h1>
<?php end_slot(); ?>

<?php
  $filters = $sf_data->getRaw('filters');
  $format  = $sf_data->getRaw('format');
  $count   = (int) $sf_data->getRaw('itemCount');
  $preview = $sf_data->getRaw('preview');
  $mimeDescs = [
    'csv'    => 'text/csv — opens in Excel, Google Sheets, LibreOffice Calc',
    'bibtex' => 'application/x-bibtex — import into LaTeX, Zotero, JabRef',
    'ris'    => 'application/x-research-info-systems — import into EndNote, Mendeley, RefWorks',
  ];
  $mimeDesc = $mimeDescs[$format] ?? '';
?>

<div class="row">
  <div class="col-md-4">

    <?php /* ===== Export Form ===== */ ?>
    <div class="card mb-3">
      <div class="card-header">
        <h6 class="mb-0"><?php echo __('Export Settings'); ?></h6>
      </div>
      <div class="card-body">
        <form method="get" id="export-form">
          <?php // GET so changing the Format re-renders this page (updating the
                // about-text + preview) instead of downloading; the Download buttons
                // set download=1 to stream the file. Was method="post", which made
                // every radio change POST-download immediately and never update the view. ?>
          <input type="hidden" name="download" id="download-flag" value="">

          <?php /* Format selector */ ?>
          <div class="mb-3">
            <label class="form-label small fw-bold"><?php echo __('Format'); ?></label>
            <div class="d-flex flex-column gap-2">
              <?php foreach (['csv', 'bibtex', 'ris'] as $fmt): ?>
                <?php $isSelected = ($format === $fmt); ?>
                <label class="btn <?php echo $isSelected ? 'btn-primary' : 'btn-outline-secondary'; ?> text-start">
                  <input type="radio" name="format" value="<?php echo $fmt; ?>"
                         <?php echo $isSelected ? 'checked' : ''; ?>
                         class="d-none"
                         onchange="var d=document.getElementById('download-flag');if(d){d.value='';}this.form.submit();">
                  <i class="fas fa-<?php echo $fmt === 'csv' ? 'table' : ($fmt === 'bibtex' ? 'file-code' : 'book'); ?> me-2"></i>
                  <?php if ($fmt === 'csv'): ?>
                    <?php echo __('CSV (Spreadsheet)'); ?>
                  <?php elseif ($fmt === 'bibtex'): ?>
                    <?php echo __('BibTeX'); ?>
                  <?php else: ?>
                    <?php echo __('RIS (EndNote)'); ?>
                  <?php endif; ?>
                </label>
              <?php endforeach; ?>
            </div>
            <?php if ($mimeDesc): ?>
              <div class="form-text small text-muted mt-1"><?php echo $mimeDesc; ?></div>
            <?php endif; ?>
          </div>

          <hr>

          <?php /* Filter: search */ ?>
          <div class="mb-3">
            <label for="f-search" class="form-label small fw-bold"><?php echo __('Search (optional)'); ?></label>
            <input type="text" class="form-control" id="f-search" name="search"
                   value="<?php echo esc_entities($filters['search'] ?? ''); ?>"
                   placeholder="<?php echo __('Title, author, ISBN...'); ?>">
          </div>

          <?php /* Filter: material type */ ?>
          <div class="mb-3">
            <label for="f-mat-type" class="form-label small fw-bold"><?php echo __('Material type'); ?></label>
            <select name="material_type" id="f-mat-type" class="form-select">
              <option value="">— <?php echo __('Any'); ?> —</option>
              <?php foreach (['book','ebook','serial','journal','magazine','thesis','conference paper','video','audio','map'] as $mt): ?>
                <option value="<?php echo $mt; ?>" <?php echo ($filters['material_type'] ?? '') === $mt ? 'selected' : ''; ?>>
                  <?php echo ucfirst($mt); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php /* Filter: language */ ?>
          <div class="mb-3">
            <label for="f-lang" class="form-label small fw-bold"><?php echo __('Language'); ?></label>
            <select name="language" id="f-lang" class="form-select">
              <option value="">— <?php echo __('Any'); ?> —</option>
              <?php foreach (['English','Afrikaans','isiZulu','isiXhosa','Sepedi','Sesotho','Setswana'] as $lang): ?>
                <option value="<?php echo $lang; ?>" <?php echo ($filters['language'] ?? '') === $lang ? 'selected' : ''; ?>>
                  <?php echo $lang; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php /* Filter: date range */ ?>
          <div class="row mb-3">
            <div class="col-6">
              <label for="f-date-from" class="form-label small fw-bold"><?php echo __('From year'); ?></label>
              <input type="text" name="date_from" id="f-date-from" class="form-control"
                     value="<?php echo esc_entities($filters['date_from'] ?? ''); ?>" placeholder="1900">
            </div>
            <div class="col-6">
              <label for="f-date-to" class="form-label small fw-bold"><?php echo __('To year'); ?></label>
              <input type="text" name="date_to" id="f-date-to" class="form-control"
                     value="<?php echo esc_entities($filters['date_to'] ?? ''); ?>" placeholder="<?php echo date('Y'); ?>">
            </div>
          </div>

          <?php /* Filter: publisher */ ?>
          <div class="mb-3">
            <label for="f-publisher" class="form-label small fw-bold"><?php echo __('Publisher (contains)'); ?></label>
            <input type="text" class="form-control" id="f-publisher" name="publisher"
                   value="<?php echo esc_entities($filters['publisher'] ?? ''); ?>">
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-success"
                    onclick="var d=document.getElementById('download-flag');if(d){d.value='1';}">
              <i class="fas fa-download me-1"></i>
              <?php echo __('Download %format%', ['%format%' => strtoupper($format)]); ?>
              <?php if ($count > 0): ?>
                <span class="badge bg-light text-dark ms-1"><?php echo $count; ?> <?php echo __('item(s)'); ?></span>
              <?php endif; ?>
            </button>
            <a href="<?php echo url_for(['module' => 'library', 'action' => 'export']); ?>"
               class="btn btn-outline-secondary">
              <?php echo __('Clear filters'); ?>
            </a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body small">
        <p class="text-muted mb-1"><strong><?php echo __('About %format% export', ['%format%' => strtoupper($format)]); ?></strong></p>
        <?php if ($format === 'csv'): ?>
          <p class="text-muted mb-0">Comma-separated values — open in any spreadsheet application. UTF-8 encoded.</p>
        <?php elseif ($format === 'bibtex'): ?>
          <p class="text-muted mb-0">BibTeX bibliography entries — compatible with LaTeX, Zotero, JabRef, and most reference managers.</p>
        <?php else: ?>
          <p class="text-muted mb-0">RIS format — supported by EndNote, Mendeley, RefWorks, Zotero, and most academic reference managers.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <?php if ($count > 0): ?>
      <div class="alert alert-info d-flex align-items-center mb-3">
        <i class="fas fa-info-circle me-2"></i>
        <?php echo __('Export will include %count% item(s).', ['%count%' => $count]); ?>
        <?php if (empty($filters)): ?>
          <?php echo __('The entire catalogue will be exported.'); ?>
        <?php else: ?>
          <?php echo __('Adjust filters above to refine the export.'); ?>
        <?php endif; ?>
      </div>

      <?php if (!empty($preview)): ?>
        <h5 class="mb-2"><?php echo __('Preview (first %n% rows)', ['%n%' => count($preview)]); ?></h5>
        <div class="table-responsive">
          <table class="table table-sm table-bordered small">
            <thead class="table-light">
              <tr>
                <?php
                  $first = $preview[0];
                  foreach (array_keys($first) as $col):
                    if (in_array($col, ['id','information_object_id','io_id','library_item_id'])) continue;
                ?>
                  <th><?php echo esc_entities($col); ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($preview as $row): ?>
                <tr>
                  <?php foreach ($row as $col => $val): ?>
                    <?php if (in_array($col, ['id','information_object_id','io_id','library_item_id'])) continue; ?>
                    <td><?php echo esc_entities((string) ($val ?? '')); ?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <div class="mt-3">
        <button type="submit" form="export-form" class="btn btn-success btn-lg"
                onclick="var d=document.getElementById('download-flag');if(d){d.value='1';}">
          <i class="fas fa-download me-2"></i>
          <?php echo __('Download %format% (%count% items)', ['%format%' => strtoupper($format), '%count%' => $count]); ?>
        </button>
      </div>

    <?php else: ?>
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php if (!empty($filters)): ?>
          <?php echo __('No items match the current filters. Try broadening your search.'); ?>
        <?php else: ?>
          <?php echo __('No library items found. The catalogue may be empty.'); ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
