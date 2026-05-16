<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$d = sfOutputEscaper::unescape($dashboard);
$totals = $d['usage_totals'];
$maxDaily = max(array_map(fn ($x) => $x['count'], $d['daily_series'] ?: [['count'=>1]]));
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Analytics</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h2 mb-0"><i class="fas fa-chart-line text-primary me-2"></i>Research analytics</h1>
    <form method="get" action="<?php echo url_for(['module' => 'research', 'action' => 'analytics']); ?>" class="d-flex">
        <input type="date" name="from" value="<?php echo htmlspecialchars($d['period']['from']); ?>" class="form-control form-control-sm me-1">
        <input type="date" name="to"   value="<?php echo htmlspecialchars($d['period']['to']); ?>"   class="form-control form-control-sm me-1">
        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
    </form>
</div>

<div class="row g-2 mb-4">
    <?php
        $tiles = [
            'Events',       $totals['total'],        'list',     'primary',
            'Researchers',  $totals['researchers'],  'users',    'success',
            'Objects',      $totals['objects'],      'archive',  'info',
            'Views',        $totals['views'],        'eye',      'secondary',
            'Searches',     $totals['searches'],     'search',   'warning',
            'Citations',    $totals['citations'],    'quote-right','danger',
            'Downloads',    $totals['downloads'],    'download', 'dark',
            'Annotations',  $totals['annotations'],  'highlighter','primary',
        ];
        for ($i = 0; $i < count($tiles); $i += 4):
            [$label, $value, $icon, $color] = [$tiles[$i], $tiles[$i+1], $tiles[$i+2], $tiles[$i+3]];
    ?>
        <div class="col-md-3">
            <div class="card border-<?php echo $color; ?>">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-<?php echo $icon; ?> text-<?php echo $color; ?> me-2"></i>
                        <div>
                            <div class="h4 mb-0"><?php echo number_format($value); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($label); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endfor; ?>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Daily volume</h5></div>
            <div class="card-body">
                <?php if (empty($d['daily_series'])): ?>
                    <p class="text-muted mb-0">No activity in this period.</p>
                <?php else: ?>
                    <div class="d-flex align-items-end" style="height:140px; gap:4px;">
                        <?php foreach ($d['daily_series'] as $row): ?>
                            <div class="flex-grow-1 d-flex flex-column align-items-center">
                                <div title="<?php echo htmlspecialchars($row['date']); ?>: <?php echo (int) $row['count']; ?>"
                                     style="background:#0d6efd; width:100%; height:<?php echo max(2, round(120 * $row['count'] / max(1, $maxDaily))); ?>px;"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-2">
                        <span><?php echo htmlspecialchars($d['daily_series'][0]['date']); ?></span>
                        <span><?php echo htmlspecialchars(end($d['daily_series'])['date']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-tags me-2"></i>Activity types</h5></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($d['top_activity_types'] as $t): ?>
                    <li class="list-group-item d-flex justify-content-between"><span><?php echo htmlspecialchars($t['type']); ?></span><strong><?php echo number_format($t['count']); ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Top researchers</h6></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($d['top_researchers'] as $r): ?>
                    <li class="list-group-item d-flex justify-content-between"><span><?php echo htmlspecialchars($r['name']); ?></span><strong><?php echo number_format($r['count']); ?></strong></li>
                <?php endforeach; ?>
                <?php if (empty($d['top_researchers'])): ?><li class="list-group-item text-muted">No data.</li><?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Popular descriptions</h6></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($d['popular_descriptions'] as $r): ?>
                    <li class="list-group-item d-flex justify-content-between"><span><?php echo htmlspecialchars(mb_substr($r['title'], 0, 50)); ?></span><strong><?php echo number_format($r['count']); ?></strong></li>
                <?php endforeach; ?>
                <?php if (empty($d['popular_descriptions'])): ?><li class="list-group-item text-muted">No data.</li><?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Top search terms</h6></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($d['search_terms'] as $r): ?>
                    <li class="list-group-item d-flex justify-content-between"><span><code><?php echo htmlspecialchars(mb_substr($r['term'], 0, 50)); ?></code></span><strong><?php echo number_format($r['count']); ?></strong></li>
                <?php endforeach; ?>
                <?php if (empty($d['search_terms'])): ?><li class="list-group-item text-muted">No data.</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Popular collections</h6></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($d['popular_collections'] as $r): ?>
                    <li class="list-group-item d-flex justify-content-between"><span><?php echo htmlspecialchars($r['name']); ?></span><strong><?php echo number_format($r['count']); ?></strong></li>
                <?php endforeach; ?>
                <?php if (empty($d['popular_collections'])): ?><li class="list-group-item text-muted">No data.</li><?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Citations by style/format</h6></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($d['citations_by_style'] as $r): ?>
                    <li class="list-group-item d-flex justify-content-between"><span><?php echo htmlspecialchars($r['style']); ?></span><strong><?php echo number_format($r['count']); ?></strong></li>
                <?php endforeach; ?>
                <?php if (empty($d['citations_by_style'])): ?><li class="list-group-item text-muted">No data.</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>
