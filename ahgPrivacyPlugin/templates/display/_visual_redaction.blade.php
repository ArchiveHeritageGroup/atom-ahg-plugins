{{--
  Visual Redaction Editor action button

  Shows button to navigate to the Visual Redaction Editor
  for administrators/editors to redact sensitive content in PDFs/images.

  @var object $object - The information object
  @var object|null $digitalObject - The digital object (if any)
--}}

@php
// Only show for users with editor credential and if there's a digital object
$sf_user = sfContext::getInstance()->getUser();
$canEdit = $sf_user->isAuthenticated() &&
           ($sf_user->isAdministrator() || $sf_user->hasCredential('editor'));
@endphp

@if($canEdit && $digitalObject)
@php
$editorUrl = url_for([
    'module' => 'privacyAdmin',
    'action' => 'visualRedactionEditor',
    'id' => $object->id
]);
@endphp
<a href="{{ $editorUrl }}"
   class="btn btn-outline-dark me-2"
   title="Visual Redaction Editor - Draw redactions on PDF/Image">
    <i class="fas fa-mask me-1"></i> Redact
</a>
@endif
