@extends('layouts.page')

@section('content')
<h1><i class="fas fa-exclamation-triangle text-danger me-2"></i>{{ __('System Error Log') }}</h1>

<!-- TEMPLATE:BLADE_ERROR_LOG -->

<!-- Stats Row -->
<div class="row mb-3">
  <div class="col-md-3">
    <div class="card border-danger">
      <div class="card-body text-center py-2">
        <div class="h4 mb-0 text-danger">{{ $unreadCount }}</div>
        <small class="text-muted">{{ __('Unread') }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card">
      <div class="card-body text-center py-2">
        <div class="h4 mb-0">{{ $todayCount }}</div>
        <small class="text-muted">{{ __('Today') }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card">
      <div class="card-body text-center py-2">
        <div class="h4 mb-0">{{ $total }}</div>
        <small class="text-muted">{{ __('Total') }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-3 d-flex align-items-center justify-content-end gap-2">
    <form method="post" class="d-inline">
      <input type="hidden" name="mark_read" value="all">
      <button type="submit" class="btn btn-sm btn-outline-success" {{ $unreadCount === 0 ? 'disabled' : '' }}>
        <i class="fas fa-check-double me-1"></i>{{ __('Mark All Read') }}
      </button>
    </form>
    <form method="post" class="d-inline">
      <input type="hidden" name="clear_old" value="30">
      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete logs older than 30 days?')">
        <i class="fas fa-trash me-1"></i>{{ __('Clear 30d+') }}
      </button>
    </form>
  </div>
</div>

<!-- Filters -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="get" class="row g-2 align-items-center">
      <input type="hidden" name="module" value="ahgSettings">
      <input type="hidden" name="action" value="errorLog">
      <div class="col-auto">
        <select name="level" class="form-select form-select-sm">
          <option value="">{{ __('All levels') }}</option>
          <option value="error" {{ $level === 'error' ? 'selected' : '' }}>Error</option>
          <option value="fatal" {{ $level === 'fatal' ? 'selected' : '' }}>Fatal</option>
          <option value="warning" {{ $level === 'warning' ? 'selected' : '' }}>Warning</option>
        </select>
      </div>
      <div class="col">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="{{ __('Search message, URL, file, exception...') }}" value="{{ e($search) }}">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i>{{ __('Filter') }}</button>
        <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'errorLog']) }}" class="btn btn-sm btn-outline-secondary">{{ __('Reset') }}</a>
      </div>
    </form>
  </div>
</div>

<!-- Error Log Table -->
<div class="card">
  <div class="table-responsive">
    <table class="table table-sm table-hover mb-0">
      <thead class="table-dark">
        <tr>
          <th style="width: 140px">{{ __('Time') }}</th>
          <th style="width: 70px">{{ __('Level') }}</th>
          <th>{{ __('Error') }}</th>
          <th style="width: 160px">{{ __('Location') }}</th>
          <th style="width: 120px">{{ __('Client') }}</th>
          <th style="width: 60px"></th>
        </tr>
      </thead>
      <tbody>
        @forelse ($errors as $err)
        <tr class="{{ !$err->is_read ? 'table-warning' : '' }}">
          <td class="small text-nowrap">
            {{ $err->created_at }}
            @if ($err->request_id)
              <br><code class="small">{{ substr($err->request_id, 0, 12) }}...</code>
            @endif
          </td>
          <td>
            @if ($err->level === 'fatal')
              <span class="badge bg-danger">FATAL</span>
            @elseif ($err->level === 'error')
              <span class="badge bg-warning text-dark">ERROR</span>
            @else
              <span class="badge bg-secondary">{{ strtoupper($err->level) }}</span>
            @endif
            @if ($err->status_code)
              <span class="badge bg-dark">{{ $err->status_code }}</span>
            @endif
          </td>
          <td>
            <div class="fw-bold small">{{ e($err->exception_class ?? '') }}</div>
            <div class="small text-truncate" style="max-width: 400px" title="{{ e($err->message) }}">{{ e(substr($err->message, 0, 200)) }}</div>
            @if ($err->url)
              <div class="small text-muted text-truncate" style="max-width: 400px">
                <span class="badge bg-light text-dark">{{ $err->http_method ?? 'GET' }}</span>
                {{ e(substr($err->url, 0, 120)) }}
              </div>
            @endif
          </td>
          <td class="small text-truncate" style="max-width: 160px" title="{{ e($err->file ?? '') }}">
            {{ $err->file ? basename($err->file) . ':' . $err->line : '-' }}
          </td>
          <td class="small">
            {{ $err->client_ip ?? '-' }}
            @if ($err->user_id)
              <br><span class="badge bg-info">user:{{ $err->user_id }}</span>
            @endif
          </td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#trace-{{ $err->id }}">
              <i class="fas fa-chevron-down"></i>
            </button>
            <form method="post" class="d-inline">
              <input type="hidden" name="delete_id" value="{{ $err->id }}">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                <i class="fas fa-times"></i>
              </button>
            </form>
          </td>
        </tr>
        @if ($err->trace)
        <tr class="collapse" id="trace-{{ $err->id }}">
          <td colspan="6">
            <pre class="bg-dark text-light p-2 rounded small mb-0" style="max-height: 300px; overflow: auto">{{ e($err->trace) }}</pre>
            @if ($err->user_agent)
              <small class="text-muted">UA: {{ e(substr($err->user_agent, 0, 150)) }}</small>
            @endif
          </td>
        </tr>
        @endif
        @empty
        <tr>
          <td colspan="6" class="text-center text-muted py-4">
            <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
            {{ __('No errors logged.') }}
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if ($totalPages > 1)
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted">{{ __('Page') }} {{ $page }} / {{ $totalPages }} ({{ $total }} {{ __('total') }})</small>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        @if ($page > 1)
          <li class="page-item"><a class="page-link" href="{{ url_for(['module' => 'ahgSettings', 'action' => 'errorLog', 'page' => $page - 1, 'level' => $level, 'q' => $search]) }}">&laquo;</a></li>
        @endif
        @for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++)
          <li class="page-item {{ $p === $page ? 'active' : '' }}"><a class="page-link" href="{{ url_for(['module' => 'ahgSettings', 'action' => 'errorLog', 'page' => $p, 'level' => $level, 'q' => $search]) }}">{{ $p }}</a></li>
        @endfor
        @if ($page < $totalPages)
          <li class="page-item"><a class="page-link" href="{{ url_for(['module' => 'ahgSettings', 'action' => 'errorLog', 'page' => $page + 1, 'level' => $level, 'q' => $search]) }}">&raquo;</a></li>
        @endif
      </ul>
    </nav>
  </div>
  @endif
</div>

<div class="mt-3">
  <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'index']) }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Settings') }}
  </a>
</div>
@endsection
