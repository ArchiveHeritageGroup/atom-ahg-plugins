@php
$s = $schedule ?? null;
$isNew = $isNew ?? true;
$repositories = $repositories ?? [];
@endphp

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-edit me-2"></i>{{ $isNew ? __('New Schedule') : __('Edit Schedule') }}</h1>
    <a href="{{ url_for(['module' => 'integrity', 'action' => 'schedules']) }}" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Schedules') }}
    </a>
  </div>

  <form method="post" action="{{ url_for(['module' => 'integrity', 'action' => 'scheduleEdit', 'id' => $s->id ?? '']) }}">
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0">{{ __('General') }}</h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="name">{{ __('Schedule Name') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $s->name ?? '' }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="description">{{ __('Description') }}</label>
            <input type="text" class="form-control" id="description" name="description" value="{{ $s->description ?? '' }}">
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0">{{ __('Scope & Algorithm') }}</h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label" for="scope_type">{{ __('Scope') }}</label>
            <select class="form-select" id="scope_type" name="scope_type">
              <option value="global" {{ ($s->scope_type ?? 'global') === 'global' ? 'selected' : '' }}>{{ __('Global (all objects)') }}</option>
              <option value="repository" {{ ($s->scope_type ?? '') === 'repository' ? 'selected' : '' }}>{{ __('Repository') }}</option>
              <option value="hierarchy" {{ ($s->scope_type ?? '') === 'hierarchy' ? 'selected' : '' }}>{{ __('Hierarchy node') }}</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="repository_id">{{ __('Repository') }}</label>
            <select class="form-select" id="repository_id" name="repository_id">
              <option value="">{{ __('— All —') }}</option>
              @foreach ($repositories as $repo)
                <option value="{{ $repo->id }}" {{ ($s->repository_id ?? '') == $repo->id ? 'selected' : '' }}>{{ $repo->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="information_object_id">{{ __('Information Object ID') }}</label>
            <input type="number" class="form-control" id="information_object_id" name="information_object_id" value="{{ $s->information_object_id ?? '' }}" placeholder="{{ __('For hierarchy scope') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="algorithm">{{ __('Hash Algorithm') }}</label>
            <select class="form-select" id="algorithm" name="algorithm">
              <option value="sha256" {{ ($s->algorithm ?? 'sha256') === 'sha256' ? 'selected' : '' }}>SHA-256</option>
              <option value="sha512" {{ ($s->algorithm ?? '') === 'sha512' ? 'selected' : '' }}>SHA-512</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="frequency">{{ __('Frequency') }}</label>
            <select class="form-select" id="frequency" name="frequency">
              <option value="daily" {{ ($s->frequency ?? '') === 'daily' ? 'selected' : '' }}>{{ __('Daily') }}</option>
              <option value="weekly" {{ ($s->frequency ?? 'weekly') === 'weekly' ? 'selected' : '' }}>{{ __('Weekly') }}</option>
              <option value="monthly" {{ ($s->frequency ?? '') === 'monthly' ? 'selected' : '' }}>{{ __('Monthly') }}</option>
              <option value="ad_hoc" {{ ($s->frequency ?? '') === 'ad_hoc' ? 'selected' : '' }}>{{ __('Ad hoc') }}</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="cron_expression">{{ __('Cron Expression') }} <small class="text-muted">({{ __('optional override') }})</small></label>
            <input type="text" class="form-control" id="cron_expression" name="cron_expression" value="{{ $s->cron_expression ?? '' }}" placeholder="0 2 * * *">
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0">{{ __('Concurrency Controls') }}</h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label" for="batch_size">{{ __('Batch Size') }} <small class="text-muted">(0 = all)</small></label>
            <input type="number" class="form-control" id="batch_size" name="batch_size" value="{{ $s->batch_size ?? 200 }}" min="0">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="io_throttle_ms">{{ __('IO Throttle (ms)') }}</label>
            <input type="number" class="form-control" id="io_throttle_ms" name="io_throttle_ms" value="{{ $s->io_throttle_ms ?? 0 }}" min="0">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="max_memory_mb">{{ __('Max Memory (MB)') }}</label>
            <input type="number" class="form-control" id="max_memory_mb" name="max_memory_mb" value="{{ $s->max_memory_mb ?? 512 }}" min="64">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="max_runtime_minutes">{{ __('Max Runtime (min)') }}</label>
            <input type="number" class="form-control" id="max_runtime_minutes" name="max_runtime_minutes" value="{{ $s->max_runtime_minutes ?? 120 }}" min="1">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="max_concurrent_runs">{{ __('Max Concurrent Runs') }}</label>
            <input type="number" class="form-control" id="max_concurrent_runs" name="max_concurrent_runs" value="{{ $s->max_concurrent_runs ?? 1 }}" min="1" max="5">
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0">{{ __('Notifications & Status') }}</h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" {{ ($s->is_enabled ?? 0) ? 'checked' : '' }}>
              <label class="form-check-label" for="is_enabled">{{ __('Enabled') }}</label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="notify_on_failure" name="notify_on_failure" value="1" {{ ($s->notify_on_failure ?? 1) ? 'checked' : '' }}>
              <label class="form-check-label" for="notify_on_failure">{{ __('Notify on failure') }}</label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="notify_on_mismatch" name="notify_on_mismatch" value="1" {{ ($s->notify_on_mismatch ?? 1) ? 'checked' : '' }}>
              <label class="form-check-label" for="notify_on_mismatch">{{ __('Notify on mismatch') }}</label>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="notify_email">{{ __('Notification Email') }}</label>
            <input type="email" class="form-control" id="notify_email" name="notify_email" value="{{ $s->notify_email ?? '' }}">
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
      <a href="{{ url_for(['module' => 'integrity', 'action' => 'schedules']) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>{{ __('Save Schedule') }}</button>
    </div>
  </form>
</main>
