<?php
$session = $sf_data->getRaw('session');
$tree = $sf_data->getRaw('tree') ?? [];
$rowCount = $sf_data->getRaw('rowCount') ?? 0;
$doCount = $sf_data->getRaw('doCount') ?? 0;
?>

<h1><?php echo __('Preview & Approve') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Ingestion Manager'), 'url' => url_for(['module' => 'ingest', 'action' => 'index'])],
        ['title' => esc_entities($session->title ?: __('Session #' . $session->id))],
        ['title' => __('Preview')]
    ]
]) ?>

<!-- Wizard Progress -->
<div class="mb-4">
    <div class="d-flex justify-content-between text-center">
        <div class="flex-fill"><span class="badge bg-success rounded-pill">1</span><br><small class="text-muted"><?php echo __('Configure') ?></small></div>
        <div class="flex-fill"><span class="badge bg-success rounded-pill">2</span><br><small class="text-muted"><?php echo __('Upload') ?></small></div>
        <div class="flex-fill"><span class="badge bg-success rounded-pill">3</span><br><small class="text-muted"><?php echo __('Map') ?></small></div>
        <div class="flex-fill"><span class="badge bg-success rounded-pill">4</span><br><small class="text-muted"><?php echo __('Validate') ?></small></div>
        <div class="flex-fill"><span class="badge bg-primary rounded-pill">5</span><br><small class="fw-bold"><?php echo __('Preview') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">6</span><br><small class="text-muted"><?php echo __('Commit') ?></small></div>
    </div>
    <div class="progress mt-2" style="height: 4px;">
        <div class="progress-bar" style="width: 75%"></div>
    </div>
</div>

<!-- Summary -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="mb-0"><?php echo $rowCount ?></h3>
                <small class="text-muted"><?php echo __('Records to create') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="mb-0"><?php echo $doCount ?></h3>
                <small class="text-muted"><?php echo __('Digital objects') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="mb-0"><?php echo ucfirst($session->sector) ?></h3>
                <small class="text-muted"><?php echo strtoupper($session->standard) ?></small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tree View -->
    <div class="col-md-7">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i><?php echo __('Hierarchy Preview') ?></h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-expand-all">
                    <i class="fas fa-expand-alt me-1"></i><?php echo __('Expand All') ?>
                </button>
            </div>
            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                <?php if (!empty($tree)): ?>
                    <?php echo renderTree($tree); ?>
                <?php else: ?>
                    <p class="text-muted"><?php echo __('No hierarchy to display (flat import)') ?></p>
                <?php endif ?>
            </div>
        </div>
    </div>

    <!-- Detail Panel -->
    <div class="col-md-5">
        <div class="card mb-4" id="detail-panel">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Record Details') ?></h5>
            </div>
            <div class="card-body" id="detail-content">
                <p class="text-muted"><?php echo __('Click a record in the tree to view details') ?></p>
            </div>
        </div>

        <?php if ($session->output_generate_sip || $session->output_generate_dip): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-box me-2"></i><?php echo __('Package Estimates') ?></h5>
            </div>
            <div class="card-body">
                <?php if ($session->output_generate_sip): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo __('SIP Package') ?></span>
                        <span class="badge bg-secondary"><?php echo $rowCount ?> objects</span>
                    </div>
                <?php endif ?>
                <?php if ($session->output_generate_dip): ?>
                    <div class="d-flex justify-content-between">
                        <span><?php echo __('DIP Package') ?></span>
                        <span class="badge bg-secondary"><?php echo $rowCount ?> objects</span>
                    </div>
                <?php endif ?>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>

<div class="d-flex justify-content-between">
    <form method="post" action="<?php echo url_for(['module' => 'ingest', 'action' => 'preview', 'id' => $session->id]) ?>">
        <input type="hidden" name="form_action" value="back">
        <button type="submit" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Validation') ?>
        </button>
    </form>
    <div>
        <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'cancel', 'id' => $session->id]) ?>"
           class="btn btn-outline-danger me-2"
           onclick="return confirm('<?php echo __('Cancel this ingest?') ?>')">
            <i class="fas fa-times me-1"></i><?php echo __('Cancel') ?>
        </a>
        <form method="post" action="<?php echo url_for(['module' => 'ingest', 'action' => 'preview', 'id' => $session->id]) ?>" class="d-inline">
            <input type="hidden" name="form_action" value="approve">
            <button type="submit" class="btn btn-success"
                    onclick="return confirm('<?php echo __('This will create records in AtoM. Proceed?') ?>')">
                <i class="fas fa-check me-1"></i><?php echo __('Approve & Commit') ?>
                (<?php echo $rowCount ?> <?php echo __('records') ?>)
            </button>
        </form>
    </div>
</div>

<?php
/**
 * Render hierarchy tree recursively.
 */
function renderTree(array $nodes, int $depth = 0): string
{
    $html = '<ul class="list-unstyled ' . ($depth > 0 ? 'ms-3 tree-children' : '') . '"' .
            ($depth > 0 ? ' style="display:block;"' : '') . '>';

    foreach ($nodes as $node) {
        $cls = 'text-success';
        if ($node['is_excluded']) {
            $cls = 'text-decoration-line-through text-danger';
        } elseif (!$node['is_valid']) {
            $cls = 'text-warning';
        }

        $hasChildren = !empty($node['children']);

        $html .= '<li class="mb-1">';
        $html .= '<div class="d-flex align-items-center tree-node" data-row="' . $node['row_number'] . '" style="cursor:pointer;">';

        if ($hasChildren) {
            $html .= '<i class="fas fa-caret-down me-1 tree-toggle"></i>';
        } else {
            $html .= '<i class="fas fa-file me-1 text-muted" style="width:14px"></i>';
        }

        $html .= '<span class="' . $cls . '">';
        $html .= htmlspecialchars($node['title']);
        $html .= '</span>';

        if ($node['level']) {
            $html .= ' <small class="badge bg-secondary ms-1">' . htmlspecialchars($node['level']) . '</small>';
        }
        if ($node['has_do']) {
            $html .= ' <i class="fas fa-paperclip text-info ms-1" title="Has digital object"></i>';
        }

        $html .= '</div>';

        if ($hasChildren) {
            $html .= renderTree($node['children'], $depth + 1);
        }

        $html .= '</li>';
    }

    $html .= '</ul>';
    return $html;
}
?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Tree toggle
    document.querySelectorAll('.tree-toggle').forEach(function(icon) {
        icon.addEventListener('click', function(e) {
            e.stopPropagation();
            var li = this.closest('li');
            var children = li.querySelector('.tree-children');
            if (children) {
                var hidden = children.style.display === 'none';
                children.style.display = hidden ? 'block' : 'none';
                this.classList.toggle('fa-caret-down', hidden);
                this.classList.toggle('fa-caret-right', !hidden);
            }
        });
    });

    // Expand all
    document.getElementById('btn-expand-all').addEventListener('click', function() {
        document.querySelectorAll('.tree-children').forEach(function(el) {
            el.style.display = 'block';
        });
        document.querySelectorAll('.tree-toggle').forEach(function(el) {
            el.classList.remove('fa-caret-right');
            el.classList.add('fa-caret-down');
        });
    });

    // Node click → detail
    document.querySelectorAll('.tree-node').forEach(function(node) {
        node.addEventListener('click', function() {
            var row = this.dataset.row;
            var detailDiv = document.getElementById('detail-content');
            detailDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i></div>';

            fetch('<?php echo url_for(['module' => 'ingest', 'action' => 'previewTree']) ?>?session_id=<?php echo $session->id ?>', {
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(function(r) { return r.json(); })
            .then(function(tree) {
                var found = findNode(tree, parseInt(row));
                if (found) {
                    detailDiv.innerHTML =
                        '<h6>' + escHtml(found.title) + '</h6>' +
                        '<dl class="mb-0">' +
                        '<dt>Row</dt><dd>#' + found.row_number + '</dd>' +
                        '<dt>Level</dt><dd>' + escHtml(found.level || '—') + '</dd>' +
                        '<dt>Legacy ID</dt><dd>' + escHtml(found.legacy_id || '—') + '</dd>' +
                        '<dt>Digital Object</dt><dd>' + (found.has_do ? '<i class="fas fa-check text-success"></i> Yes' : 'No') + '</dd>' +
                        '<dt>Valid</dt><dd>' + (found.is_valid ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>') + '</dd>' +
                        '</dl>';
                }
            });
        });
    });

    function findNode(nodes, rowNum) {
        for (var i = 0; i < nodes.length; i++) {
            if (nodes[i].row_number === rowNum) return nodes[i];
            if (nodes[i].children && nodes[i].children.length) {
                var found = findNode(nodes[i].children, rowNum);
                if (found) return found;
            }
        }
        return null;
    }

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
});
</script>
