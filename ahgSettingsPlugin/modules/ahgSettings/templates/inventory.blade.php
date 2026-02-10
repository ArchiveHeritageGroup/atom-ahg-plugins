@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Inventory') }}</h1>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'inventory'])) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="inventory-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#inventory-collapse" aria-expanded="false" aria-controls="inventory-collapse">
              {{ __('Inventory settings') }}
            </button>
          </h2>
          <div id="inventory-collapse" class="accordion-collapse collapse" aria-labelledby="inventory-heading">
            <div class="accordion-body">
              @if (!empty($unknownValueDetected))
                <div class="alert alert-danger" role="alert">
                  {{ __('Unknown value detected.') }}<br>
                </div>
              @endif

              {!! render_field(
                  $form->levels
                      ->label(__('Levels of description'))
                      ->help(__('Select the levels of description to be included in the inventory list. If no levels are selected, the inventory list link will not be displayed. You can use the control (Mac ⌘) and/or shift keys to multi-select values from the Levels of description menu.')),
                  null,
                  ['class' => 'form-autocomplete']
              ) !!}

              <br>
              @php
                $taxonomy = QubitTaxonomy::getById(QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID);
              @endphp
              <a href="{{ url_for(['module' => 'taxonomy', 'slug' => $taxonomy->slug]) }}">{{ __('Review the current terms in the Levels of description taxonomy.') }}</a>
            </div>
          </div>
        </div>
      </div>

      <section class="actions mb-3">
        <input class="btn atom-btn-outline-success" type="submit" value="{{ __('Save') }}">
      </section>

    </form>
  </div>
</div>
@endsection
