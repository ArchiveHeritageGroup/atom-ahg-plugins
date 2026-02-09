@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('LDAP authentication') }}</h1>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'ldap'])) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="ldap-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ldap-collapse" aria-expanded="false" aria-controls="ldap-collapse">
              {{ __('LDAP authentication settings') }}
            </button>
          </h2>
          <div id="ldap-collapse" class="accordion-collapse collapse" aria-labelledby="ldap-heading">
            <div class="accordion-body">
              {!! render_field($form->ldapHost->label(__('Host'))) !!}

              {!! render_field($form->ldapPort->label(__('Port'))) !!}

              {!! render_field($form->ldapBaseDn->label(__('Base DN'))) !!}

              {!! render_field($form->ldapBindAttribute->label(__('Bind Lookup Attribute'))) !!}
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
