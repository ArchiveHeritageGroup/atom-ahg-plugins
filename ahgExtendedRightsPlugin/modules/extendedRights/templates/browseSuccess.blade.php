@php
$rsData = $rightsStatements ?? [];
$ccData = $ccLicenses ?? [];
$tkData = $tkLabels ?? [];
$statsData = $stats ?? [];
@endphp
<main id="content" class="container-xxl py-4">
  <h1 class="mb-4"><i class="fas fa-copyright me-2"></i>{{ __('Browse Rights') }}</h1>

  <div class="row">
    <!-- Rights Statements -->
    <div class="col-md-4 mb-4">
      <div class="card h-100">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">{{ __('RightsStatements.org') }}</h5>
        </div>
        <div class="card-body">
          <p class="text-muted small">{{ __('Standardized rights statements for cultural heritage.') }}</p>
          @if (!empty($rsData))
            <ul class="list-unstyled">
              @foreach ($rsData as $categoryOrItem)
                @php
                $items = isset($categoryOrItem['labels']) ? $categoryOrItem['labels'] : (is_array($categoryOrItem) ? $categoryOrItem : [$categoryOrItem]);
                @endphp
                @foreach ($items as $rs)
                  <li class="mb-2">
                    <a href="{{ $rs->uri ?? '' }}" target="_blank">
                      {{ $rs->name ?? $rs->code ?? 'Unknown' }}
                    </a>
                  </li>
                @endforeach
              @endforeach
            </ul>
          @else
            <p class="text-muted">{{ __('No rights statements configured.') }}</p>
          @endif
        </div>
      </div>
    </div>

    <!-- Creative Commons -->
    <div class="col-md-4 mb-4">
      <div class="card h-100">
        <div class="card-header bg-success text-white">
          <h5 class="mb-0">{{ __('Creative Commons') }}</h5>
        </div>
        <div class="card-body">
          <p class="text-muted small">{{ __('Open licensing for sharing and reuse.') }}</p>
          @if (!empty($ccData))
            <ul class="list-unstyled">
              @foreach ($ccData as $cc)
                <li class="mb-2">
                  <a href="{{ $cc->uri ?? '' }}" target="_blank">
                    {{ $cc->name ?? ('CC ' . ($cc->code ?? '')) }}
                  </a>
                </li>
              @endforeach
            </ul>
          @else
            <p class="text-muted">{{ __('No Creative Commons licenses configured.') }}</p>
          @endif
        </div>
      </div>
    </div>

    <!-- TK Labels -->
    <div class="col-md-4 mb-4">
      <div class="card h-100">
        <div class="card-header" style="background-color: #1a4d2e; color: white;">
          <h5 class="mb-0">{{ __('Traditional Knowledge Labels') }}</h5>
        </div>
        <div class="card-body">
          <p class="text-muted small">{{ __('Labels for Indigenous cultural heritage.') }}</p>
          @if (!empty($tkData))
            <ul class="list-unstyled">
              @foreach ($tkData as $categoryOrItem)
                @php
                $items = isset($categoryOrItem['labels']) ? $categoryOrItem['labels'] : (is_array($categoryOrItem) ? $categoryOrItem : [$categoryOrItem]);
                @endphp
                @foreach ($items as $tk)
                  <li class="mb-2">
                    @if (!empty($tk->color))
                      <span style="display:inline-block;width:12px;height:12px;background:{{ $tk->color }};border-radius:2px;margin-right:5px;"></span>
                    @endif
                    <a href="{{ $tk->uri ?? '' }}" target="_blank">
                      {{ $tk->name ?? $tk->code ?? 'Unknown' }}
                    </a>
                  </li>
                @endforeach
              @endforeach
            </ul>
          @else
            <p class="text-muted">{{ __('No TK Labels configured.') }}</p>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics -->
  @if ($statsData)
  <div class="card mt-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">{{ __('Rights Coverage Statistics') }}</h5>
    </div>
    <div class="card-body">
      <div class="row text-center">
        <div class="col"><h3 class="text-primary">{{ number_format($statsData["total_objects"] ?? $statsData["objectsWithRights"] ?? 0) }}</h3><small class="text-muted">{{ __('Total Objects') }}</small></div>
        <div class="col"><h3 class="text-primary">{{ number_format($statsData["with_rights_statement"] ?? 0) }}</h3><small class="text-muted">{{ __('With Rights Statement') }}</small></div>
        <div class="col"><h3 class="text-success">{{ number_format($statsData["with_cc_license"] ?? 0) }}</h3><small class="text-muted">{{ __('With CC License') }}</small></div>
        <div class="col"><h3 class="text-info">{{ number_format($statsData["with_tk_labels"] ?? 0) }}</h3><small class="text-muted">{{ __('With TK Labels') }}</small></div>
        <div class="col"><h3 class="text-warning">{{ number_format($statsData["active_embargoes"] ?? $statsData["activeEmbargoes"] ?? 0) }}</h3><small class="text-muted">{{ __('Active Embargoes') }}</small></div>
      </div>
    </div>
  </div>
  @endif
</main>
