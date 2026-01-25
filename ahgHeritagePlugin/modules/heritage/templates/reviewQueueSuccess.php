<?php
/**
 * Contribution Review Queue.
 */

decorate_with('layout_2col');

// Helper to convert Symfony escaped arrays to plain arrays
$toArray = function($val) use (&$toArray) {
    if (is_array($val)) {
        return array_map($toArray, $val);
    }
    if ($val instanceof Traversable) {
        return array_map($toArray, iterator_to_array($val));
    }
    return $val;
};

$queueData = $toArray($queueData ?? []);
$types = $toArray($types ?? []);
$contributions = $queueData['contributions'] ?? [];
$countsByType = $queueData['counts_by_type'] ?? [];
$total = $queueData['total'] ?? 0;
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-inbox me-2"></i>Review Queue
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<?php include_partial('heritage/adminSidebar', ['active' => 'contributions']); ?>

<!-- Queue Stats -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0"><i class="fas fa-funnel me-2"></i>Filter by Type</h6>
    </div>
    <div class="list-group list-group-flush">
        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'reviewQueue']); ?>"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo empty($_GET['type']) ? 'active' : ''; ?>">
            All Types
            <span class="badge bg-primary"><?php echo $total; ?></span>
        </a>
        <?php foreach ($countsByType as $type): ?>
        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'reviewQueue', 'type' => $type['code']]); ?>"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo ($_GET['type'] ?? '') === $type['code'] ? 'active' : ''; ?>">
            <span>
                <i class="fas <?php echo $type['icon']; ?> me-2"></i>
                <?php echo esc_specialchars($type['name']); ?>
            </span>
            <span class="badge bg-warning"><?php echo $type['count']; ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php end_slot(); ?>

<!-- Main Content -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Pending Contributions</h5>
        <span class="badge bg-warning"><?php echo $total; ?> pending</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($contributions)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-check-circle display-1 text-success mb-3 d-block"></i>
            <p class="mb-0">All caught up! No pending contributions to review.</p>
        </div>
        <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($contributions as $contrib): ?>
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center">
                        <i class="fas <?php echo $contrib['type']['icon']; ?> fs-4 text-<?php echo $contrib['type']['color']; ?> me-3"></i>
                        <div>
                            <h6 class="mb-0">
                                <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'reviewContribution', 'id' => $contrib['id']]); ?>" class="text-decoration-none">
                                    <?php echo esc_specialchars($contrib['item']['title']); ?>
                                </a>
                            </h6>
                            <small class="text-muted">
                                <?php echo $contrib['type']['name']; ?> &middot;
                                Submitted <?php echo date('M d, Y H:i', strtotime($contrib['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-<?php echo match($contrib['contributor']['trust_level']) {
                            'expert' => 'primary',
                            'trusted' => 'success',
                            'contributor' => 'info',
                            default => 'secondary'
                        }; ?>">
                            <?php echo ucfirst($contrib['contributor']['trust_level']); ?>
                        </span>
                    </div>
                </div>

                <!-- Contributor Info -->
                <div class="d-flex align-items-center mb-2 small text-muted">
                    <span class="me-3">
                        <i class="fas fa-user me-1"></i>
                        <?php echo esc_specialchars($contrib['contributor']['display_name']); ?>
                    </span>
                    <span class="me-3">
                        <i class="fas fa-check-circle text-success me-1"></i>
                        <?php echo $contrib['contributor']['approved_count']; ?> approved
                    </span>
                    <span>
                        <i class="fas fa-gift text-primary me-1"></i>
                        +<?php echo $contrib['type']['points_value']; ?> pts
                    </span>
                </div>

                <!-- Content Preview -->
                <div class="bg-light rounded p-2 small mb-2">
                    <?php
                    $content = $contrib['content'];
                    if (!empty($content['text'])) {
                        echo esc_specialchars(substr($content['text'], 0, 200)) . (strlen($content['text'] ?? '') > 200 ? '...' : '');
                    } elseif (!empty($content['name'])) {
                        echo 'Identified: <strong>' . esc_specialchars($content['name']) . '</strong>';
                        if (!empty($content['relationship'])) {
                            echo ' (' . esc_specialchars($content['relationship']) . ')';
                        }
                    } elseif (!empty($content['suggestion'])) {
                        echo 'Field: <strong>' . esc_specialchars($content['field'] ?? 'unknown') . '</strong> → ';
                        echo esc_specialchars(substr($content['suggestion'], 0, 100));
                    } elseif (!empty($content['tags'])) {
                        echo 'Tags: ';
                        foreach ($content['tags'] as $tag) {
                            echo '<span class="badge bg-secondary me-1">' . esc_specialchars($tag) . '</span>';
                        }
                    }
                    ?>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-2">
                    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'reviewContribution', 'id' => $contrib['id']]); ?>"
                       class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>Review
                    </a>
                    <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $contrib['item']['slug']]); ?>"
                       class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="fas fa-box-arrow-up-right me-1"></i>View Item
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if (($queueData['pages'] ?? 1) > 1): ?>
        <div class="card-footer bg-transparent">
            <nav aria-label="Queue pagination">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php for ($i = 1; $i <= $queueData['pages']; $i++): ?>
                    <li class="page-item <?php echo $queueData['page'] == $i ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo url_for(['module' => 'heritage', 'action' => 'reviewQueue', 'page' => $i, 'type' => $_GET['type'] ?? null]); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
