@php
$entries = $entries ?? [];
$filterOutcome = $filterOutcome ?? null;
$filterRepositoryId = $filterRepositoryId ?? null;
$filterDateFrom = $filterDateFrom ?? null;
$filterDateTo = $filterDateTo ?? null;
@endphp

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-book me-2"></i>{{ __('Verification Ledger') }}</h1>
    <a href="{{ url_for(['module' => 'integrity', 'action' => 'index']) }}" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i>{{ __('Dashboard') }}
    </a>
  </div>

  <div class="alert alert-info py-2">
    <i class="fas fa-info-circle me-1"></i>
    {{ __('The integrity ledger is append-only. Entries are never updated or deleted, providing a complete audit trail of all verification attempts.') }}
  </div>

  {{-- Filters --}}
  <div class="card mb-3">
    <div class="card-body py-2">
      <form method="get" action="{{ url_for(['module' => 'integrity', 'action' => 'ledger']) }}" class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label small mb-0">{{ __('Outcome') }}</label>
          <select name="outcome" class="form-select form-select-sm">
            <option value="">{{ __('All') }}</option>
            @foreach (['pass','mismatch','missing','unreadable','permission_error','path_drift','no_baseline','error'] as $o)
              <option value="{{ $o }}" {{ $filterOutcome === $o ? 'selected' : '' }}>{{ $o }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-auto">
          <label class="form-label small mb-0">{{ __('From') }}</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filterDateFrom ?? '' }}">
        </div>
        <div class="col-auto">
          <label class="form-label small mb-0">{{ __('To') }}</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filterDateTo ?? '' }}">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary btn-sm">{{ __('Filter') }}</button>
          <a href="{{ url_for(['module' => 'integrity', 'action' => 'ledger']) }}" class="btn btn-outline-secondary btn-sm">{{ __('Reset') }}</a>
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
              <th>{{ __('Run') }}</th>
              <th>{{ __('Object') }}</th>
              <th>{{ __('Outcome') }}</th>
              <th>{{ __('File') }}</th>
              <th>{{ __('Size') }}</th>
              <th>{{ __('Exists') }}</th>
              <th>{{ __('Hash Match') }}</th>
              <th>{{ __('Algorithm') }}</th>
              <th>{{ __('Duration') }}</th>
              <th>{{ __('Verified') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($entries as $e)
              <tr class="{{ $e->outcome !== 'pass' ? 'table-danger' : '' }}">
                <td>{{ $e->id }}</td>
                <td>
                  @if ($e->run_id)
                    <a href="{{ url_for(['module' => 'integrity', 'action' => 'runDetail', 'id' => $e->run_id]) }}">{{ $e->run_id }}</a>
                  @else —
                  @endif
                </td>
                <td>{{ $e->digital_object_id }}</td>
                <td><span class="badge {{ $e->outcome === 'pass' ? 'bg-success' : 'bg-danger' }}">{{ $e->outcome }}</span></td>
                <td class="text-truncate" style="max-width:250px" title="{{ $e->file_path ?? '' }}">{{ basename($e->file_path ?? '—') }}</td>
                <td>{{ $e->file_size ? number_format($e->file_size) : '—' }}</td>
                <td>{!! $e->file_exists ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' !!}</td>
                <td>
                  @if ($e->hash_match === null) —
                  @elseif ($e->hash_match) <i class="fas fa-check text-success"></i>
                  @else <i class="fas fa-times text-danger"></i>
                  @endif
                </td>
                <td><code>{{ $e->algorithm }}</code></td>
                <td>{{ $e->duration_ms ? $e->duration_ms . 'ms' : '—' }}</td>
                <td>{{ $e->verified_at }}</td>
              </tr>
            @empty
              <tr><td colspan="11" class="text-muted text-center py-3">{{ __('No ledger entries found.') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @if (count($entries) >= 200)
    <p class="text-muted small mt-2">{{ __('Showing latest 200 entries. Use filters to narrow results.') }}</p>
  @endif
</main>
