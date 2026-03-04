@php
$policy = $policy ?? null;
$isNew = $isNew ?? true;
$repositories = $repositories ?? [];
@endphp

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-archive me-2"></i>{{ $isNew ? __('New Retention Policy') : __('Edit Retention Policy') }}</h1>
    <a href="{{ url_for(['module' => 'integrity', 'action' => 'policies']) }}" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i>{{ __('Back') }}
    </a>
  </div>

  <form method="post" action="{{ url_for(['module' => 'integrity', 'action' => 'policyEdit', 'id' => $policy->id ?? '']) }}">
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0">{{ __('Policy Details') }}</h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">{{ __('Name') }} *</label>
            <input type="text" name="name" class="form-control" value="{{ $policy->name ?? '' }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">{{ __('Retention Period (days)') }}</label>
            <input type="number" name="retention_period_days" class="form-control" value="{{ $policy->retention_period_days ?? 0 }}" min="0">
            <small class="text-muted">{{ __('0 = indefinite retention (never eligible for disposition)') }}</small>
          </div>
          <div class="col-12">
            <label class="form-label">{{ __('Description') }}</label>
            <textarea name="description" class="form-control" rows="2">{{ $policy->description ?? '' }}</textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ __('Trigger Type') }}</label>
            <select name="trigger_type" class="form-select">
              @foreach (['ingest_date', 'last_modified', 'closure_date', 'last_access'] as $tt)
                <option value="{{ $tt }}" {{ ($policy->trigger_type ?? 'ingest_date') === $tt ? 'selected' : '' }}>{{ $tt }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ __('Scope Type') }}</label>
            <select name="scope_type" class="form-select" onchange="toggleScope(this.value)">
              @foreach (['global', 'repository', 'hierarchy'] as $st)
                <option value="{{ $st }}" {{ ($policy->scope_type ?? 'global') === $st ? 'selected' : '' }}>{{ $st }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4" id="repoField" style="display:{{ ($policy->scope_type ?? 'global') === 'repository' ? 'block' : 'none' }}">
            <label class="form-label">{{ __('Repository') }}</label>
            <select name="repository_id" class="form-select">
              <option value="">{{ __('Select...') }}</option>
              @foreach ($repositories as $repo)
                <option value="{{ $repo->id }}" {{ ($policy->repository_id ?? '') == $repo->id ? 'selected' : '' }}>{{ $repo->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4" id="hierarchyField" style="display:{{ ($policy->scope_type ?? 'global') === 'hierarchy' ? 'block' : 'none' }}">
            <label class="form-label">{{ __('Information Object ID') }}</label>
            <input type="number" name="information_object_id" class="form-control" value="{{ $policy->information_object_id ?? '' }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ __('Object Format (MIME type)') }}</label>
            <input type="text" name="object_format" class="form-control" value="{{ $policy->object_format ?? '' }}" placeholder="e.g. image/tiff, application/pdf">
            <small class="text-muted">{{ __('Leave empty for all formats. Uses prefix matching.') }}</small>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input type="checkbox" name="is_enabled" class="form-check-input" value="1" id="isEnabled"
                {{ ($policy->is_enabled ?? 0) ? 'checked' : '' }}>
              <label class="form-check-label" for="isEnabled">{{ __('Enabled') }}</label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save me-1"></i>{{ $isNew ? __('Create Policy') : __('Save Changes') }}
    </button>
  </form>
</main>

<script @cspNonce>
function toggleScope(val) {
  document.getElementById('repoField').style.display = val === 'repository' ? 'block' : 'none';
  document.getElementById('hierarchyField').style.display = val === 'hierarchy' ? 'block' : 'none';
}
</script>
