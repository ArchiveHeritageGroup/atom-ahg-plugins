/**
 * Descriptive-standard switch on the archival-description edit form.
 *
 * When the "Display standard" dropdown changes, save the current edits and
 * reload the edit form into the selected standard's field set. The save is a
 * normal form submit carrying _afterSave=edit, which IoFormHelper::handlePost
 * honours by redirecting back to /informationobject/<slug>/edit; the edit action
 * then dispatches to the matching per-standard form (ISAD / Dublin Core / RAD /
 * MODS / DACS), prefilled with the just-saved values.
 *
 * Loaded via a direct <script src> tag from each manage edit template (the theme
 * does not call include_javascripts()).
 */
(function () {
  'use strict';

  var sel = document.getElementById('displayStandardId');
  if (!sel) {
    return;
  }
  var form = sel.closest('form');
  if (!form) {
    return;
  }

  var previousValue = sel.value;

  sel.addEventListener('change', function () {
    var opt = sel.options[sel.selectedIndex];
    var label = opt ? opt.text.trim() : '';
    var message =
      'Switch the descriptive standard to "' + label + '"?\n\n' +
      'Your current changes will be saved and the form will reload with that ' +
      'standard\'s fields.';

    if (!window.confirm(message)) {
      sel.value = previousValue; // user cancelled - restore the previous choice
      return;
    }

    var flag = form.querySelector('input[name="_afterSave"]');
    if (!flag) {
      flag = document.createElement('input');
      flag.type = 'hidden';
      flag.name = '_afterSave';
      form.appendChild(flag);
    }
    flag.value = 'edit';

    // Use submit() (not requestSubmit) so a required field left empty - or hidden
    // inside a collapsed accordion, where the browser can't show its validation
    // bubble - does not SILENTLY block the switch. The server validates and
    // re-renders with errors if needed. The theme CSRF shim patches
    // HTMLFormElement.prototype.submit, so the token is still injected.
    form.submit();
  });
})();
