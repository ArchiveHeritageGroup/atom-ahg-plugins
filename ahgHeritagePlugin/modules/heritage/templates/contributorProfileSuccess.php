<?php
/**
 * Public Contributor Profile.
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

$profile = $toArray($profile ?? []);
$contributor = $profile['contributor'] ?? [];
$badges = $profile['badges'] ?? [];
$recentContributions = $profile['recent_contributions'] ?? [];
$statsByType = $profile['stats_by_type'] ?? [];
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-user me-2"></i>Contributor Profile
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<!-- Profile Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body text-center">
        <?php if (!empty($contributor['avatar_url'])): ?>
        <img src="<?php echo esc_specialchars($contributor['avatar_url']); ?>"
             class="rounded-circle mb-3" width="100" height="100" alt="Avatar">
        <?php else: ?>
        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
            <i class="fas fa-user display-3 text-primary"></i>
        </div>
        <?php endif; ?>
        <h4 class="mb-1"><?php echo esc_specialchars($contributor['display_name'] ?? 'Contributor'); ?></h4>
        <span class="badge bg-<?php echo match($contributor['trust_level'] ?? 'new') {
            'expert' => 'primary',
            'trusted' => 'success',
            'contributor' => 'info',
            default => 'secondary'
        }; ?> mb-2">
            <?php echo ucfirst($contributor['trust_level'] ?? 'new'); ?> Contributor
        </span>
        <?php if (!empty($contributor['bio'])): ?>
        <p class="text-muted small mt-3 mb-0"><?php echo nl2br(esc_specialchars($contributor['bio'])); ?></p>
        <?php endif; ?>
        <p class="text-muted small mt-3 mb-0">
            Member since <?php echo date('F Y', strtotime($contributor['created_at'] ?? 'now')); ?>
        </p>
    </div>
</div>

<!-- Stats -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Statistics</h6>
    </div>
    <div class="card-body">
        <div class="row g-3 text-center">
            <div class="col-4">
                <div class="h4 text-primary mb-0"><?php echo number_format($contributor['approved_contributions'] ?? 0); ?></div>
                <small class="text-muted">Approved</small>
            </div>
            <div class="col-4">
                <div class="h4 text-success mb-0"><?php echo number_format($contributor['points'] ?? 0); ?></div>
                <small class="text-muted">Points</small>
            </div>
            <div class="col-4">
                <div class="h4 text-info mb-0"><?php echo count($badges); ?></div>
                <small class="text-muted">Badges</small>
            </div>
        </div>
    </div>
</div>

<!-- Badges -->
<?php if (!empty($badges)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h6 class="mb-0"><i class="fas fa-award me-2"></i>Badges Earned</h6>
    </div>
    <ul class="list-group list-group-flush">
        <?php foreach ($badges as $badge): ?>
        <li class="list-group-item d-flex align-items-center">
            <i class="fas <?php echo $badge['icon'] ?? 'fas fa-award'; ?> fs-4 text-<?php echo $badge['color'] ?? 'primary'; ?> me-3"></i>
            <div>
                <strong><?php echo esc_specialchars($badge['name']); ?></strong>
                <div class="small text-muted"><?php echo esc_specialchars($badge['description'] ?? ''); ?></div>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<?php end_slot(); ?>

<!-- Main Content -->
<!-- Contributions by Type -->
<?php if (!empty($statsByType)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h5 class="mb-0">Contributions by Type</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($statsByType as $stat): ?>
            <div class="col-md-4 col-6">
                <div class="d-flex align-items-center">
                    <i class="fas <?php echo $stat['icon']; ?> fs-3 text-primary me-3"></i>
                    <div>
                        <div class="h5 mb-0"><?php echo number_format($stat['count']); ?></div>
                        <small class="text-muted"><?php echo esc_specialchars($stat['name']); ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Contributions -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h5 class="mb-0">Recent Contributions</h5>
    </div>
    <?php if (empty($recentContributions)): ?>
    <div class="card-body text-center text-muted py-5">
        <i class="fas fa-inbox display-4 mb-3 d-block"></i>
        <p>No approved contributions yet.</p>
    </div>
    <?php else: ?>
    <div class="list-group list-group-flush">
        <?php foreach ($recentContributions as $contrib): ?>
        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $contrib['item_slug']]); ?>"
           class="list-group-item list-group-item-action">
            <div class="d-flex align-items-center">
                <i class="fas <?php echo $contrib['type_icon']; ?> fs-4 text-primary me-3"></i>
                <div class="flex-grow-1">
                    <h6 class="mb-0"><?php echo esc_specialchars($contrib['item_title'] ?? 'Untitled'); ?></h6>
                    <small class="text-muted">
                        <?php echo $contrib['type_name']; ?> &middot;
                        <?php echo date('M d, Y', strtotime($contrib['created_at'])); ?>
                    </small>
                </div>
                <i class="fas fa-chevron-right text-muted"></i>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="mt-4">
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'leaderboard']); ?>" class="btn btn-outline-primary">
        <i class="fas fa-trophy me-1"></i>View Leaderboard
    </a>
</div>
