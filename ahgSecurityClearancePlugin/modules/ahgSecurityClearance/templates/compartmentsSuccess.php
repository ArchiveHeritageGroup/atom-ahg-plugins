<?php
/**
 * Security Compartments Template
 */
$compartments = $sf_data->getRaw('compartments');
$userCounts = $sf_data->getRaw('userCounts');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-project-diagram me-2"></i><?php echo __('Security Compartments') ?></h1>
    <a href="<?php echo url_for(['module' => 'ahgSecurityClearance', 'action' => 'dashboard']) ?>" class="btn btn-primary">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard') ?>
    </a>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-lock me-2"></i><?php echo __('Compartments') ?></h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($compartments)): ?>
        <p class="text-muted text-center py-4"><?php echo __('No compartments defined') ?></p>
        <?php else: ?>
        <table class="table table-hover table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Name') ?></th>
                    <th><?php echo __('Code') ?></th>
                    <th><?php echo __('Description') ?></th>
                    <th class="text-center"><?php echo __('Users') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compartments as $comp): ?>
                <tr>
                    <td><strong><?php echo esc_entities($comp->name) ?></strong></td>
                    <td><code><?php echo esc_entities($comp->code) ?></code></td>
                    <td><?php echo esc_entities($comp->description ?? '-') ?></td>
                    <td class="text-center">
                        <span class="badge bg-primary"><?php echo $userCounts[$comp->id] ?? 0 ?></span>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <?php endif ?>
    </div>
</div>
