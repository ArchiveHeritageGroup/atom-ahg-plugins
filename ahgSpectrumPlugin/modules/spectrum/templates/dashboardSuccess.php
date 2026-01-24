<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-tasks me-2"></i><?php echo __('Spectrum Workflow Dashboard'); ?></h1>
<?php end_slot(); ?>

<div class="row">
    <!-- Sidebar -->
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><?php echo __('Filter'); ?></h5>
            </div>
            <div class="card-body">
                <form method="get" action="">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Repository'); ?></label>
                        <select name="repository" class="form-select">
                            <option value=""><?php echo __('All repositories'); ?></option>
                            <?php foreach ($repositories as $repo): ?>
                            <option value="<?php echo $repo->id; ?>" <?php echo $selectedRepository == $repo->id ? 'selected' : ''; ?>>
                                <?php echo esc_entities($repo->authorized_form_of_name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i><?php echo __('Apply Filter'); ?>
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><?php echo __('Quick Links'); ?></h5>
            </div>
            <div class="card-body">
                <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'dataQuality']); ?>" class="btn btn-outline-success w-100 mb-2">
                    <i class="fas fa-check-circle me-1"></i><?php echo __('Data Quality Dashboard'); ?>
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><?php echo __('Status Legend'); ?></h5></div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex align-items-center"><span class="badge bg-success me-2">&nbsp;</span> <?php echo __('Completed'); ?></li>
                <li class="list-group-item d-flex align-items-center"><span class="badge bg-primary me-2">&nbsp;</span> <?php echo __('In Progress'); ?></li>
                <li class="list-group-item d-flex align-items-center"><span class="badge bg-warning me-2">&nbsp;</span> <?php echo __('Pending Review'); ?></li>
                <li class="list-group-item d-flex align-items-center"><span class="badge bg-secondary me-2">&nbsp;</span> <?php echo __('On Hold'); ?></li>
                <li class="list-group-item d-flex align-items-center"><span class="badge bg-danger me-2">&nbsp;</span> <?php echo __('Overdue'); ?></li>
                <li class="list-group-item d-flex align-items-center"><span class="badge bg-light text-dark me-2">&nbsp;</span> <?php echo __('Not Started'); ?></li>
            </ul>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="col-md-9">
        <!-- Overall Completion Card -->
        <div class="card mb-4 bg-info text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="rounded-circle bg-white text-info d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                            <span class="h3 mb-0"><?php echo $overallCompletion['percentage']; ?>%</span>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h3><?php echo __('Overall Workflow Completion'); ?></h3>
                        <p class="mb-0"><?php echo $overallCompletion['completed']; ?> <?php echo __('of'); ?> <?php echo $overallCompletion['total']; ?> <?php echo __('procedures completed'); ?></p>
                        <div class="progress mt-2" style="height: 10px;">
                            <div class="progress-bar bg-white" style="width: <?php echo $overallCompletion['percentage']; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-primary"><?php echo $workflowStats['total_objects']; ?></h2>
                        <small class="text-muted"><?php echo __('Total Objects'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-success"><?php echo $workflowStats['completed_procedures']; ?></h2>
                        <small class="text-muted"><?php echo __('Completed'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-warning"><?php echo $workflowStats['in_progress_procedures']; ?></h2>
                        <small class="text-muted"><?php echo __('In Progress'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-secondary"><?php echo $workflowStats['pending_procedures']; ?></h2>
                        <small class="text-muted"><?php echo __('Pending'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Procedure Status Overview -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><?php echo __('Procedure Status Overview'); ?></h5></div>
            <div class="card-body">
                <?php if (empty($procedureStatusCounts)): ?>
                <p class="text-muted mb-0"><?php echo __('No workflow data yet. Start workflows from individual object pages.'); ?></p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th><?php echo __('Procedure'); ?></th>
                                <th class="text-center"><?php echo __('Pending'); ?></th>
                                <th class="text-center"><?php echo __('In Progress'); ?></th>
                                <th class="text-center"><?php echo __('Completed'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($procedures as $procKey => $procDef): ?>
                            <?php $counts = $procedureStatusCounts[$procKey] ?? []; ?>
                            <tr>
                                <td><i class="<?php echo $procDef['icon'] ?? 'fa fa-circle'; ?> me-2"></i><?php echo $procDef['label']; ?></td>
                                <td class="text-center"><span class="badge bg-secondary"><?php echo $counts['pending'] ?? 0; ?></span></td>
                                <td class="text-center">
                                    <?php 
                                    $inProgress = 0;
                                    foreach ($counts as $state => $count) {
                                        if (!in_array($state, ['pending', 'completed', 'verified', 'closed', 'confirmed'])) {
                                            $inProgress += $count;
                                        }
                                    }
                                    ?>
                                    <span class="badge bg-primary"><?php echo $inProgress; ?></span>
                                </td>
                                <td class="text-center">
                                    <?php $completed = ($counts['completed'] ?? 0) + ($counts['verified'] ?? 0) + ($counts['closed'] ?? 0) + ($counts['confirmed'] ?? 0); ?>
                                    <span class="badge bg-success"><?php echo $completed; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><?php echo __('Recent Activity'); ?></h5></div>
            <div class="card-body">
                <?php if (empty($recentActivity)): ?>
                <p class="text-muted text-center mb-0"><em><?php echo __('No recent workflow activity.'); ?></em></p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th><?php echo __('Date'); ?></th>
                                <th><?php echo __('Object'); ?></th>
                                <th><?php echo __('Procedure'); ?></th>
                                <th><?php echo __('Action'); ?></th>
                                <th><?php echo __('User'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivity as $activity): ?>
                            <tr>
                                <td><small><?php echo date('Y-m-d H:i', strtotime($activity->created_at)); ?></small></td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'museum', 'action' => 'index', 'slug' => $activity->slug]); ?>">
                                        <?php echo esc_entities($activity->object_title ?? $activity->slug); ?>
                                    </a>
                                </td>
                                <td><?php echo ucwords(str_replace('_', ' ', $activity->procedure_type)); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo ucwords(str_replace('_', ' ', $activity->from_state)); ?></span>
                                    <i class="fas fa-arrow-right mx-1"></i>
                                    <span class="badge bg-primary"><?php echo ucwords(str_replace('_', ' ', $activity->to_state)); ?></span>
                                </td>
                                <td><small><?php echo esc_entities($activity->user_name ?? ''); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
