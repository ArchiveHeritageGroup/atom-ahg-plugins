<?php
$v = $sf_data->getRaw('values') ?: [];
$errors = $sf_data->getRaw('errors') ?: [];
$ratings = $sf_data->getRaw('ratings') ?: [];
$priorities = $sf_data->getRaw('priorities') ?: [];
$obj = $sf_data->getRaw('object');
$chk = $sf_data->getRaw('check');
$val = fn ($k) => htmlspecialchars((string) ($v[$k] ?? ''), ENT_QUOTES, 'UTF-8');
$err = fn ($k) => isset($errors[$k]) ? ' is-invalid' : '';
$msg = fn ($k) => isset($errors[$k]) ? '<div class="invalid-feedback">' . htmlspecialchars(__($errors[$k]), ENT_QUOTES, 'UTF-8') . '</div>' : '';
$area = function ($k, $label, $help = '', $rows = 3) use ($val) {
    return '<div class="mb-3"><label for="' . $k . '" class="form-label">' . __($label) . '</label>'
        . '<textarea name="' . $k . '" id="' . $k . '" class="form-control" rows="' . $rows . '">' . $val($k) . '</textarea>'
        . ($help ? '<div class="form-text">' . __($help) . '</div>' : '') . '</div>';
};
$action = $isNew ? '/condition/check/new/edit?object_id=' . (int) $obj->id : '/condition/check/' . (int) $chk->id . '/edit';
?>

<div class="container-fluid py-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/<?php echo htmlspecialchars($obj->slug ?? '', ENT_QUOTES) ?>"><?php echo htmlspecialchars($obj->title ?? '', ENT_QUOTES) ?></a></li>
            <li class="breadcrumb-item"><a href="/<?php echo htmlspecialchars($obj->slug ?? '', ENT_QUOTES) ?>/condition"><?php echo __('Condition Reports') ?></a></li>
            <li class="breadcrumb-item active"><?php echo $isNew ? __('New condition check') : htmlspecialchars($chk->condition_check_reference ?? ('#' . $chk->id), ENT_QUOTES) ?></li>
        </ol>
    </nav>

    <h1 class="h3 mb-1"><i class="fas fa-clipboard-check me-2"></i><?php echo $isNew ? __('New Condition Check') : __('Edit Condition Check') ?></h1>
    <p class="text-muted"><?php echo htmlspecialchars(trim(($obj->identifier ? $obj->identifier . ' - ' : '') . ($obj->title ?? '')), ENT_QUOTES) ?></p>

    <?php if ($errors): ?>
    <div class="alert alert-danger"><?php echo __('The check was not saved. Correct the fields marked below; everything you entered is kept.') ?></div>
    <?php endif ?>

    <form method="post" action="<?php echo $action ?>" id="condition-check-form">
        <input type="hidden" name="_ahg_csrf_token" value="<?php echo htmlspecialchars(class_exists('\AtomFramework\Services\CsrfService') ? \AtomFramework\Services\CsrfService::generateToken() : '', ENT_QUOTES); ?>">

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('The check') ?></h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="check_date" class="form-label"><?php echo __('Check date') ?> <span class="text-danger">*</span></label>
                        <input type="date" name="check_date" id="check_date" class="form-control<?php echo $err('check_date') ?>" value="<?php echo $val('check_date') ?>">
                        <?php echo $msg('check_date') ?>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="checked_by" class="form-label"><?php echo __('Checked by') ?> <span class="text-danger">*</span></label>
                        <input type="text" name="checked_by" id="checked_by" class="form-control<?php echo $err('checked_by') ?>" value="<?php echo $val('checked_by') ?>">
                        <?php echo $msg('checked_by') ?>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="check_reason" class="form-label"><?php echo __('Reason for check') ?></label>
                        <input type="text" name="check_reason" id="check_reason" class="form-control" list="check-reasons" value="<?php echo $val('check_reason') ?>">
                        <datalist id="check-reasons">
                            <?php foreach (['Routine inspection', 'Acquisition', 'Loan out', 'Loan in', 'Exhibition', 'Damage reported', 'Before treatment', 'After treatment', 'Audit'] as $r): ?>
                            <option value="<?php echo __($r) ?>">
                            <?php endforeach ?>
                        </datalist>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i><?php echo __('Condition') ?></h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="overall_condition" class="form-label"><?php echo __('Overall condition') ?> <span class="text-danger">*</span></label>
                        <select name="overall_condition" id="overall_condition" class="form-select<?php echo $err('overall_condition') ?>">
                            <option value=""><?php echo __('Choose...') ?></option>
                            <?php foreach ($ratings as $code => $label): ?>
                            <option value="<?php echo htmlspecialchars($code, ENT_QUOTES) ?>"<?php echo ($v['overall_condition'] ?? '') === $code ? ' selected' : '' ?>><?php echo __($label) ?></option>
                            <?php endforeach ?>
                        </select>
                        <?php echo $msg('overall_condition') ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="completeness" class="form-label"><?php echo __('Completeness') ?></label>
                        <select name="completeness" id="completeness" class="form-select">
                            <option value=""><?php echo __('Not assessed') ?></option>
                            <?php foreach (['complete' => 'Complete', 'incomplete' => 'Incomplete', 'fragment' => 'Fragment'] as $code => $label): ?>
                            <option value="<?php echo $code ?>"<?php echo ($v['completeness'] ?? '') === $code ? ' selected' : '' ?>><?php echo __($label) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <?php echo $area('condition_description', 'Condition description', 'What is wrong, where, and how severe.', 4) ?>
                <?php echo $area('hazards_noted', 'Hazards noted', 'Anything that endangers the object or the people handling it.', 2) ?>
                <?php echo $area('technical_assessment', 'Technical assessment', 'Examination methods and findings, for a conservator.', 3) ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-tools me-2"></i><?php echo __('Treatment') ?></h5></div>
            <div class="card-body">
                <?php echo $area('recommendations', 'Treatment recommendations', '', 3) ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="treatment_priority" class="form-label"><?php echo __('Treatment priority') ?></label>
                        <select name="treatment_priority" id="treatment_priority" class="form-select<?php echo $err('treatment_priority') ?>">
                            <option value=""><?php echo __('None') ?></option>
                            <?php foreach ($priorities as $code => $label): ?>
                            <option value="<?php echo htmlspecialchars($code, ENT_QUOTES) ?>"<?php echo ($v['treatment_priority'] ?? '') === $code ? ' selected' : '' ?>><?php echo __($label) ?></option>
                            <?php endforeach ?>
                        </select>
                        <?php echo $msg('treatment_priority') ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="next_check_date" class="form-label"><?php echo __('Next check') ?></label>
                        <input type="date" name="next_check_date" id="next_check_date" class="form-control<?php echo $err('next_check_date') ?>" value="<?php echo $val('next_check_date') ?>">
                        <?php echo $msg('next_check_date') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-box-open me-2"></i><?php echo __('Care recommendations') ?></h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6"><?php echo $area('environment_recommendation', 'Environment', 'Temperature, relative humidity, light.', 2) ?></div>
                    <div class="col-md-6"><?php echo $area('handling_recommendation', 'Handling', '', 2) ?></div>
                    <div class="col-md-4"><?php echo $area('display_recommendation', 'Display', '', 2) ?></div>
                    <div class="col-md-4"><?php echo $area('storage_recommendation', 'Storage', '', 2) ?></div>
                    <div class="col-md-4"><?php echo $area('packing_recommendation', 'Packing and transport', '', 2) ?></div>
                </div>
                <?php echo $area('condition_note', 'Additional notes', '', 2) ?>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i><?php echo $isNew ? __('Record condition check') : __('Save changes') ?></button>
            <a href="<?php echo $isNew ? '/' . htmlspecialchars($obj->slug ?? '', ENT_QUOTES) . '/condition' : '/condition/check/' . (int) $chk->id . '/view' ?>" class="btn btn-outline-secondary"><?php echo __('Cancel') ?></a>
        </div>
    </form>
</div>
