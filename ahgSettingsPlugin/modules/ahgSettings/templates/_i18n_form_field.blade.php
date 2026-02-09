@if (!empty($label))
  {!! $form->{$name}->renderLabel($label) !!}
@endif

@if (strlen($error = $form->{$name}->renderError()))
  {!! $error !!}
@endif

@if ($sourceCultureHelper = $settings[$name]->getSourceCultureHelper($sf_user->getCulture()))
  <div class="default-translation">{!! $sourceCultureHelper !!}</div>
@endif

{!! $form->{$name}->render() !!}
