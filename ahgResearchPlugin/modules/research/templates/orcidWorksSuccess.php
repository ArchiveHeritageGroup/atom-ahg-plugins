<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$link  = sfOutputEscaper::unescape($link);
$works = sfOutputEscaper::unescape($works ?? []);
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">ORCID Works</li>
    </ol>
</nav>

<h1 class="h2 mb-3"><i class="fab fa-orcid text-success me-2"></i>ORCID Works</h1>

<?php if ($msg = $sf_user->getFlash('error')): ?><div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($msg = $sf_user->getFlash('success')): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-warning"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<?php if (!$config_ok): ?>
    <div class="alert alert-secondary">
        <strong>ORCID is not configured.</strong>
        Set <code>orcid_client_id</code>, <code>orcid_client_secret</code>, and <code>orcid_redirect_uri</code> in
        <code>atom-ahg-plugins/ahgResearchPlugin/config/app.yml</code>.
    </div>
<?php elseif (!$link): ?>
    <div class="alert alert-info">
        You have not linked your ORCID yet.
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'orcidConnect']); ?>" class="btn btn-success btn-sm ms-2"><i class="fab fa-orcid me-1"></i>Connect with ORCID</a>
    </div>
<?php else: ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-9">
                    <div><strong>ORCID iD:</strong> <a href="https://orcid.org/<?php echo htmlspecialchars($link->orcid_id); ?>"><?php echo htmlspecialchars($link->orcid_id); ?></a></div>
                    <div class="small text-muted">Scope: <?php echo htmlspecialchars($link->scope ?: '-'); ?> &middot;
                        Expires: <?php echo htmlspecialchars($link->expires_at ?: '-'); ?> &middot;
                        Last sync: <?php echo htmlspecialchars($link->last_synced_at ?: 'never'); ?> &middot;
                        Last works count: <?php echo (int) $link->last_works_count; ?>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'orcidWorks']); ?>" class="d-inline">
                        <input type="hidden" name="form_action" value="pull">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync me-1"></i>Pull works</button>
                    </form>
                    <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'orcidWorks']); ?>" class="d-inline">
                        <input type="hidden" name="form_action" value="unlink">
                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Unlink this ORCID?');"><i class="fas fa-unlink me-1"></i>Unlink</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <h5 class="mb-2">Works (<?php echo count($works); ?>)</h5>
    <?php if (empty($works)): ?>
        <p class="text-muted">No works fetched yet. Click "Pull works" above.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr><th>Title</th><th>Type</th><th>Year</th><th>Journal</th><th>DOI</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($works as $w): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($w['title'] ?: '-'); ?></td>
                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($w['type'] ?: '-'); ?></span></td>
                            <td><?php echo htmlspecialchars($w['year'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($w['journal'] ?: '-'); ?></td>
                            <td>
                                <?php if ($w['doi']): ?>
                                    <a href="https://doi.org/<?php echo htmlspecialchars($w['doi']); ?>"><?php echo htmlspecialchars($w['doi']); ?></a>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endif; ?>
