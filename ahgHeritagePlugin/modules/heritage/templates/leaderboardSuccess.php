<?php
/**
 * Contributor Leaderboard.
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

$leaderboard = $toArray($leaderboard ?? []);
$stats = $toArray($stats ?? []);
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-trophy me-2"></i>Contributor Leaderboard
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<!-- Stats Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Community Stats</h6>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
            <span>Total Contributions</span>
            <strong><?php echo number_format($stats['total'] ?? 0); ?></strong>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <span>Approved</span>
            <strong class="text-success"><?php echo number_format($stats['approved'] ?? 0); ?></strong>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <span>Pending Review</span>
            <strong class="text-warning"><?php echo number_format($stats['pending'] ?? 0); ?></strong>
        </div>
        <hr>
        <div class="d-flex justify-content-between mb-2">
            <span>This Week</span>
            <strong class="text-primary"><?php echo number_format($stats['this_week'] ?? 0); ?></strong>
        </div>
        <div class="d-flex justify-content-between">
            <span>This Month</span>
            <strong class="text-info"><?php echo number_format($stats['this_month'] ?? 0); ?></strong>
        </div>
    </div>
</div>

<!-- By Type -->
<?php if (!empty($stats['by_type'])): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>By Type</h6>
    </div>
    <ul class="list-group list-group-flush">
        <?php foreach ($stats['by_type'] as $type): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>
                <i class="fas <?php echo $type['icon']; ?> me-2"></i>
                <?php echo esc_specialchars($type['name']); ?>
            </span>
            <span class="badge bg-primary"><?php echo number_format($type['total']); ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<?php end_slot(); ?>

<!-- Main Content -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Top Contributors</h5>
        <!-- Period Filter -->
        <div class="btn-group btn-group-sm">
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'leaderboard']); ?>"
               class="btn btn-outline-secondary <?php echo empty($period) ? 'active' : ''; ?>">All Time</a>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'leaderboard', 'period' => 'month']); ?>"
               class="btn btn-outline-secondary <?php echo $period === 'month' ? 'active' : ''; ?>">This Month</a>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'leaderboard', 'period' => 'week']); ?>"
               class="btn btn-outline-secondary <?php echo $period === 'week' ? 'active' : ''; ?>">This Week</a>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($leaderboard)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-users display-1 mb-3 d-block"></i>
            <p>No contributors yet. Be the first!</p>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contributorRegister']); ?>" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i>Join Now
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">Rank</th>
                        <th>Contributor</th>
                        <th class="text-center">Level</th>
                        <th class="text-center">Contributions</th>
                        <th class="text-center">Points</th>
                        <th class="text-center">Badges</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaderboard as $entry): ?>
                    <tr>
                        <td>
                            <?php if ($entry['rank'] === 1): ?>
                            <span class="fs-4">🥇</span>
                            <?php elseif ($entry['rank'] === 2): ?>
                            <span class="fs-4">🥈</span>
                            <?php elseif ($entry['rank'] === 3): ?>
                            <span class="fs-4">🥉</span>
                            <?php else: ?>
                            <span class="badge bg-secondary"><?php echo $entry['rank']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contributorProfile', 'id' => $entry['id']]); ?>"
                               class="text-decoration-none d-flex align-items-center">
                                <?php if (!empty($entry['avatar_url'])): ?>
                                <img src="<?php echo esc_specialchars($entry['avatar_url']); ?>"
                                     class="rounded-circle me-2" width="32" height="32" alt="">
                                <?php else: ?>
                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                    <i class="fas fa-user text-primary"></i>
                                </div>
                                <?php endif; ?>
                                <strong><?php echo esc_specialchars($entry['display_name']); ?></strong>
                            </a>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?php echo match($entry['trust_level']) {
                                'expert' => 'primary',
                                'trusted' => 'success',
                                'contributor' => 'info',
                                default => 'secondary'
                            }; ?>">
                                <?php echo ucfirst($entry['trust_level']); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <strong><?php echo number_format($entry['approved_contributions']); ?></strong>
                        </td>
                        <td class="text-center">
                            <strong class="text-primary"><?php echo number_format($entry['points']); ?></strong>
                        </td>
                        <td class="text-center">
                            <?php if ($entry['badge_count'] > 0): ?>
                            <span class="badge bg-warning">
                                <i class="fas fa-award"></i> <?php echo $entry['badge_count']; ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Call to Action -->
<div class="text-center mt-4">
    <p class="text-muted mb-3">Help preserve our heritage and earn recognition!</p>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search']); ?>" class="btn btn-primary btn-lg">
        <i class="fas fa-search me-2"></i>Find Items to Contribute
    </a>
</div>
