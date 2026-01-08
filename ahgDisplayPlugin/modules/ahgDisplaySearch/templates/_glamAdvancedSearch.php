<?php
/**
 * GLAM Advanced Search Panel - Full ISAD(G) Fields with Sector Groupings
 */
$showAdvanced = isset($_GET['showAdvanced']) && $_GET['showAdvanced'] == '1';
$params = $_GET;
$currentType = $params['type'] ?? '';

// Get levels filtered by sector
$levelsBySector = [];
try {
    // Get all levels for "All" option
    $allLevels = \Illuminate\Database\Capsule\Manager::table('term')
        ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
        ->where('term.taxonomy_id', QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
        ->where('term_i18n.culture', 'en')
        ->orderBy('term_i18n.name')
        ->select('term.id', 'term_i18n.name')
        ->get()
        ->toArray();
    
    // Get levels per sector from configuration table
    $sectorLevels = \Illuminate\Database\Capsule\Manager::table('level_of_description_sector as los')
        ->join('term_i18n as ti', function($join) {
            $join->on('los.term_id', '=', 'ti.id')
                 ->where('ti.culture', '=', 'en');
        })
        ->select('los.sector', 'los.term_id as id', 'ti.name', 'los.display_order')
        ->orderBy('los.display_order')
        ->get();
    
    // Group by sector
    foreach ($sectorLevels as $sl) {
        if (!isset($levelsBySector[$sl->sector])) {
            $levelsBySector[$sl->sector] = [];
        }
        $levelsBySector[$sl->sector][] = (object)['id' => $sl->id, 'name' => $sl->name];
    }
    $levelsBySector[''] = $allLevels;
} catch (Exception $e) {
    error_log("Levels query error: " . $e->getMessage());
    $levelsBySector[''] = [];
}

// Get repositories
$repositories = [];
try {
    $repositories = \Illuminate\Database\Capsule\Manager::table('repository')
        ->join('actor_i18n', 'repository.id', '=', 'actor_i18n.id')
        ->where('actor_i18n.culture', 'en')
        ->whereNotNull('actor_i18n.authorized_form_of_name')
        ->where('actor_i18n.authorized_form_of_name', '!=', '')
        ->orderBy('actor_i18n.authorized_form_of_name')
        ->select('repository.id', 'actor_i18n.authorized_form_of_name as name')
        ->get()
        ->toArray();
} catch (Exception $e) {
    error_log("Repository query error: " . $e->getMessage());
}

// Determine which levels to show
$currentLevels = isset($levelsBySector[$currentType]) && !empty($levelsBySector[$currentType]) 
    ? $levelsBySector[$currentType] 
    : $levelsBySector[''];
?>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

<div class="accordion mb-3" id="glamAdvancedSearchAccordion">
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button<?php echo $showAdvanced ? '' : ' collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#glamAdvancedSearchPanel" aria-expanded="<?php echo $showAdvanced ? 'true' : 'false'; ?>">
        <i class="fas fa-sliders-h me-2"></i><?php echo __('Advanced search options'); ?>
      </button>
    </h2>
    <div id="glamAdvancedSearchPanel" class="accordion-collapse collapse<?php echo $showAdvanced ? ' show' : ''; ?>">
      <div class="accordion-body">
        <form method="get" action="<?php echo url_for(['module' => 'ahgDisplay', 'action' => 'browse']); ?>" id="glam-advanced-search-form">
          
          <!-- Sector Quick Filter -->
          <div class="mb-4">
            <label class="form-label fw-bold"><i class="fas fa-layer-group me-1"></i><?php echo __('Search in sector'); ?></label>
            <div class="d-flex flex-wrap gap-2">
              <a href="<?php echo url_for(['module' => 'ahgDisplay', 'action' => 'browse', 'showAdvanced' => '1']); ?>" class="btn <?php echo empty($currentType) ? 'btn-secondary' : 'btn-outline-secondary'; ?>">
                <i class="fas fa-globe me-1"></i><?php echo __('All'); ?>
              </a>
              <a href="<?php echo url_for(['module' => 'ahgDisplay', 'action' => 'browse', 'type' => 'archive', 'showAdvanced' => '1']); ?>" class="btn <?php echo $currentType === 'archive' ? 'btn-success' : 'btn-outline-success'; ?>">
                <i class="fas fa-archive me-1"></i><?php echo __('Archive'); ?>
              </a>
              <a href="<?php echo url_for(['module' => 'ahgDisplay', 'action' => 'browse', 'type' => 'library', 'showAdvanced' => '1']); ?>" class="btn <?php echo $currentType === 'library' ? 'btn-info text-white' : 'btn-outline-info'; ?>">
                <i class="fas fa-book me-1"></i><?php echo __('Library'); ?>
              </a>
              <a href="<?php echo url_for(['module' => 'ahgDisplay', 'action' => 'browse', 'type' => 'museum', 'showAdvanced' => '1']); ?>" class="btn <?php echo $currentType === 'museum' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                <i class="fas fa-landmark me-1"></i><?php echo __('Museum'); ?>
              </a>
              <a href="<?php echo url_for(['module' => 'ahgDisplay', 'action' => 'browse', 'type' => 'gallery', 'showAdvanced' => '1']); ?>" class="btn <?php echo $currentType === 'gallery' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                <i class="fas fa-palette me-1"></i><?php echo __('Gallery'); ?>
              </a>
              <a href="<?php echo url_for(['module' => 'ahgDisplay', 'action' => 'browse', 'type' => 'dam', 'showAdvanced' => '1']); ?>" class="btn <?php echo $currentType === 'dam' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="fas fa-images me-1"></i><?php echo __('Photos'); ?>
              </a>
            </div>
          </div>

          <input type="hidden" name="type" value="<?php echo htmlspecialchars($currentType); ?>">

          <!-- Nav Tabs -->
          <ul class="nav nav-tabs mb-3" id="advSearchTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#basic-search" type="button"><?php echo __('Basic'); ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#content-search" type="button"><?php echo __('Content'); ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#access-search" type="button"><?php echo __('Access Points'); ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#dates-search" type="button"><?php echo __('Dates'); ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#filters-search" type="button"><?php echo __('Filters'); ?></button></li>
          </ul>

          <div class="tab-content">
            <!-- Basic Tab -->
            <div class="tab-pane fade show active" id="basic-search">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold"><?php echo __('Any field'); ?></label>
                  <input type="text" name="query" class="form-control" value="<?php echo htmlspecialchars($params['query'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold"><?php echo __('Title'); ?></label>
                  <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($params['title'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold"><?php echo __('Identifier'); ?></label>
                  <input type="text" name="identifier" class="form-control" value="<?php echo htmlspecialchars($params['identifier'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold"><?php echo __('Reference code'); ?></label>
                  <input type="text" name="referenceCode" class="form-control" value="<?php echo htmlspecialchars($params['referenceCode'] ?? ''); ?>">
                </div>
              </div>
            </div>
            
            <!-- Content Tab -->
            <div class="tab-pane fade" id="content-search">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold"><?php echo __('Scope and content'); ?></label>
                  <input type="text" name="scopeAndContent" class="form-control" value="<?php echo htmlspecialchars($params['scopeAndContent'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold"><?php echo __('Extent and medium'); ?></label>
                  <input type="text" name="extentAndMedium" class="form-control" value="<?php echo htmlspecialchars($params['extentAndMedium'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold"><?php echo __('Archival history'); ?></label>
                  <input type="text" name="archivalHistory" class="form-control" value="<?php echo htmlspecialchars($params['archivalHistory'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold"><?php echo __('Acquisition'); ?></label>
                  <input type="text" name="acquisition" class="form-control" value="<?php echo htmlspecialchars($params['acquisition'] ?? ''); ?>">
                </div>
              </div>
            </div>
            
            <!-- Access Points Tab -->
            <div class="tab-pane fade" id="access-search">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold"><?php echo __('Creator'); ?></label>
                  <input type="text" name="creatorSearch" class="form-control" value="<?php echo htmlspecialchars($params['creatorSearch'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold"><?php echo __('Subject'); ?></label>
                  <input type="text" name="subjectSearch" class="form-control" value="<?php echo htmlspecialchars($params['subjectSearch'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold"><?php echo __('Place'); ?></label>
                  <input type="text" name="placeSearch" class="form-control" value="<?php echo htmlspecialchars($params['placeSearch'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold"><?php echo __('Genre'); ?></label>
                  <input type="text" name="genreSearch" class="form-control" value="<?php echo htmlspecialchars($params['genreSearch'] ?? ''); ?>">
                </div>
              </div>
            </div>
            
            <!-- Dates Tab -->
            <div class="tab-pane fade" id="dates-search">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-bold"><?php echo __('Date from'); ?></label>
                  <input type="date" name="startDate" class="form-control" value="<?php echo htmlspecialchars($params['startDate'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold"><?php echo __('Date to'); ?></label>
                  <input type="date" name="endDate" class="form-control" value="<?php echo htmlspecialchars($params['endDate'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold"><?php echo __('Date matching'); ?></label>
                  <select name="rangeType" class="form-select">
                    <option value="inclusive" <?php echo ($params['rangeType'] ?? '') === 'inclusive' ? 'selected' : ''; ?>><?php echo __('Overlapping'); ?></option>
                    <option value="exact" <?php echo ($params['rangeType'] ?? '') === 'exact' ? 'selected' : ''; ?>><?php echo __('Exact'); ?></option>
                  </select>
                </div>
              </div>
            </div>
            
            <!-- Filters Tab -->
            <div class="tab-pane fade" id="filters-search">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-bold"><?php echo __('Level of description'); ?>
                    <?php if ($currentType): ?><span class="badge bg-secondary"><?php echo ucfirst($currentType); ?></span><?php endif; ?>
                  </label>
                  <select name="level" class="form-select">
                    <option value=""><?php echo __('Any level'); ?></option>
                    <?php foreach ($currentLevels as $level): ?>
                      <option value="<?php echo $level->id; ?>" <?php echo ($params['level'] ?? '') == $level->id ? 'selected' : ''; ?>><?php echo htmlspecialchars($level->name); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold"><?php echo __('Repository'); ?></label>
                  <select name="repo" id="repo-select">
                    <option value=""><?php echo __('Any repository'); ?></option>
                    <?php foreach ($repositories as $repo): ?>
                      <option value="<?php echo $repo->id; ?>" <?php echo ($params['repo'] ?? '') == $repo->id ? 'selected' : ''; ?>><?php echo htmlspecialchars($repo->name); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold"><?php echo __('Digital objects'); ?></label>
                  <select name="hasDigital" class="form-select">
                    <option value=""><?php echo __('Any'); ?></option>
                    <option value="1" <?php echo ($params['hasDigital'] ?? '') === '1' ? 'selected' : ''; ?>><?php echo __('With digital objects'); ?></option>
                    <option value="0" <?php echo ($params['hasDigital'] ?? '') === '0' ? 'selected' : ''; ?>><?php echo __('Without digital objects'); ?></option>
                  </select>
                </div>
                <div class="col-12">
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="topLevel" id="topLevel-all" value="0" <?php echo ($params['topLevel'] ?? '0') === '0' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="topLevel-all"><?php echo __('All descriptions'); ?></label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="topLevel" id="topLevel-top" value="1" <?php echo ($params['topLevel'] ?? '') === '1' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="topLevel-top"><?php echo __('Top-level only'); ?></label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <input type="hidden" name="showAdvanced" value="1">
          
          <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
            <a href="<?php echo url_for(['module' => 'ahgDisplay', 'action' => 'browse']); ?>" class="btn btn-outline-secondary"><i class="fas fa-undo me-1"></i><?php echo __('Reset'); ?></a>
            <button type="submit" class="btn btn-success"><i class="fas fa-search me-1"></i><?php echo __('Search'); ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var repoSelect = document.getElementById('repo-select');
    if (repoSelect) {
        new TomSelect(repoSelect, {
            create: false,
            sortField: { field: "text", direction: "asc" },
            placeholder: '<?php echo __('Type to search...'); ?>',
            allowEmptyOption: true
        });
    }
});
</script>
