<?php use_helper('Date'); ?>
<?php
// Extract raw arrays from Symfony decorator
$objectsArray = $objects instanceof sfOutputEscaperArrayDecorator ? $objects->getRawValue() : (array) ($objects ?? []);
$sectionsArray = $sections instanceof sfOutputEscaperArrayDecorator ? $sections->getRawValue() : (array) ($sections ?? []);
?>

<div class="row">
  <div class="col-12">
    <nav aria-label="breadcrumb" class="d-print-none">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'index']); ?>">Exhibitions</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'show', 'id' => $exhibition['id']]); ?>"><?php echo htmlspecialchars($exhibition['title']); ?></a></li>
        <li class="breadcrumb-item active">Object List</li>
      </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1>Exhibition Object List</h1>
        <p class="text-muted mb-0"><?php echo htmlspecialchars($exhibition['title']); ?></p>
      </div>
      <div class="d-print-none">
        <div class="btn-group">
          <button type="button" class="btn btn-outline-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Print
          </button>
          <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'objectList', 'id' => $exhibition['id'], 'format' => 'csv']); ?>"
             class="btn btn-outline-secondary">
            <i class="fas fa-download"></i> Export CSV
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Print Header (only visible in print) -->
<div class="d-none d-print-block mb-4">
  <div class="row">
    <div class="col-8">
      <h2><?php echo htmlspecialchars($exhibition['title']); ?></h2>
      <?php if (!empty($exhibition['subtitle'])): ?>
        <p class="lead"><?php echo htmlspecialchars($exhibition['subtitle']); ?></p>
      <?php endif; ?>
    </div>
    <div class="col-4 text-end">
      <p class="mb-1"><strong>Object Checklist</strong></p>
      <p class="mb-1">Generated: <?php echo date('d M Y'); ?></p>
      <p class="mb-0">Objects: <?php echo count($objectsArray); ?></p>
    </div>
  </div>
  <hr>
</div>

<!-- Summary Info -->
<div class="row mb-4 d-print-none">
  <div class="col-md-3">
    <div class="card bg-light">
      <div class="card-body text-center">
        <h3 class="mb-0"><?php echo count($objectsArray); ?></h3>
        <small class="text-muted">Total Objects</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-light">
      <div class="card-body text-center">
        <h3 class="mb-0"><?php echo count($sectionsArray); ?></h3>
        <small class="text-muted">Sections</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-light">
      <div class="card-body text-center">
        <?php
          $totalValue = 0;
          foreach ($objectsArray as $obj) {
            $totalValue += floatval($obj['insurance_value'] ?? 0);
          }
        ?>
        <h3 class="mb-0">R<?php echo number_format($totalValue, 0); ?></h3>
        <small class="text-muted">Total Insurance Value</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-light">
      <div class="card-body text-center">
        <h3 class="mb-0"><?php echo count(array_filter($objectsArray, fn($o) => !empty($o['is_loan']))); ?></h3>
        <small class="text-muted">Loan Objects</small>
      </div>
    </div>
  </div>
</div>

<?php if (empty($objectsArray)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-archive fa-3x text-muted mb-3"></i>
      <h5>No objects in this exhibition</h5>
      <p class="text-muted">Add objects to generate the object list.</p>
      <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'objects', 'id' => $exhibition['id']]); ?>"
         class="btn btn-primary d-print-none">
        <i class="fas fa-plus"></i> Add Objects
      </a>
    </div>
  </div>
<?php else: ?>
  <!-- Group by section -->
  <?php
    $groupedObjects = ['_unassigned' => []];
    foreach ($sectionsArray as $section) {
      $groupedObjects[$section['id']] = ['section' => $section, 'objects' => []];
    }
    foreach ($objectsArray as $obj) {
      if (!empty($obj['section_id']) && isset($groupedObjects[$obj['section_id']])) {
        $groupedObjects[$obj['section_id']]['objects'][] = $obj;
      } else {
        $groupedObjects['_unassigned'][] = $obj;
      }
    }
  ?>

  <?php foreach ($groupedObjects as $key => $group): ?>
    <?php if ($key === '_unassigned'): ?>
      <?php if (!empty($group)): ?>
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0">Unassigned Objects</h5>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
              <thead>
                <tr>
                  <th style="width: 30px;"></th>
                  <th>Object Number</th>
                  <th>Title/Description</th>
                  <th>Display Location</th>
                  <th class="text-end">Insurance Value</th>
                  <th class="d-print-none"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($group as $index => $obj): ?>
                  <tr>
                    <td class="text-center"><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($obj['object_number'] ?? '-'); ?></td>
                    <td>
                      <?php echo htmlspecialchars($obj['object_title'] ?? 'Untitled'); ?>
                      <?php if (!empty($obj['is_loan'])): ?>
                        <span class="badge bg-warning text-dark">Loan</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($obj['display_location'] ?? '-'); ?></td>
                    <td class="text-end">
                      <?php if (!empty($obj['insurance_value'])): ?>
                        R<?php echo number_format($obj['insurance_value'], 2); ?>
                      <?php else: ?>
                        -
                      <?php endif; ?>
                    </td>
                    <td class="d-print-none">
                      <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $obj['object_slug']]); ?>"
                         class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <?php if (!empty($group['objects'])): ?>
        <div class="card mb-4">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-0"><?php echo htmlspecialchars($group['section']['name']); ?></h5>
                <?php if (!empty($group['section']['gallery_name'])): ?>
                  <small class="text-muted"><?php echo htmlspecialchars($group['section']['gallery_name']); ?></small>
                <?php endif; ?>
              </div>
              <span class="badge bg-primary"><?php echo count($group['objects']); ?> objects</span>
            </div>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
              <thead>
                <tr>
                  <th style="width: 30px;"></th>
                  <th>Object Number</th>
                  <th>Title/Description</th>
                  <th>Display Location</th>
                  <th class="text-end">Insurance Value</th>
                  <th class="d-print-none"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($group['objects'] as $index => $obj): ?>
                  <tr>
                    <td class="text-center"><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($obj['object_number'] ?? '-'); ?></td>
                    <td>
                      <?php echo htmlspecialchars($obj['object_title'] ?? 'Untitled'); ?>
                      <?php if (!empty($obj['is_loan'])): ?>
                        <span class="badge bg-warning text-dark">Loan</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($obj['display_location'] ?? '-'); ?></td>
                    <td class="text-end">
                      <?php if (!empty($obj['insurance_value'])): ?>
                        R<?php echo number_format($obj['insurance_value'], 2); ?>
                      <?php else: ?>
                        -
                      <?php endif; ?>
                    </td>
                    <td class="d-print-none">
                      <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $obj['object_slug']]); ?>"
                         class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="table-light">
                  <td colspan="4" class="text-end"><strong>Section Total:</strong></td>
                  <td class="text-end">
                    <strong>
                      R<?php echo number_format(array_sum(array_column($group['objects'], 'insurance_value')), 2); ?>
                    </strong>
                  </td>
                  <td class="d-print-none"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  <?php endforeach; ?>

  <!-- Grand Total -->
  <div class="card bg-light">
    <div class="card-body">
      <div class="row">
        <div class="col-md-8">
          <h5 class="mb-0">Grand Total</h5>
        </div>
        <div class="col-md-4 text-end">
          <h4 class="mb-0">R<?php echo number_format($totalValue, 2); ?></h4>
          <small class="text-muted"><?php echo count($objectsArray); ?> objects</small>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Print Footer -->
<div class="d-none d-print-block mt-4">
  <hr>
  <div class="row">
    <div class="col-6">
      <p class="small text-muted mb-0">
        Exhibition: <?php echo htmlspecialchars($exhibition['title']); ?><br>
        <?php if (!empty($exhibition['opening_date'])): ?>
          Dates: <?php echo $exhibition['opening_date']; ?>
          <?php if (!empty($exhibition['closing_date'])): ?> - <?php echo $exhibition['closing_date']; ?><?php endif; ?>
        <?php endif; ?>
      </p>
    </div>
    <div class="col-6 text-end">
      <p class="small text-muted mb-0">
        Generated: <?php echo date('d M Y H:i'); ?><br>
        Page <span class="page-number"></span>
      </p>
    </div>
  </div>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
@media print {
  .d-print-none {
    display: none !important;
  }
  .d-none.d-print-block {
    display: block !important;
  }
  .card {
    border: 1px solid #ddd !important;
    box-shadow: none !important;
    break-inside: avoid;
  }
  .card-header {
    background-color: #f8f9fa !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .badge {
    border: 1px solid #999;
  }
  table {
    font-size: 11px;
  }
  @page {
    margin: 1.5cm;
  }
}
</style>
