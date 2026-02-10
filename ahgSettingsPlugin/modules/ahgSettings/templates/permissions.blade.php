@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Permissions') }}</h1>

    {!! $permissionsForm->renderGlobalErrors() !!}
    {!! $permissionsAccessStatementsForm->renderGlobalErrors() !!}
    {!! $permissionsCopyrightStatementForm->renderGlobalErrors() !!}
    {!! $permissionsPreservationSystemAccessStatementForm->renderGlobalErrors() !!}

    {!! $permissionsForm->renderFormTag(
        url_for(['module' => 'ahgSettings', 'action' => 'permissions']),
        ['autocomplete' => 'off']
    ) !!}

      {!! $permissionsForm->renderHiddenFields() !!}
      {!! $permissionsAccessStatementsForm->renderHiddenFields() !!}
      {!! $permissionsCopyrightStatementForm->renderHiddenFields() !!}
      {!! $permissionsPreservationSystemAccessStatementForm->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="permissions-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#permissions-collapse" aria-expanded="false" aria-controls="permissions-collapse">
              {{ __('PREMIS access permissions') }}
            </button>
          </h2>
          <div id="permissions-collapse" class="accordion-collapse collapse" aria-labelledby="permissions-heading">
            <div class="accordion-body">
              {!! render_field($permissionsForm['granted_right']->label(__('PREMIS act'))) !!}

              <h3 class="fs-6 mb-2">
                {{ __('Permissions') }}
              </h3>

              <div class="table-responsive mb-3">
                <table class="table table-bordered mb-0">
                  <colgroup><col></colgroup>
                  <colgroup span="3"></colgroup>
                  <colgroup span="3"></colgroup>
                  <colgroup span="3"></colgroup>
                  <tr>
                    <th rowspan="2" scope="colgroup" class="text-center">{{ __('Basis') }}</th>
                    <th colspan="3" scope="colgroup" class="text-center">{{ __('Allow') }}</th>
                    <th colspan="3" scope="colgroup" class="text-center">{{ __('Conditional') }}</th>
                    <th colspan="3" scope="colgroup" class="text-center">{{ __('Disallow') }}</th>
                  </tr>
                  <tr>
                    <th scope="col"><button class="btn btn-sm atom-btn-white w-100">{{ __('Master') }}</button></th>
                    <th scope="col"><button class="btn btn-sm atom-btn-white w-100">{{ __('Reference') }}</button></th>
                    <th scope="col"><button class="btn btn-sm atom-btn-white w-100">{{ __('Thumb') }}</button></th>
                    <th scope="col"><button class="btn btn-sm atom-btn-white w-100">{{ __('Master') }}</button></th>
                    <th scope="col"><button class="btn btn-sm atom-btn-white w-100">{{ __('Reference') }}</button></th>
                    <th scope="col"><button class="btn btn-sm atom-btn-white w-100">{{ __('Thumb') }}</button></th>
                    <th scope="col"><button class="btn btn-sm atom-btn-white w-100">{{ __('Master') }}</button></th>
                    <th scope="col"><button class="btn btn-sm atom-btn-white w-100">{{ __('Reference') }}</button></th>
                    <th scope="col"><button class="btn btn-sm atom-btn-white w-100">{{ __('Thumb') }}</button></th>
                  </tr>
                  @foreach ($permissionsForm['permissions'] as $k => $sf)
                    <tr>
                      <th class="text-end" scope="row">{{ $basis[$k] }}</th>
                      <td class="text-center">{!! $sf['allow_master'] !!}</td>
                      <td class="text-center">{!! $sf['allow_reference'] !!}</td>
                      <td class="text-center">{!! $sf['allow_thumb'] !!}</td>
                      <td class="text-center">{!! $sf['conditional_master'] !!}</td>
                      <td class="text-center">{!! $sf['conditional_reference'] !!}</td>
                      <td class="text-center">{!! $sf['conditional_thumb'] !!}</td>
                      <td class="text-center">{!! $sf['disallow_master'] !!}</td>
                      <td class="text-center">{!! $sf['disallow_reference'] !!}</td>
                      <td class="text-center">{!! $sf['disallow_thumb'] !!}</td>
                  @endforeach
                  </tbody>
                </table>
              </div>

              <div class="text-end">
                <div class="btn-group" role="group" aria-label="{{ __('Permission toggles') }}">
                  <button type="button" class="btn btn-sm atom-btn-white all">{{ __('All') }}</button>
                  <button type="button" class="btn btn-sm atom-btn-white none">{{ __('None') }}</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="statements-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#statements-collapse" aria-expanded="false" aria-controls="statements-collapse">
              {{ __('PREMIS access statements') }}
            </button>
          </h2>
          <div id="statements-collapse" class="accordion-collapse collapse" aria-labelledby="statements-heading">
            <div class="accordion-body">

              <?php $firstKey = array_key_first($basis); ?>

              <ul class="nav nav-tabs mb-3" role="tablist">
                @foreach ($basis as $basisSlug => $basisName)
                  <li class="nav-item" role="presentation">
                    <?php $isFirst = $firstKey === $basisSlug; ?>
                    <button
                      class="nav-link{{ $isFirst ? ' active' : '' }}"
                      id="{{ $basisSlug }}-tab"
                      type="button"
                      role="tab"
                      aria-controls="{{ $basisSlug }}-pane"
                      aria-selected="{{ $isFirst ? 'true' : 'false' }}"
                      data-bs-toggle="tab"
                      data-bs-target="#{{ $basisSlug }}-pane">
                      {{ $basisName }}
                    </button>
                  </li>
                @endforeach
              </ul>

              <div class="tab-content">
                <?php $settings = $permissionsAccessStatementsForm->getSettings(); ?>
                @foreach ($basis as $basisSlug => $basisName)
                  <?php $isFirst = $firstKey === $basisSlug; ?>
                  <div
                    class="tab-pane fade{{ $isFirst ? ' show active' : '' }}"
                    id="{{ $basisSlug }}-pane"
                    role="tabpanel"
                    aria-labelledby="{{ $basisSlug }}-tab"
                  >
                    <?php $name = "{$basisSlug}_disallow"; ?>
                    <?php $field = $permissionsAccessStatementsForm[$name]; ?>
                    {!! render_field($field->label(__('Disallow statement')), $settings[$name], ['name' => 'value', 'class' => 'resizable']) !!}

                    <?php $name = "{$basisSlug}_conditional"; ?>
                    <?php $field = $permissionsAccessStatementsForm[$name]; ?>
                    {!! render_field($field->label(__('Conditional statement')), $settings[$name], ['name' => 'value', 'class' => 'resizable']) !!}
                  </div>
                @endforeach
              </div>

            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="copyright-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#copyright-collapse" aria-expanded="false" aria-controls="copyright-collapse">
              {{ __('Copyright statement') }}
            </button>
          </h2>
          <div id="copyright-collapse" class="accordion-collapse collapse" aria-labelledby="copyright-heading">
            <div class="accordion-body">
              {!! render_field(
                  $permissionsCopyrightStatementForm
                      ->copyrightStatementEnabled
                      ->label(__('Enable copyright statement'))) !!}

              {!! render_field(
                  $permissionsCopyrightStatementForm
                      ->copyrightStatement
                      ->label(__('Copyright statement'))
                      ->help(__('When enabled the following text will appear whenever a user tries to download a %1% master with an associated rights statement where the Basis = copyright and the Restriction = conditional. You can style and customize the text as in a static page.', ['%1%' => mb_strtolower(sfConfig::get('app_ui_label_digitalobject'))])),
                  $copyrightStatementSetting,
                  ['name' => 'value', 'class' => 'resizable']) !!}

              <input class="btn atom-btn-white mb-3" type="submit" name="preview" value="{{ __('Preview') }}"/>

              {!! render_field(
                  $permissionsCopyrightStatementForm
                      ->copyrightStatementApplyGlobally
                      ->label(__('Apply to every %1%', ['%1%' => mb_strtolower(sfConfig::get('app_ui_label_digitalobject'))]))
                      ->help(__('When enabled, the copyright pop-up will be applied to every %1%, regardless of whether there is an accompanying Rights statement.', ['%1%' => mb_strtolower(sfConfig::get('app_ui_label_digitalobject'))]))) !!}
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="preservation-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#preservation-collapse" aria-expanded="false" aria-controls="preservation-collapse">
              {{ __('Preservation system access statement') }}
            </button>
          </h2>
          <div id="preservation-collapse" class="accordion-collapse collapse" aria-labelledby="preservation-heading">
            <div class="accordion-body">
              {!! render_field(
                  $permissionsPreservationSystemAccessStatementForm
                      ->preservationSystemAccessStatementEnabled
                      ->label(__('Enable access statement'))) !!}

              {!! render_field(
                  $permissionsPreservationSystemAccessStatementForm
                      ->preservationSystemAccessStatement
                      ->label(__('Access statement'))
                      ->help(__('When enabled the text above will appear in the %1% metadata section to describe how a user may access the original and preservation copy of the file stored in a linked digital preservation system. The text appears in the "Permissions" field. When disabled, the "Permissions" field is not displayed.', ['%1%' => mb_strtolower(sfConfig::get('app_ui_label_digitalobject'))])),
                  $preservationSystemAccessStatementSetting,
                  ['name' => 'value', 'class' => 'resizable']) !!}
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
