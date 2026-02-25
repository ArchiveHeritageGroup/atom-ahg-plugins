<?php
  // $filters: array of ['label' => 'Type', 'param' => 'type' OR 'name' => 'type', 'options' => [...], 'current' => '']
  // $action: the browse action name
  // $country (optional): show country text field
  // $country_current (optional): current country value
?>
<form method="get" action="<?php echo url_for(['module' => 'registry', 'action' => $action]); ?>">

  <?php if (!empty($filters)): ?>
    <?php foreach ($filters as $filter): ?>
      <?php
        $paramName = $filter['param'] ?? ($filter['name'] ?? '');
        $currentVal = $filter['current'] ?? $sf_request->getParameter($paramName, '');
        $options = $filter['options'] ?? [];
      ?>
      <div class="card mb-3">
        <div class="card-header py-2 fw-semibold small"><?php echo $filter['label'] ?? ''; ?></div>
        <div class="card-body py-2">
          <?php if (is_array($options) && isset($options[0]) && is_array($options[0])): ?>
            <!-- Checkbox/radio style options: [['value' => ..., 'label' => ..., 'count' => ...], ...] -->
            <?php foreach ($options as $opt): ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="<?php echo htmlspecialchars($paramName, ENT_QUOTES, 'UTF-8'); ?>[]" value="<?php echo htmlspecialchars($opt['value'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" id="filter_<?php echo htmlspecialchars($paramName . '_' . ($opt['value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"<?php
                  $curArr = is_array($currentVal) ? $currentVal : [$currentVal];
                  echo in_array($opt['value'] ?? '', $curArr) ? ' checked' : '';
                ?>>
                <label class="form-check-label small" for="filter_<?php echo htmlspecialchars($paramName . '_' . ($opt['value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo htmlspecialchars($opt['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                  <?php if (isset($opt['count'])): ?>
                    <span class="text-muted">(<?php echo (int) $opt['count']; ?>)</span>
                  <?php endif; ?>
                </label>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <!-- Select style options: ['value' => 'Label', ...] -->
            <select name="<?php echo htmlspecialchars($paramName, ENT_QUOTES, 'UTF-8'); ?>" class="form-select form-select-sm">
              <?php foreach ($options as $val => $label): ?>
                <option value="<?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (string) $currentVal === (string) $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (!empty($country)): ?>
  <div class="card mb-3">
    <div class="card-header py-2 fw-semibold small"><?php echo __('Country'); ?></div>
    <div class="card-body py-2">
      <input type="text" class="form-control form-control-sm" name="country" value="<?php echo htmlspecialchars($country_current ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('e.g. South Africa'); ?>">
    </div>
  </div>
  <?php endif; ?>

  <button type="submit" class="btn btn-primary btn-sm w-100 mb-2"><?php echo __('Apply Filters'); ?></button>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => $action]); ?>" class="btn btn-outline-secondary btn-sm w-100"><?php echo __('Clear Filters'); ?></a>
</form>
