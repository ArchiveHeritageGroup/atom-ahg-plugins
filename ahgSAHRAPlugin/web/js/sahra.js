/* ahgSAHRAPlugin - front-end behaviour */
(function () {
  'use strict';

  // Decision form: show the "issued" fields only when outcome = issued.
  document.querySelectorAll('form[data-sahra-decision]').forEach(function (form) {
    var issuedBlock = form.querySelector('[data-decision-issued]');
    function apply() {
      var checked = form.querySelector('[data-decision-outcome]:checked');
      var issued = checked && checked.value === 'issued';
      if (issuedBlock) {
        issuedBlock.style.display = issued ? '' : 'none';
      }
    }
    form.querySelectorAll('[data-decision-outcome]').forEach(function (r) {
      r.addEventListener('change', apply);
    });
    apply();
  });
})();
