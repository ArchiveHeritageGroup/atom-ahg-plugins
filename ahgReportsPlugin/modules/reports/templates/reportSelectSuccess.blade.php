@extends('layouts.page')

@section('title')
  @if (isset($resource))
    <h1 class="multiline">
      {!! $title !!}
      <span class="sub">{{ $resource->title ?? $resource->slug ?? '' }}</span>
    </h1>
  @else
    <h1>{{ __('Select Report Type') }}</h1>
  @endif
@endsection

@section('content')

  @if (isset($resource))
    {!! $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportSelect', 'slug' => $resource->slug]), ['enctype' => 'multipart/form-data']) !!}
  @else
    {!! $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportSelect']), ['enctype' => 'multipart/form-data']) !!}
  @endif

    {!! $form->renderHiddenFields() !!}

    <section id="content">

      <fieldset class="collapsible">

        <input type="hidden" name="importType" value="{{ $type }}"/>

          <div class="form-item">
            <label>{{ __('Type') }}</label>
            <select  name="objectType" id="objectType">
              <!--<option value="access"><?php //echo sfConfig::get('app_ui_label_accession', __('Access (Archival Description)')); ?></option>-->
              <option value="accession">{{ sfConfig::get('app_ui_label_accession', __('Accession')) }}</option>

			  <option value="informationObject">{{ sfConfig::get('app_ui_label_informationobject', __('Archival Description')) }}</option>
              <!--<option value="audit_trail"><?php //echo __('Audit Trail'); ?></option>-->
              <option value="authorityRecord">{{ sfConfig::get('app_ui_label_actor', __('Authority Record/Actor')) }}</option>
              <!--<option value="booked_in"><?php //echo sfConfig::get('app_ui_label_user', __('Booked In')); ?></option>-->
              <!--<option value="booked_out"><?php //echo sfConfig::get('app_ui_label_user', __('Booked Out')); ?></option>-->
              <option value="donor">{{ sfConfig::get('app_ui_label_donor', __('Donor')) }}</option>
              <option value="physical_storage">{{ __('Physical Storage') }}</option>
              <!--<option value="preservation"><?php //echo __('Preservation'); ?></option>-->
              <!--<option value="registry"><?php //echo __('Registry'); ?></option>-->
              <option value="repository">{{ sfConfig::get('app_ui_label_donor', __('Repository/Archival Institution')) }}</option>
              <!--<option value="researcher"><?php //echo sfConfig::get('app_ui_label_researcher', __('Researcher')); ?></option>-->
              <!--<option value="service_provider"><?php //echo __('Service Provider'); ?></option>-->
              <!--<option value="user"><?php //echo sfConfig::get('app_ui_label_user', __('User Action')); ?></option>-->
            </select>

          <div class="form-item">
    </section>

	<section class="actions mb-3">
		<input class="btn atom-btn-outline-success" type="submit" id="bookout" value="{{ __('Select') }}"/>
	</section>
  </form>

@endsection
