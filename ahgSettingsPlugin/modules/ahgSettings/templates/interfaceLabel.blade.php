@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('User interface label') }}</h1>

    {!! $uiLabelForm->renderGlobalErrors() !!}

    {!! $uiLabelForm->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'interfaceLabel'])) !!}

      {!! $uiLabelForm->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="interface-label-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#interface-label-collapse" aria-expanded="false" aria-controls="interface-label-collapse">
              {{ __('User interface labels') }}
            </button>
          </h2>
          <div id="interface-label-collapse" class="accordion-collapse collapse" aria-labelledby="interface-label-heading">
            <div class="accordion-body">
              @foreach ($uiLabelForm->getSettings() as $setting)
                @php $name = $setting->getName(); @endphp
                {!! render_field($uiLabelForm->{$name}->label('<code>'.$name.'</code>'), $setting) !!}
              @endforeach
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
