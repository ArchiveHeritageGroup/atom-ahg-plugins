<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$lecture = sfOutputEscaper::unescape($lecture ?? null);
$type    = $type ?? 'standalone';
$isEdit  = !empty($lecture);
$val = function ($key, $default = '') use ($lecture) {
    return htmlspecialchars((string) ($lecture[$key] ?? $default), ENT_QUOTES);
};
$statuses = ['draft', 'scheduled', 'delivered', 'published', 'archived'];
$curStatus = $lecture['status'] ?? 'draft';
$isTalk = ($type === 'talk');
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'lectures']); ?>">Lecture Builder</a></li>
        <li class="breadcrumb-item active"><?php echo $isEdit ? 'Edit' : 'New'; ?></li>
    </ol>
</nav>

<h1 class="h2 mb-3">
    <i class="fas fa-chalkboard-teacher text-primary me-2"></i>
    <?php echo $isEdit ? __('Edit lecture') : __('New lecture'); ?>
    <span class="badge bg-light text-dark text-capitalize"><?php echo htmlspecialchars($type); ?></span>
</h1>

<?php if ($msg = $sf_user->getFlash('error')): ?><div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<form method="post" action="<?php echo $isEdit
    ? url_for(['module' => 'research', 'action' => 'lectureBuilder', 'id' => $lecture['id']])
    : url_for(['module' => 'research', 'action' => 'lectureBuilder']); ?>">
    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type, ENT_QUOTES); ?>">

    <div class="card mb-3">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" required maxlength="255" class="form-control" value="<?php echo $val('title'); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Subtitle</label>
                <input type="text" name="subtitle" maxlength="255" class="form-control" value="<?php echo $val('subtitle'); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Summary</label>
                <textarea name="summary" rows="3" class="form-control"><?php echo htmlspecialchars((string) ($lecture['summary'] ?? '')); ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Speaker name</label>
                    <input type="text" name="speaker_name" maxlength="255" class="form-control" value="<?php echo $val('speaker_name'); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Speaker affiliation</label>
                    <input type="text" name="speaker_affiliation" maxlength="255" class="form-control" value="<?php echo $val('speaker_affiliation'); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Scheduled at</label>
                    <input type="datetime-local" name="scheduled_at" class="form-control" value="<?php echo !empty($lecture['scheduled_at']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($lecture['scheduled_at'])), ENT_QUOTES) : ''; ?>">
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">Venue / location</label>
                    <input type="text" name="location" maxlength="255" class="form-control" value="<?php echo $val('location'); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" min="0" class="form-control" value="<?php echo $val('duration_minutes'); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Recording URL</label>
                    <input type="text" name="recording_url" maxlength="1000" class="form-control" value="<?php echo $val('recording_url'); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Slides URL</label>
                    <input type="text" name="slides_url" maxlength="1000" class="form-control" value="<?php echo $val('slides_url'); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Curriculum reference</label>
                    <input type="text" name="curriculum_ref" maxlength="255" class="form-control" value="<?php echo $val('curriculum_ref'); ?>" placeholder="<?php echo $type === 'curriculum' ? 'e.g. training module ref' : ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo $curStatus === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo $isEdit ? 'Save changes' : 'Create lecture'; ?></button>
        <a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'research', 'action' => 'lectures']); ?>">Cancel</a>
    </div>
</form>
