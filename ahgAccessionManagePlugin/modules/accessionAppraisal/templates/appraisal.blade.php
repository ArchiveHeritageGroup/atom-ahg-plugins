@php decorate_with('layout_1col') @endphp

@slot('title')
  <h1>
    <?php echo __('Appraisal'); ?>
    <small class="text-muted">&mdash; {{ $accession['identifier'] ?? '' }}</small>
  </h1>
@endslot

@slot('content')
@php
  $flash = $sf_user->getFlash('notice', '');
  $accId = $accession['id'] ?? 0;
  $accTitle = $accession['title'] ?? '';

  $typeLabels = [
      'archival' => __('Archival'),
      'monetary' => __('Monetary'),
      'insurance' => __('Insurance'),
      'historical' => __('Historical'),
      'research' => __('Research'),
  ];
  $significanceLabels = [
      'low' => __('Low'),
      'medium' => __('Medium'),
      'high' => __('High'),
      'exceptional' => __('Exceptional'),
      'national_significance' => __('National Significance'),
  ];
  $recommendationLabels = [
      'pending' => __('Pending'),
      'accept' => __('Accept'),
      'reject' => __('Reject'),
      'partial' => __('Partial'),
      'defer' => __('Defer'),
  ];
  $recommendationColors = [
      'pending' => 'warning',
      'accept' => 'success',
      'reject' => 'danger',
      'partial' => 'info',
      'defer' => 'secondary',
  ];
@endphp

@if ($flash)
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
  {{ $flash }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url_for(['module' => 'accessionManage', 'action' => 'dashboard']) }}">{{ __('Accessions') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ url_for('@accession_view_override?slug=' . ($accession['slug'] ?? '')) }}">{{ $accession['identifier'] ?? '' }}</a></li>
    <li class="breadcrumb-item active">{{ __('Appraisal') }}</li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h4 mb-0">{{ $accession['identifier'] ?? '' }}</h2>
    @if (!empty($accTitle))
    <p class="text-muted mb-0">{{ $accTitle }}</p>
    @endif
  </div>
  <div class="btn-group">
    <a href="{{ url_for('@accession_valuation_view?id=' . $accId) }}" class="btn btn-outline-primary btn-sm">
      <i class="fas fa-coins me-1"></i>{{ __('Valuation History') }}
    </a>
    <a href="{{ url_for('@accession_appraisal_templates') }}" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-file-alt me-1"></i>{{ __('Templates') }}
    </a>
  </div>
</div>

@if ($currentAppraisal)
{{-- ================================================================== --}}
{{-- APPRAISAL DETAIL VIEW (editing existing appraisal) --}}
{{-- ================================================================== --}}
@php
  $ap = $currentAppraisal['appraisal'];
  $criteria = $currentAppraisal['criteria'] ?? [];
  $appraiserName = $currentAppraisal['appraiser_name'] ?? __('Unknown');
  $weightedScore = $currentAppraisal['weighted_score'];
@endphp

<form method="post" action="{{ url_for('@accession_appraisal_save?id=' . $accId) }}">
  <input type="hidden" name="appraisal_id" value="{{ $ap->id }}">

  <div class="row">
    <div class="col-lg-8">
      {{-- Appraisal Info Card --}}
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="fas fa-clipboard-check me-2"></i>{{ __('Appraisal Detail') }}</span>
          <span class="badge bg-{{ $recommendationColors[$ap->recommendation] ?? 'secondary' }}">
            {{ $recommendationLabels[$ap->recommendation] ?? ucfirst($ap->recommendation) }}
          </span>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label fw-bold">{{ __('Type') }}</label>
              <select name="appraisal_type" class="form-select">
                @foreach ($typeLabels as $val => $label)
                <option value="{{ $val }}" {{ $ap->appraisal_type === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">{{ __('Significance') }}</label>
              <select name="significance" class="form-select">
                <option value="">{{ __('-- Select --') }}</option>
                @foreach ($significanceLabels as $val => $label)
                <option value="{{ $val }}" {{ ($ap->significance ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">{{ __('Recommendation') }}</label>
              <select name="recommendation" class="form-select">
                @foreach ($recommendationLabels as $val => $label)
                <option value="{{ $val }}" {{ $ap->recommendation === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label fw-bold">{{ __('Monetary Value') }}</label>
              <div class="input-group">
                <select name="currency" class="form-select" style="max-width: 80px;">
                  @foreach (['ZAR', 'USD', 'EUR', 'GBP'] as $cur)
                  <option value="{{ $cur }}" {{ ($ap->currency ?? 'ZAR') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                  @endforeach
                </select>
                <input type="number" name="monetary_value" class="form-control" step="0.01" value="{{ $ap->monetary_value ?? '' }}" placeholder="0.00">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">{{ __('Appraiser') }}</label>
              <input type="text" class="form-control" value="{{ $appraiserName }}" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">{{ __('Appraised Date') }}</label>
              <input type="datetime-local" name="appraised_at" class="form-control" value="{{ $ap->appraised_at ? date('Y-m-d\TH:i', strtotime($ap->appraised_at)) : '' }}">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">{{ __('Summary') }}</label>
            <textarea name="summary" class="form-control" rows="3">{{ $ap->summary ?? '' }}</textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">{{ __('Detailed Notes') }}</label>
            <textarea name="detailed_notes" class="form-control" rows="4">{{ $ap->detailed_notes ?? '' }}</textarea>
          </div>
        </div>
      </div>

      {{-- Criteria Scoring Grid --}}
      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-star-half-alt me-2"></i>{{ __('Criteria Scoring') }}
        </div>
        <div class="card-body p-0">
          @if (count($criteria) > 0)
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>{{ __('Criterion') }}</th>
                  <th class="text-center" style="width:60px;">{{ __('Weight') }}</th>
                  <th class="text-center" style="width:280px;">{{ __('Score (1-5)') }}</th>
                  <th>{{ __('Notes') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($criteria as $c)
                <tr data-criterion-id="{{ $c->id }}">
                  <td>
                    <strong>{{ $c->criterion_name }}</strong>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-secondary">{{ number_format($c->weight, 2) }}</span>
                  </td>
                  <td class="text-center">
                    <div class="btn-group" role="group">
                      @for ($s = 1; $s <= 5; $s++)
                      <input type="radio" class="btn-check score-radio" name="scores[{{ $c->id }}]" id="score_{{ $c->id }}_{{ $s }}" value="{{ $s }}" autocomplete="off" {{ ((int)($c->score ?? 0)) === $s ? 'checked' : '' }} data-criterion-id="{{ $c->id }}">
                      <label class="btn btn-outline-primary btn-sm" for="score_{{ $c->id }}_{{ $s }}">{{ $s }}</label>
                      @endfor
                    </div>
                  </td>
                  <td>
                    <span class="text-muted small">{{ $c->notes ?? '' }}</span>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @else
          <div class="text-center py-4 text-muted">
            <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
            {{ __('No criteria defined. Apply a template when creating the appraisal to populate criteria.') }}
          </div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      {{-- Weighted Score Card --}}
      <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white">
          <i class="fas fa-calculator me-2"></i>{{ __('Weighted Score') }}
        </div>
        <div class="card-body text-center">
          <div class="display-4 fw-bold" id="weightedScoreDisplay">
            @if ($weightedScore !== null)
            {{ number_format($weightedScore, 2) }}
            @else
            <span class="text-muted">&mdash;</span>
            @endif
          </div>
          <p class="text-muted mb-0">{{ __('out of 5.00') }}</p>
          @if ($weightedScore !== null)
          <div class="progress mt-3" style="height: 8px;">
            <div class="progress-bar {{ $weightedScore >= 4 ? 'bg-success' : ($weightedScore >= 3 ? 'bg-info' : ($weightedScore >= 2 ? 'bg-warning' : 'bg-danger')) }}" style="width: {{ ($weightedScore / 5) * 100 }}%"></div>
          </div>
          @endif
        </div>
      </div>

      {{-- Actions --}}
      <div class="card mb-4">
        <div class="card-body">
          <button type="submit" class="btn btn-primary w-100 mb-2">
            <i class="fas fa-save me-1"></i>{{ __('Save Appraisal') }}
          </button>
          <a href="{{ url_for('@accession_appraisal_form?id=' . $accId) }}" class="btn btn-outline-secondary w-100">
            <i class="fas fa-arrow-left me-1"></i>{{ __('Back to List') }}
          </a>
        </div>
      </div>

      {{-- Record Info --}}
      <div class="card">
        <div class="card-header">
          <i class="fas fa-info-circle me-2"></i>{{ __('Record Info') }}
        </div>
        <div class="card-body">
          <small class="text-muted">
            {{ __('Created') }}: {{ date('d M Y H:i', strtotime($ap->created_at)) }}<br>
            {{ __('Updated') }}: {{ date('d M Y H:i', strtotime($ap->updated_at)) }}
          </small>
        </div>
      </div>
    </div>
  </div>
</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var radios = document.querySelectorAll('.score-radio');
  radios.forEach(function(radio) {
    radio.addEventListener('change', function() {
      var criterionId = this.getAttribute('data-criterion-id');
      var score = this.value;

      fetch('<?php echo url_for("@accession_api_appraisal_score?id=0"); ?>'.replace('/0/', '/' + criterionId + '/'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'id=' + criterionId + '&score=' + score
      })
      .then(function(resp) { return resp.json(); })
      .then(function(data) {
        if (data.weighted_score !== null && data.weighted_score !== undefined) {
          var display = document.getElementById('weightedScoreDisplay');
          display.textContent = parseFloat(data.weighted_score).toFixed(2);
        }
      })
      .catch(function(err) {
        console.error('Score update failed:', err);
      });
    });
  });
});
</script>

@else
{{-- ================================================================== --}}
{{-- APPRAISAL LIST + NEW APPRAISAL FORM --}}
{{-- ================================================================== --}}

<div class="row">
  <div class="col-lg-8">
    {{-- Existing Appraisals --}}
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-list me-2"></i>{{ __('Appraisals') }}
      </div>
      <div class="card-body p-0">
        @if (is_array($appraisals) && count($appraisals) > 0)
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Significance') }}</th>
                <th>{{ __('Recommendation') }}</th>
                <th>{{ __('Value') }}</th>
                <th>{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($appraisals as $a)
              <tr>
                <td>{{ $a->appraised_at ? date('d M Y', strtotime($a->appraised_at)) : date('d M Y', strtotime($a->created_at)) }}</td>
                <td>
                  <span class="badge bg-info">{{ $typeLabels[$a->appraisal_type] ?? ucfirst($a->appraisal_type) }}</span>
                </td>
                <td>{{ $significanceLabels[$a->significance ?? ''] ?? ($a->significance ? ucfirst(str_replace('_', ' ', $a->significance)) : '&mdash;') }}</td>
                <td>
                  <span class="badge bg-{{ $recommendationColors[$a->recommendation] ?? 'secondary' }}">
                    {{ $recommendationLabels[$a->recommendation] ?? ucfirst($a->recommendation) }}
                  </span>
                </td>
                <td>
                  @if ($a->monetary_value)
                  {{ $a->currency ?? 'ZAR' }} {{ number_format($a->monetary_value, 2) }}
                  @else
                  <span class="text-muted">&mdash;</span>
                  @endif
                </td>
                <td>
                  <a href="{{ url_for('@accession_appraisal_form?id=' . $accId . '&appraisal_id=' . $a->id) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View / Edit') }}">
                    <i class="fas fa-pen"></i>
                  </a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
        <div class="text-center py-4 text-muted">
          <i class="fas fa-clipboard fa-2x mb-2 d-block"></i>
          {{ __('No appraisals recorded yet. Create one below.') }}
        </div>
        @endif
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    {{-- New Appraisal Form --}}
    <div class="card mb-4 border-success">
      <div class="card-header bg-success text-white">
        <i class="fas fa-plus-circle me-2"></i>{{ __('New Appraisal') }}
      </div>
      <div class="card-body">
        <form method="post" action="{{ url_for('@accession_appraisal_save?id=' . $accId) }}">
          <div class="mb-3">
            <label class="form-label">{{ __('Template') }}</label>
            <select name="template_id" class="form-select">
              <option value="">{{ __('-- No Template --') }}</option>
              @if (is_array($templates))
              @foreach ($templates as $t)
              <option value="{{ $t->id }}">{{ $t->name }}{{ $t->sector ? ' (' . ucfirst($t->sector) . ')' : '' }}{{ $t->is_default ? ' *' : '' }}</option>
              @endforeach
              @endif
            </select>
            <div class="form-text">{{ __('Selecting a template populates the criteria scoring grid.') }}</div>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Type') }}</label>
            <select name="appraisal_type" class="form-select">
              @foreach ($typeLabels as $val => $label)
              <option value="{{ $val }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Significance') }}</label>
            <select name="significance" class="form-select">
              <option value="">{{ __('-- Select --') }}</option>
              @foreach ($significanceLabels as $val => $label)
              <option value="{{ $val }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Recommendation') }}</label>
            <select name="recommendation" class="form-select">
              @foreach ($recommendationLabels as $val => $label)
              <option value="{{ $val }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Monetary Value') }}</label>
            <div class="input-group">
              <select name="currency" class="form-select" style="max-width: 80px;">
                @foreach (['ZAR', 'USD', 'EUR', 'GBP'] as $cur)
                <option value="{{ $cur }}">{{ $cur }}</option>
                @endforeach
              </select>
              <input type="number" name="monetary_value" class="form-control" step="0.01" placeholder="0.00">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Summary') }}</label>
            <textarea name="summary" class="form-control" rows="3"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Detailed Notes') }}</label>
            <textarea name="detailed_notes" class="form-control" rows="3"></textarea>
          </div>

          <button type="submit" class="btn btn-success w-100">
            <i class="fas fa-plus me-1"></i>{{ __('Create Appraisal') }}
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

@endif
@endslot
