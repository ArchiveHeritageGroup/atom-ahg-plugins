<?php decorate_with('layout_2col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Browse Repository Report'); ?></h1>
  <div style="margin-bottom: 1rem;">
    <a href="<?php echo url_for(['module' => 'reports', 'action' => 'reportSelect']); ?>" class="c-btn">
      <i class="fa fa-arrow-left"></i> <?php echo __("Back to Reports"); ?>
    </a>
  </div>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>

  <section class="sidebar-widget">
    
    <h4><?php echo __('Filter options'); ?></h4>

    <?php echo $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportRepository']), ['method' => 'get']); ?>

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
      <label><input type="checkbox" onclick="toggleColumn(0)" checked> Identifier</label>
      <label><input type="checkbox" onclick="toggleColumn(1)" checked> Name</label>
      <label><input type="checkbox" onclick="toggleColumn(2)" checked> Desc Status</label>
      <label><input type="checkbox" onclick="toggleColumn(3)" checked> Desc Detail</label>
      <label><input type="checkbox" onclick="toggleColumn(4)" checked> Desc ID</label>
      <label><input type="checkbox" onclick="toggleColumn(5)" checked> Geocultural</label>
      <label><input type="checkbox" onclick="toggleColumn(6)" checked> Collecting</label>
      <label><input type="checkbox" onclick="toggleColumn(7)" checked> Buildings</label>
      <label><input type="checkbox" onclick="toggleColumn(8)" checked> Holdings</label>
      <label><input type="checkbox" onclick="toggleColumn(9)" checked> Finding Aids</label>
      <label><input type="checkbox" onclick="toggleColumn(10)" checked> Opening Times</label>
      <label><input type="checkbox" onclick="toggleColumn(11)" checked> Access</label>
      <label><input type="checkbox" onclick="toggleColumn(12)" checked> Disabled Access</label>
      <label><input type="checkbox" onclick="toggleColumn(13)" checked> Research</label>
      <label><input type="checkbox" onclick="toggleColumn(14)" checked> Reproduction</label>
      <label><input type="checkbox" onclick="toggleColumn(15)" checked> Public Facilities</label>
      <label><input type="checkbox" onclick="toggleColumn(16)" checked> Institution ID</label>
      <label><input type="checkbox" onclick="toggleColumn(17)" checked> Rules</label>
      <label><input type="checkbox" onclick="toggleColumn(18)" checked> Sources</label>
      <label><input type="checkbox" onclick="toggleColumn(19)" checked> Revision</label>
      <label><input type="checkbox" onclick="toggleColumn(20)" checked> Created</label>
    </div>

    <script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
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
      downloadLink.download = 'repository_report_' + new Date().getTime() + '.csv';
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
            <th>Identifier</th>
            <th>Name</th>
            <th>Description Status</th>
            <th>Description Detail</th>
            <th>Description Identifier</th>
            <th>Geocultural Context</th>
            <th>Collecting Policies</th>
            <th>Buildings</th>
            <th>Holdings</th>
            <th>Finding Aids</th>
            <th>Opening Times</th>
            <th>Access Conditions</th>
            <th>Disabled Access</th>
            <th>Research Services</th>
            <th>Reproduction Services</th>
            <th>Public Facilities</th>
            <th>Description Institution Identifier</th>
            <th>Description Rules</th>
            <th>Description Sources</th>
            <th>Description Revision History</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $termService = new \AtomExtensions\Services\TermService('en');
          foreach ($results as $item) { 
          ?>
            <tr>
              <td><?php echo isset($item->identifier) ? link_to($item->identifier, ['module' => 'repository', 'slug' => $item->id]) : '-'; ?></td>
              <td><?php echo $item->name ?? '-'; ?></td>
              <td><?php echo isset($item->descStatusId) ? $termService->getTermName($item->descStatusId) : '-'; ?></td>
              <td><?php echo isset($item->descDetailId) ? $termService->getTermName($item->descDetailId) : '-'; ?></td>
              <td><?php echo $item->descIdentifier ?? '-'; ?></td>
              <td><?php echo $item->geoculturalContext ?? '-'; ?></td>
              <td><?php echo $item->collectingPolicies ?? '-'; ?></td>
              <td><?php echo $item->buildings ?? '-'; ?></td>
              <td><?php echo $item->holdings ?? '-'; ?></td>
              <td><?php echo $item->findingAids ?? '-'; ?></td>
              <td><?php echo $item->openingTimes ?? '-'; ?></td>
              <td><?php echo $item->accessConditions ?? '-'; ?></td>
              <td><?php echo $item->disabledAccess ?? '-'; ?></td>
              <td><?php echo $item->researchServices ?? '-'; ?></td>
              <td><?php echo $item->reproductionServices ?? '-'; ?></td>
              <td><?php echo $item->publicFacilities ?? '-'; ?></td>
              <td><?php echo $item->descInstitutionIdentifier ?? '-'; ?></td>
              <td><?php echo $item->descRules ?? '-'; ?></td>
              <td><?php echo $item->descSources ?? '-'; ?></td>
              <td><?php echo $item->descRevisionHistory ?? '-'; ?></td>
              <td><?php echo $item->createdAt ?? '-'; ?></td>
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