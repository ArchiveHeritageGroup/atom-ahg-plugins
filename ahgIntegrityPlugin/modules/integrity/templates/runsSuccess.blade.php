@php
$runs = $runs ?? [];
$schedules = $schedules ?? [];
$filterScheduleId = $filterScheduleId ?? null;
$filterStatus = $filterStatus ?? null;
@endphp

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-history me-2"></i>{{ __('Run History') }}</h1>
    <a href="{{ url_for(['module' => 'integrity', 'action' => 'index']) }}" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i>{{ __('Dashboard') }}
    </a>
  </div>

  {{-- Filters --}}
  <div class="card mb-3">
    <div class="card-body py-2">
      <form method="get" action="{{ url_for(['module' => 'integrity', 'action' => 'runs']) }}" class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label small mb-0">{{ __('Schedule') }}</label>
          <select name="schedule_id" class="form-select form-select-sm">
            <option value="">{{ __('All') }}</option>
            @foreach ($schedules as $sch)
              <option value="{{ $sch->id }}" {{ $filterScheduleId == $sch->id ? 'selected' : '' }}>{{ $sch->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-auto">
          <label class="form-label small mb-0">{{ __('Status') }}</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">{{ __('All') }}</option>
            @foreach (['running','completed','partial','failed','timeout','cancelled'] as $st)
              <option value="{{ $st }}" {{ $filterStatus === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary btn-sm">{{ __('Filter') }}</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>{{ __('Schedule') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Scanned') }}</th>
              <th>{{ __('Passed') }}</th>
              <th>{{ __('Failed') }}</th>
              <th>{{ __('Missing') }}</th>
              <th>{{ __('Errors') }}</th>
              <th>{{ __('Triggered') }}</th>
              <th>{{ __('Started') }}</th>
              <th>{{ __('Completed') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($runs as $run)
              @php
                $statusClass = match($run->status) {
                    'completed' => 'bg-success',
                    'running' => 'bg-info',
                    'partial' => 'bg-warning',
                    'failed' => 'bg-danger',
                    'timeout' => 'bg-warning',
                    default => 'bg-secondary',
                };
              @endphp
              <tr>
                <td><a href="{{ url_for(['module' => 'integrity', 'action' => 'runDetail', 'id' => $run->id]) }}">{{ $run->id }}</a></td>
                <td>{{ $run->schedule_name ?? '—' }}</td>
                <td><span class="badge {{ $statusClass }}">{{ $run->status }}</span></td>
                <td>{{ number_format($run->objects_scanned) }}</td>
                <td>{{ number_format($run->objects_passed) }}</td>
                <td class="{{ $run->objects_failed > 0 ? 'text-danger fw-bold' : '' }}">{{ number_format($run->objects_failed) }}</td>
                <td>{{ number_format($run->objects_missing) }}</td>
                <td>{{ number_format($run->objects_error) }}</td>
                <td>{{ $run->triggered_by }}</td>
                <td>{{ $run->started_at }}</td>
                <td>{{ $run->completed_at ?? '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="11" class="text-muted text-center py-3">{{ __('No runs found.') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
