<?php decorate_with('layout_2col'); ?>

<?php slot('title'); ?>
  <div class="d-flex justify-content-between align-items-center">
    <h1><?php echo __('Browse Donor Report'); ?></h1>
    <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
  </div>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>

  <section class="sidebar-widget">

    <h4><?php echo __('Filter options'); ?></h4>

    <form action="<?php echo url_for(['module' => 'reports', 'action' => 'reportDonor']); ?>" method="get">

      <div class="form-item mb-3">
        <label class="form-label"><?php echo __('Culture'); ?></label>
        <select name="culture" class="form-select form-select-sm">
          <option value="en" <?php echo ($culture ?? 'en') == 'en' ? 'selected' : ''; ?>>English</option>
          <option value="fr" <?php echo ($culture ?? '') == 'fr' ? 'selected' : ''; ?>>French</option>
          <option value="af" <?php echo ($culture ?? '') == 'af' ? 'selected' : ''; ?>>Afrikaans</option>
        </select>
      </div>

      <div class="form-item mb-3">
        <label class="form-label"><?php echo __('Date start'); ?></label>
        <input type="date" name="dateStart" class="form-control form-control-sm" value="<?php echo $sf_request->getParameter('dateStart', ''); ?>">
      </div>

      <div class="form-item mb-3">
        <label class="form-label"><?php echo __('Date end'); ?></label>
        <input type="date" name="dateEnd" class="form-control form-control-sm" value="<?php echo $sf_request->getParameter('dateEnd', ''); ?>">
      </div>

      <div class="form-item mb-3">
        <label class="form-label"><?php echo __('Results per page'); ?></label>
        <select name="limit" class="form-select form-select-sm">
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
          <option value="500">500</option>
        </select>
      </div>

      <section class="mb-3">
        <button class="btn btn-primary w-100" type="submit"><i class="fas fa-search me-1"></i><?php echo __('Search'); ?></button>
      </section>

      <div>
        <button type="button" onclick="exportTableToCSV()" class="btn btn-success w-100">
          <i class="fa fa-download me-1"></i> <?php echo __('Export CSV'); ?>
        </button>
      </div>

    </form>

  </section>

<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if (isset($donors) && count($donors) > 0) { ?>

    <div class="alert alert-info">
      <?php echo __('Found %1% results', ['%1%' => count($donors)]); ?>
    </div>

    <script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
    function toggleColumn(colNum) {
      var table = document.getElementById('reportTable');
      var rows = table.getElementsByTagName('tr');
      for (var i = 0; i < rows.length; i++) {
        var cell = rows[i].cells[colNum];
        if (cell) {
          cell.style.display = cell.style.display === 'none' ? '' : 'none';
        }
      }
    }

    function exportTableToCSV() {
      var table = document.getElementById('reportTable');
      var csv = [];
      var rows = table.querySelectorAll('tr');
      for (var i = 0; i < rows.length; i++) {
        var row = [];
        var cols = rows[i].querySelectorAll('td, th');
        for (var j = 0; j < cols.length; j++) {
          if (cols[j].style.display !== 'none') {
            var text = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + text + '"');
          }
        }
        csv.push(row.join(','));
      }
      var csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
      var downloadLink = document.createElement('a');
      downloadLink.download = 'donor_report_' + new Date().getTime() + '.csv';
      downloadLink.href = window.URL.createObjectURL(csvFile);
      downloadLink.style.display = 'none';
      document.body.appendChild(downloadLink);
      downloadLink.click();
      document.body.removeChild(downloadLink);
    }
    </script>

    <div class="table-responsive">
      <table id="reportTable" class="table table-bordered table-striped table-sm">
        <thead class="table-dark">
          <tr>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('Email'); ?></th>
            <th><?php echo __('Telephone'); ?></th>
            <th><?php echo __('Created'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($donors as $item) { ?>
            <tr>
              <td><?php echo $item->name ?? '-'; ?></td>
              <td><?php echo $item->email ?? '-'; ?></td>
              <td><?php echo $item->telephone ?? '-'; ?></td>
              <td><?php echo $item->created_at ?? '-'; ?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

  <?php } else { ?>
    <div class="alert alert-warning">
      <?php echo __('No results found. Use the filter options to search for donors.'); ?>
    </div>
  <?php } ?>

<?php end_slot(); ?>
