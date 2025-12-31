<?php
/**
 * Spectrum Workflow Timeline Component
 * 
 * Displays procedure status and timeline for an object.
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */
?>

<?php
$objectId = $resource->id;
$procedures = ahgSpectrumWorkflowService::getProcedures();
$statuses = ahgSpectrumWorkflowService::getObjectProcedureStatus($objectId);
$progress = ahgSpectrumWorkflowService::calculateWorkflowProgress($objectId);
$timeline = ahgSpectrumWorkflowService::getObjectTimeline($objectId);
$statusColors = ahgSpectrumWorkflowService::$statusColors;

// Group procedures by category
$categories = [
    'pre-entry' => ['label' => 'Entry', 'icon' => 'fa-sign-in'],
    'acquisition' => ['label' => 'Acquisition', 'icon' => 'fa-plus-circle'],
    'location' => ['label' => 'Location', 'icon' => 'fa-map-marker'],
    'control' => ['label' => 'Control', 'icon' => 'fa-check-square'],
    'documentation' => ['label' => 'Documentation', 'icon' => 'fa-book'],
    'care' => ['label' => 'Care', 'icon' => 'fa-heart'],
    'financial' => ['label' => 'Financial', 'icon' => 'fa-money'],
    'loans' => ['label' => 'Loans', 'icon' => 'fa-exchange'],
    'risk' => ['label' => 'Risk', 'icon' => 'fa-exclamation-triangle'],
    'disposal' => ['label' => 'Disposal', 'icon' => 'fa-trash'],
    'exit' => ['label' => 'Exit', 'icon' => 'fa-sign-out']
];

$proceduresByCategory = [];
foreach ($statuses as $procId => $procStatus) {
    $category = $procStatus['procedure']['category'];
    if (!isset($proceduresByCategory[$category])) {
        $proceduresByCategory[$category] = [];
    }
    $proceduresByCategory[$category][$procId] = $procStatus;
}
?>

<div class="spectrum-workflow-panel" id="spectrum-workflow">
    
    <!-- Progress Summary -->
    <div class="workflow-progress">
        <div class="progress-header">
            <h4><i class="fa fa-tasks"></i> <?php echo __('Spectrum Workflow'); ?></h4>
            <span class="progress-percent"><?php echo $progress['percentComplete']; ?>%</span>
        </div>
        
        <div class="progress-bar-container">
            <div class="progress">
                <div class="progress-bar bg-success" style="width: <?php echo ($progress['completed'] / $progress['total']) * 100; ?>%"
                     title="<?php echo __('%1% completed', ['%1%' => $progress['completed']]); ?>"></div>
                <div class="progress-bar bg-primary" style="width: <?php echo ($progress['inProgress'] / $progress['total']) * 100; ?>%"
                     title="<?php echo __('%1% in progress', ['%1%' => $progress['inProgress']]); ?>"></div>
                <div class="progress-bar bg-danger" style="width: <?php echo ($progress['overdue'] / $progress['total']) * 100; ?>%"
                     title="<?php echo __('%1% overdue', ['%1%' => $progress['overdue']]); ?>"></div>
            </div>
        </div>
        
        <div class="progress-stats">
            <span class="stat completed"><i class="fa fa-check-circle"></i> <?php echo $progress['completed']; ?> <?php echo __('completed'); ?></span>
            <span class="stat in-progress"><i class="fa fa-spinner"></i> <?php echo $progress['inProgress']; ?> <?php echo __('in progress'); ?></span>
            <?php if ($progress['overdue'] > 0): ?>
                <span class="stat overdue"><i class="fa fa-exclamation-circle"></i> <?php echo $progress['overdue']; ?> <?php echo __('overdue'); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Procedure Categories -->
    <div class="workflow-categories">
        <?php foreach ($categories as $catId => $catDef): ?>
            <?php if (!isset($proceduresByCategory[$catId])) continue; ?>
            
            <div class="workflow-category" data-category="<?php echo $catId; ?>">
                <div class="category-header">
                    <i class="fa <?php echo $catDef['icon']; ?>"></i>
                    <span class="category-label"><?php echo __($catDef['label']); ?></span>
                    <span class="category-count">
                        <?php 
                        $catCompleted = 0;
                        foreach ($proceduresByCategory[$catId] as $ps) {
                            if ($ps['status'] === ahgSpectrumWorkflowService::STATUS_COMPLETED) $catCompleted++;
                        }
                        echo $catCompleted . '/' . count($proceduresByCategory[$catId]);
                        ?>
                    </span>
                </div>
                
                <div class="category-procedures">
                    <?php foreach ($proceduresByCategory[$catId] as $procId => $procStatus): ?>
                        <div class="procedure-item status-<?php echo $procStatus['status']; ?>" 
                             data-procedure="<?php echo $procId; ?>"
                             data-status="<?php echo $procStatus['status']; ?>">
                            
                            <div class="procedure-status-indicator" 
                                 style="background-color: <?php echo $statusColors[$procStatus['status']]; ?>"></div>
                            
                            <div class="procedure-info">
                                <span class="procedure-name">
                                    <i class="fa <?php echo $procStatus['procedure']['icon']; ?>"></i>
                                    <?php echo __($procStatus['procedure']['label']); ?>
                                </span>
                                
                                <?php if ($procStatus['lastUpdate']): ?>
                                    <span class="procedure-date">
                                        <?php echo date('d M Y', strtotime($procStatus['lastUpdate'])); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($procStatus['dueDate'] && $procStatus['status'] !== ahgSpectrumWorkflowService::STATUS_COMPLETED): ?>
                                    <span class="procedure-due <?php echo strtotime($procStatus['dueDate']) < time() ? 'overdue' : ''; ?>">
                                        <?php echo __('Due: %1%', ['%1%' => date('d M Y', strtotime($procStatus['dueDate']))]); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="procedure-actions">
                                <button type="button" class="btn-procedure-action" 
                                        data-action="update" data-procedure="<?php echo $procId; ?>"
                                        title="<?php echo __('Update status'); ?>">
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <button type="button" class="btn-procedure-action" 
                                        data-action="view" data-procedure="<?php echo $procId; ?>"
                                        title="<?php echo __('View details'); ?>">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent Timeline -->
    <div class="workflow-timeline">
        <h5><i class="fa fa-clock-o"></i> <?php echo __('Recent Activity'); ?></h5>
        
        <?php if (empty($timeline)): ?>
            <p class="no-activity"><?php echo __('No procedure activity recorded yet.'); ?></p>
        <?php else: ?>
            <ul class="timeline-list">
                <?php foreach (array_slice($timeline, 0, 10) as $event): ?>
                    <li class="timeline-event">
                        <span class="event-time"><?php echo date('d M H:i', strtotime($event['timestamp'])); ?></span>
                        <span class="event-procedure"><?php echo $event['procedureLabel']; ?></span>
                        <span class="event-status" style="color: <?php echo $statusColors[$event['newStatus']]; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $event['newStatus'])); ?>
                        </span>
                        <?php if ($event['notes']): ?>
                            <span class="event-notes"><?php echo $event['notes']; ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (count($timeline) > 10): ?>
                <a href="#" class="view-all-timeline"><?php echo __('View all activity (%1%)', ['%1%' => count($timeline)]); ?></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</div>

<!-- Procedure Update Modal -->
<div class="modal fade" id="procedureUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('Update Procedure Status'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="procedureUpdateForm">
                    <input type="hidden" name="objectId" value="<?php echo $objectId; ?>">
                    <input type="hidden" name="procedureId" id="updateProcedureId">
                    
                    <div class="form-group">
                        <label><?php echo __('Status'); ?></label>
                        <select name="status" class="form-control" id="updateStatus">
                            <option value="not_started"><?php echo __('Not Started'); ?></option>
                            <option value="in_progress"><?php echo __('In Progress'); ?></option>
                            <option value="pending_review"><?php echo __('Pending Review'); ?></option>
                            <option value="completed"><?php echo __('Completed'); ?></option>
                            <option value="on_hold"><?php echo __('On Hold'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo __('Due Date'); ?></label>
                        <input type="date" name="dueDate" class="form-control" id="updateDueDate">
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo __('Assigned To'); ?></label>
                        <input type="text" name="assignedTo" class="form-control" id="updateAssignedTo">
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo __('Notes'); ?></label>
                        <textarea name="notes" class="form-control" rows="3" id="updateNotes"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo __('Cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="saveProcedureUpdate"><?php echo __('Save'); ?></button>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initSpectrumWorkflow();
    });

    function initSpectrumWorkflow() {
        // Update status buttons
        document.querySelectorAll('.btn-procedure-action[data-action="update"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var procedureId = this.dataset.procedure;
                var item = this.closest('.procedure-item');
                var currentStatus = item.dataset.status;
                
                document.getElementById('updateProcedureId').value = procedureId;
                document.getElementById('updateStatus').value = currentStatus;
                
                // Show modal
                $('#procedureUpdateModal').modal('show');
            });
        });

        // Save procedure update
        document.getElementById('saveProcedureUpdate').addEventListener('click', function() {
            var form = document.getElementById('procedureUpdateForm');
            var data = new FormData(form);

            fetch('/api/spectrum/updateProcedure', {
                method: 'POST',
                body: data
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.error || 'Update failed');
                }
            })
            .catch(function(err) {
                alert('Error: ' + err.message);
            });
        });

        // Category collapse
        document.querySelectorAll('.category-header').forEach(function(header) {
            header.addEventListener('click', function() {
                var category = this.closest('.workflow-category');
                category.classList.toggle('collapsed');
            });
        });
    }

})();
</script>
