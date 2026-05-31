<?php
/**
 * Runtime data-entry form rendered from a configurable template.
 *
 * Vars: $template, $formHtml, $formType, $objectId, $record, $submitLabel.
 */
$isEdit = !empty($objectId);
$recordTitle = null;
if ($isEdit && $record) {
    $recordTitle = $record->title ?? ($record->identifier ?? ('#' . $objectId));
}
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'forms', 'action' => 'index']); ?>">Forms</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo $isEdit ? 'Edit' : 'New'; ?> &mdash; <?php echo htmlspecialchars($template->name); ?>
                    </li>
                </ol>
            </nav>
            <h1>
                <i class="fas fa-edit me-2"></i>
                <?php if ($isEdit): ?>
                    Edit: <?php echo htmlspecialchars((string) $recordTitle); ?>
                <?php else: ?>
                    New Record &mdash; <?php echo htmlspecialchars($template->name); ?>
                <?php endif; ?>
            </h1>
            <?php if (!empty($template->description)): ?>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($template->description); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-10">
            <form method="post" action="<?php echo url_for(['module' => 'forms', 'action' => 'submit']); ?>">
                <input type="hidden" name="template_id" value="<?php echo (int) $template->id; ?>">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($formType); ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="object_id" value="<?php echo (int) $objectId; ?>">
                <?php endif; ?>
                <?php if (isset($csrf_token) && $csrf_token): ?>
                    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <?php echo $formHtml; ?>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="<?php echo $isEdit && $record && isset($record->slug)
                            ? url_for([$formType === 'accession' ? 'accession' : 'informationobject', 'slug' => $record->slug])
                            : url_for(['module' => 'forms', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> <?php echo htmlspecialchars($submitLabel); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
