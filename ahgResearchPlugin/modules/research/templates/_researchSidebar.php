<?php
/**
 * Research Plugin Sidebar Navigation.
 *
 * @param string $active  The active menu key
 * @param int    $unreadNotifications  Unread notification count
 */
$active = $active ?? '';
$unreadNotifications = $unreadNotifications ?? 0;
$isAdmin = $sf_user->isAdministrator();

// Research mode (cloned from Heratio): the mode curates this sidebar —
// Beginning shows the core essentials, Intermediate adds the working tools,
// Advanced reveals everything. Self-looked-up so it is correct on every page.
$expLevel = null;
if ($sf_user->isAuthenticated()) {
    try {
        $expLevel = \Illuminate\Database\Capsule\Manager::table('research_researcher')
            ->where('user_id', $sf_user->getAttribute('user_id'))
            ->value('experience_level');
    } catch (\Throwable $e) {
        $expLevel = null;
    }
}
$expLevel = $expLevel ?: 'intermediate';
$lvlRank = ['beginning' => 1, 'intermediate' => 2, 'advanced' => 3];
$lvlCur = $lvlRank[$expLevel] ?? 2;
$atLeast = function ($n) use ($lvlCur) { return $lvlCur >= $n; };
$modeUrl = url_for(['module' => 'research', 'action' => 'saveExperienceLevel']);
$guideUrl = url_for(['module' => 'research', 'action' => 'projects']) . '#research-modes';
$n = sfConfig::get('csp_nonce', '');
$nonce = $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
?>
<div class="list-group mb-4">
    <span class="list-group-item bg-light fw-bold text-uppercase small d-flex justify-content-between align-items-center">
        <span><?php echo __('Research mode'); ?></span>
        <a href="<?php echo $guideUrl; ?>" title="<?php echo __('What do these modes mean?'); ?>" class="text-decoration-none"><i class="fas fa-circle-question"></i></a>
    </span>
    <div class="list-group-item">
        <select id="research-experience-level" class="form-select form-select-sm" data-url="<?php echo $modeUrl; ?>" aria-label="<?php echo __('Research mode'); ?>">
            <option value="beginning" <?php echo $expLevel === 'beginning' ? 'selected' : ''; ?>><?php echo __('Beginning'); ?></option>
            <option value="intermediate" <?php echo $expLevel === 'intermediate' ? 'selected' : ''; ?>><?php echo __('Intermediate'); ?></option>
            <option value="advanced" <?php echo $expLevel === 'advanced' ? 'selected' : ''; ?>><?php echo __('Advanced'); ?></option>
        </select>
        <small id="research-experience-level-status" class="text-muted d-block mt-1" aria-live="polite"></small>
    </div>
</div>

<div class="list-group mb-4">
    <span class="list-group-item bg-light fw-bold text-uppercase small"><?php echo __('Research'); ?></span>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'workspace' ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt me-2"></i><?php echo __('My Workspace'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'projects']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'projects' ? 'active' : ''; ?>">
        <i class="fas fa-project-diagram me-2"></i><?php echo __('My Projects'); ?>
    </a>
<?php if ($atLeast(2)): ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'workspaces']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'workspaces' ? 'active' : ''; ?>">
        <i class="fas fa-users me-2"></i><?php echo __('Team Workspaces'); ?>
    </a>
<?php endif; ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'collections']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'collections' ? 'active' : ''; ?>">
        <i class="fas fa-layer-group me-2"></i><?php echo __('Evidence Sets'); ?>
    </a>
<?php if ($atLeast(2)): ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'journal']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'journal' ? 'active' : ''; ?>">
        <i class="fas fa-journal-whills me-2"></i><?php echo __('Research Journal'); ?>
    </a>
<?php endif; ?>
<?php if ($atLeast(3)): ?>
    <a href="<?php echo url_for(['module' => 'researchjournal', 'action' => 'index']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'journalBuilder' ? 'active' : ''; ?>">
        <i class="fas fa-book-open me-2"></i><?php echo __('Journal Builder'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'targetJournals']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'targetJournals' ? 'active' : ''; ?>">
        <i class="fas fa-compass me-2"></i><?php echo __('Where to Publish'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'lectures']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'lectures' ? 'active' : ''; ?>">
        <i class="fas fa-chalkboard-teacher me-2"></i><?php echo __('Lecture Builder'); ?>
    </a>
<?php endif; ?>
    <a href="<?php echo url_for(['module' => 'training', 'action' => 'index']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'training' ? 'active' : ''; ?>">
        <i class="fas fa-graduation-cap me-2"></i><?php echo __('Training Courses'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'bibliographies']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'bibliographies' ? 'active' : ''; ?>">
        <i class="fas fa-book me-2"></i><?php echo __('Bibliographies'); ?>
    </a>
<?php if ($atLeast(2)): ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'dmps']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'dmps' ? 'active' : ''; ?>">
        <i class="fas fa-clipboard-list me-2"></i><?php echo __('Data Management Plans'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'reports']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'reports' ? 'active' : ''; ?>">
        <i class="fas fa-file-alt me-2"></i><?php echo __('My Reports'); ?>
    </a>
<?php endif; ?>
<?php if (Illuminate\Database\Capsule\Manager::table('atom_plugin')->where('name', 'ahgFavoritesPlugin')->where('is_enabled', 1)->exists()): ?>
    <a href="<?php echo url_for(['module' => 'favorites', 'action' => 'browse']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'favorites' ? 'active' : ''; ?>">
        <i class="fas fa-heart me-2"></i><?php echo __('My Favorites'); ?>
    </a>
<?php endif; ?>
</div>

<div class="list-group mb-4">
    <span class="list-group-item bg-light fw-bold text-uppercase small"><?php echo __('Knowledge Platform'); ?></span>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'savedSearches']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'savedSearches' ? 'active' : ''; ?>">
        <i class="fas fa-search me-2"></i><?php echo __('Saved Searches'); ?>
    </a>
<?php if ($atLeast(2)): ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'annotations']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'annotations' ? 'active' : ''; ?>">
        <i class="fas fa-highlighter me-2"></i><?php echo __('Annotation Studio'); ?>
    </a>
<?php endif; ?>
<?php if ($atLeast(3)): ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'validationQueue']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'validationQueue' ? 'active' : ''; ?>">
        <i class="fas fa-check-double me-2"></i><?php echo __('Validation Queue'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'entityResolution']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'entityResolution' ? 'active' : ''; ?>">
        <i class="fas fa-object-group me-2"></i><?php echo __('Entity Resolution'); ?>
    </a>
<?php endif; ?>
<?php if ($atLeast(2)): ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'browseAssessments']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'assessments' ? 'active' : ''; ?>">
        <i class="fas fa-clipboard-check me-2"></i><?php echo __('Source Assessments'); ?>
    </a>
<?php endif; ?>
<?php if ($atLeast(3)): ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'odrlPolicies']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'odrlPolicies' ? 'active' : ''; ?>">
        <i class="fas fa-balance-scale me-2"></i><?php echo __('ODRL Policies'); ?>
    </a>
<?php endif; ?>
<?php if ($atLeast(2)): ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'documentTemplates']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'documentTemplates' ? 'active' : ''; ?>">
        <i class="fas fa-file-alt me-2"></i><?php echo __('Document Templates'); ?>
    </a>
<?php endif; ?>
</div>

<div class="list-group mb-4">
    <span class="list-group-item bg-light fw-bold text-uppercase small"><?php echo __('Services'); ?></span>
<?php if ($atLeast(2)): ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'reproductions']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'reproductions' ? 'active' : ''; ?>">
        <i class="fas fa-copy me-2"></i><?php echo __('Reproduction Requests'); ?>
    </a>
<?php endif; ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'book']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'book' ? 'active' : ''; ?>">
        <i class="fas fa-calendar-plus me-2"></i><?php echo __('Book Reading Room'); ?>
    </a>
</div>

<div class="list-group mb-4">
    <span class="list-group-item bg-light fw-bold text-uppercase small"><?php echo __('System'); ?></span>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'notifications']); ?>"
       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $active === 'notifications' ? 'active' : ''; ?>">
        <span><i class="fas fa-bell me-2"></i><?php echo __('Notifications'); ?></span>
        <?php if ($unreadNotifications > 0): ?>
        <span class="badge bg-danger rounded-pill"><?php echo $unreadNotifications; ?></span>
        <?php endif; ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'profile']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'profile' ? 'active' : ''; ?>">
        <i class="fas fa-user-cog me-2"></i><?php echo __('My Profile'); ?>
    </a>
</div>

<?php if ($isAdmin): ?>
<div class="list-group mb-4">
    <span class="list-group-item bg-light fw-bold text-uppercase small"><?php echo __('Administration'); ?></span>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'researchers']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'researchers' ? 'active' : ''; ?>">
        <i class="fas fa-user-check me-2"></i><?php echo __('Manage Researchers'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'bookings']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'bookings' ? 'active' : ''; ?>">
        <i class="fas fa-calendar-alt me-2"></i><?php echo __('Manage Bookings'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'rooms']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'rooms' ? 'active' : ''; ?>">
        <i class="fas fa-door-open me-2"></i><?php echo __('Reading Rooms'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'seats']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'seats' ? 'active' : ''; ?>">
        <i class="fas fa-chair me-2"></i><?php echo __('Seat Management'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'equipment']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'equipment' ? 'active' : ''; ?>">
        <i class="fas fa-tools me-2"></i><?php echo __('Equipment'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'retrievalQueue']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'retrievalQueue' ? 'active' : ''; ?>">
        <i class="fas fa-dolly me-2"></i><?php echo __('Retrieval Queue'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'walkIn']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'walkIn' ? 'active' : ''; ?>">
        <i class="fas fa-walking me-2"></i><?php echo __('Walk-In Visitors'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'adminTypes']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'adminTypes' ? 'active' : ''; ?>">
        <i class="fas fa-tags me-2"></i><?php echo __('Researcher Types'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'adminStatistics']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'adminStatistics' ? 'active' : ''; ?>">
        <i class="fas fa-chart-bar me-2"></i><?php echo __('Statistics'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'institutions']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'institutions' ? 'active' : ''; ?>">
        <i class="fas fa-university me-2"></i><?php echo __('Institutions'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'activities']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'activities' ? 'active' : ''; ?>">
        <i class="fas fa-stream me-2"></i><?php echo __('Activity Log'); ?>
    </a>
</div>
<?php endif; ?>

<script <?php echo $nonce; ?>>
(function () {
    var sel = document.getElementById('research-experience-level');
    if (!sel || sel.dataset.bound) return;
    sel.dataset.bound = '1';
    var status = document.getElementById('research-experience-level-status');
    function note(msg) { if (status) { status.textContent = msg; } }
    sel.addEventListener('change', function () {
        note('<?php echo __('Saving...'); ?>');
        fetch(sel.dataset.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: JSON.stringify({ level: sel.value })
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (d && d.ok) {
                // The sidebar curation is server-rendered, so reload to apply the
                // new mode's link set immediately.
                window.location.reload();
            } else {
                note('<?php echo __('Could not save'); ?>');
            }
        }).catch(function () { note('<?php echo __('Could not save'); ?>'); });
    });
})();
</script>
