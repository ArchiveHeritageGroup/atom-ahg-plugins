@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1><i class="fas fa-hashtag me-2"></i>{{ __('Sector Numbering Schemes') }}</h1>

    <div class="alert alert-info" role="alert">
      <i class="fas fa-info-circle me-2"></i>
      {{ __('Configure unique identifier numbering schemes per GLAM/DAM sector. Leave fields blank to inherit the global settings.') }}
      <br><small class="text-muted">{{ __('Note: Accession numbering uses a single global counter across all sectors.') }}</small>
    </div>

    <!-- Global Reference Card -->
    <div class="card mb-4">
      <div class="card-header bg-secondary text-white">
        <i class="fas fa-globe me-2"></i>{{ __('Current Global Identifier Settings (Reference)') }}
      </div>
      <div class="card-body">
        <dl class="row small mb-0">
          <dt class="col-sm-3">{{ __('Mask Enabled') }}</dt>
          <dd class="col-sm-3"><code>{{ ($globalValues['identifier_mask_enabled'] ?? '0') ? __('Yes') : __('No') }}</code></dd>
          <dt class="col-sm-3">{{ __('Mask') }}</dt>
          <dd class="col-sm-3"><code>{{ $globalValues['identifier_mask'] ?? '-' }}</code></dd>
          <dt class="col-sm-3">{{ __('Counter') }}</dt>
          <dd class="col-sm-3"><code>{{ $globalValues['identifier_counter'] ?? '-' }}</code></dd>
        </dl>
        <div class="text-end mt-2">
          <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'identifier']) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-cog me-1"></i>{{ __('Edit Global Settings') }}
          </a>
        </div>
      </div>
    </div>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'sectorNumbering'])) !!}
      {!! $form->renderHiddenFields() !!}

      @php
        // Ensure $sectors is a plain array (Blade variables are already raw)
        if (isset($sectors) && $sectors instanceof sfOutputEscaperArrayDecorator) {
            $sectors = iterator_to_array($sectors);
        }
        $sectors = isset($sectors) && is_array($sectors) ? $sectors : [];

        // Default identifier mask patterns per sector
        $sectorDefaults = [
            'archive' => ['identifier_mask' => 'ARCH/%Y%/%04i%'],
            'museum'  => ['identifier_mask' => 'MUS.%Y%.%04i%'],
            'library' => ['identifier_mask' => 'LIB/%Y%/%04i%'],
            'gallery' => ['identifier_mask' => 'GAL.%Y%.%04i%'],
            'dam'     => ['identifier_mask' => 'DAM-%Y%-%06i%'],
        ];

        $fieldName = function ($sector, $key) {
            return 'sector_' . $sector . '__' . $key;
        };
      @endphp

      @if (empty($sectors))
        <div class="alert alert-warning" role="alert">
          <i class="fas fa-exclamation-triangle me-2"></i>
          {{ __('No GLAM/DAM sectors detected. Enable sector plugins to configure numbering.') }}
        </div>
      @else

        <ul class="nav nav-tabs" role="tablist">
          @php $i = 0; @endphp
          @foreach ($sectors as $code => $label)
            <li class="nav-item" role="presentation">
              <button
                class="nav-link {{ $i === 0 ? 'active' : '' }}"
                id="sector-tab-{{ $code }}"
                data-bs-toggle="tab"
                data-bs-target="#sector-pane-{{ $code }}"
                type="button"
                role="tab"
                aria-controls="sector-pane-{{ $code }}"
                aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
              >
                <i class="fas fa-layer-group me-1"></i>{{ __($label) }}
              </button>
            </li>
            @php $i++; @endphp
          @endforeach
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom p-4 bg-white">
          @php $i = 0; @endphp
          @foreach ($sectors as $code => $label)
            <div
              class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}"
              id="sector-pane-{{ $code }}"
              role="tabpanel"
              aria-labelledby="sector-tab-{{ $code }}"
            >
              <h4 class="mb-3">
                <span class="badge bg-primary">{{ __($label) }}</span>
                {{ __('Numbering Scheme') }}
              </h4>
              <p class="text-muted small mb-4">
                {{ __('Override global settings for the %1% sector. Empty fields inherit global values.', ['%1%' => __($label)]) }}
              </p>

              <div class="card mb-3">
                <div class="card-header"><i class="fas fa-fingerprint me-2"></i>{{ __('Identifier Numbering') }}</div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-4">
                      {!! render_field($form[$fieldName($code, 'identifier_mask_enabled')]->label(__('Enable identifier mask'))) !!}
                    </div>
                    <div class="col-md-4">
                      {!! render_field($form[$fieldName($code, 'identifier_mask')]->label(__('Identifier mask pattern'))) !!}
                      @php $idDefault = $sectorDefaults[$code]['identifier_mask'] ?? ''; @endphp
                      @if ($idDefault)
                        <div class="form-text">
                          <i class="fas fa-lightbulb text-warning me-1"></i>
                          {{ __('Suggested:') }} <code>{{ $idDefault }}</code>
                          <button type="button" class="btn btn-sm btn-outline-primary ms-2 use-default-btn"
                                  data-target="{{ $fieldName($code, 'identifier_mask') }}"
                                  data-value="{{ $idDefault }}">
                            {{ __('Use') }}
                          </button>
                        </div>
                      @endif
                    </div>
                    <div class="col-md-4">
                      {!! render_field($form[$fieldName($code, 'identifier_counter')]->label(__('Identifier counter')), null, ['type' => 'number']) !!}
                    </div>
                  </div>
                </div>
              </div>

            </div>
            @php $i++; @endphp
          @endforeach
        </div>

      @endif

      <section class="actions mt-4">
        <input class="btn btn-success" type="submit" value="{{ __('Save') }}">
        <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'index']) }}" class="btn btn-outline-secondary ms-2">
          {{ __('Cancel') }}
        </a>
      </section>

    </form>

    <script {!! $csp_nonce !!}>
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.use-default-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var targetName = this.dataset.target;
          var value = this.dataset.value;
          var input = document.querySelector('input[name="' + targetName + '"]');
          if (input) {
            input.value = value;
            input.focus();
          }
        });
      });
    });
    </script>

  </div>
</div>
@endsection
