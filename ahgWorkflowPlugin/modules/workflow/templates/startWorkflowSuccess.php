<?php
$record = isset($sf_data) ? $sf_data->getRaw('record') : $record;
$workflow = isset($sf_data) ? $sf_data->getRaw('workflow') : $workflow;
$steps = $workflow ? array_values((array) ($workflow->steps ?? [])) : [];
$title = $record->title ?: ($record->identifier ?: ('#' . $record->id));
?>
<div class="container-fluid">
    <h1><i class="fas fa-paper-plane me-2"></i><?php echo __('Submit for review') ?></h1>
    <p class="text-muted"><?php echo htmlspecialchars($title) ?><?php echo $record->identifier ? ' (' . htmlspecialchars($record->identifier) . ')' : '' ?></p>

    <?php if (!$workflow || !$steps): ?>
        <div class="alert alert-warning"><?php echo __('No workflow applies to this record. An administrator can create one under Workflow administration.') ?></div>
        <?php if ($record->slug): ?><a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'informationobject', 'slug' => $record->slug]) ?>"><?php echo __('Back to the record') ?></a><?php endif ?>
    <?php else: ?>
        <div class="card mb-3">
            <div class="card-body">
                <p class="mb-2"><?php echo __('The record goes through %1%:', ['%1%' => '<strong>' . htmlspecialchars($workflow->name) . '</strong>']) ?></p>
                <ol class="mb-3">
                    <?php foreach ($steps as $step): ?>
                        <li><?php echo htmlspecialchars($step->name) ?></li>
                    <?php endforeach ?>
                </ol>
                <p class="text-muted small mb-3"><?php echo __('The first step waits in the task pool until a reviewer claims it. The workflow records each decision; it does not change or publish the record.') ?></p>
                <form method="post" class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i><?php echo __('Submit for review') ?></button>
                    <?php if ($record->slug): ?><a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'informationobject', 'slug' => $record->slug]) ?>"><?php echo __('Cancel') ?></a><?php endif ?>
                </form>
            </div>
        </div>
    <?php endif ?>
</div>
