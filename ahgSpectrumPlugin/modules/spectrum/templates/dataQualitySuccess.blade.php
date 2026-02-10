<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url_for(['module' => 'spectrum', 'action' => 'dashboard']) }}">Spectrum</a></li>
        <li class="breadcrumb-item active">Data Quality</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><i class="fas fa-chart-line text-primary me-2"></i>Data Quality Dashboard</h1>
    <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card h-100 {{ $qualityScore >= 80 ? 'border-success' : ($qualityScore >= 50 ? 'border-warning' : 'border-danger') }}">
            <div class="card-body text-center">
                <h1 class="display-3 {{ $qualityScore >= 80 ? 'text-success' : ($qualityScore >= 50 ? 'text-warning' : 'text-danger') }}">
                    {{ $qualityScore }}%
                </h1>
                <h5>Overall Quality Score</h5>
                <p class="text-muted">Based on metadata completeness</p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-database me-2"></i>Collection Overview</h5></div>
            <div class="card-body">
                <h2 class="text-primary">{{ number_format($totalObjects) }}</h2>
                <p class="text-muted mb-0">Total Objects in Collection</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card h-100 {{ $missingTitles == 0 ? 'border-success' : 'border-danger' }}">
            <div class="card-body text-center">
                <h3 class="{{ $missingTitles == 0 ? 'text-success' : 'text-danger' }}">{{ number_format($missingTitles) }}</h3>
                <p class="mb-0"><i class="fas fa-heading me-1"></i>Missing Titles</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card h-100 {{ $missingDates == 0 ? 'border-success' : 'border-warning' }}">
            <div class="card-body text-center">
                <h3 class="{{ $missingDates == 0 ? 'text-success' : 'text-warning' }}">{{ number_format($missingDates) }}</h3>
                <p class="mb-0"><i class="fas fa-calendar me-1"></i>Missing Dates</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card h-100 {{ $missingRepository == 0 ? 'border-success' : 'border-warning' }}">
            <div class="card-body text-center">
                <h3 class="{{ $missingRepository == 0 ? 'text-success' : 'text-warning' }}">{{ number_format($missingRepository) }}</h3>
                <p class="mb-0"><i class="fas fa-building me-1"></i>Missing Repository</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card h-100 {{ $missingDigitalObjects == 0 ? 'border-success' : 'border-info' }}">
            <div class="card-body text-center">
                <h3 class="{{ $missingDigitalObjects == 0 ? 'text-success' : 'text-info' }}">{{ number_format($missingDigitalObjects) }}</h3>
                <p class="mb-0"><i class="fas fa-image me-1"></i>No Digital Objects</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Recommendations</h5></div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            @if($missingTitles > 0)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fas fa-exclamation-triangle text-danger me-2"></i>Add titles to {{ $missingTitles }} objects</span>
                <span class="badge bg-danger">High Priority</span>
            </li>
            @endif
            @if($missingDates > 0)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fas fa-exclamation-circle text-warning me-2"></i>Add date information to {{ $missingDates }} objects</span>
                <span class="badge bg-warning text-dark">Medium Priority</span>
            </li>
            @endif
            @if($missingRepository > 0)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fas fa-info-circle text-warning me-2"></i>Assign repository to {{ $missingRepository }} objects</span>
                <span class="badge bg-warning text-dark">Medium Priority</span>
            </li>
            @endif
            @if($missingDigitalObjects > 0)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fas fa-image text-info me-2"></i>Consider adding digital objects to {{ $missingDigitalObjects }} records</span>
                <span class="badge bg-info">Low Priority</span>
            </li>
            @endif
            @if($qualityScore == 100)
            <li class="list-group-item text-success">
                <i class="fas fa-check-circle me-2"></i>Excellent! All quality checks passed.
            </li>
            @endif
        </ul>
    </div>
</div>
