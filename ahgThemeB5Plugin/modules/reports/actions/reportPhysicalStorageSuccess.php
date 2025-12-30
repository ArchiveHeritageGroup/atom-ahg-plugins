<?php decorate_with('layout_2col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Browse Physical Storage Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>

  <section class="sidebar-widget">
    
    <div style="margin-bottom: 1rem;">
      <a href="<?php echo url_for(['module' => 'reports', 'action' => 'reportSelect']); ?>" class="c-btn" style="width:100%;">
        <i class="fa fa-arrow-left"></i> <?php echo __('Back to Reports'); ?>
      </a>
    </div>

    <h4><?php echo __('Filter options'); ?></h4>

    <?php echo $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportPhysicalStorage']), ['method' => 'get']); ?>

      <?php echo $form->renderHiddenFields(); ?>

      <div class="form-item">
        <label><?php echo __('Repository'); ?></label>
        <?php echo $form['repositoryId']->render(); ?>
      </div>

      <div class="form-item">
        <label><?php echo __('Culture'); ?></label>
        <?php echo $form['culture']->render(); ?>
      </div>

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
        <label>
          <?php echo $form['showLinkedIO']->render(); ?>
          <?php echo __('Show linked Information Objects'); ?>
        </label>
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

<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if (isset($results) && count($results) > 0) { ?>
    
    <div class="alert alert-info">
      <?php echo __('Found %1% results', ['%1%' => $total]); ?>
    </div>

    <div style="margin-bottom: 1rem; font-size: 0.85rem;">
      <strong><?php echo __('Show/Hide Columns'); ?>:</strong><br/>
      <label><input type="checkbox" onclick="toggleColumn(0)" checked> <?php echo __('Name'); ?></label>
      <label><input type="checkbox" onclick="toggleColumn(1)" checked> <?php echo __('Location'); ?></label>
      <label><input type="checkbox" onclick="toggleColumn(2)" checked> <?php echo __('Repository'); ?></label>
      <label><input type="checkbox" onclick="toggleColumn(3)" checked> <?php echo __('Culture'); ?></label>
      <label><input type="checkbox" onclick="toggleColumn(4)" checked> <?php echo __('Created'); ?></label>
      <?php if (isset($_GET['showLinkedIO']) && $_GET['showLinkedIO']) { ?>
        <label><input type="checkbox" onclick="toggleColumn(5)" checked> <?php echo __('Linked Information Objects'); ?></label>
      <?php } ?>
    </div>

    <script <?php echo sfConfig::get('csp_nonce', ''); ?>>
    function toggleColumn(colNum) {
      var table = document.getElementById('reportTable');
      var rows = table.getElementsByTagName('tr');
      
      for (var i = 0; i < rows.length; i++) {
        var cell = rows[i].cells[colNum];
        if (cell) {
          if (cell.style.display === 'none') {
            cell.style.display = '';
          } else {
            cell.style.display = 'none';
          }
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
      downloadLink.download = 'physical_storage_report_' + new Date().getTime() + '.csv';
      downloadLink.href = window.URL.createObjectURL(csvFile);
      downloadLink.style.display = 'none';
      document.body.appendChild(downloadLink);
      downloadLink.click();
      document.body.removeChild(downloadLink);
    }
    </script>

    <div class="table-responsive" style="max-height: 600px; overflow: auto;">
      <table id="reportTable" class="table table-bordered table-striped table-sm">
        <thead>
          <tr>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('Location'); ?></th>
            <th><?php echo __('Repository'); ?></th>
            <th><?php echo __('Culture'); ?></th>
            <th><?php echo __('Created'); ?></th>
            <?php if (isset($_GET['showLinkedIO']) && $_GET['showLinkedIO']) { ?>
              <th><?php echo __('Linked Information Objects'); ?></th>
            <?php } ?>
          </tr>
        </thead>
        <tbody>
          <?php 
          $termService = new \AtomExtensions\Services\TermService('en');
          foreach ($results as $item) { 
          ?>
            <tr>
              <td><?php echo $item->name ?? '-'; ?></td>
              <td><?php echo $item->location ?? '-'; ?></td>
              <td>
                <?php 
                if (isset($item->repositoryId)) {
                  $repoService = new \AtomExtensions\Repositories\PhysicalObjectRepository();
                  $repos = $repoService->getAvailableRepositories('en');
                  $repo = $repos->firstWhere('id', $item->repositoryId);
                  echo $repo ? $repo->name : '-';
                } else {
                  echo '-';
                }
                ?>
              </td>
              <td><?php echo $item->culture ?? '-'; ?></td>
              <td><?php echo $item->createdAt ?? '-'; ?></td>
              <?php if (isset($_GET['showLinkedIO']) && $_GET['showLinkedIO']) { ?>
                <td>
                  <?php if (isset($item->linkedInformationObjects) && count($item->linkedInformationObjects) > 0) { ?>
                    <ul style="margin: 0; padding-left: 20px;">
                      <?php foreach ($item->linkedInformationObjects as $io) { ?>
                        <li>
                          <?php echo link_to($io->title . ' (' . $io->identifier . ')', ['module' => 'informationobject', 'slug' => $io->id]); ?>
                        </li>
                      <?php } ?>
                    </ul>
                  <?php } else { ?>
                    -
                  <?php } ?>
                </td>
              <?php } ?>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

  <?php } else { ?>
    <div class="alert alert-warning">
      <?php echo __('No results found.'); ?>
    </div>
  <?php } ?>

<?php end_slot(); ?>
