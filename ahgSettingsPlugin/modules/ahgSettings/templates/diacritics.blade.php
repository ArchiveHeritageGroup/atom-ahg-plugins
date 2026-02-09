@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Diacritics settings') }}</h1>

    <div class="alert alert-info">
      <p>
        {{ __('Please rebuild the search index after uploading diacritics mappings.') }}
      </p>
      <pre>$ php symfony search:populate</pre>
    </div>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'diacritics'])) !!}

    {!! $form->renderHiddenFields() !!}

    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header" id="diacritics-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#diacritics-collapse"
            aria-expanded="false" aria-controls="diacritics-collapse">
            {{ __('Diacritics Settings') }}
          </button>
        </h2>
        <div id="diacritics-collapse" class="accordion-collapse collapse" aria-labelledby="diacritics-heading">
          <div class="accordion-body">
            {!! render_field($form->diacritics->label(__('Diacritics'))) !!}
          </div>
        </div>
      </div>
      <div class="accordion-item">
        <h2 class="accordion-header" id="mappings-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mappings-collapse"
            aria-expanded="false" aria-controls="mappings-collapse">
            {{ __('CSV Mapping YAML') }}
          </button>
        </h2>

        <div id="mappings-collapse" class="accordion-collapse collapse" aria-labelledby="sending-heading">

          <div class="alert alert-info m-3 mb-0">
            <p>
              {{ __('Example CSV:') }}
            </p>
            <pre>type: mapping<br>mappings:<br>  - &Agrave; => A<br>  - &Aacute; => A</pre>
          </div>

          <div class="accordion-body">
            {!! render_field($form->mappings->label(__('Mappings YAML'))) !!}
          </div>
        </div>
      </div>
    </div>

    <section class="actions">
      <input class="btn atom-btn-outline-success" type="submit" value="{{ __('Save') }}">
    </section>

    </form>
  </div>
</div>
@endsection
