@php
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
@endphp

<div class="spectrum-workflow-panel" id="spectrum-workflow">

    <!-- Progress Summary -->
    <div class="workflow-progress">
        <div class="progress-header">
            <h4><i class="fa fa-tasks"></i> {{ __('Spectrum Workflow') }}</h4>
            <span class="progress-percent">{{ $progress['percentComplete'] }}%</span>
        </div>

        <div class="progress-bar-container">
            <div class="progress">
                <div class="progress-bar bg-success" style="width: {{ ($progress['completed'] / $progress['total']) * 100 }}%"
                     title="{{ __('%1% completed', ['%1%' => $progress['completed']]) }}"></div>
                <div class="progress-bar bg-primary" style="width: {{ ($progress['inProgress'] / $progress['total']) * 100 }}%"
                     title="{{ __('%1% in progress', ['%1%' => $progress['inProgress']]) }}"></div>
                <div class="progress-bar bg-danger" style="width: {{ ($progress['overdue'] / $progress['total']) * 100 }}%"
                     title="{{ __('%1% overdue', ['%1%' => $progress['overdue']]) }}"></div>
            </div>
        </div>

        <div class="progress-stats">
            <span class="stat completed"><i class="fa fa-check-circle"></i> {{ $progress['completed'] }} {{ __('completed') }}</span>
            <span class="stat in-progress"><i class="fa fa-spinner"></i> {{ $progress['inProgress'] }} {{ __('in progress') }}</span>
            @if($progress['overdue'] > 0)
                <span class="stat overdue"><i class="fa fa-exclamation-circle"></i> {{ $progress['overdue'] }} {{ __('overdue') }}</span>
            @endif
        </div>
    </div>

    <!-- Procedure Categories -->
    <div class="workflow-categories">
        @foreach($categories as $catId => $catDef)
            @if(!isset($proceduresByCategory[$catId])) @continue @endif

            <div class="workflow-category" data-category="{{ $catId }}">
                <div class="category-header">
                    <i class="fa {{ $catDef['icon'] }}"></i>
                    <span class="category-label">{{ __($catDef['label']) }}</span>
                    <span class="category-count">
                        @php
                        $catCompleted = 0;
                        foreach ($proceduresByCategory[$catId] as $ps) {
                            if ($ps['status'] === ahgSpectrumWorkflowService::STATUS_COMPLETED) $catCompleted++;
                        }
                        echo $catCompleted . '/' . count($proceduresByCategory[$catId]);
                        @endphp
                    </span>
                </div>

                <div class="category-procedures">
                    @foreach($proceduresByCategory[$catId] as $procId => $procStatus)
                        <div class="procedure-item status-{{ $procStatus['status'] }}"
                             data-procedure="{{ $procId }}"
                             data-status="{{ $procStatus['status'] }}">

                            <div class="procedure-status-indicator"
                                 style="background-color: {{ $statusColors[$procStatus['status']] }}"></div>

                            <div class="procedure-info">
                                <span class="procedure-name">
                                    <i class="fa {{ $procStatus['procedure']['icon'] }}"></i>
                                    {{ __($procStatus['procedure']['label']) }}
                                </span>

                                @if($procStatus['lastUpdate'])
                                    <span class="procedure-date">
                                        {{ date('d M Y', strtotime($procStatus['lastUpdate'])) }}
                                    </span>
                                @endif

                                @if($procStatus['dueDate'] && $procStatus['status'] !== ahgSpectrumWorkflowService::STATUS_COMPLETED)
                                    <span class="procedure-due {{ strtotime($procStatus['dueDate']) < time() ? 'overdue' : '' }}">
                                        {{ __('Due: %1%', ['%1%' => date('d M Y', strtotime($procStatus['dueDate']))]) }}
                                    </span>
                                @endif
                            </div>

                            <div class="procedure-actions">
                                <button type="button" class="btn-procedure-action"
                                        data-action="update" data-procedure="{{ $procId }}"
                                        title="{{ __('Update status') }}">
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <button type="button" class="btn-procedure-action"
                                        data-action="view" data-procedure="{{ $procId }}"
                                        title="{{ __('View details') }}">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <!-- Recent Timeline -->
    <div class="workflow-timeline">
        <h5><i class="fa fa-clock-o"></i> {{ __('Recent Activity') }}</h5>

        @if(empty($timeline))
            <p class="no-activity">{{ __('No procedure activity recorded yet.') }}</p>
        @else
            <ul class="timeline-list">
                @foreach(array_slice($timeline, 0, 10) as $event)
                    <li class="timeline-event">
                        <span class="event-time">{{ date('d M H:i', strtotime($event['timestamp'])) }}</span>
                        <span class="event-procedure">{{ $event['procedureLabel'] }}</span>
                        <span class="event-status" style="color: {{ $statusColors[$event['newStatus']] }}">
                            {{ ucfirst(str_replace('_', ' ', $event['newStatus'])) }}
                        </span>
                        @if($event['notes'])
                            <span class="event-notes">{{ $event['notes'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if(count($timeline) > 10)
                <a href="#" class="view-all-timeline">{{ __('View all activity (%1%)', ['%1%' => count($timeline)]) }}</a>
            @endif
        @endif
    </div>

</div>

<!-- Procedure Update Modal -->
<div class="modal fade" id="procedureUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Update Procedure Status') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="procedureUpdateForm">
                    <input type="hidden" name="objectId" value="{{ $objectId }}">
                    <input type="hidden" name="procedureId" id="updateProcedureId">

                    <div class="form-group">
                        <label>{{ __('Status') }}</label>
                        <select name="status" class="form-control" id="updateStatus">
                            <option value="not_started">{{ __('Not Started') }}</option>
                            <option value="in_progress">{{ __('In Progress') }}</option>
                            <option value="pending_review">{{ __('Pending Review') }}</option>
                            <option value="completed">{{ __('Completed') }}</option>
                            <option value="on_hold">{{ __('On Hold') }}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>{{ __('Due Date') }}</label>
                        <input type="date" name="dueDate" class="form-control" id="updateDueDate">
                    </div>

                    <div class="form-group">
                        <label>{{ __('Assigned To') }}</label>
                        <input type="text" name="assignedTo" class="form-control" id="updateAssignedTo">
                    </div>

                    <div class="form-group">
                        <label>{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3" id="updateNotes"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="saveProcedureUpdate">{{ __('Save') }}</button>
            </div>
        </div>
    </div>
</div>

<script {!! $csp_nonce !!}>
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
                new bootstrap.Modal(document.getElementById('procedureUpdateModal')).show();
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
