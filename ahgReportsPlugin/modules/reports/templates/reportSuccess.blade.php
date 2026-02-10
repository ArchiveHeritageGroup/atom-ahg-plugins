@extends('layouts.page')

@php
// Unescape arrays from Symfony output escaper
$filters = sfOutputEscaper::unescape($filters ?? []);
$outputFormats = sfOutputEscaper::unescape($outputFormats ?? []);
$parameters = sfOutputEscaper::unescape($parameters ?? []);
$results = sfOutputEscaper::unescape($results ?? []);
$repositories = sfOutputEscaper::unescape($repositories ?? []);
$levels = sfOutputEscaper::unescape($levels ?? []);
$mediaTypes = sfOutputEscaper::unescape($mediaTypes ?? []);
$users = sfOutputEscaper::unescape($users ?? []);
$glamTypes = sfOutputEscaper::unescape($glamTypes ?? []);
@endphp

@section('content')
<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url_for(['module' => 'staticpage', 'action' => 'static', 'id' => 'homepage']) }}">{{ __('Home') }}</a></li>
            <li class="breadcrumb-item"><a href="/admin/dashboard">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __($reportName) }}</li>
        </ol>
    </nav>

    <!-- Report Header -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>{{ __($reportName) }}</h4>
                <small class="opacity-75">{{ __($reportDescription ?? '') }}</small>
            </div>
            <div>
                <span class="badge bg-light text-dark">{{ __(ucfirst($reportCategory)) }}</span>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-body border-bottom">
            <form method="get" class="row g-3">
                <input type="hidden" name="code" value="{{ $reportCode }}">

                @if (isset($parameters['repository_id']))
                <div class="col-md-3">
                    <label class="form-label">{{ __('Repository') }}</label>
                    <select name="repository_id" class="form-select form-select-sm">
                        <option value="">{{ __('All repositories') }}</option>
                        @foreach ($repositories as $repo)
                        <option value="{{ $repo->id }}" {{ ($filters['repository_id'] ?? '') == $repo->id ? 'selected' : '' }}>
                            {{ $repo->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if (!empty($glamTypes))
                <div class="col-md-2">
                    <label class="form-label">{{ __('GLAM Type') }}</label>
                    <select name="glam_type" class="form-select form-select-sm">
                        <option value="">{{ __('All types') }}</option>
                        @foreach ($glamTypes as $type)
                        <option value="{{ $type }}" {{ ($filters['glam_type'] ?? '') == $type ? 'selected' : '' }}>
                            {{ ucfirst($type) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if (isset($parameters['level_of_description']))
                <div class="col-md-2">
                    <label class="form-label">{{ __('Level') }}</label>
                    <select name="level_of_description" class="form-select form-select-sm">
                        <option value="">{{ __('All levels') }}</option>
                        @foreach ($levels as $level)
                        <option value="{{ $level->id }}" {{ ($filters['level_of_description'] ?? '') == $level->id ? 'selected' : '' }}>
                            {{ $level->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if (isset($parameters['date_from']) || isset($parameters['date_to']))
                <div class="col-md-2">
                    <label class="form-label">{{ __('From') }}</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('To') }}</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                @endif

                @if (isset($parameters['media_type']))
                <div class="col-md-2">
                    <label class="form-label">{{ __('Media Type') }}</label>
                    <select name="media_type" class="form-select form-select-sm">
                        <option value="">{{ __('All types') }}</option>
                        @foreach ($mediaTypes as $type)
                        <option value="{{ $type->id }}" {{ ($filters['media_type'] ?? '') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if (isset($parameters['user_id']))
                <div class="col-md-2">
                    <label class="form-label">{{ __('User') }}</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">{{ __('All users') }}</option>
                        @foreach ($users as $user)
                        <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                            {{ $user->username }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if (isset($parameters['status']))
                <div class="col-md-2">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">{{ __('All') }}</option>
                        <option value="pending" {{ ($filters['status'] ?? '') == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                        <option value="approved" {{ ($filters['status'] ?? '') == 'approved' ? 'selected' : '' }}>{{ __('Approved') }}</option>
                        <option value="denied" {{ ($filters['status'] ?? '') == 'denied' ? 'selected' : '' }}>{{ __('Denied') }}</option>
                    </select>
                </div>
                @endif

                @if (isset($parameters['months_ahead']))
                <div class="col-md-2">
                    <label class="form-label">{{ __('Months Ahead') }}</label>
                    <select name="months_ahead" class="form-select form-select-sm">
                        <option value="3" {{ ($filters['months_ahead'] ?? 6) == 3 ? 'selected' : '' }}>3</option>
                        <option value="6" {{ ($filters['months_ahead'] ?? 6) == 6 ? 'selected' : '' }}>6</option>
                        <option value="12" {{ ($filters['months_ahead'] ?? 6) == 12 ? 'selected' : '' }}>12</option>
                        <option value="24" {{ ($filters['months_ahead'] ?? 6) == 24 ? 'selected' : '' }}>24</option>
                    </select>
                </div>
                @endif

                @if (isset($parameters['period']))
                <div class="col-md-2">
                    <label class="form-label">{{ __('Period') }}</label>
                    <select name="period" class="form-select form-select-sm">
                        <option value="monthly" {{ ($filters['period'] ?? 'monthly') == 'monthly' ? 'selected' : '' }}>{{ __('Monthly') }}</option>
                        <option value="yearly" {{ ($filters['period'] ?? 'monthly') == 'yearly' ? 'selected' : '' }}>{{ __('Yearly') }}</option>
                    </select>
                </div>
                @endif

                <div class="col-md-2">
                    <label class="form-label">{{ __('Per Page') }}</label>
                    <select name="limit" class="form-select form-select-sm">
                        <option value="25" {{ ($filters['limit'] ?? 25) == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ ($filters['limit'] ?? 25) == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ ($filters['limit'] ?? 25) == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm me-2">
                        <i class="fas fa-filter me-1"></i>{{ __('Filter') }}
                    </button>
                    <a href="{{ url_for(['module' => 'reports', 'action' => 'report', 'code' => $reportCode]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-undo me-1"></i>{{ __('Reset') }}
                    </a>
                </div>
            </form>
        </div>

        <!-- Export buttons -->
        <div class="card-body py-2 border-bottom bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    @if ($pager)
                    <span class="text-muted">
                        {{ __('Showing %1% - %2% of %3% results', [
                            '%1%' => (($pager->page - 1) * $pager->limit) + 1,
                            '%2%' => min($pager->page * $pager->limit, $pager->total),
                            '%3%' => number_format($pager->total)
                        ]) }}
                    </span>
                    @elseif (!empty($results))
                    <span class="text-muted">{{ count($results) }} {{ __('results') }}</span>
                    @endif
                </div>
                <div class="btn-group">
                    @php
                    $exportParams = array_merge(['module' => 'reports', 'action' => 'report', 'code' => $reportCode], is_array($filters) ? $filters : []);
                    @endphp
                    @if (in_array('csv', $outputFormats))
                    <a href="{{ url_for(array_merge($exportParams, ['format' => 'csv'])) }}" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-file-csv me-1"></i>CSV
                    </a>
                    @endif
                    @if (in_array('xlsx', $outputFormats))
                    <a href="{{ url_for(array_merge($exportParams, ['format' => 'xlsx'])) }}" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    @endif
                    @if (in_array('pdf', $outputFormats))
                    <a href="{{ url_for(array_merge($exportParams, ['format' => 'pdf'])) }}" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                    @endif
                    @if (in_array('json', $outputFormats))
                    <a href="{{ url_for(array_merge($exportParams, ['format' => 'json'])) }}" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-file-code me-1"></i>JSON
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats (if available) -->
    @if (isset($summary) && !empty($summary))
    <div class="row mb-4">
        @foreach ($summary as $key => $value)
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="mb-0">{{ number_format($value) }}</h3>
                    <small class="text-muted">{{ __(ucwords(str_replace('_', ' ', $key))) }}</small>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Results Table -->
    <div class="card">
        <div class="card-body p-0">
            @if (empty($results))
            <div class="alert alert-info m-3">
                <i class="fas fa-info-circle me-2"></i>{{ __('No results found. Try adjusting your filters.') }}
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            @php
                            $first = (array) $results[0];
                            @endphp
                            @foreach (array_keys($first) as $col)
                            @php $label = ucwords(str_replace('_', ' ', $col)); @endphp
                            <th>
                                @php
                                $currentSort = $filters['sort'] ?? 'id';
                                $currentDir = $filters['dir'] ?? 'desc';
                                $newDir = ($currentSort == $col && $currentDir == 'asc') ? 'desc' : 'asc';
                                $sortUrl = url_for(array_merge($exportParams, ['sort' => $col, 'dir' => $newDir]));
                                @endphp
                                <a href="{{ $sortUrl }}" class="text-decoration-none text-dark">
                                    {{ __($label) }}
                                    @if ($currentSort == $col)
                                    <i class="fas fa-sort-{{ $currentDir == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </a>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $row)
                        <tr>
                            @foreach ((array) $row as $col => $value)
                            <td>
                                @php
                                // Format special columns
                                if (in_array($col, ['created_at', 'updated_at', 'date', 'visit_date', 'request_date', 'treatment_date', 'report_date', 'end_date'])) {
                                    echo $value ? date('Y-m-d H:i', strtotime($value)) : '-';
                                } elseif (in_array($col, ['byte_size', 'total_size', 'avg_size'])) {
                                    echo $value ? number_format($value / 1024 / 1024, 2) . ' MB' : '-';
                                } elseif (in_array($col, ['percentage'])) {
                                    echo $value . '%';
                                } elseif (in_array($col, ['count', 'view_count', 'download_count', 'search_count', 'record_count', 'accession_count', 'visit_count'])) {
                                    echo number_format($value);
                                } elseif ($col == 'title' && isset($row->id)) {
                                    // Link to record
                                    echo '<a href="' . url_for(['module' => 'informationobject', 'slug' => $row->id]) . '">' . truncate_text($value, 60) . '</a>';
                                } elseif (in_array($col, ['status', 'priority', 'clearance_level', 'overall_condition'])) {
                                    $badgeClass = 'bg-secondary';
                                    if (in_array($value, ['approved', 'completed', 'good', 'public'])) $badgeClass = 'bg-success';
                                    elseif (in_array($value, ['pending', 'medium', 'restricted'])) $badgeClass = 'bg-warning text-dark';
                                    elseif (in_array($value, ['denied', 'urgent', 'poor', 'secret', 'top_secret'])) $badgeClass = 'bg-danger';
                                    echo '<span class="badge ' . $badgeClass . '">' . ucfirst(str_replace('_', ' ', $value)) . '</span>';
                                } else {
                                    echo truncate_text($value, 80) ?: '-';
                                }
                                @endphp
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <!-- Pagination -->
        @if ($pager && $pager->pages > 1)
        <div class="card-footer">
            <nav>
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    @if ($pager->hasPrev)
                    <li class="page-item">
                        <a class="page-link" href="{{ url_for(array_merge($exportParams, ['page' => $pager->page - 1])) }}">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    @endif

                    @php
                    $start = max(1, $pager->page - 2);
                    $end = min($pager->pages, $pager->page + 2);
                    @endphp
                    @for ($p = $start; $p <= $end; $p++)
                    <li class="page-item {{ $p == $pager->page ? 'active' : '' }}">
                        <a class="page-link" href="{{ url_for(array_merge($exportParams, ['page' => $p])) }}">
                            {{ $p }}
                        </a>
                    </li>
                    @endfor

                    @if ($pager->hasNext)
                    <li class="page-item">
                        <a class="page-link" href="{{ url_for(array_merge($exportParams, ['page' => $pager->page + 1])) }}">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    @endif
                </ul>
            </nav>
        </div>
        @endif
    </div>
</div>
@endsection
