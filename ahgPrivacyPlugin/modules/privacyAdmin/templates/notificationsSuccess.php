<?php use_helper('Date'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="h2 mb-0">
                <i class="fas fa-bell me-2"></i><?php echo __('Notifications'); ?>
                <?php if (count($notifications) > 0): ?>
                <span class="badge bg-danger"><?php echo count($notifications); ?></span>
                <?php endif; ?>
            </h1>
        </div>
        <?php if (count($notifications) > 0): ?>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'notificationMarkAllRead']); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-check-double me-1"></i><?php echo __('Mark All Read'); ?>
        </a>
        <?php endif; ?>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>

    <?php if (count($notifications) === 0): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
            <h5 class="text-muted"><?php echo __('No unread notifications'); ?></h5>
            <p class="text-muted">You are all caught up!</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <ul class="list-group list-group-flush">
            <?php foreach ($notifications as $notification): ?>
            <?php
            $typeIcons = [
                'submitted' => 'paper-plane text-primary',
                'approved' => 'check-circle text-success',
                'rejected' => 'times-circle text-danger',
                'comment' => 'comment text-info',
                'reminder' => 'clock text-warning'
            ];
            $icon = $typeIcons[$notification->notification_type] ?? 'bell text-secondary';
            ?>
            <li class="list-group-item list-group-item-action">
                <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'notificationRead', 'id' => $notification->id]); ?>" class="text-decoration-none text-dark d-block">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-<?php echo $icon; ?> fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo esc_entities($notification->subject); ?></strong>
                                <small class="text-muted"><?php echo $notification->created_at; ?></small>
                            </div>
                            <?php if ($notification->message): ?>
                            <p class="mb-0 text-muted small"><?php echo esc_entities($notification->message); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
