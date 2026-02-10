@if ($rightsData['has_rights'])

<section id="extended-rights-area" class="card mb-3">
  <div class="card-header">
    <h4 class="mb-0">
      {{ __('Rights Information') }}
      @if ($sf_user->isAuthenticated())
        <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'edit', 'slug' => $resource->slug]) }}" class="btn btn-sm btn-outline-secondary float-end">
          <i class="fas fa-edit"></i> {{ __('Edit') }}
        </a>
      @endif
    </h4>
  </div>
  <div class="card-body">
    <!-- Rights Badges -->
    @if (!empty($rightsData['badges']))
      <div class="rights-badges mb-3">
        @foreach ($rightsData['badges'] as $badge)
          <a href="{{ $badge['uri'] }}" target="_blank" class="rights-badge me-2 mb-2 d-inline-block" title="{{ $badge['label'] }}">
            @if ($badge['type'] === 'creative_commons')
              {!! $badge['badge_html'] !!}
            @else
              <img src="{{ $badge['icon'] }}" alt="{{ $badge['label'] }}" class="rights-badge-icon">
            @endif
          </a>
        @endforeach
      </div>
    @endif

    @php $primary = $rightsData['primary']; @endphp
    @if ($primary)
      <!-- Rights Statement -->
      @if ($primary->rightsStatement)
        <div class="field mb-3">
          <h5>{{ __('Rights Statement') }}</h5>
          <div class="d-flex align-items-start">
            <img src="{{ $primary->rightsStatement->icon_url }}" alt="" class="me-3" style="width:88px;">
            <div>
              <strong>{{ $primary->rightsStatement->name }}</strong>
              <p class="text-muted mb-1">{{ $primary->rightsStatement->definition }}</p>
              <a href="{{ $primary->rightsStatement->uri }}" target="_blank" class="small">{{ __('Learn more') }} <i class="fas fa-external-link-alt"></i></a>
            </div>
          </div>
        </div>
      @endif

      <!-- Creative Commons -->
      @if ($primary->creativeCommonsLicense)
        <div class="field mb-3">
          <h5>{{ __('License') }}</h5>
          <div class="d-flex align-items-center">
            {!! $primary->creativeCommonsLicense->badge_html !!}
            <div class="ms-3">
              <strong>{{ $primary->creativeCommonsLicense->name }}</strong>
              <br>
              <a href="{{ $primary->creativeCommonsLicense->uri }}" target="_blank" class="small">{{ __('View license') }} <i class="fas fa-external-link-alt"></i></a>
            </div>
          </div>
        </div>
      @endif

      <!-- TK Labels -->
      @if ($primary->tkLabels && count($primary->tkLabels) > 0)
        <div class="field mb-3">
          <h5>{{ __('Traditional Knowledge Labels') }}</h5>
          <div class="tk-labels-grid">
            @foreach ($primary->tkLabels as $label)
              <div class="tk-label-item d-flex align-items-start mb-2">
                <img src="{{ $label->icon_url }}" alt="" class="me-2" style="width:48px;height:48px;">
                <div>
                  <strong>{{ $label->name }}</strong>
                  <p class="small text-muted mb-0">{{ $label->description }}</p>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endif

      <!-- Rights Holder -->
      @if ($primary->rights_holder)
        <div class="field mb-3">
          <h5>{{ __('Rights Holder') }}</h5>
          @if ($primary->rights_holder_uri)
            <a href="{{ $primary->rights_holder_uri }}" target="_blank">{{ $primary->rights_holder }}</a>
          @else
            {{ $primary->rights_holder }}
          @endif
        </div>
      @endif

      <!-- Copyright Notice -->
      @if ($primary->copyright_notice)
        <div class="field mb-3">
          <h5>{{ __('Copyright Notice') }}</h5>
          <p>{{ $primary->copyright_notice }}</p>
        </div>
      @endif

      <!-- Usage Conditions -->
      @if ($primary->rights_note)
        <div class="field mb-3">
          <h5>{{ __('Usage Notes') }}</h5>
          <p>{!! nl2br(e($primary->rights_note)) !!}</p>
        </div>
      @endif
    @endif
  </div>
</section>

@else

@if ($sf_user->isAuthenticated())
<section id="extended-rights-area" class="card mb-3">
  <div class="card-body text-center">
    <p class="text-muted mb-2">{{ __('No extended rights information has been added.') }}</p>
    <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'edit', 'slug' => $resource->slug]) }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> {{ __('Add Rights Information') }}
    </a>
  </div>
</section>
@endif

@endif
