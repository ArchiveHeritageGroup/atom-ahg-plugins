<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$fonds = sfOutputEscaper::unescape($fonds ?? []);
$results = sfOutputEscaper::unescape($results);
$selected = $selectedFondsIds ?? [];
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Cross-fonds query</li>
    </ol>
</nav>

<h1 class="h2 mb-3"><i class="fas fa-network-wired text-primary me-2"></i>Cross-fonds reasoning query</h1>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?php echo url_for(['module' => 'research', 'action' => 'crossFondsQuery']); ?>">
            <div class="mb-3">
                <label class="form-label fw-semibold">Query</label>
                <input type="text" name="q" class="form-control form-control-lg" placeholder="Search across selected fonds…" value="<?php echo htmlspecialchars($q); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Fonds / collections (Ctrl/Cmd-click to multi-select)</label>
                <select name="fonds[]" class="form-select" multiple size="10" required>
                    <?php foreach ($fonds as $f): ?>
                        <option value="<?php echo (int) $f['id']; ?>" <?php echo in_array((int) $f['id'], $selected, true) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(($f['title'] ?: 'Untitled') . ' (' . $f['level'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted"><?php echo count($fonds); ?> fonds/collections available.</small>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="expand" value="1" class="form-check-input" id="expand-chk" <?php echo $expand ? 'checked' : ''; ?>>
                <label class="form-check-label" for="expand-chk">Expand with thesaurus terms (if semantic-search is enabled)</label>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Run</button>
        </form>
    </div>
</div>

<?php if (is_array($results)): ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Results <span class="badge bg-secondary"><?php echo count($results['results']); ?></span></h5>
        <small class="text-muted"><?php echo $results['elapsed_ms']; ?>ms &middot; <?php echo $results['total']; ?> total hits</small>
    </div>
    <?php if (!empty($results['expanded_query'])): ?>
        <div class="alert alert-info small">Expanded query: <code><?php echo htmlspecialchars($results['expanded_query']); ?></code></div>
    <?php endif; ?>

    <?php if (empty($results['results'])): ?>
        <div class="card"><div class="card-body text-muted">No hits.</div></div>
    <?php else: ?>
        <ol class="list-group list-group-numbered" id="cross-fonds-results">
            <?php foreach ($results['results'] as $i => $r): ?>
                <li class="list-group-item d-flex justify-content-between align-items-start" data-citation-n="<?php echo (int) $i + 1; ?>">
                    <div class="ms-2 me-auto">
                        <strong><?php echo htmlspecialchars($r['title']); ?></strong>
                        <?php if (!empty($r['reference'])): ?> <span class="badge bg-light text-dark"><?php echo htmlspecialchars($r['reference']); ?></span><?php endif; ?>
                        <div class="small text-muted"><?php echo htmlspecialchars($r['snippet']); ?></div>
                        <?php if (!empty($r['slug'])): ?>
                            <a href="/index.php/<?php echo htmlspecialchars($r['slug']); ?>" class="small">Open record &raquo;</a>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-primary rounded-pill" title="Elasticsearch score"><?php echo number_format($r['score'], 2); ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
<?php endif; ?>
