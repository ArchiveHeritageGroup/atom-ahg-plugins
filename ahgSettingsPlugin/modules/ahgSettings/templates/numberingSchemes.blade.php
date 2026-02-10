@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1><i class="fas fa-hashtag me-2"></i>{{ __('Numbering Schemes') }}</h1>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <form method="get" class="d-inline-flex gap-2">
          <select name="sector" class="form-select form-select-sm" style="width: auto;">
            <option value="">{{ __('All Sectors') }}</option>
            @foreach ($sectors as $code => $label)
              <option value="{{ $code }}" {{ $sectorFilter === $code ? 'selected' : '' }}>
                {{ __($label) }}
              </option>
            @endforeach
          </select>
          <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Filter') }}</button>
        </form>
      </div>
      <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'numberingSchemeEdit']) }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>{{ __('Add Scheme') }}
      </a>
    </div>

    @if (empty($schemes))
      <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        {{ __('No numbering schemes configured. Click "Add Scheme" to create one.') }}
      </div>
    @else

    <div class="table-responsive">
      <table class="table table-hover">
        <thead class="table-dark">
          <tr>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Sector') }}</th>
            <th>{{ __('Pattern') }}</th>
            <th>{{ __('Preview') }}</th>
            <th>{{ __('Counter') }}</th>
            <th>{{ __('Reset') }}</th>
            <th class="text-center">{{ __('Default') }}</th>
            <th class="text-center">{{ __('Active') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @php
          $service = \AtomExtensions\Services\NumberingService::getInstance();
          @endphp
          @foreach ($schemes as $scheme)
            @php
              $previews = $service->previewMultiple($scheme->sector, 1);
              $preview = $previews[0] ?? '-';
            @endphp
          <tr>
            <td>
              <strong>{{ $scheme->name }}</strong>
              @if ($scheme->description)
                <br><small class="text-muted">{{ $scheme->description }}</small>
              @endif
            </td>
            <td>
              <span class="badge bg-{{ match ($scheme->sector) {
                  'archive' => 'primary',
                  'library' => 'success',
                  'museum' => 'info',
                  'gallery' => 'warning',
                  'dam' => 'secondary',
                  default => 'dark'
              } }}">
                {{ ucfirst($scheme->sector) }}
              </span>
            </td>
            <td><code>{{ $scheme->pattern }}</code></td>
            <td><code class="text-success">{{ $preview }}</code></td>
            <td>{{ number_format($scheme->current_sequence) }}</td>
            <td>
              {{ match ($scheme->sequence_reset) {
                  'yearly' => __('Yearly'),
                  'monthly' => __('Monthly'),
                  default => __('Never')
              } }}
            </td>
            <td class="text-center">
              @if ($scheme->is_default)
                <i class="fas fa-star text-warning" title="{{ __('Default') }}"></i>
              @else
                <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'numberingSchemes', 'do' => 'setDefault', 'id' => $scheme->id]) }}"
                   class="text-muted" title="{{ __('Set as default') }}">
                  <i class="far fa-star"></i>
                </a>
              @endif
            </td>
            <td class="text-center">
              @if ($scheme->is_active)
                <i class="fas fa-check-circle text-success"></i>
              @else
                <i class="fas fa-times-circle text-danger"></i>
              @endif
            </td>
            <td>
              <div class="btn-group btn-group-sm">
                <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'numberingSchemeEdit', 'id' => $scheme->id]) }}"
                   class="btn btn-outline-primary" title="{{ __('Edit') }}">
                  <i class="fas fa-edit"></i>
                </a>
                <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'numberingSchemes', 'do' => 'resetSequence', 'id' => $scheme->id]) }}"
                   class="btn btn-outline-warning" title="{{ __('Reset sequence') }}"
                   onclick="return confirm('{{ __('Reset sequence to 0?') }}');">
                  <i class="fas fa-redo"></i>
                </a>
                <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'numberingSchemes', 'do' => 'delete', 'id' => $scheme->id]) }}"
                   class="btn btn-outline-danger" title="{{ __('Delete') }}"
                   onclick="return confirm('{{ __('Delete this scheme?') }}');">
                  <i class="fas fa-trash"></i>
                </a>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @endif

    <!-- Token Reference -->
    <div class="card mt-4">
      <div class="card-header bg-secondary text-white">
        <i class="fas fa-code me-2"></i>{{ __('Available Tokens') }}
      </div>
      <div class="card-body">
        <div class="row">
          @foreach ($tokens as $token => $description)
          <div class="col-md-4 mb-2">
            <code>{{ $token }}</code>
            <small class="text-muted d-block">{{ $description }}</small>
          </div>
          @endforeach
        </div>
      </div>
    </div>

  </div>
</div>
@endsection
