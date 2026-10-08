<?php
/*
 * Turns the physical storage form's Building ... Shelf text fields into linked
 * pickers: each field suggests the places of its type beneath the place chosen
 * above it. Typing a name that is not suggested adds a new place on save
 * (StoragePlacementService). Without JavaScript the fields stay plain text and
 * save the same way.
 *
 * Include inside the form, after the fields.
 */
$levels = \AhgStorageManage\Services\StoragePlacementService::LEVELS;
$apiUrl = url_for(['module' => 'storageLocation', 'action' => 'apiLocations']);
$n = sfConfig::get('csp_nonce', '');
$nonce = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
?>
<?php foreach ($levels as $level) { ?>
  <datalist id="ahg-loc-<?php echo $level; ?>"></datalist>
<?php } ?>
<p class="small text-muted mb-0">
  <?php echo __('Each field suggests the places inside the one above it. Type a name that is not suggested to add a new place.'); ?>
</p>
<script <?php echo $nonce; ?>>
(function () {
  var levels = <?php echo json_encode($levels); ?>;
  var api = <?php echo json_encode($apiUrl); ?>;
  var form = document.querySelector('input[name="building"]');
  form = form && form.form;
  if (!form || !window.fetch) { return; }

  var field = {}, ids = {};
  levels.forEach(function (level) {
    var input = form.querySelector('input[name="' + level + '"]');
    if (!input) { return; }
    input.setAttribute('list', 'ahg-loc-' + level);
    input.setAttribute('autocomplete', 'off');
    field[level] = input;
    ids[level] = {};
  });

  // The id of the nearest filled level above, null at the top, or false when
  // that level is a new name (nothing exists beneath it yet).
  function parentOf(i) {
    for (var j = i - 1; j >= 0; j--) {
      var input = field[levels[j]];
      var name = input ? input.value.trim().toLowerCase() : '';
      if (name) { return ids[levels[j]].hasOwnProperty(name) ? ids[levels[j]][name] : false; }
    }
    return null;
  }

  function load(i) {
    var level = levels[i];
    if (!field[level]) { return Promise.resolve(); }
    var list = document.getElementById('ahg-loc-' + level);
    var parent = parentOf(i);
    list.innerHTML = '';
    ids[level] = {};
    if (false === parent) { return Promise.resolve(); }
    var url = api + (api.indexOf('?') < 0 ? '?' : '&') + 'type=' + encodeURIComponent(level)
      + (null === parent ? '' : '&parent_id=' + parent);
    return fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.json() : { data: [] }; })
      .then(function (json) {
        (json.data || []).forEach(function (row) {
          // With nothing above, only top-level places, as the save looks there.
          if (null === parent && null !== row.parent_id) { return; }
          ids[level][String(row.name).toLowerCase()] = row.id;
          var option = document.createElement('option');
          option.value = row.name;
          list.appendChild(option);
        });
      })
      .catch(function () {});
  }

  function loadFrom(i) {
    var chain = Promise.resolve();
    for (var j = i; j < levels.length; j++) {
      (function (k) { chain = chain.then(function () { return load(k); }); })(j);
    }
    return chain;
  }

  levels.forEach(function (level, i) {
    if (field[level]) {
      field[level].addEventListener('change', function () { loadFrom(i + 1); });
    }
  });
  loadFrom(0);
})();
</script>
