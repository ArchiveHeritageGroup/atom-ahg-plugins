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
                        <?php
                        // Grouped like the left nav: a bold section header + its items.
                        $groups = [
                            ['label' => __('Collections'), 'icon' => 'fa-layer-group', 'field' => 'collection_ids', 'items' => $collections, 'name' => 'name'],
                            ['label' => __('Projects'), 'icon' => 'fa-project-diagram', 'field' => 'project_ids', 'items' => $projects, 'name' => 'title'],
                            ['label' => __('Favourites folders'), 'icon' => 'fa-star', 'field' => 'folder_ids', 'items' => $folders, 'name' => 'name'],
                        ];
                        ?>
                        <?php foreach ($groups as $g): ?>
                            <?php if (!empty($g['items'])): ?>
                                <div class="list-group mb-3">
                                    <span class="list-group-item bg-light fw-bold text-uppercase small">
                                        <i class="fas <?php echo $g['icon']; ?> me-1"></i><?php echo $g['label']; ?>
                                    </span>
                                    <?php foreach ($g['items'] as $it): $nm = $g['name']; ?>
                                        <label class="list-group-item d-flex align-items-center">
                                            <input class="form-check-input me-2 mt-0" type="checkbox" name="<?php echo $g['field']; ?>[]" value="<?php echo (int) $it->id; ?>">
                                            <span class="flex-grow-1"><?php echo htmlspecialchars($it->$nm); ?></span>
                                            <span class="badge bg-secondary rounded-pill"><?php echo (int) $it->item_count; ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>

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
