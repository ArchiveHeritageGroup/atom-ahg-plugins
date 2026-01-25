<?php
/**
 * My Contributions.
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

$contributionData = $toArray($contributionData ?? []);
$profile = $toArray($profile ?? []);
$contributions = $contributionData['contributions'] ?? [];
$stats = $contributionData['stats'] ?? [];
$contributor = $profile['contributor'] ?? [];
$badges = $profile['badges'] ?? [];
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-journal-whills-text me-2"></i>My Contributions
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<!-- Profile Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body text-center">
        <?php if (!empty($contributor['avatar_url'])): ?>
        <img src="<?php echo esc_specialchars($contributor['avatar_url']); ?>"
             class="rounded-circle mb-3" width="80" height="80" alt="Avatar">
        <?php else: ?>
        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
            <i class="fas fa-user display-4 text-primary"></i>
        </div>
        <?php endif; ?>
        <h5 class="mb-1"><?php echo esc_specialchars($contributor['display_name'] ?? 'Contributor'); ?></h5>
        <span class="badge bg-<?php echo match($contributor['trust_level'] ?? 'new') {
            'expert' => 'primary',
            'trusted' => 'success',
            'contributor' => 'info',
            default => 'secondary'
        }; ?>">
            <?php echo ucfirst($contributor['trust_level'] ?? 'new'); ?>
        </span>
        <p class="text-muted small mt-2 mb-0">
            Member since <?php echo date('M Y', strtotime($contributor['created_at'] ?? 'now')); ?>
        </p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-2 mb-4">
    <div class="col-6">
        <div class="card border-0 bg-primary bg-opacity-10 text-center">
            <div class="card-body py-3">
                <div class="h4 mb-0 text-primary"><?php echo number_format($stats['total'] ?? 0); ?></div>
                <small class="text-muted">Total</small>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card border-0 bg-success bg-opacity-10 text-center">
            <div class="card-body py-3">
                <div class="h4 mb-0 text-success"><?php echo number_format($stats['approved'] ?? 0); ?></div>
                <small class="text-muted">Approved</small>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card border-0 bg-warning bg-opacity-10 text-center">
            <div class="card-body py-3">
                <div class="h4 mb-0 text-warning"><?php echo number_format($stats['pending'] ?? 0); ?></div>
                <small class="text-muted">Pending</small>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card border-0 bg-info bg-opacity-10 text-center">
            <div class="card-body py-3">
                <div class="h4 mb-0 text-info"><?php echo number_format($stats['total_points'] ?? 0); ?></div>
                <small class="text-muted">Points</small>
            </div>
        </div>
    </div>
</div>

<!-- Badges -->
<?php if (!empty($badges)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h6 class="mb-0"><i class="fas fa-award me-2"></i>Badges</h6>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($badges as $badge): ?>
            <span class="badge bg-<?php echo $badge['color'] ?? 'primary'; ?>" title="<?php echo esc_specialchars($badge['description'] ?? ''); ?>">
                <i class="fas <?php echo $badge['icon'] ?? 'fas fa-award'; ?> me-1"></i>
                <?php echo esc_specialchars($badge['name']); ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
<?php end_slot(); ?>

<!-- Main Content -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Contribution History</h5>
        <!-- Status Filter -->
        <div class="btn-group btn-group-sm">
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'myContributions']); ?>"
               class="btn btn-outline-secondary <?php echo empty($_GET['status']) ? 'active' : ''; ?>">All</a>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'myContributions', 'status' => 'pending']); ?>"
               class="btn btn-outline-secondary <?php echo ($_GET['status'] ?? '') === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'myContributions', 'status' => 'approved']); ?>"
               class="btn btn-outline-secondary <?php echo ($_GET['status'] ?? '') === 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'myContributions', 'status' => 'rejected']); ?>"
               class="btn btn-outline-secondary <?php echo ($_GET['status'] ?? '') === 'rejected' ? 'active' : ''; ?>">Rejected</a>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($contributions)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-inbox display-1 mb-3 d-block"></i>
            <p class="mb-3">No contributions yet.</p>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search']); ?>" class="btn btn-primary">
                <i class="fas fa-search me-1"></i>Browse Collection
            </a>
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
                                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $contrib['item']['slug']]); ?>" class="text-decoration-none">
                                    <?php echo esc_specialchars($contrib['item']['title']); ?>
                                </a>
                            </h6>
                            <small class="text-muted">
                                <?php echo $contrib['type']['name']; ?> &middot;
                                <?php echo date('M d, Y', strtotime($contrib['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                    <div class="text-end">
                        <?php
                        $statusColor = match($contrib['status']) {
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'pending' => 'warning',
                            default => 'secondary'
                        };
                        ?>
                        <span class="badge bg-<?php echo $statusColor; ?>">
                            <?php echo ucfirst($contrib['status']); ?>
                        </span>
                        <?php if ($contrib['points_awarded'] > 0): ?>
                        <div class="small text-success mt-1">
                            +<?php echo $contrib['points_awarded']; ?> pts
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Content Preview -->
                <div class="bg-light rounded p-2 small mb-2">
                    <?php
                    $content = $contrib['content'];
                    if (!empty($content['text'])) {
                        echo esc_specialchars(substr($content['text'], 0, 200)) . (strlen($content['text'] ?? '') > 200 ? '...' : '');
                    } elseif (!empty($content['name'])) {
                        echo 'Identified: ' . esc_specialchars($content['name']);
                    } elseif (!empty($content['suggestion'])) {
                        echo 'Correction: ' . esc_specialchars(substr($content['suggestion'], 0, 100));
                    } elseif (!empty($content['tags'])) {
                        echo 'Tags: ' . esc_specialchars(implode(', ', $content['tags']));
                    }
                    ?>
                </div>

                <!-- Review Notes -->
                <?php if (!empty($contrib['review_notes'])): ?>
                <div class="alert alert-<?php echo $contrib['status'] === 'approved' ? 'success' : 'danger'; ?> py-2 small mb-0">
                    <strong>Reviewer:</strong> <?php echo esc_specialchars($contrib['review_notes']); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if (($contributionData['pages'] ?? 1) > 1): ?>
        <div class="card-footer bg-transparent">
            <nav aria-label="Contribution pagination">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php for ($i = 1; $i <= $contributionData['pages']; $i++): ?>
                    <li class="page-item <?php echo $contributionData['page'] == $i ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo url_for(['module' => 'heritage', 'action' => 'myContributions', 'page' => $i, 'status' => $_GET['status'] ?? null]); ?>">
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
