@extends('layouts.page')

@php
$jobs = $jobData['jobs'] ?? [];
$total = $jobData['total'] ?? 0;
@endphp

@section('title')
<h1 class="h3">
    <i class="fas fa-layer-group me-2"></i>Batch Operations
</h1>
@endsection

@section('sidebar')
@include('heritage/adminSidebar', ['active' => 'batch'])

<div class="mt-4">
    <label class="form-label">Filter by Status</label>
    <div class="list-group">
        <a href="?" class="list-group-item list-group-item-action {{ !$sf_request->getParameter('status') ? 'active' : '' }}">All Jobs</a>
        <a href="?status=processing" class="list-group-item list-group-item-action {{ $sf_request->getParameter('status') === 'processing' ? 'active' : '' }}">Processing</a>
        <a href="?status=completed" class="list-group-item list-group-item-action {{ $sf_request->getParameter('status') === 'completed' ? 'active' : '' }}">Completed</a>
        <a href="?status=failed" class="list-group-item list-group-item-action {{ $sf_request->getParameter('status') === 'failed' ? 'active' : '' }}">Failed</a>
    </div>
</div>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Batch Jobs</h5>
        <span class="badge bg-secondary">{{ number_format($total) }} jobs</span>
    </div>
    <div class="card-body p-0">
        @if (empty($jobs))
        <div class="text-center text-muted py-5">
            <i class="fas fa-inbox fs-1 mb-3 d-block"></i>
            <p>No batch jobs found.</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Job</th>
                        <th>Type</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jobs as $job)
                    @php
                        $progress = $job->total_items > 0 ? round(($job->processed_items / $job->total_items) * 100) : 0;
                        $statusColors = [
                            'pending' => 'secondary', 'queued' => 'info', 'processing' => 'primary',
                            'completed' => 'success', 'failed' => 'danger', 'cancelled' => 'secondary', 'paused' => 'warning'
                        ];
                        $color = $statusColors[$job->status] ?? 'secondary';
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $job->job_name ?? "Job #{$job->id}" }}</strong>
                            <br><small class="text-muted">by {{ $job->username ?? 'Unknown' }}</small>
                        </td>
                        <td>{{ $job->job_type }}</td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" style="width: {{ $progress }}%">{{ $progress }}%</div>
                            </div>
                            <small class="text-muted">{{ $job->processed_items }}/{{ $job->total_items }} items</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $color }}">{{ ucfirst($job->status) }}</span>
                            @if ($job->failed_items > 0)
                            <br><small class="text-danger">{{ $job->failed_items }} failed</small>
                            @endif
                        </td>
                        <td>
                            <small>{{ date('M d, H:i', strtotime($job->created_at)) }}</small>
                        </td>
                        <td class="text-end">
                            <a href="#" class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
