@extends('layouts.page')

@section('title')
<h1><i class="fas fa-tasks me-2"></i>{{ __('Spectrum Workflow Dashboard') }}</h1>
@endsection

@section('content')

<div class="row">
    <!-- Sidebar -->
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">{{ __('Filter') }}</h5>
            </div>
            <div class="card-body">
                <form method="get" action="">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Repository') }}</label>
                        <select name="repository" class="form-select">
                            <option value="">{{ __('All repositories') }}</option>
                            @foreach ($repositories as $repo)
                            <option value="{{ $repo->id }}" {{ $selectedRepository == $repo->id ? 'selected' : '' }}>
                                {{ $repo->authorized_form_of_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>{{ __('Apply Filter') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">{{ __('Quick Links') }}</h5>
            </div>
            <div class="card-body">
                <a href="{{ url_for(['module' => 'spectrum', 'action' => 'myTasks']) }}" class="btn btn-outline-primary w-100 mb-2">
                    <i class="fas fa-clipboard-list me-1"></i>{{ __('My Tasks') }}
                    @php
                    if ($sf_user->isAuthenticated()) {
                        $userId = $sf_user->getAttribute('user_id');
                        if ($userId) {
                            $finalStates = ['completed', 'resolved', 'closed', 'cancelled', 'rejected'];
                            $taskCount = \Illuminate\Database\Capsule\Manager::table('spectrum_workflow_state')
                                ->where('assigned_to', $userId)
                                ->whereNotIn('current_state', $finalStates)
                                ->count();
                            if ($taskCount > 0) {
                                echo '<span class="badge bg-danger ms-1">' . $taskCount . '</span>';
                            }
                        }
                    }
                    @endphp
                </a>
                <a href="{{ url_for(['module' => 'spectrum', 'action' => 'dataQuality']) }}" class="btn btn-outline-success w-100 mb-2">
                    <i class="fas fa-check-circle me-1"></i>{{ __('Data Quality Dashboard') }}
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('Status Legend') }}</h5></div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex align-items-center py-1"><span class="badge bg-light text-dark me-2">&nbsp;</span> {{ __('Pending / Proposed / Requested') }}</li>
                <li class="list-group-item d-flex align-items-center py-1"><span class="badge bg-info me-2">&nbsp;</span> {{ __('Received / Documented / Reported') }}</li>
                <li class="list-group-item d-flex align-items-center py-1"><span class="badge bg-primary me-2">&nbsp;</span> {{ __('In Progress / Examining / Investigating') }}</li>
                <li class="list-group-item d-flex align-items-center py-1"><span class="badge bg-warning text-dark me-2">&nbsp;</span> {{ __('Review / Under Review / Assessed') }}</li>
                <li class="list-group-item d-flex align-items-center py-1"><span class="badge bg-cyan me-2" style="background-color: #17a2b8 !important;">&nbsp;</span> {{ __('Approved / Scheduled / Quoted') }}</li>
                <li class="list-group-item d-flex align-items-center py-1"><span class="badge bg-success me-2">&nbsp;</span> {{ __('Completed / Resolved / Accessioned') }}</li>
                <li class="list-group-item d-flex align-items-center py-1"><span class="badge bg-secondary me-2">&nbsp;</span> {{ __('Disposed / Closed') }}</li>
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
                            <span class="h3 mb-0">{{ $overallCompletion['percentage'] }}%</span>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h3>{{ __('Overall Workflow Completion') }}</h3>
                        <p class="mb-0">{{ $overallCompletion['completed'] }} {{ __('of') }} {{ $overallCompletion['total'] }} {{ __('procedures completed') }}</p>
                        <div class="progress mt-2" style="height: 10px;">
                            <div class="progress-bar bg-white" style="width: {{ $overallCompletion['percentage'] }}%"></div>
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
                        <h2 class="text-primary">{{ $workflowStats['total_objects'] }}</h2>
                        <small class="text-muted">{{ __('Total Objects') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-success">{{ $workflowStats['completed_procedures'] }}</h2>
                        <small class="text-muted">{{ __('Completed') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-warning">{{ $workflowStats['in_progress_procedures'] }}</h2>
                        <small class="text-muted">{{ __('In Progress') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-secondary">{{ $workflowStats['pending_procedures'] }}</h2>
                        <small class="text-muted">{{ __('Pending') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Procedure Status Overview -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ __('Procedure Status Overview') }}</h5></div>
            <div class="card-body">
                @if (empty($procedureStatusCounts))
                <p class="text-muted mb-0">{{ __('No workflow data yet. Start workflows from individual object pages.') }}</p>
                @else
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('Procedure') }}</th>
                                <th class="text-center">{{ __('Pending') }}</th>
                                <th class="text-center">{{ __('In Progress') }}</th>
                                <th class="text-center">{{ __('Completed') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($procedures as $procKey => $procDef)
                            @php $counts = $procedureStatusCounts[$procKey] ?? []; @endphp
                            <tr>
                                <td><i class="{{ $procDef['icon'] ?? 'fa fa-circle' }} me-2"></i>{{ $procDef['label'] }}</td>
                                <td class="text-center"><span class="badge bg-secondary">{{ $counts['pending'] ?? 0 }}</span></td>
                                <td class="text-center">
                                    @php
                                    $inProgress = 0;
                                    foreach ($counts as $state => $count) {
                                        if (!in_array($state, ['pending', 'completed', 'verified', 'closed', 'confirmed'])) {
                                            $inProgress += $count;
                                        }
                                    }
                                    @endphp
                                    <span class="badge bg-primary">{{ $inProgress }}</span>
                                </td>
                                <td class="text-center">
                                    @php $completed = ($counts['completed'] ?? 0) + ($counts['verified'] ?? 0) + ($counts['closed'] ?? 0) + ($counts['confirmed'] ?? 0); @endphp
                                    <span class="badge bg-success">{{ $completed }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('Recent Activity') }}</h5></div>
            <div class="card-body">
                @if (empty($recentActivity))
                <p class="text-muted text-center mb-0"><em>{{ __('No recent workflow activity.') }}</em></p>
                @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Object') }}</th>
                                <th>{{ __('Procedure') }}</th>
                                <th>{{ __('Action') }}</th>
                                <th>{{ __('User') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentActivity as $activity)
                            <tr>
                                <td><small>{{ date('Y-m-d H:i', strtotime($activity->created_at)) }}</small></td>
                                <td>
                                    <a href="{{ url_for(['module' => 'museum', 'action' => 'index', 'slug' => $activity->slug]) }}">
                                        {{ $activity->object_title ?? $activity->slug }}
                                    </a>
                                </td>
                                <td>{{ ucwords(str_replace('_', ' ', $activity->procedure_type)) }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', $activity->from_state)) }}</span>
                                    <i class="fas fa-arrow-right mx-1"></i>
                                    <span class="badge bg-primary">{{ ucwords(str_replace('_', ' ', $activity->to_state)) }}</span>
                                </td>
                                <td><small>{{ $activity->user_name ?? '' }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
