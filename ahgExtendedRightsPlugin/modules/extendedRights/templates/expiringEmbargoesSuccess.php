<?php
$embargoes = sfOutputEscaper::unescape($embargoes ?? []);
$days = $days ?? 30;
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
                <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'dashboard']); ?>"><?php echo __('Rights Management'); ?></a></li>
                <li class="breadcrumb-item active"><?php echo __('Expiring Embargoes'); ?></li>
            </ol>
        </nav>
        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'dashboard']); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard'); ?>
        </a>
    </div>
    
    <div class="card">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-clock me-2"></i><?php echo __('Embargoes Expiring Within %1% Days', ['%1%' => $days]); ?></h4>
            <div class="btn-group btn-group-sm">
                <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'expiringEmbargoes', 'days' => 7]); ?>" 
                   class="btn <?php echo $days == 7 ? 'btn-dark' : 'btn-outline-dark'; ?>">7 days</a>
                <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'expiringEmbargoes', 'days' => 30]); ?>" 
                   class="btn <?php echo $days == 30 ? 'btn-dark' : 'btn-outline-dark'; ?>">30 days</a>
                <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'expiringEmbargoes', 'days' => 90]); ?>" 
                   class="btn <?php echo $days == 90 ? 'btn-dark' : 'btn-outline-dark'; ?>">90 days</a>
            </div>
        </div>
        
        <div class="card-body p-0">
            <?php if (empty($embargoes)): ?>
            <div class="alert alert-success m-3">
                <i class="fas fa-check-circle me-2"></i><?php echo __('No embargoes expiring within the next %1% days.', ['%1%' => $days]); ?>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Title'); ?></th>
                            <th><?php echo __('Expiry Date'); ?></th>
                            <th><?php echo __('Days Remaining'); ?></th>
                            <th><?php echo __('Restriction'); ?></th>
                            <th><?php echo __('Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($embargoes as $embargo): 
                            $embargo = (object) $embargo;
                            $daysRemaining = (int) $embargo->days_remaining;
                            $urgencyClass = $daysRemaining <= 7 ? 'table-danger' : ($daysRemaining <= 14 ? 'table-warning' : '');
                        ?>
                        <tr class="<?php echo $urgencyClass; ?>">
                            <td>
                                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $embargo->slug ?? $embargo->object_id]); ?>">
                                    <?php echo htmlspecialchars($embargo->title ?? 'Untitled'); ?>
                                </a>
                            </td>
                            <td><?php echo $embargo->end_date; ?></td>
                            <td>
                                <?php if ($daysRemaining <= 7): ?>
                                <span class="badge bg-danger"><?php echo $daysRemaining; ?> <?php echo __('days'); ?></span>
                                <?php elseif ($daysRemaining <= 14): ?>
                                <span class="badge bg-warning text-dark"><?php echo $daysRemaining; ?> <?php echo __('days'); ?></span>
                                <?php else: ?>
                                <span class="badge bg-info"><?php echo $daysRemaining; ?> <?php echo __('days'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($embargo->embargo_type . " - " . $embargo->reason ?? '-'); ?></td>
                            <td>
                                <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'edit', 'id' => $embargo->id]); ?>" 
                                   class="btn btn-sm btn-outline-primary" title="<?php echo __('Edit'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'liftEmbargo', 'id' => $embargo->id]); ?>" 
                                   class="btn btn-sm btn-outline-success" title="<?php echo __('Lift Embargo'); ?>">
                                    <i class="fas fa-unlock"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card-footer text-muted">
            <?php echo __('Total: %1% embargoes', ['%1%' => count($embargoes)]); ?>
        </div>
    </div>
</div>
