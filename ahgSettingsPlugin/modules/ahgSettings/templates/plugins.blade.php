@extends('layouts.page')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="mb-0"><i class="fas fa-puzzle-piece"></i> {{ __('Plugin Management') }}</h1>
    <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'index']) }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>{{ __('Back to AHG Settings') }}
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <div class="row align-items-center">
            <div class="col-auto">
                <strong><i class="fas fa-filter me-2"></i>Category</strong>
                <div class="btn-group btn-group-sm ms-2" role="group">
                    <button type="button" class="btn btn-outline-primary active" data-filter="all">All</button>
                    @foreach ($categories as $key => $cat)
                    <button type="button" class="btn btn-outline-{{ $cat['class'] }}" data-filter="{{ $key }}">
                        <i class="fas {{ $cat['icon'] }} me-1"></i>{{ $cat['label'] }}
                    </button>
                    @endforeach
                </div>
            </div>
            <div class="col-auto">
                <strong><i class="fas fa-toggle-on me-2"></i>Status</strong>
                <div class="btn-group btn-group-sm ms-2" role="group">
                    <button type="button" class="btn btn-outline-primary active" data-status="all">All</button>
                    <button type="button" class="btn btn-outline-success" data-status="enabled">
                        <i class="fas fa-check me-1"></i>Enabled
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-status="disabled">
                        <i class="fas fa-times me-1"></i>Disabled
                    </button>
                </div>
            </div>
            <div class="col-auto ms-auto">
                @php
                    $enabledCount = 0;
                    $disabledCount = 0;
                    foreach ($plugins as $p) {
                        if (!empty($p['is_enabled'])) { $enabledCount++; } else { $disabledCount++; }
                    }
                @endphp
                <span class="badge bg-success">{{ $enabledCount }} Enabled</span>
                <span class="badge bg-secondary">{{ $disabledCount }} Disabled</span>
                <span class="badge bg-primary">{{ count($plugins) }} Total</span>
            </div>
        </div>
    </div>
</div>

<div class="row" id="plugins-grid">
    @if (empty($plugins))
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No plugins found in database.
            Run <code>php bin/atom extension:discover</code> to see available plugins.
        </div>
    </div>
    @else
    @foreach ($plugins as $plugin)
    @php
        $isEnabled = !empty($plugin['is_enabled']);
        $category = $plugin['category'] ?? 'other';
        $catInfo = $categories[$category] ?? $categories['other'];
    @endphp
    <div class="col-lg-4 col-md-6 mb-4 plugin-card"
         data-category="{{ $category }}"
         data-status="{{ $isEnabled ? 'enabled' : 'disabled' }}">
        <div class="card h-100 {{ $isEnabled ? '' : 'border-secondary opacity-75' }}">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="badge bg-{{ $catInfo['class'] }}">
                    <i class="fas {{ $catInfo['icon'] }} me-1"></i>{{ $catInfo['label'] }}
                </span>
                <span class="badge {{ $isEnabled ? 'bg-success' : 'bg-secondary' }}">
                    {{ $isEnabled ? 'Enabled' : 'Disabled' }}
                </span>
            </div>
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-plug me-2 text-muted"></i>
                    {{ e($plugin['name']) }}
                </h5>
                <p class="card-text text-muted small">
                    {{ e($plugin['description'] ?? 'No description available') }}
                </p>
                @if (!empty($plugin['version']))
                <small class="text-muted">
                    <i class="fas fa-code-branch me-1"></i>v{{ e($plugin['version']) }}
                </small>
                @endif
            </div>
            <div class="card-footer bg-white">
                <form method="post" action="{{ url_for(['module' => 'ahgSettings', 'action' => 'plugins']) }}" class="d-inline">
                    <input type="hidden" name="plugin_name" value="{{ e($plugin['name']) }}">
                    @php $isLocked = !empty($plugin['is_locked']); $isCore = !empty($plugin['is_core']); @endphp
                    @if ($isCore)
                    <span class="badge bg-primary"><i class="fas fa-shield-alt me-1"></i>Core</span>
                    @elseif ($isLocked)
                    <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Locked</span>
                    @elseif ($isEnabled)
                    <button type="submit" name="plugin_action" value="disable"
                            class="btn btn-sm btn-outline-danger btn-plugin-disable"
                            data-plugin-name="{{ e($plugin['name']) }}">
                        <i class="fas fa-power-off me-1"></i>Disable
                    </button>
                    @else
                    <button type="submit" name="plugin_action" value="enable"
                            class="btn btn-sm btn-success">
                        <i class="fas fa-check me-1"></i>Enable
                    </button>
                    @endif
                    @if (!empty($plugin['record_check_query']) && $isEnabled)
                    <span class="badge bg-info ms-1" title="This plugin has a record check query — cannot be disabled if records exist"><i class="fas fa-database me-1"></i>Record-linked</span>
                    @endif
                </form>
            </div>
        </div>
    </div>
    @endforeach
    @endif
</div>

<script {!! $csp_nonce !!}>
document.addEventListener('DOMContentLoaded', function() {
    var activeCategory = 'all';
    var activeStatus = 'all';
    var cards = document.querySelectorAll('.plugin-card');

    function filterCards() {
        cards.forEach(function(card) {
            var catMatch = (activeCategory === 'all' || card.dataset.category === activeCategory);
            var statusMatch = (activeStatus === 'all' || card.dataset.status === activeStatus);
            card.style.display = (catMatch && statusMatch) ? '' : 'none';
        });
    }

    // Category filter buttons
    document.querySelectorAll('.card-header [data-filter]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.card-header [data-filter]').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            activeCategory = btn.dataset.filter;
            filterCards();
        });
    });

    // Status filter buttons
    document.querySelectorAll('.card-header [data-status]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.card-header [data-status]').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            activeStatus = btn.dataset.status;
            filterCards();
        });
    });

    // Disable button confirmation
    document.querySelectorAll('.btn-plugin-disable').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Disable ' + btn.dataset.pluginName + '?')) {
                e.preventDefault();
            }
        });
    });
});
</script>
@endsection
