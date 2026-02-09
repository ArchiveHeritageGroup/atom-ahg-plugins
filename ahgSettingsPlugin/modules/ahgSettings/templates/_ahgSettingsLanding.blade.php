<div class="card-header">
    <h4 class="mb-0">
        <i class="fas fa-th-large"></i>
        {{ __('Settings Overview') }}
    </h4>
    <small class="text-muted">{{ __('Select a section to configure') }}</small>
</div>
<div class="card-body">
    <div class="row">
        @foreach ($sections as $sectionKey => $sectionInfo)
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas {{ $sectionInfo['icon'] }} fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title">{{ __($sectionInfo['label']) }}</h5>
                    <p class="card-text text-muted small">{{ __($sectionInfo['description']) }}</p>
                </div>
                <div class="card-footer bg-transparent border-0 text-center">
                    <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'section', 'section' => $sectionKey]) }}" class="btn btn-outline-primary">
                        <i class="fas fa-cog"></i> {{ __('Configure') }}
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
