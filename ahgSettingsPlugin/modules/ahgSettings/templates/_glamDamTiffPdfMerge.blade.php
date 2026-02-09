@php
\AhgCore\Core\AhgDb::init();
require_once \sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/TiffPdfMergeRepository.php';

$repository = new \AtomFramework\Repositories\TiffPdfMergeRepository();
$stats = $repository->getStatistics();
$recentJobs = $repository->getJobs([], 5);
@endphp

<div class="card mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>TIFF to PDF Merge</h5>
        <a href="{{ url_for(['module' => 'tiffpdfmerge', 'action' => 'index']) }}" class="btn btn-light btn-sm">
            <i class="fas fa-plus me-1"></i>New Merge
        </a>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-2 col-6">
                <div class="border rounded p-3 text-center">
                    <div class="fs-3 fw-bold text-primary">{{ $stats['total_jobs'] }}</div>
                    <small class="text-muted">Total</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="border rounded p-3 text-center">
                    <div class="fs-3 fw-bold text-warning">{{ $stats['pending'] }}</div>
                    <small class="text-muted">Pending</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="border rounded p-3 text-center">
                    <div class="fs-3 fw-bold text-info">{{ $stats['processing'] }}</div>
                    <small class="text-muted">Processing</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="border rounded p-3 text-center">
                    <div class="fs-3 fw-bold text-success">{{ $stats['completed'] }}</div>
                    <small class="text-muted">Completed</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="border rounded p-3 text-center">
                    <div class="fs-3 fw-bold text-danger">{{ $stats['failed'] }}</div>
                    <small class="text-muted">Failed</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="border rounded p-3 text-center">
                    <div class="fs-3 fw-bold text-secondary">{{ $stats['total_files'] }}</div>
                    <small class="text-muted">Files</small>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card h-100 border-primary">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-file-pdf text-primary me-2"></i>Create PDF from Images</h6>
                        <p class="card-text small text-muted">Upload multiple TIFF, JPEG, PNG files and merge into a single PDF/A document.</p>
                        <a href="{{ url_for(['module' => 'tiffpdfmerge', 'action' => 'index']) }}" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i>Start New Merge
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-tasks text-info me-2"></i>Background Jobs</h6>
                        <p class="card-text small text-muted">PDF merge runs as background jobs. View status and download completed PDFs.</p>
                        <a href="{{ url_for(['module' => 'jobs', 'action' => 'browse']) }}" class="btn btn-outline-info">
                            <i class="fas fa-list me-1"></i>View All Jobs
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if ($recentJobs->count() > 0)
        <h6 class="mb-3"><i class="fas fa-history me-2"></i>Recent Merge Jobs</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>User</th>
                        <th class="text-center">Files</th>
                        <th class="text-center">Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentJobs as $job)
                    <tr>
                        <td><i class="fas fa-file-pdf text-danger me-1"></i>{{ htmlspecialchars($job->job_name) }}</td>
                        <td><small class="text-muted">{{ htmlspecialchars($job->username ?? 'Unknown') }}</small></td>
                        <td class="text-center"><span class="badge bg-secondary">{{ $job->total_files }}</span></td>
                        <td class="text-center">
                            @php
                            $statusClass = match($job->status) {
                                'pending' => 'warning',
                                'processing' => 'info',
                                'completed' => 'success',
                                'failed' => 'danger',
                                default => 'secondary'
                            };
                            $statusIcon = match($job->status) {
                                'pending' => 'clock',
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
                        </td>
                        <td><small class="text-muted">{{ date('M j, g:i A', strtotime($job->created_at)) }}</small></td>
                        <td class="text-end">
                            @if ($job->status === 'completed' && $job->output_path)
                            <a href="{{ url_for(['module' => 'tiffpdfmerge', 'action' => 'download', 'job_id' => $job->id]) }}"
                               class="btn btn-sm btn-success" title="Download PDF">
                                <i class="fas fa-download"></i>
                            </a>
                            @endif
                            @if ($job->output_digital_object_id)
                            <span class="badge bg-primary ms-1" title="Attached to record">
                                <i class="fas fa-paperclip"></i>
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-4 text-muted">
            <i class="fas fa-inbox fa-3x mb-3"></i>
            <p class="mb-0">No merge jobs yet. Create your first PDF merge!</p>
        </div>
        @endif
    </div>
</div>
