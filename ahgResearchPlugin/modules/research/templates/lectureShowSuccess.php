<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$lecture   = sfOutputEscaper::unescape($lecture ?? []);
$sections  = sfOutputEscaper::unescape($sections ?? []);
$resources = sfOutputEscaper::unescape($resources ?? []);
$id        = (int) $lecture['id'];
$status    = $lecture['status'] ?? 'draft';
$isPublished = ($status === 'published');
$mediaTypes = ['image', 'video', 'audio', 'embed'];
$resourceTypes = ['reading', 'slides', 'video', 'link', 'file'];
$statuses = ['draft', 'scheduled', 'delivered', 'published', 'archived'];
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'lectures']); ?>">Lecture Builder</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($lecture['title']); ?></li>
    </ol>
</nav>

<?php if ($msg = $sf_user->getFlash('error')): ?><div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($msg = $sf_user->getFlash('success')): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h2 mb-1"><?php echo htmlspecialchars($lecture['title']); ?>
            <span class="badge bg-light text-dark text-capitalize"><?php echo htmlspecialchars($lecture['type']); ?></span>
        </h1>
        <?php if (!empty($lecture['subtitle'])): ?><p class="text-muted mb-1"><?php echo htmlspecialchars($lecture['subtitle']); ?></p><?php endif; ?>
        <span class="badge bg-<?php echo $isPublished ? 'success' : 'secondary'; ?> text-capitalize"><?php echo htmlspecialchars($status); ?></span>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?php echo url_for(['module' => 'research', 'action' => 'lectureBuilder', 'id' => $id]); ?>"><i class="fas fa-edit me-1"></i>Edit</a>
        <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'lecturePublish', 'id' => $id]); ?>" class="d-inline">
            <input type="hidden" name="publish" value="<?php echo $isPublished ? 0 : 1; ?>">
            <button class="btn btn-outline-<?php echo $isPublished ? 'warning' : 'success'; ?>" type="submit">
                <i class="fas fa-<?php echo $isPublished ? 'eye-slash' : 'globe'; ?> me-1"></i><?php echo $isPublished ? 'Unpublish' : 'Publish'; ?>
            </button>
        </form>
        <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'lectureDelete', 'id' => $id]); ?>" class="d-inline" onsubmit="return confirm('Delete this lecture and all its sections and resources?');">
            <button class="btn btn-outline-danger" type="submit"><i class="fas fa-trash me-1"></i>Delete</button>
        </form>
    </div>
</div>

<!-- Talk / schedule meta -->
<?php if (!empty($lecture['speaker_name']) || !empty($lecture['scheduled_at']) || !empty($lecture['location']) || !empty($lecture['duration_minutes']) || !empty($lecture['recording_url']) || !empty($lecture['slides_url'])): ?>
<div class="card mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <?php if (!empty($lecture['speaker_name'])): ?>
                <dt class="col-sm-3">Speaker</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($lecture['speaker_name']); ?><?php if (!empty($lecture['speaker_affiliation'])): ?> <span class="text-muted">&middot; <?php echo htmlspecialchars($lecture['speaker_affiliation']); ?></span><?php endif; ?></dd>
            <?php endif; ?>
            <?php if (!empty($lecture['scheduled_at'])): ?>
                <dt class="col-sm-3">Scheduled</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars(date('j M Y, H:i', strtotime($lecture['scheduled_at']))); ?></dd>
            <?php endif; ?>
            <?php if (!empty($lecture['location'])): ?>
                <dt class="col-sm-3">Venue</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($lecture['location']); ?></dd>
            <?php endif; ?>
            <?php if (!empty($lecture['duration_minutes'])): ?>
                <dt class="col-sm-3">Duration</dt>
                <dd class="col-sm-9"><?php echo (int) $lecture['duration_minutes']; ?> min</dd>
            <?php endif; ?>
            <?php if (!empty($lecture['recording_url'])): ?>
                <dt class="col-sm-3">Recording</dt>
                <dd class="col-sm-9"><a href="<?php echo htmlspecialchars($lecture['recording_url'], ENT_QUOTES); ?>" target="_blank" rel="noopener">Open recording</a></dd>
            <?php endif; ?>
            <?php if (!empty($lecture['slides_url'])): ?>
                <dt class="col-sm-3">Slides</dt>
                <dd class="col-sm-9"><a href="<?php echo htmlspecialchars($lecture['slides_url'], ENT_QUOTES); ?>" target="_blank" rel="noopener">Open slides</a></dd>
            <?php endif; ?>
            <?php if (!empty($lecture['curriculum_ref'])): ?>
                <dt class="col-sm-3">Curriculum ref</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($lecture['curriculum_ref']); ?></dd>
            <?php endif; ?>
        </dl>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($lecture['summary'])): ?>
<div class="card mb-3"><div class="card-body"><p class="mb-0"><?php echo nl2br(htmlspecialchars($lecture['summary'])); ?></p></div></div>
<?php endif; ?>

<!-- Status quick-set -->
<form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'lectureStatus', 'id' => $id]); ?>" class="d-flex align-items-center gap-2 mb-4">
    <label class="form-label mb-0">Status</label>
    <select name="status" class="form-select form-select-sm" style="width:auto;">
        <?php foreach ($statuses as $s): ?>
            <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-outline-secondary" type="submit">Update</button>
</form>

<div class="row">
    <!-- Sections -->
    <div class="col-lg-8">
        <h4><i class="fas fa-list-ol me-2"></i>Content sections <span class="badge bg-secondary"><?php echo count($sections); ?></span></h4>
        <?php if (empty($sections)): ?>
            <p class="text-muted">No sections yet. Add one below.</p>
        <?php else: ?>
            <?php foreach ($sections as $sec): ?>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><?php echo htmlspecialchars($sec['heading'] ?? '(untitled section)'); ?></strong>
                        <span>
                            <span class="badge bg-light text-dark">#<?php echo (int) $sec['sort_order']; ?></span>
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo url_for(['module' => 'research', 'action' => 'lectureSectionEdit', 'id' => $sec['id']]); ?>"><i class="fas fa-edit"></i></a>
                            <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'lectureSectionDelete', 'id' => $sec['id']]); ?>" class="d-inline" onsubmit="return confirm('Remove this section?');">
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
                            </form>
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($sec['media_url'])): ?>
                            <?php $mt = $sec['media_type'] ?? 'embed'; $murl = htmlspecialchars($sec['media_url'], ENT_QUOTES); ?>
                            <div class="mb-3">
                                <?php if ($mt === 'image'): ?>
                                    <img src="<?php echo $murl; ?>" class="img-fluid rounded" alt="">
                                <?php elseif ($mt === 'video'): ?>
                                    <video src="<?php echo $murl; ?>" controls class="w-100"></video>
                                <?php elseif ($mt === 'audio'): ?>
                                    <audio src="<?php echo $murl; ?>" controls class="w-100"></audio>
                                <?php else: ?>
                                    <a href="<?php echo $murl; ?>" target="_blank" rel="noopener"><i class="fas fa-external-link-alt me-1"></i>Embedded media</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="lecture-section-body"><?php echo $sec['body_html'] ?? ''; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Add section -->
        <div class="card border-primary">
            <div class="card-header bg-primary text-white"><i class="fas fa-plus me-2"></i>Add section</div>
            <div class="card-body">
                <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'lectureSectionStore', 'id' => $id]); ?>">
                    <div class="mb-2"><label class="form-label">Heading</label><input type="text" name="heading" maxlength="255" class="form-control"></div>
                    <div class="mb-2"><label class="form-label">Body (Markdown)</label><textarea name="body_markdown" rows="5" class="form-control"></textarea></div>
                    <div class="row">
                        <div class="col-md-8 mb-2"><label class="form-label">Media URL</label><input type="text" name="media_url" maxlength="1000" class="form-control"></div>
                        <div class="col-md-4 mb-2"><label class="form-label">Media type</label>
                            <select name="media_type" class="form-select"><option value="">None</option>
                                <?php foreach ($mediaTypes as $mt): ?><option value="<?php echo $mt; ?>"><?php echo ucfirst($mt); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="fas fa-plus me-1"></i>Add section</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Resources -->
    <div class="col-lg-4">
        <h4><i class="fas fa-book me-2"></i>Resources <span class="badge bg-secondary"><?php echo count($resources); ?></span></h4>
        <?php if (empty($resources)): ?>
            <p class="text-muted">No resources yet.</p>
        <?php else: ?>
            <ul class="list-group mb-3">
                <?php foreach ($resources as $res): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <span class="badge bg-info text-dark text-capitalize me-1"><?php echo htmlspecialchars($res['resource_type']); ?></span>
                            <?php if (!empty($res['url'])): ?>
                                <a href="<?php echo htmlspecialchars($res['url'], ENT_QUOTES); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($res['label']); ?></a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($res['label']); ?>
                            <?php endif; ?>
                        </span>
                        <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'lectureResourceDelete', 'id' => $res['id'], 'lecture_id' => $id]); ?>" class="d-inline" onsubmit="return confirm('Remove this resource?');">
                            <button class="btn btn-sm btn-link text-danger p-0" type="submit"><i class="fas fa-times"></i></button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="card border-secondary">
            <div class="card-header"><i class="fas fa-plus me-2"></i>Add resource</div>
            <div class="card-body">
                <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'lectureResourceStore', 'id' => $id]); ?>">
                    <div class="mb-2"><label class="form-label">Label</label><input type="text" name="label" required maxlength="255" class="form-control"></div>
                    <div class="mb-2"><label class="form-label">URL</label><input type="text" name="url" maxlength="1000" class="form-control"></div>
                    <div class="mb-2"><label class="form-label">Type</label>
                        <select name="resource_type" class="form-select">
                            <?php foreach ($resourceTypes as $rt): ?><option value="<?php echo $rt; ?>"><?php echo ucfirst($rt); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-secondary btn-sm" type="submit"><i class="fas fa-plus me-1"></i>Add</button>
                </form>
            </div>
        </div>
    </div>
</div>
