<?php
$d = $sf_data->getRaw('dpia');           // null when creating
$acts = $sf_data->getRaw('activities');
$isEdit = !empty($d);
$status = $isEdit ? $d->status : 'draft';
$v = function ($field) use ($d) { return ($d && isset($d->$field)) ? htmlspecialchars((string) $d->$field) : ''; };
$ta = function ($field) use ($d) { return ($d && isset($d->$field)) ? htmlspecialchars((string) $d->$field) : ''; };
$badge = ['draft' => 'secondary', 'review' => 'warning', 'completed' => 'success', 'archived' => 'dark'];
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dpiaList']); ?>" class="btn btn-outline-secondary btn-sm me-2"><i class="fas fa-arrow-left"></i></a>
            <span class="h2"><i class="fas fa-shield-halved me-2"></i><?php echo $isEdit ? __('Edit DPIA') : __('New DPIA'); ?></span>
        </div>
        <?php if ($isEdit): ?>
        <span class="badge bg-<?php echo $badge[$status] ?? 'secondary'; ?> fs-6 align-self-center"><?php echo __(ucfirst($status)); ?><?php echo $d->high_risk ? ' · '.__('High risk') : ''; ?></span>
        <?php endif; ?>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>
    <?php if ($sf_user->hasFlash('error')): ?><div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div><?php endif; ?>

    <form method="post" action="<?php echo url_for($isEdit ? ['module' => 'privacyAdmin', 'action' => 'dpiaForm', 'id' => $d->id] : ['module' => 'privacyAdmin', 'action' => 'dpiaForm']); ?>">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
        <?php $readonly = in_array($status, ['completed', 'archived'], true) ? 'readonly disabled' : ''; ?>

        <div class="card mb-3"><div class="card-body">
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label"><?php echo __('Name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?php echo $v('name'); ?>" <?php echo $readonly; ?>>
                </div>
                <div class="col-md-5">
                    <label class="form-label"><?php echo __('Linked ROPA activity'); ?></label>
                    <select name="processing_activity_id" class="form-select" <?php echo $readonly; ?>>
                        <option value="">&mdash; <?php echo __('none'); ?> &mdash;</option>
                        <?php foreach ((array) $acts as $a): ?>
                        <option value="<?php echo $a->id; ?>" <?php echo ($d && $d->processing_activity_id == $a->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($a->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label"><?php echo __('Processing description'); ?></label>
                    <textarea name="description" class="form-control" rows="2" <?php echo $readonly; ?>><?php echo $ta('description'); ?></textarea>
                </div>
                <div class="col-12 form-check ms-2">
                    <input type="checkbox" class="form-check-input" name="high_risk" id="high_risk" value="1" <?php echo ($d && $d->high_risk) ? 'checked' : ''; ?> <?php echo $readonly; ?>>
                    <label class="form-check-label" for="high_risk"><?php echo __('High-risk processing (special category / large-scale / biometric / cross-border) — auto-flagged from the text below if matched'); ?></label>
                </div>
            </div>
        </div></div>

        <?php
        $sections = [
            ['1. '.__('Necessity & proportionality'), 'necessity_proportionality', __('Why is the processing necessary, and proportionate to its purpose?')],
            ['2. '.__('Risks to data subjects'), 'risks_to_subjects', __('Identify the risks to the rights and freedoms of data subjects.')],
            ['3. '.__('Mitigation measures'), 'measures_to_mitigate', __('Measures to address each risk.')],
            ['3b. '.__('Residual risks'), 'residual_risks', __('Risk remaining after mitigation.')],
            ['4. '.__('DPO consultation opinion'), 'dpo_opinion', __('Data Protection Officer opinion / advice.')],
        ];
        foreach ($sections as $s): ?>
        <div class="card mb-3"><div class="card-header bg-light fw-bold"><?php echo $s[0]; ?></div><div class="card-body">
            <textarea name="<?php echo $s[1]; ?>" class="form-control" rows="3" placeholder="<?php echo htmlspecialchars($s[2]); ?>" <?php echo $readonly; ?>><?php echo $ta($s[1]); ?></textarea>
        </div></div>
        <?php endforeach; ?>

        <div class="card mb-3"><div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label"><?php echo __('DPO consulted on'); ?></label>
                <input type="date" name="dpo_consulted_at" class="form-control" value="<?php echo $v('dpo_consulted_at'); ?>" <?php echo $readonly; ?>>
            </div>
        </div></div>

        <?php if (!in_array($status, ['completed', 'archived'], true)): ?>
        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save'); ?></button>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dpiaList']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        </div>
        <?php endif; ?>
    </form>

    <?php if ($isEdit && in_array($status, ['draft', 'review'], true)): ?>
    <div class="card border-success"><div class="card-header bg-success text-white fw-bold"><i class="fas fa-signature me-1"></i><?php echo __('Sign-off'); ?></div><div class="card-body">
        <?php if ($status === 'draft'): ?>
        <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dpiaReview', 'id' => $d->id]); ?>" class="mb-3">
            <button type="submit" class="btn btn-warning"><i class="fas fa-clipboard-check me-1"></i><?php echo __('Move to review'); ?></button>
            <span class="text-muted small ms-2"><?php echo __('Mark the assessment ready for DPO sign-off.'); ?></span>
        </form>
        <?php endif; ?>
        <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dpiaSignOff', 'id' => $d->id]); ?>">
            <label class="form-label"><?php echo __('Sign-off note (optional)'); ?></label>
            <textarea name="signoff_note" class="form-control mb-2" rows="2"></textarea>
            <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i><?php echo __('Sign off as completed'); ?></button>
            <span class="text-muted small ms-2"><?php echo __('Marks the DPIA completed and stamps the linked ROPA entry (dpia_completed / dpia_date).'); ?></span>
        </form>
    </div></div>
    <?php endif; ?>
</div>
