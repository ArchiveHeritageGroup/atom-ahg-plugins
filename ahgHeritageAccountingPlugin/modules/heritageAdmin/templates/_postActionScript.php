<?php

/**
 * Bind confirmation prompts for _postAction forms (#262).
 *
 * Include once per template that uses _postAction. Kept out of the partial so
 * the handler is registered a single time rather than once per button, and out
 * of inline onsubmit attributes so it survives CSP enforcement (#248).
 */
?>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });
});
</script>
