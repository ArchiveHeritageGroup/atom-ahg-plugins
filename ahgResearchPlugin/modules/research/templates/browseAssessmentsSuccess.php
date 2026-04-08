<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Source Assessments</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2"><i class="fas fa-clipboard-check text-primary me-2"></i>Source Assessments</h1>
    <span class="badge bg-secondary fs-6"><?php echo count($assessments ?? []); ?> assessments</span>
</div>

<?php
$assessments = isset($assessments) && is_array($assessments) ? $assessments : (isset($assessments) && method_exists($assessments, 'getRawValue') ? $assessments->getRawValue() : []);
$metricCounts = isset($metricCounts) && is_array($metricCounts) ? $metricCounts : (isset($metricCounts) && method_exists($metricCounts, 'getRawValue') ? $metricCounts->getRawValue() : []);
?>

<div class="card">
    <div class="card-body p-0">
        <?php if (!empty($assessments)): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Record</th>
                        <th>Researcher</th>
                        <th>Source Type</th>
                        <th>Form</th>
                        <th>Completeness</th>
                        <th>Trust Score</th>
                        <th>Metrics</th>
                        <th>Assessed</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($assessments as $a): ?>
                    <tr>
                        <td>
                            <?php if ($a->slug): ?>
                            <a href="/<?php echo htmlspecialchars($a->slug); ?>"><?php echo htmlspecialchars($a->object_title ?? 'Untitled'); ?></a>
                            <?php else: ?>
                            <?php echo htmlspecialchars($a->object_title ?? 'Object #' . $a->object_id); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars(trim(($a->first_name ?? '') . ' ' . ($a->last_name ?? ''))); ?></td>
                        <td><span class="badge bg-<?php echo match($a->source_type) { 'primary' => 'success', 'secondary' => 'info', 'tertiary' => 'warning', default => 'secondary' }; ?>"><?php echo ucfirst($a->source_type ?? ''); ?></span></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $a->source_form ?? '')); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $a->completeness ?? '')); ?></td>
                        <td>
                            <?php $ts = (int) ($a->trust_score ?? 0); ?>
                            <span class="badge bg-<?php echo $ts >= 70 ? 'success' : ($ts >= 40 ? 'warning text-dark' : 'danger'); ?>"><?php echo $ts; ?></span>
                        </td>
                        <td>
                            <?php $mc = $metricCounts[$a->object_id] ?? 0; ?>
                            <?php if ($mc > 0): ?>
                            <span class="badge bg-info"><?php echo $mc; ?></span>
                            <?php else: ?>
                            <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo date('M j, Y', strtotime($a->assessed_at)); ?></small></td>
                        <td>
                            <a href="/research/source-assessment/<?php echo (int) $a->object_id; ?>" class="btn btn-sm btn-outline-primary" title="View/Edit"><i class="fas fa-pencil-alt"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-clipboard-check fa-3x mb-3 opacity-25"></i>
            <h5>No Assessments</h5>
            <p>Source assessments will appear here when researchers assess archival records.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
