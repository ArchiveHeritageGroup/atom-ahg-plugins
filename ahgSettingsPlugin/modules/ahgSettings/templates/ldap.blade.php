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
          <div id="ldap-collapse" class="accordion-collapse collapse show" aria-labelledby="ldap-heading">
            <div class="accordion-body">
              {!! render_field($form->ldap_enabled->label(__('Enable LDAP authentication'))) !!}
              <p class="form-text mt-0 mb-3">
                {{ __('When enabled (and the user class is switched to ahgLdapUser — see Activation below), logins authenticate against the LDAP/AD directory below, falling back to the local password. When off, behaviour is unchanged (local accounts only).') }}
              </p>

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

    {{-- Test LDAP connection (CSP-safe: plain form POST, result via flash) --}}
    <div class="card mb-4">
      <div class="card-header"><strong>{{ __('Test LDAP connection') }}</strong></div>
      <div class="card-body">
        <p class="text-muted">{{ __('Tests connectivity (and a bind, if you supply a test user) against the saved settings above. Save first, then test. No changes are made.') }}</p>
        <form method="post" action="{{ url_for(['module' => 'ahgSettings', 'action' => 'ldapTest']) }}" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label" for="ldap_test_user">{{ __('Test username (optional)') }}</label>
            <input type="text" class="form-control" id="ldap_test_user" name="test_user" autocomplete="off">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="ldap_test_pass">{{ __('Test password (optional)') }}</label>
            <input type="password" class="form-control" id="ldap_test_pass" name="test_pass" autocomplete="off">
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn atom-btn-outline-primary">{{ __('Test connection') }}</button>
          </div>
        </form>
      </div>
    </div>

    {{-- Activation (auth-critical; performed per server by an admin) --}}
    <div class="card mb-4">
      <div class="card-header"><strong>{{ __('Activation') }}</strong></div>
      <div class="card-body">
        <p>{{ __('Enabling the toggle alone does not change login until the AtoM user class is switched. On the server (once, by an admin):') }}</p>
        <ol class="mb-2">
          <li>{{ __('Ensure the php-ldap extension is installed.') }}</li>
          <li>{!! __('In %file%, set the user class to %class%:', ['%file%' => '<code>config/factories.yml</code>', '%class%' => '<code>ahgLdapUser</code>']) !!}
            <pre class="bg-light p-2 border rounded mt-1 mb-1"><code>all:
  user:
    class: ahgLdapUser
    param:
      timeout: 1800</code></pre>
          </li>
          <li>{{ __('Clear cache and restart php-fpm.') }}</li>
        </ol>
        <p class="text-muted small mb-0">{{ __('ahgLdapUser is safe-by-default: with the toggle off (or php-ldap missing) it behaves exactly like the standard local-password login.') }}</p>
      </div>
    </div>
  </div>
</div>
@endsection
