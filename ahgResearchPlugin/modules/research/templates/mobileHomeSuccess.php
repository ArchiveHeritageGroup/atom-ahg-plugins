<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => 'offline', 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$researcher = sfOutputEscaper::unescape($researcher);
$collections = sfOutputEscaper::unescape($collections ?? []);
$projects = sfOutputEscaper::unescape($projects ?? []);
$folders = sfOutputEscaper::unescape($folders ?? []);
$buildUrl = url_for(['module' => 'research', 'action' => 'buildOfflinePackage']);
$syncUrl = url_for(['module' => 'research', 'action' => 'syncUpload']);
$hasAny = count($collections) + count($projects) + count($folders) > 0;
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active"><?php echo __('Work Offline'); ?></li>
    </ol>
</nav>

<h1 class="h2"><i class="fas fa-laptop me-2"></i><?php echo __('Work Offline'); ?></h1>

<?php if ($msg = $sf_user->getFlash('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<?php if ($msg = $sf_user->getFlash('error')): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- STEP 1: take offline -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-primary text-white"><i class="fas fa-download me-1"></i><?php echo __('1. Take records offline'); ?></div>
            <div class="card-body">
                <p class="text-muted small">
                    <?php echo __('Choose what to take with you, then download a self-contained package. It opens in any web browser with no internet or login — add notes, sources, suggestions and files, then bring them back below. Only records you are permitted to see are included.'); ?>
                </p>

                <?php if (!$hasAny): ?>
                    <div class="alert alert-info small mb-0">
                        <?php echo __('You have no collections, projects or favourites folders yet. Add records to a Collection, Project or Favourites folder first.'); ?>
                    </div>
                <?php else: ?>
                    <form method="post" action="<?php echo $buildUrl; ?>">
                        <?php if (!empty($collections)): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold mb-1"><i class="fas fa-layer-group me-1"></i><?php echo __('Collections'); ?></label>
                                <?php foreach ($collections as $c): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="collection_ids[]" value="<?php echo (int) $c->id; ?>" id="c<?php echo (int) $c->id; ?>">
                                        <label class="form-check-label" for="c<?php echo (int) $c->id; ?>"><?php echo htmlspecialchars($c->name); ?> <span class="text-muted small">(<?php echo (int) $c->item_count; ?>)</span></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($projects)): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold mb-1"><i class="fas fa-project-diagram me-1"></i><?php echo __('Projects'); ?></label>
                                <?php foreach ($projects as $p): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="project_ids[]" value="<?php echo (int) $p->id; ?>" id="p<?php echo (int) $p->id; ?>">
                                        <label class="form-check-label" for="p<?php echo (int) $p->id; ?>"><?php echo htmlspecialchars($p->title); ?> <span class="text-muted small">(<?php echo (int) $p->item_count; ?>)</span></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($folders)): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold mb-1"><i class="fas fa-star me-1"></i><?php echo __('Favourites folders'); ?></label>
                                <?php foreach ($folders as $f): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="folder_ids[]" value="<?php echo (int) $f->id; ?>" id="f<?php echo (int) $f->id; ?>">
                                        <label class="form-check-label" for="f<?php echo (int) $f->id; ?>"><?php echo htmlspecialchars($f->name); ?> <span class="text-muted small">(<?php echo (int) $f->item_count; ?>)</span></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="include_notes" value="1" id="incnotes" checked>
                            <label class="form-check-label" for="incnotes"><?php echo __('Include my existing notes/annotations on those records'); ?></label>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-download me-1"></i><?php echo __('Download offline package'); ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- STEP 2: sync back -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-success text-white"><i class="fas fa-cloud-arrow-up me-1"></i><?php echo __('2. Bring your work back'); ?></div>
            <div class="card-body">
                <p class="text-muted small">
                    <?php echo __('Finished working offline? In the package, click "Save for sync" to download a researcher-sync.json file, then upload it here. Your notes and sources are added to your research, files are attached, and metadata suggestions go to a curator for review.'); ?>
                </p>
                <form method="post" action="<?php echo $syncUrl; ?>" enctype="multipart/form-data">
                    <div class="mb-1">
                        <input type="file" name="sync_file" class="form-control" required>
                    </div>
                    <div class="form-text mb-2"><?php echo __('Choose the <strong>researcher-sync.json</strong> file you downloaded from the package.'); ?></div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-cloud-arrow-up me-1"></i><?php echo __('Upload &amp; sync'); ?></button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><i class="fas fa-circle-info me-1"></i><?php echo __('How it works'); ?></div>
            <div class="card-body small text-muted">
                <ol class="mb-0 ps-3">
                    <li><?php echo __('Pick collections / projects / favourites and download the package.'); ?></li>
                    <li><?php echo __('Open index.html in any browser — no internet needed.'); ?></li>
                    <li><?php echo __('Add notes, sources, suggestions, files to records.'); ?></li>
                    <li><?php echo __('Click "Save for sync" → get researcher-sync.json.'); ?></li>
                    <li><?php echo __('Upload it here to bring everything back.'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
