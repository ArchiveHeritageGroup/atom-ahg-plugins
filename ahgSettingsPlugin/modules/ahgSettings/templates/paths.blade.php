@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Site Paths Setup') }}</h1>

    <form action="{{ url_for('settings/paths') }}" method="post">
      <div id="content">

        <table class="table sticky-enabled">
          <thead>
            <tr>
              <th>{{ __('Name') }}</th>
              <th>{{ __('Value') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>{!! $pathsForm['bulk']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['bulk']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['bulk']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['bulk_index']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['bulk_index']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['bulk_index']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['bulk_optimize_index']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['bulk_optimize_index']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['bulk_optimize_index']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['bulk_rename']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['bulk_rename']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['bulk_rename']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['bulk_verbose']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['bulk_verbose']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['bulk_verbose']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['bulk_output']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['bulk_output']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['bulk_output']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['bulk_skip_duplicates']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['bulk_skip_duplicates']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['bulk_skip_duplicates']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['output_path']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['output_path']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['output_path']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['output_filename']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['output_filename']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['output_filename']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['bulk_delete']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['bulk_delete']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['bulk_delete']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['log']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['log']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['log']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['log_path']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['log_path']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['log_path']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['log_filename']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['log_filename']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['log_filename']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['move']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['move']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['move']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['move_path']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['move_path']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['move_path']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['upload_path']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['upload_path']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['upload_path']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['download_path']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['download_path']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['download_path']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['unpublish_path']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['unpublish_path']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['unpublish_path']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['publish_path']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['publish_path']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['publish_path']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['update_path']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['update_path']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['update_path']->render() !!}
              </td>
            </tr>
            <tr>
              <td>{!! $pathsForm['mq_path']->renderLabel(null, ['title' => __('To Do')]) !!}</td>
              <td>
                @if (strlen($error = $pathsForm['mq_path']->renderError()))
                  {!! $error !!}
                @endif
                {!! $pathsForm['mq_path']->render() !!}
              </td>
            </tr>
          </tbody>
        </table>

      </div>

      <section class="actions mb-3">
        <input class="btn atom-btn-outline-success" type="submit" value="{{ __('Save') }}">
      </section>

    </form>
  </div>
</div>
@endsection
