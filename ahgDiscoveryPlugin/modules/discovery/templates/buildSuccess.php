<?php decorate_with('layout_1col'); ?>

<?php use_helper('I18N'); ?>

<?php
$objectId         = (int) ($objectId ?? 0);
$objectType       = $sf_data->getRaw('objectType') ?? 'ead';
$objectTitle      = $sf_data->getRaw('objectTitle') ?? '';
$objectIdentifier = $sf_data->getRaw('objectIdentifier') ?? '';
$objectSlug       = $sf_data->getRaw('objectSlug') ?? '';
$indexStatus      = sfOutputEscaper::unescape($indexStatus ?? null);
$tree             = sfOutputEscaper::unescape($tree ?? null);

$status      = $indexStatus['status'] ?? 'none';
$indexedAt   = $indexStatus['indexed_at'] ?? null;
$modelUsed   = $indexStatus['model_used'] ?? null;
$nodeCount   = (int) ($indexStatus['node_count'] ?? 0);
$errorMsg    = $indexStatus['error_message'] ?? null;
$treeId      = $indexStatus['tree_id'] ?? null;

// Status badge classes
$statusBadgeMap = [
    'none'     => 'bg-secondary',
    'pending'  => 'bg-info',
    'building' => 'bg-warning text-dark',
    'ready'    => 'bg-success',
    'error'    => 'bg-danger',
];
$statusBadgeClass = $statusBadgeMap[$status] ?? 'bg-secondary';

// Type badge
$typeBadgeMap = [
    'ead'  => 'bg-success',
    'pdf'  => 'bg-info',
    'rico' => 'bg-warning text-dark',
];
$typeBadgeClass = $typeBadgeMap[$objectType] ?? 'bg-secondary';
?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-tree me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0"><?php echo __('PageIndex Builder'); ?></h1>
      <span class="text-muted"><?php echo __('Build and manage the document tree index'); ?></span>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="discovery-container">

  <!-- Record Info Card -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-light">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <span class="badge <?php echo $typeBadgeClass; ?> me-2"><?php echo strtoupper(htmlspecialchars($objectType, ENT_QUOTES, 'UTF-8')); ?></span>
          <?php if (!empty($objectSlug)): ?>
            <a href="/<?php echo htmlspecialchars($objectSlug, ENT_QUOTES, 'UTF-8'); ?>" class="fw-bold text-decoration-none">
              <?php echo htmlspecialchars($objectTitle, ENT_QUOTES, 'UTF-8'); ?>
            </a>
          <?php else: ?>
            <span class="fw-bold"><?php echo htmlspecialchars($objectTitle, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </div>
        <?php if (!empty($objectIdentifier)): ?>
          <span class="text-muted"><?php echo htmlspecialchars($objectIdentifier, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
      </div>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-sm-3">
          <small class="text-muted d-block"><?php echo __('Status'); ?></small>
          <span class="badge <?php echo $statusBadgeClass; ?>"><?php echo htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="col-sm-3">
          <small class="text-muted d-block"><?php echo __('Indexed At'); ?></small>
          <span><?php echo $indexedAt ? htmlspecialchars($indexedAt, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">-</span>'; ?></span>
        </div>
        <div class="col-sm-3">
          <small class="text-muted d-block"><?php echo __('Model'); ?></small>
          <span><?php echo $modelUsed ? htmlspecialchars($modelUsed, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">-</span>'; ?></span>
        </div>
        <div class="col-sm-3">
          <small class="text-muted d-block"><?php echo __('Nodes'); ?></small>
          <span><?php echo $nodeCount > 0 ? $nodeCount : '<span class="text-muted">-</span>'; ?></span>
        </div>
      </div>

      <?php if ($status === 'error' && !empty($errorMsg)): ?>
      <div class="alert alert-danger mt-3 mb-0">
        <i class="fas fa-exclamation-triangle me-1"></i>
        <strong><?php echo __('Error:'); ?></strong>
        <?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="card-footer bg-white">
      <div class="d-flex align-items-center gap-2">
        <button id="build-btn" class="btn btn-primary" type="button">
          <i class="fas fa-hammer me-1"></i>
          <?php echo ($status === 'ready' || $status === 'error') ? __('Rebuild Index') : __('Build Index'); ?>
        </button>
        <span id="build-status" class="text-muted ms-2"></span>
        <div id="build-spinner" class="spinner-border spinner-border-sm text-primary d-none" role="status">
          <span class="visually-hidden"><?php echo __('Building...'); ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Tree View (only if status=ready and tree is available) -->
  <?php if ($status === 'ready' && !empty($tree)): ?>
  <div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <h6 class="mb-0"><i class="fas fa-sitemap me-2"></i><?php echo __('Tree Structure'); ?></h6>
      <button class="btn btn-sm btn-outline-secondary" id="toggle-all-btn" type="button">
        <i class="fas fa-expand-alt me-1"></i><?php echo __('Expand All'); ?>
      </button>
    </div>
    <div class="card-body p-2">
      <div id="tree-container">
        <?php echo renderTreeNode($tree, 0); ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php
/**
 * Recursively render a tree node as a collapsible HTML structure.
 */
function renderTreeNode(array $node, int $depth): string
{
    $id = htmlspecialchars($node['id'] ?? '', ENT_QUOTES, 'UTF-8');
    $title = htmlspecialchars($node['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8');
    $summary = htmlspecialchars($node['summary'] ?? '', ENT_QUOTES, 'UTF-8');
    $level = htmlspecialchars($node['level'] ?? '', ENT_QUOTES, 'UTF-8');
    $keywords = $node['keywords'] ?? [];
    $children = $node['children'] ?? [];
    $hasChildren = !empty($children);
    $indent = $depth * 1.25;
    $collapseId = 'tree-node-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $id);

    $html = '<div class="tree-node" style="margin-left: ' . $indent . 'rem;">';

    // Node header
    $html .= '<div class="tree-node-header d-flex align-items-start py-1">';

    if ($hasChildren) {
        $html .= '<a class="tree-toggle me-1 text-muted text-decoration-none" data-bs-toggle="collapse" href="#' . $collapseId . '" role="button" aria-expanded="' . ($depth < 2 ? 'true' : 'false') . '">';
        $html .= '<i class="fas fa-caret-' . ($depth < 2 ? 'down' : 'right') . ' tree-caret"></i>';
        $html .= '</a>';
    } else {
        $html .= '<span class="me-1" style="width: 14px; display: inline-block;"></span>';
    }

    // Level badge
    if (!empty($level)) {
        $html .= '<span class="badge bg-light text-muted me-1" style="font-size: 0.68rem;">' . $level . '</span>';
    }

    // Title
    $html .= '<span class="fw-semibold" style="font-size: 0.9rem;">' . $title . '</span>';

    // Node ID
    $html .= '<small class="text-muted ms-2" style="font-size: 0.7rem;">' . $id . '</small>';

    $html .= '</div>';

    // Node details (summary + keywords) -- collapsed for deeper nodes
    if (!empty($summary) || !empty($keywords)) {
        $html .= '<div class="tree-node-details ms-3 mb-1" style="font-size: 0.82rem;">';
        if (!empty($summary)) {
            $html .= '<div class="text-muted">' . $summary . '</div>';
        }
        if (!empty($keywords)) {
            $html .= '<div class="mt-1">';
            foreach ($keywords as $kw) {
                $html .= '<span class="badge bg-light text-muted me-1" style="font-size: 0.68rem;">' . htmlspecialchars($kw, ENT_QUOTES, 'UTF-8') . '</span>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
    }

    // Children (collapsible)
    if ($hasChildren) {
        $showClass = $depth < 2 ? 'show' : '';
        $html .= '<div class="collapse ' . $showClass . '" id="' . $collapseId . '">';
        foreach ($children as $child) {
            $html .= renderTreeNode($child, $depth + 1);
        }
        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}
?>

<?php end_slot(); ?>

<?php slot('after-content'); ?>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>>
/* Build Page Styles */
.discovery-container { max-width: 960px; margin: 0 auto; }

.tree-node { border-left: 1px solid #e9ecef; }
.tree-node:last-child { border-left-color: transparent; }
.tree-node-header:hover { background: #f8f9fa; border-radius: 3px; }

.tree-toggle { width: 14px; text-align: center; display: inline-block; }
.tree-toggle .tree-caret { transition: transform 0.15s; }
.tree-toggle[aria-expanded="true"] .tree-caret { transform: rotate(0deg); }
.tree-toggle[aria-expanded="false"] .tree-caret { transform: rotate(-90deg); }
.tree-toggle.collapsed .tree-caret { transform: rotate(-90deg); }
</style>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>>
(function() {
  'use strict';

  var buildBtn    = document.getElementById('build-btn');
  var buildStatus = document.getElementById('build-status');
  var buildSpinner = document.getElementById('build-spinner');
  var toggleAllBtn = document.getElementById('toggle-all-btn');

  var objectId   = <?php echo (int) $objectId; ?>;
  var objectType = <?php echo json_encode($objectType); ?>;

  if (buildBtn) {
    buildBtn.addEventListener('click', function() {
      buildBtn.disabled = true;
      buildSpinner.classList.remove('d-none');
      buildStatus.textContent = <?php echo json_encode(__('Building index... this may take a minute.')); ?>;

      fetch('/discovery/build', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + objectId + '&type=' + encodeURIComponent(objectType)
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        buildSpinner.classList.add('d-none');

        if (data.success) {
          buildStatus.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>' +
            <?php echo json_encode(__('Index built successfully!')); ?> +
            ' (' + (data.node_count || 0) + ' nodes)</span>';
          // Reload to show the tree
          setTimeout(function() {
            window.location.reload();
          }, 1500);
        } else {
          buildBtn.disabled = false;
          buildStatus.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>' +
            (data.error || <?php echo json_encode(__('Build failed')); ?>) + '</span>';
        }
      })
      .catch(function(err) {
        buildSpinner.classList.add('d-none');
        buildBtn.disabled = false;
        buildStatus.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>' +
          <?php echo json_encode(__('Network error. Please try again.')); ?> + '</span>';
        console.error('[build]', err);
      });
    });
  }

  // Toggle all tree nodes
  if (toggleAllBtn) {
    var expanded = true; // Default: top levels are expanded
    toggleAllBtn.addEventListener('click', function() {
      expanded = !expanded;
      var collapses = document.querySelectorAll('#tree-container .collapse');
      collapses.forEach(function(el) {
        if (expanded) {
          el.classList.add('show');
        } else {
          el.classList.remove('show');
        }
      });

      // Update caret icons
      var toggles = document.querySelectorAll('#tree-container .tree-toggle');
      toggles.forEach(function(t) {
        t.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        if (expanded) {
          t.classList.remove('collapsed');
        } else {
          t.classList.add('collapsed');
        }
      });

      toggleAllBtn.innerHTML = expanded
        ? '<i class="fas fa-compress-alt me-1"></i>' + <?php echo json_encode(__('Collapse All')); ?>
        : '<i class="fas fa-expand-alt me-1"></i>' + <?php echo json_encode(__('Expand All')); ?>;
    });
  }

  // Listen for Bootstrap collapse events to update caret direction
  document.querySelectorAll('#tree-container .tree-toggle').forEach(function(toggle) {
    var target = document.querySelector(toggle.getAttribute('href'));
    if (target) {
      target.addEventListener('show.bs.collapse', function() {
        toggle.setAttribute('aria-expanded', 'true');
        toggle.classList.remove('collapsed');
      });
      target.addEventListener('hide.bs.collapse', function() {
        toggle.setAttribute('aria-expanded', 'false');
        toggle.classList.add('collapsed');
      });
    }
  });

})();
</script>

<?php end_slot(); ?>
