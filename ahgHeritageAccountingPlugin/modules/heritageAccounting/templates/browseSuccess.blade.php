@extends('layouts.page')

@section('title', __('Heritage Assets'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">
                <i class="fas fa-landmark me-2"></i>{{ __('Heritage Assets') }}
            </h1>
            <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'add']) }}" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>{{ __('Add Asset') }}
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{ __('Search') }}</label>
                    <div class="position-relative"><input type="text" name="sq" id="heritageSearch" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Identifier, title, donor...') }}" autocomplete="off"><div id="heritageResults" class="autocomplete-dropdown"></div></div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Standard') }}</label>
                    <select name="standard_id" class="form-select">
                        <option value="">{{ __('All Standards') }}</option>
                        @foreach ($standards as $s)
                            <option value="{{ $s->id }}" {{ ($filters['standard_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Class') }}</label>
                    <select name="class_id" class="form-select">
                        <option value="">{{ __('All Classes') }}</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}" {{ ($filters['class_id'] ?? '') == $c->id ? 'selected' : '' }}>{!! htmlspecialchars_decode($c->name) !!}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="recognised" {{ ($filters['recognition_status'] ?? '') == 'recognised' ? 'selected' : '' }}>{{ __('Recognised') }}</option>
                        <option value="not_recognised" {{ ($filters['recognition_status'] ?? '') == 'not_recognised' ? 'selected' : '' }}>{{ __('Not Recognised') }}</option>
                        <option value="pending" {{ ($filters['recognition_status'] ?? '') == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                        <option value="derecognised" {{ ($filters['recognition_status'] ?? '') == 'derecognised' ? 'selected' : '' }}>{{ __('Derecognised') }}</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search me-1"></i>{{ __('Filter') }}</button>
                    <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'browse']) }}" class="btn btn-outline-secondary">{{ __('Clear') }}</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span>{{ __('Showing %1% of %2% assets', ['%1%' => count($assets), '%2%' => $total]) }}</span>
            <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'dashboard']) }}" class="btn btn-sm btn-light">
                <i class="fas fa-chart-pie me-1"></i>{{ __('Dashboard') }}
            </a>
        </div>
        <div class="card-body p-0">
            @if (!empty($assets))
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Identifier') }}</th>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Class') }}</th>
                                <th>{{ __('Standard') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Measurement') }}</th>
                                <th class="text-end">{{ __('Carrying Amount') }}</th>
                                <th>{{ __('Last Valuation') }}</th>
                                <th class="text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assets as $asset)
                                <tr>
                                    <td>
                                        <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) }}">
                                            {{ $asset->object_identifier ?: 'N/A' }}
                                        </a>
                                    </td>
                                    <td>{{ $asset->object_title ?: '-' }}</td>
                                    <td><span class="badge bg-secondary">{{ $asset->class_name ?: '-' }}</span></td>
                                    <td>{{ $asset->standard_code ?: '-' }}</td>
                                    <td>
                                        @php
                                        $statusColors = ['recognised' => 'success', 'not_recognised' => 'secondary', 'pending' => 'warning', 'derecognised' => 'danger'];
                                        $color = $statusColors[$asset->recognition_status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">{{ ucfirst(str_replace('_', ' ', $asset->recognition_status)) }}</span>
                                    </td>
                                    <td>{{ ucfirst($asset->measurement_basis ?: '-') }}</td>
                                    <td class="text-end fw-bold">{{ number_format($asset->current_carrying_amount, 2) }}</td>
                                    <td>{{ $asset->last_valuation_date ? format_date($asset->last_valuation_date, 'D') : '-' }}</td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) }}" class="btn btn-outline-primary" title="{{ __('View') }}">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'edit', 'id' => $asset->id]) }}" class="btn btn-outline-warning" title="{{ __('Edit') }}">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($total > $limit)
                    <nav class="p-3">
                        <ul class="pagination justify-content-center mb-0">
                            @php $totalPages = ceil($total / $limit); @endphp
                            @for ($i = 1; $i <= $totalPages; $i++)
                                <li class="page-item {{ $i == $page ? 'active' : '' }}">
                                    <a class="page-link" href="?page={{ $i }}&sq={{ urlencode($filters['search'] ?? '') }}&standard_id={{ $filters['standard_id'] ?? '' }}&class_id={{ $filters['class_id'] ?? '' }}&status={{ $filters['recognition_status'] ?? '' }}">{{ $i }}</a>
                                </li>
                            @endfor
                        </ul>
                    </nav>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <p class="text-muted">{{ __('No heritage assets found matching your criteria.') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>


<style {!! $csp_nonce !!}>
.autocomplete-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 250px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.autocomplete-dropdown .ac-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}
.autocomplete-dropdown .ac-item:hover {
    background-color: #f5f5f5;
}
.autocomplete-dropdown .ac-item:last-child {
    border-bottom: none;
}
</style>

<script {!! $csp_nonce !!}>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('heritageSearch');
    var resultsDiv = document.getElementById('heritageResults');
    var debounceTimer;

    if (!searchInput || !resultsDiv) return;

    searchInput.addEventListener('input', function() {
        var query = this.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            resultsDiv.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(function() {
            fetch('{{ url_for(["module" => "heritageApi", "action" => "autocomplete"]) }}?term=' + encodeURIComponent(query))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.length === 0) {
                        resultsDiv.style.display = 'none';
                        return;
                    }
                    resultsDiv.innerHTML = data.map(function(item) {
                        return '<div class="ac-item" data-label="' + (item.title || item.label).replace(/"/g, '&quot;') + '">' + item.label + '</div>';
                    }).join('');
                    resultsDiv.style.display = 'block';
                })
                .catch(function() { resultsDiv.style.display = 'none'; });
        }, 300);
    });

    resultsDiv.addEventListener('click', function(e) {
        if (e.target.classList.contains('ac-item')) {
            searchInput.value = e.target.dataset.label;
            resultsDiv.style.display = 'none';
        }
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#heritageSearch') && !e.target.closest('#heritageResults')) {
            resultsDiv.style.display = 'none';
        }
    });
});
</script>
@endsection
