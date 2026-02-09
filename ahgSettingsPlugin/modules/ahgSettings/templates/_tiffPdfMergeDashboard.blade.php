@php
\AhgCore\Core\AhgDb::init();
require_once \sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/TiffPdfMergeRepository.php';

$userId = \sfContext::getInstance()->user->getAttribute('user_id');
$repository = new \AtomFramework\Repositories\TiffPdfMergeRepository();
$userStats = $repository->getStatistics($userId);
$userJobs = $repository->getJobs(['user_id' => $userId], 3);
@endphp

<div class="card shadow-sm h-100">
    <div class="card-header bg-gradient bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fas fa-layer-group me-2"></i>
                PDF Merge Tool
            </h6>
            <a href="{{ url_for(['module' => 'tiffpdfmerge', 'action' => 'index']) }}"
               class="btn btn-sm btn-light">
                <i class="fas fa-plus"></i>
            </a>
        </div>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Combine multiple TIFF, JPEG, or PNG images into a single PDF/A document.
        </p>

        <!-- Quick Stats -->
        <div class="row g-2 mb-3">
            <div class="col-4 text-center">
                <div class="fs-4 fw-bold text-primary">{{ $userStats['completed'] }}</div>
                <small class="text-muted">Completed</small>
            </div>
            <div class="col-4 text-center">
                <div class="fs-4 fw-bold text-warning">{{ $userStats['pending'] + $userStats['processing'] }}</div>
                <small class="text-muted">In Progress</small>
            </div>
            <div class="col-4 text-center">
                <div class="fs-4 fw-bold text-secondary">{{ $userStats['total_jobs'] }}</div>
                <small class="text-muted">Total</small>
            </div>
        </div>

        @if ($userJobs->count() > 0)
        <div class="list-group list-group-flush small">
            @foreach ($userJobs as $job)
            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                <div class="text-truncate" style="max-width: 150px;" title="{{ htmlspecialchars($job->job_name) }}">
                    {{ htmlspecialchars($job->job_name) }}
                </div>
                <div>
                    @if ($job->status === 'completed' && $job->output_path)
                    <a href="{{ url_for(['module' => 'tiffpdfmerge', 'action' => 'download', 'job_id' => $job->id]) }}"
                       class="btn btn-sm btn-success py-0 px-1" title="Download">
                        <i class="fas fa-download"></i>
                    </a>
                    @else
                    <span class="badge bg-{{ $job->status === 'pending' ? 'warning' : ($job->status === 'processing' ? 'info' : 'danger') }}">
                        {{ ucfirst($job->status) }}
                    </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    <div class="card-footer bg-light">
        <a href="{{ url_for(['module' => 'tiffpdfmerge', 'action' => 'index']) }}" class="btn btn-primary btn-sm w-100">
            <i class="fas fa-file-pdf me-1"></i>
            Create New PDF
        </a>
    </div>
</div>
