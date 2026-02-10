@php
$title = 'PDF Merge Jobs';
@endphp

@section('title', $title)

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-layer-group text-primary me-2"></i>
                PDF Merge Jobs
            </h2>
            <p class="text-muted mb-0">View and manage all TIFF to PDF merge jobs</p>
        </div>
        <div>
            <a href="{{ url_for(['module' => 'tiffpdfmerge', 'action' => 'index']) }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>New Merge
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0 me-2">Status:</label>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="pending" {{ $filterStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="queued" {{ $filterStatus === 'queued' ? 'selected' : '' }}>Queued</option>
                        <option value="processing" {{ $filterStatus === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="completed" {{ $filterStatus === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="failed" {{ $filterStatus === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="col-auto ms-auto">
                    <span class="text-muted small">
                        Showing {{ $jobs->count() }} of {{ $totalJobs }} jobs
                    </span>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <div class="col">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-primary">{{ $stats['total_jobs'] }}</div>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-warning">{{ $stats['pending'] }}</div>
                    <small class="text-muted">Pending</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-info">{{ ($stats['queued'] ?? 0) + $stats['processing'] }}</div>
                    <small class="text-muted">In Progress</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-success">{{ $stats['completed'] }}</div>
                    <small class="text-muted">Completed</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-danger">{{ $stats['failed'] }}</div>
                    <small class="text-muted">Failed</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Jobs Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Job Name</th>
                        <th>User</th>
                        <th class="text-center">Files</th>
                        <th class="text-center">Status</th>
                        <th>Created</th>
                        <th>Completed</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if($jobs->isEmpty())
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                            No jobs found
                        </td>
                    </tr>
                    @else
                    @foreach($jobs as $job)
                    <tr>
                        <td><span class="badge bg-secondary">#{{ $job->id }}</span></td>
                        <td>
                            <i class="fas fa-file-pdf text-danger me-1"></i>
                            <strong>{{ $job->job_name }}</strong>
                            @if($job->information_object_id)
                            <br><small class="text-muted">
                                <i class="fas fa-link me-1"></i>Linked to record
                            </small>
                            @endif
                        </td>
                        <td>
                            <small>{{ $job->username ?? 'Unknown' }}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $job->total_files }}</span>
                        </td>
                        <td class="text-center">
                            @php
                            $statusClass = match($job->status) {
                                'pending' => 'warning',
                                'queued' => 'info',
                                'processing' => 'primary',
                                'completed' => 'success',
                                'failed' => 'danger',
                                default => 'secondary'
                            };
                            $statusIcon = match($job->status) {
                                'pending' => 'clock',
                                'queued' => 'hourglass-half',
                                'processing' => 'spinner fa-spin',
                                'completed' => 'check-circle',
                                'failed' => 'times-circle',
                                default => 'question-circle'
                            };
                            @endphp
                            <span class="badge bg-{{ $statusClass }}">
                                <i class="fas fa-{{ $statusIcon }} me-1"></i>
                                {{ ucfirst($job->status) }}
                            </span>
                            @if($job->status === 'failed' && $job->error_message)
                            <br><small class="text-danger" title="{{ $job->error_message }}">
                                <i class="fas fa-exclamation-triangle"></i> Error
                            </small>
                            @endif
                        </td>
                        <td>
                            <small>{{ date('M j, Y', strtotime($job->created_at)) }}</small>
                            <br><small class="text-muted">{{ date('g:i A', strtotime($job->created_at)) }}</small>
                        </td>
                        <td>
                            @if($job->completed_at)
                            <small>{{ date('M j, Y', strtotime($job->completed_at)) }}</small>
                            <br><small class="text-muted">{{ date('g:i A', strtotime($job->completed_at)) }}</small>
                            @else
                            <small class="text-muted">-</small>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                @if($job->status === 'completed' && $job->output_path)
                                <a href="{{ url_for(['module' => 'tiffpdfmerge', 'action' => 'download', 'job_id' => $job->id]) }}"
                                   class="btn btn-success" title="Download PDF">
                                    <i class="fas fa-download"></i>
                                </a>
                                @endif

                                @if($job->status === 'pending')
                                <button type="button" class="btn btn-primary btn-process" data-job-id="{{ $job->id }}" title="Process Now">
                                    <i class="fas fa-play"></i>
                                </button>
                                @endif

                                <a href="{{ url_for(['module' => 'tiffpdfmerge', 'action' => 'view', 'job_id' => $job->id]) }}"
                                   class="btn btn-outline-secondary" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @if(in_array($job->status, ['pending', 'completed', 'failed']))
                                <button type="button" class="btn btn-outline-danger btn-delete" data-job-id="{{ $job->id }}" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($totalPages > 1)
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            @for($p = 1; $p <= $totalPages; $p++)
            <li class="page-item {{ $p === $currentPage ? 'active' : '' }}">
                <a class="page-link" href="?page={{ $p }}{{ $filterStatus ? '&status=' . $filterStatus : '' }}">
                    {{ $p }}
                </a>
            </li>
            @endfor
        </ul>
    </nav>
    @endif
</div>

<script {!! $csp_nonce !!}>
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', async function() {
        if (!confirm('Delete this job and all its files?')) return;

        const jobId = this.dataset.jobId;
        try {
            const response = await fetch('{{ url_for(['module' => 'tiffpdfmerge', 'action' => 'delete']) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'job_id=' + jobId
            });
            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + result.error);
            }
        } catch (e) {
            alert('Error deleting job');
        }
    });
});

document.querySelectorAll('.btn-process').forEach(btn => {
    btn.addEventListener('click', async function() {
        const jobId = this.dataset.jobId;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            const response = await fetch('{{ url_for(['module' => 'tiffpdfmerge', 'action' => 'process']) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'job_id=' + jobId
            });
            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + result.error);
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-play"></i>';
            }
        } catch (e) {
            alert('Error processing job');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-play"></i>';
        }
    });
});

// Auto-refresh if there are processing jobs
@if($hasProcessingJobs)
setTimeout(() => location.reload(), 5000);
@endif
</script>
