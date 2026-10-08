<?php decorate_with('layout_1col'); ?>
<?php use_helper('Date'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Browse accessions'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <div class="d-flex flex-wrap gap-2 mb-3">
    <?php echo get_component('search', 'inlineSearch', [
        'label' => __('Search accessions'),
        'landmarkLabel' => __('Accession'),
    ]); ?>

    <div class="d-flex flex-wrap gap-2 ms-auto">
      <?php echo get_partial('default/sortPickers', ['options' => $sortOptions]); ?>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?php echo url_for('@accession_intake_queue'); ?>" class="btn btn-sm btn-outline-primary">
      <i class="fas fa-inbox"></i> <?php echo __('Intake Queue'); ?>
    </a>
    <a href="<?php echo url_for('@accession_dashboard'); ?>" class="btn btn-sm btn-outline-secondary">
      <i class="fas fa-tachometer-alt"></i> <?php echo __('Dashboard'); ?>
    </a>
    <a href="<?php echo url_for('@accession_valuation_report'); ?>" class="btn btn-sm btn-outline-secondary">
      <i class="fas fa-chart-bar"></i> <?php echo __('Valuation Report'); ?>
    </a>
  </div>

  <?php // CAAIS 1.1 repository filter (#203). Keeps the search and sort. ?>
  <?php $repositoryOptions = $sf_data->getRaw('repositoryOptions'); ?>
  <?php if (!empty($repositoryOptions)) { ?>
    <form method="get" action="<?php echo url_for('@accession_browse_override'); ?>" class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <?php $rawRequest = sfContext::getInstance()->getRequest(); ?>
      <?php foreach (['subquery', 'sort', 'sortDir', 'limit'] as $keep) { ?>
        <?php $keepValue = $rawRequest->getParameter($keep, ''); ?>
        <?php if (is_string($keepValue) && '' !== $keepValue) { ?>
          <input type="hidden" name="<?php echo $keep; ?>" value="<?php echo htmlspecialchars($keepValue, ENT_QUOTES, 'UTF-8'); ?>">
        <?php } ?>
      <?php } ?>
      <label for="accession-repository-filter" class="form-label mb-0"><?php echo __('Repository'); ?></label>
      <select name="repository" id="accession-repository-filter" class="form-select form-select-sm w-auto">
        <option value=""><?php echo __('All repositories'); ?></option>
        <?php foreach ($repositoryOptions as $rid => $rname) { ?>
          <option value="<?php echo (int) $rid; ?>"<?php echo (int) $rid === (int) $selectedRepository ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $rname, ENT_QUOTES, 'UTF-8'); ?></option>
        <?php } ?>
      </select>
      <button type="submit" class="btn btn-sm atom-btn-white"><?php echo __('Filter'); ?></button>
    </form>
  <?php } ?>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <?php // CAAIS export of the ticked accessions; the table sits inside the form so the boxes submit with it. ?>
  <?php if ($canExportCaais) { ?>
    <form method="get" action="<?php echo url_for('@accession_caais_export'); ?>" id="caais-export-form">
  <?php } ?>
  <div class="table-responsive mb-3">
    <table class="table table-bordered mb-0">
      <thead>
        <tr>
          <?php if ($canExportCaais) { ?>
            <th>
              <input type="checkbox" class="form-check-input" id="caais-select-all" aria-label="<?php echo __('Select all on this page'); ?>">
            </th>
          <?php } ?>
          <th>
            <?php echo __('Accession number'); ?>
          </th>
          <th>
            <?php echo __('Title'); ?>
          </th>
          <th>
            <?php echo __('Repository'); ?>
          </th>
          <th>
            <?php echo __('Acquisition date'); ?>
          </th>
          <th>
            <?php echo __('Status'); ?>
          </th>
          <th>
            <?php echo __('Priority'); ?>
          </th>
          <?php if ('lastUpdated' == $sf_request->sort) { ?>
            <th>
              <?php echo __('Updated'); ?>
            </th>
          <?php } ?>
        </tr>
      </thead>
      <tbody>
        <?php $rawService = $sf_data->getRaw('browseService'); ?>
        <?php foreach ($sf_data->getRaw('pager')->getResults() as $doc) { ?>
          <?php $title = $rawService->extractI18nField($doc, 'title'); ?>
          <?php
            $statusBadge = '';
            $v2Status = $doc['v2_status'] ?? '';
            if ($v2Status) {
                $statusColors = [
                    'draft' => 'secondary',
                    'submitted' => 'info',
                    'under_review' => 'warning',
                    'accepted' => 'success',
                    'rejected' => 'danger',
                    'returned' => 'dark',
                ];
                $color = $statusColors[$v2Status] ?? 'secondary';
                $statusBadge = '<span class="badge bg-' . $color . '">' . htmlspecialchars(str_replace('_', ' ', ucfirst($v2Status))) . '</span>';
            }

            $priorityBadge = '';
            $v2Priority = $doc['v2_priority'] ?? '';
            if ($v2Priority) {
                $priorityColors = [
                    'low' => 'secondary',
                    'normal' => 'primary',
                    'high' => 'warning',
                    'urgent' => 'danger',
                ];
                $color = $priorityColors[$v2Priority] ?? 'secondary';
                $priorityBadge = '<span class="badge bg-' . $color . '">' . htmlspecialchars(ucfirst($v2Priority)) . '</span>';
            }
          ?>
          <tr>
            <?php if ($canExportCaais) { ?>
              <td>
                <input type="checkbox" class="form-check-input caais-select" name="ids[]" value="<?php echo (int) ($doc['_id'] ?? 0); ?>" aria-label="<?php echo __('Select %1%', ['%1%' => htmlspecialchars((string) ($doc['identifier'] ?? ''), ENT_QUOTES, 'UTF-8')]); ?>">
              </td>
            <?php } ?>
            <td class="w-15">
              <?php echo link_to($doc['identifier'] ?? '', '@accession_view_override?slug=' . ($doc['slug'] ?? '')); ?>
            </td>
            <td>
              <?php echo link_to(render_title($title), '@accession_view_override?slug=' . ($doc['slug'] ?? '')); ?>
            </td>
            <td class="w-15">
              <?php echo htmlspecialchars((string) ($doc['repository_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </td>
            <td class="w-15">
              <?php echo isset($doc['date']) ? format_date($doc['date'], 'i') : ''; ?>
            </td>
            <td class="w-10">
              <?php echo $statusBadge; ?>
            </td>
            <td class="w-10">
              <?php echo $priorityBadge; ?>
            </td>
            <?php if ('lastUpdated' == $sf_request->sort) { ?>
              <td class="w-15">
                <?php echo isset($doc['updatedAt']) ? format_date($doc['updatedAt'], 'f') : ''; ?>
              </td>
            <?php } ?>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
  <?php if ($canExportCaais) { ?>
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span><?php echo __('CAAIS export of the selected accessions:'); ?></span>
        <button type="submit" name="format" value="json" class="btn btn-sm atom-btn-white">JSON</button>
        <button type="submit" name="format" value="csv" class="btn btn-sm atom-btn-white">CSV</button>
        <button type="submit" name="format" value="xml" class="btn btn-sm atom-btn-white">XML</button>
        <div class="form-check ms-2">
          <input type="checkbox" class="form-check-input" name="external" value="1" id="caais-export-external">
          <label class="form-check-label" for="caais-export-external"><?php echo __('For sharing - withhold confidential sources'); ?></label>
        </div>
      </div>
    </form>
    <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
      (function () {
        var all = document.getElementById('caais-select-all');
        var form = document.getElementById('caais-export-form');
        if (!all || !form) { return; }
        all.addEventListener('change', function () {
          form.querySelectorAll('.caais-select').forEach(function (box) { box.checked = all.checked; });
        });
        // Nothing ticked would only reach a 404; say so instead.
        form.addEventListener('submit', function (e) {
          if (!form.querySelector('.caais-select:checked')) {
            e.preventDefault();
            alert(<?php echo json_encode(__('Tick at least one accession to export.'), JSON_HEX_TAG | JSON_HEX_AMP); ?>);
          }
        });
      })();
    </script>
  <?php } ?>
<?php end_slot(); ?>

<?php slot('after-content'); ?>

  <?php echo get_partial('default/pager', ['pager' => $pager]); ?>

  <section class="actions mb-3">
    <?php echo link_to(__('Add new'), '@accession_add_override', ['class' => 'btn atom-btn-outline-light']); ?>
  </section>

<?php end_slot(); ?>
