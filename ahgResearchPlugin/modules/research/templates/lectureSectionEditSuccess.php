<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$section = sfOutputEscaper::unescape($section ?? []);
$lecture = sfOutputEscaper::unescape($lecture ?? []);
$lectureId = (int) $section['lecture_id'];
$mediaTypes = ['image', 'video', 'audio', 'embed'];
$curMedia = $section['media_type'] ?? '';
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'lectures']); ?>">Lecture Builder</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'lectureShow', 'id' => $lectureId]); ?>"><?php echo htmlspecialchars($lecture['title'] ?? 'Lecture'); ?></a></li>
        <li class="breadcrumb-item active">Edit section</li>
    </ol>
</nav>

<h1 class="h2 mb-3"><i class="fas fa-edit text-primary me-2"></i><?php echo __('Edit section'); ?></h1>

<?php if ($msg = $sf_user->getFlash('error')): ?><div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'lectureSectionEdit', 'id' => $section['id']]); ?>">
    <div class="card mb-3"><div class="card-body">
        <div class="mb-3">
            <label class="form-label">Heading</label>
            <input type="text" name="heading" maxlength="255" class="form-control" value="<?php echo htmlspecialchars((string) ($section['heading'] ?? ''), ENT_QUOTES); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Body (Markdown)</label>
            <textarea name="body_markdown" rows="10" class="form-control"><?php echo htmlspecialchars((string) ($section['body_markdown'] ?? '')); ?></textarea>
            <div class="form-text">Supports headings, <strong>**bold**</strong>, <em>*italic*</em>, <code>`code`</code>, links, lists and blockquotes.</div>
        </div>
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Media URL</label>
                <input type="text" name="media_url" maxlength="1000" class="form-control" value="<?php echo htmlspecialchars((string) ($section['media_url'] ?? ''), ENT_QUOTES); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Media type</label>
                <select name="media_type" class="form-select">
                    <option value="">None</option>
                    <?php foreach ($mediaTypes as $mt): ?>
                        <option value="<?php echo $mt; ?>" <?php echo $curMedia === $mt ? 'selected' : ''; ?>><?php echo ucfirst($mt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mb-3" style="max-width:160px;">
            <label class="form-label">Sort order</label>
            <input type="number" name="sort_order" class="form-control" value="<?php echo (int) ($section['sort_order'] ?? 0); ?>">
        </div>
    </div></div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i>Save section</button>
        <a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'research', 'action' => 'lectureShow', 'id' => $lectureId]); ?>">Cancel</a>
    </div>
</form>
