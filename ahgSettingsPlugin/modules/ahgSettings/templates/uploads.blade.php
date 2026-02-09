@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Uploads settings') }}</h1>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'uploads'])) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="settings-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#settings-collapse" aria-expanded="false" aria-controls="settings-collapse">
              {{ __('Upload settings') }}
            </button>
          </h2>
          <div id="settings-collapse" class="accordion-collapse collapse" aria-labelledby="settings-heading">
            <div class="accordion-body">
              {!! render_field($form->upload_quota
                  ->label(__('Total space available for uploads'))) !!}

              {!! render_field($form->enable_repository_quotas
                  ->label(
                    __('%1% upload limits',
                    [
                        '%1%' => sfConfig::get('app_ui_label_repository'),
                    ]
                  ))
                  ->help(__(
                    'When enabled, an &quot;Upload limit&quot; meter is displayed for authenticated users on the %1% view page, and administrators can limit the disk space each %1% is allowed for %2% uploads',
                    [
                        '%1%' => strtolower(sfConfig::get('app_ui_label_repository')),
                        '%2%' => strtolower(sfConfig::get('app_ui_label_digitalobject')),
                    ]))) !!}

              {!! render_field($form->repository_quota
                  ->label(__(
                      'Default %1% upload limit (GB)',
                      ['%1%' => strtolower(sfConfig::get('app_ui_label_repository'))]
                  ))
                  ->help(__(
                      'Default %1% upload limit for a new %2%.  A value of &quot;0&quot; (zero) disables file upload.  A value of &quot;-1&quot; allows unlimited uploads',
                      [
                          '%1%' => strtolower(sfConfig::get('app_ui_label_digitalobject')),
                          '%2%' => strtolower(sfConfig::get('app_ui_label_repository')),
                      ]))) !!}

              {!! render_field($form->explode_multipage_files
                  ->label(__('Upload multi-page files as multiple descriptions'))) !!}
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
