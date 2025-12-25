<?php decorate_with('layout_2col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Browse Authority Record/Actor Report'); ?></h1>
  <div style="margin-bottom: 1rem;">
    <a href="<?php echo url_for(['module' => 'reports', 'action' => 'reportSelect']); ?>" class="c-btn">
      <i class="fa fa-arrow-left"></i> <?php echo __("Back to Reports"); ?>
    </a>
  </div>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
  <section class="sidebar-widget">
    <h4><?php echo __('Filter options'); ?></h4>
    <?php echo $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportAuthorityRecord']), ['method' => 'get']); ?>
      <?php echo $form->renderHiddenFields(); ?>

      <div class="form-item">
        <label><?php echo __('Date start'); ?></label>
        <?php echo $form['dateStart']->render(); ?>
      </div>

      <div class="form-item">
        <label><?php echo __('Date end'); ?></label>
        <?php echo $form['dateEnd']->render(); ?>
      </div>

      <div class="form-item">
        <label><?php echo __('Date of'); ?></label>
        <?php echo $form['dateOf']->render(); ?>
      </div>

      <div class="form-item">
        <label><?php echo __('Entity type'); ?></label>
        <?php echo $form['entityType']->render(); ?>
      </div>

      <div class="form-item">
        <label><?php echo __('Sort by'); ?></label>
        <?php echo $form['sort']->render(); ?>
      </div>

      <div class="form-item">
        <label><?php echo __('Results per page'); ?></label>
        <?php echo $form['limit']->render(); ?>
      </div>

      <section>
        <input class="c-btn c-btn-submit" type="submit" value="<?php echo __('Search'); ?>"/>
      </section>

      <div style="margin-top: 1rem;">
        <button type="button" onclick="exportTableToCSV()" class="c-btn" style="width:100%;">
          <i class="fa fa-download"></i> <?php echo __('Export CSV'); ?>
        </button>
      </div>
    </form>
  </section>

  <?php if (isset($statistics)): ?>
  <?php $stats = $sf_data->getRaw('statistics'); ?>
  <section class="sidebar-widget">
    <h4><?php echo __('Statistics'); ?></h4>
    <ul class="list-unstyled">
      <li><strong><?php echo __('Total'); ?>:</strong> <?php echo $stats['total'] ?? 0; ?></li>
      <?php if (!empty($stats['by_type'])): ?>
        <?php foreach ($stats['by_type'] as $type => $count): ?>
          <li><strong><?php echo esc_specialchars($type); ?>:</strong> <?php echo $count; ?></li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
  </section>
  <?php endif; ?>
<?php end_slot(); ?>

<?php slot('content'); ?>
<?php
$rawResults = isset($results) ? $sf_data->getRaw('results') : [];
$rawTotal = isset($total) ? $sf_data->getRaw('total') : 0;
$rawCurrentPage = isset($currentPage) ? $sf_data->getRaw('currentPage') : 1;
$rawLastPage = isset($lastPage) ? $sf_data->getRaw('lastPage') : 1;
$rawHasNext = isset($hasNext) ? $sf_data->getRaw('hasNext') : false;
$rawHasPrevious = isset($hasPrevious) ? $sf_data->getRaw('hasPrevious') : false;
?>

<section>
  <?php if (!empty($rawResults)): ?>
    <div class="alert alert-info">
      <?php echo __('Showing %1% of %2% results (Page %3% of %4%)', [
        '%1%' => count($rawResults),
        '%2%' => $rawTotal,
        '%3%' => $rawCurrentPage,
        '%4%' => $rawLastPage
      ]); ?>
    </div>

    <table id="reportTable" class="table table-striped sticky-enabled tablesorter">
      <thead>
        <tr>
          <th><?php echo __('Name'); ?></th>
          <th><?php echo __('Type'); ?></th>
          <th><?php echo __('Dates'); ?></th>
          <th><?php echo __('Created'); ?></th>
          <th><?php echo __('Updated'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rawResults as $result): ?>
          <tr>
            <td>
              <a href="<?php echo url_for(['module' => 'actor', 'slug' => $result['slug'] ?? '']); ?>">
                <?php echo esc_specialchars($result['authorized_form_of_name'] ?? 'N/A'); ?>
              </a>
            </td>
            <td><?php echo esc_specialchars($result['entity_type_name'] ?? 'N/A'); ?></td>
            <td><?php echo esc_specialchars($result['dates_of_existence'] ?? ''); ?></td>
            <td><?php echo isset($result['created_at']) ? date('Y-m-d', strtotime($result['created_at'])) : 'N/A'; ?></td>
            <td><?php echo isset($result['updated_at']) ? date('Y-m-d', strtotime($result['updated_at'])) : 'N/A'; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($rawLastPage > 1): ?>
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-center">
        <?php if ($rawHasPrevious): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'reports', 'action' => 'reportAuthorityRecord', 'page' => $rawCurrentPage - 1] + sfOutputEscaper::unescape($sf_request->getGetParameters())); ?>">
              <?php echo __('Previous'); ?>
            </a>
          </li>
        <?php endif; ?>

        <?php for ($p = max(1, $rawCurrentPage - 2); $p <= min($rawLastPage, $rawCurrentPage + 2); $p++): ?>
          <li class="page-item <?php echo $p == $rawCurrentPage ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'reports', 'action' => 'reportAuthorityRecord', 'page' => $p] + sfOutputEscaper::unescape($sf_request->getGetParameters())); ?>">
              <?php echo $p; ?>
            </a>
          </li>
        <?php endfor; ?>

        <?php if ($rawHasNext): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'reports', 'action' => 'reportAuthorityRecord', 'page' => $rawCurrentPage + 1] + sfOutputEscaper::unescape($sf_request->getGetParameters())); ?>">
              <?php echo __('Next'); ?>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
    <?php endif; ?>

  <?php else: ?>
    <div class="alert alert-warning">
      <?php echo __('No results found. Adjust your search criteria.'); ?>
    </div>
  <?php endif; ?>
</section>

<script>
function exportTableToCSV() {
  var table = document.getElementById('reportTable');
  if (!table) {
    alert('No data to export');
    return;
  }
  var csv = [];
  var rows = table.querySelectorAll('tr');
  for (var i = 0; i < rows.length; i++) {
    var row = [], cols = rows[i].querySelectorAll('td, th');
    for (var j = 0; j < cols.length; j++) {
      var text = cols[j].innerText.replace(/"/g, '""');
      row.push('"' + text + '"');
    }
    csv.push(row.join(','));
  }
  var csvContent = csv.join('\n');
  var blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
  var link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'authority_report_<?php echo date('Y-m-d'); ?>.csv';
  link.click();
}
</script>
<?php end_slot(); ?>
