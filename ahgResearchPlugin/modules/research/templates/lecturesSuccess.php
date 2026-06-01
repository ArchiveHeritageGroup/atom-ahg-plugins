<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$curriculum = sfOutputEscaper::unescape($curriculum ?? []);
$talks      = sfOutputEscaper::unescape($talks ?? []);
$standalone = sfOutputEscaper::unescape($standalone ?? []);

$statusBadge = function ($s) {
    $map = [
        'draft' => 'secondary', 'scheduled' => 'info', 'delivered' => 'primary',
        'published' => 'success', 'archived' => 'dark',
    ];
    $cls = $map[$s] ?? 'secondary';

    return '<span class="badge bg-' . $cls . '">' . htmlspecialchars(ucfirst((string) $s)) . '</span>';
};

$renderGroup = function (array $rows, string $type, string $icon, string $title) use ($statusBadge) {
    echo '<div class="card mb-4">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<h5 class="mb-0"><i class="fas ' . $icon . ' me-2"></i>' . htmlspecialchars($title) . ' <span class="badge bg-secondary">' . count($rows) . '</span></h5>';
    echo '<a class="btn btn-sm btn-primary" href="' . url_for(['module' => 'research', 'action' => 'lectureBuilder', 'type' => $type]) . '"><i class="fas fa-plus me-1"></i>New</a>';
    echo '</div>';
    echo '<div class="card-body p-0">';
    if (empty($rows)) {
        echo '<div class="p-3 text-muted">No ' . htmlspecialchars($title) . ' yet.</div>';
    } else {
        echo '<div class="list-group list-group-flush">';
        foreach ($rows as $l) {
            $url = url_for(['module' => 'research', 'action' => 'lectureShow', 'id' => $l['id']]);
            echo '<a href="' . $url . '" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">';
            echo '<div><strong>' . htmlspecialchars($l['title']) . '</strong>';
            if (!empty($l['subtitle'])) {
                echo '<div class="small text-muted">' . htmlspecialchars($l['subtitle']) . '</div>';
            }
            if (!empty($l['speaker_name'])) {
                echo '<div class="small text-muted"><i class="fas fa-user me-1"></i>' . htmlspecialchars($l['speaker_name']);
                if (!empty($l['speaker_affiliation'])) {
                    echo ' &middot; ' . htmlspecialchars($l['speaker_affiliation']);
                }
                echo '</div>';
            }
            if (!empty($l['scheduled_at'])) {
                echo '<div class="small text-muted"><i class="fas fa-calendar me-1"></i>' . htmlspecialchars(date('j M Y H:i', strtotime($l['scheduled_at']))) . '</div>';
            }
            echo '</div>';
            echo '<div>' . $statusBadge($l['status'] ?? 'draft') . '</div>';
            echo '</a>';
        }
        echo '</div>';
    }
    echo '</div></div>';
};
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Lecture Builder</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h2 mb-0"><i class="fas fa-chalkboard-teacher text-primary me-2"></i><?php echo __('Lecture Builder'); ?></h1>
</div>

<?php if ($msg = $sf_user->getFlash('error')): ?><div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($msg = $sf_user->getFlash('success')): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<?php $renderGroup($curriculum, 'curriculum', 'fa-graduation-cap', 'Curriculum content'); ?>
<?php $renderGroup($talks, 'talk', 'fa-microphone', 'Talks &amp; public lectures'); ?>
<?php $renderGroup($standalone, 'standalone', 'fa-file-alt', 'Standalone lectures'); ?>
