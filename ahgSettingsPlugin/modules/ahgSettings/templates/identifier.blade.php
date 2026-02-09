@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Identifier Settings') }}</h1>

    <div class="alert alert-info" role="alert">
      <i class="fas fa-info-circle me-2"></i>
      {{ __('Configure global identifier and accession numbering. Clear the application cache and rebuild the search index if you change the reference code separator.') }}
      <pre class="mt-2 mb-0">$ php symfony cc && php symfony search:populate</pre>
    </div>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'identifier'])) !!}
      {!! $form->renderHiddenFields() !!}

      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-box me-2"></i>{{ __('Accession Numbering') }}
        </div>
        <div class="card-body">
          {!! render_field($form->accession_mask_enabled->label(__('Accession mask enabled'))) !!}
          {!! render_field($form->accession_mask->label(__('Accession mask'))) !!}
          {!! render_field($form->accession_counter->label(__('Accession counter')), null, ['type' => 'number']) !!}
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-fingerprint me-2"></i>{{ __('Identifier Numbering') }}
        </div>
        <div class="card-body">
          {!! render_field($form->identifier_mask_enabled->label(__('Identifier mask enabled'))) !!}
          {!! render_field($form->identifier_mask->label(__('Identifier mask'))) !!}
          {!! render_field($form->identifier_counter->label(__('Identifier counter')), null, ['type' => 'number']) !!}
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-cog me-2"></i>{{ __('Reference Code Options') }}
        </div>
        <div class="card-body">
          {!! render_field($form->separator_character->label(__('Reference code separator'))) !!}
          {!! render_field($form->inherit_code_informationobject->label(__('Inherit reference code (information object)'))) !!}
          {!! render_field($form->inherit_code_dc_xml->label(__('Inherit reference code (DC XML)'))) !!}
          {!! render_field($form->prevent_duplicate_actor_identifiers->label(__(
              '%1% identifiers: prevent entry/import of duplicates',
              ['%1%' => sfConfig::get('app_ui_label_actor')]
          ))) !!}
        </div>
      </div>

      <div class="alert alert-secondary" role="alert">
        <i class="fas fa-layer-group me-2"></i>
        <strong>{{ __('Sector-specific numbering?') }}</strong>
        {{ __('Configure different numbering schemes per GLAM/DAM sector in') }}
        <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'sectorNumbering']) }}">
          {{ __('Sector Numbering Settings') }}
        </a>.
      </div>

      <section class="actions">
        <input class="btn btn-success" type="submit" value="{{ __('Save') }}">
        <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'index']) }}" class="btn btn-outline-secondary ms-2">
          {{ __('Cancel') }}
        </a>
      </section>

    </form>
  </div>
</div>
@endsection
