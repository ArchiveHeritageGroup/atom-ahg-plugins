@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Security settings') }}</h1>

    <div class="alert alert-info">
      {{ __('Note: Incorrect security settings can result in the AtoM web UI becoming inaccessible.') }}
    </div>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'security'])) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="security-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#security-collapse" aria-expanded="false" aria-controls="security-collapse">
              {{ __('Security settings') }}
            </button>
          </h2>
          <div id="security-collapse" class="accordion-collapse collapse" aria-labelledby="security-heading">
            <div class="accordion-body">
              {!! render_field($form->limit_admin_ip) !!}
              {!! render_field($form->require_ssl_admin) !!}
              {!! render_field($form->require_strong_passwords) !!}
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
