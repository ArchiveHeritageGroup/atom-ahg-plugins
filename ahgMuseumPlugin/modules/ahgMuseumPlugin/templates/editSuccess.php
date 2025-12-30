<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">

  <!-- Template Selector -->
  <section id="template-selector" class="sidebar-section">
    <h4><?php echo __('Object Template'); ?></h4>
    <div class="template-list">
      <?php foreach ($availableTemplates as $id => $tpl): ?>
        <a href="#" data-template="<?php echo $id; ?>"
           class="template-option <?php echo $templateId === $id ? 'active' : ''; ?>">
          <i class="fa <?php echo $tpl['icon']; ?>"></i>
          <span><?php echo $tpl['label']; ?></span>
        </a>
      <?php endforeach; ?>
    </div>

  </section>

  <!-- Completeness Meter -->
  <section id="completeness-meter" class="sidebar-section">
    <h4><?php echo __('Record Completeness'); ?></h4>
    <div class="progress-container">
      <div class="progress">
        <div class="progress-bar <?php echo $completeness >= 80 ? 'bg-success' : ($completeness >= 50 ? 'bg-warning' : 'bg-danger'); ?>"
             style="width: <?php echo $completeness; ?>%">
        </div>
      </div>
      <span class="completeness-value"><?php echo $completeness; ?>%</span>
    </div>
    <p class="help-text"><?php echo __('Fill all required and recommended fields for complete cataloguing.'); ?></p>
  </section>

  <!-- CCO Reference -->
  <section id="cco-reference" class="sidebar-section">
    <h4><?php echo __('CCO Reference'); ?></h4>
    <p class="small"><?php echo __('This form follows the Cataloguing Cultural Objects (CCO) standard.'); ?></p>
    <a href="http://cco.vrafoundation.org/" target="_blank" class="btn btn-sm btn-cco-guide">
      <i class="fa fa-external-link"></i> <?php echo __('CCO Guide'); ?>
    </a>
  </section>

  <!-- Field Legend -->
  <section id="field-legend" class="sidebar-section">
    <h4><?php echo __('Field Legend'); ?></h4>
    <ul class="legend-list">
      <li><span class="badge badge-required">Required</span> <?php echo __('Must be completed'); ?></li>
      <li><span class="badge badge-recommended">Recommended</span> <?php echo __('Should be completed'); ?></li>
      <li><span class="badge badge-optional">Optional</span> <?php echo __('Complete if applicable'); ?></li>
    </ul>
  </section>

</div>
<!-- Tom Select CSS (JS loaded in after-content slot) -->
<link href="/plugins/ahgThemeB5Plugin/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="/plugins/ahgThemeB5Plugin/js/tom-select.complete.min.js"></script>
<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
document.addEventListener("DOMContentLoaded", function() {
  // Creator dropdown
  var creatorSelect = document.querySelector("select[name=\"creator\"]");
  if (creatorSelect) {
    new TomSelect(creatorSelect, {
      placeholder: "Search or select creator...",
      allowEmptyOption: true,
      create: false
    });
  }
  // Repository dropdown
  var repoSelect = document.querySelector("select[name=\"repository\"]");
  if (repoSelect) {
    new TomSelect(repoSelect, {
      placeholder: "Search or select repository...",
      allowEmptyOption: true,
      create: false
    });
  }
});
</script>
<?php end_slot(); ?>


<?php slot('title'); ?>
<h1 class="multiline">
  <?php echo __('CCO Cataloguing'); ?>
  <span class="sub"><?php echo ahgCCOTemplates::getTemplate($templateId)['label']; ?> Template</span>
</h1>
</script>
<?php end_slot(); ?>

<?php slot('content'); ?>

<style>
/* CCO Form Styling - Collections Management Dashboard Theme */
.sidebar-section {
  background: #fff;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 15px;
  border: 1px solid #ddd;
}

.sidebar-section h4 {
  color: #1a5c4c;
  font-size: 14px;
  font-weight: 700;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 2px solid #e9ecef;
}

.template-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.template-option {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 15px;
  font-size: 12px;
  text-decoration: none;
  color: #333;
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  transition: all 0.2s;
}

.template-option:hover {
  background: #e9ecef;
  text-decoration: none;
  color: #1a5c4c;
}

.template-option.active {
  background: #1a5c4c;
  color: #fff;
  border-color: #1a5c4c;
}

.template-option i {
  margin-right: 5px;
  font-size: 11px;
}

.progress-container {
  display: flex;
  align-items: center;
  gap: 10px;
}

.progress {
  flex: 1;
  height: 20px;
  background: #e9ecef;
  border-radius: 10px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  border-radius: 10px;
  transition: width 0.3s;
}

.completeness-value {
  font-weight: 700;
  min-width: 40px;
}

.btn-cco-guide {
  background: #1a5c4c;
  color: #fff;
  border: none;
}

.btn-cco-guide:hover {
  background: #145043;
  color: #fff;
}

.legend-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.legend-list li {
  margin-bottom: 6px;
  font-size: 12px;
}

.badge-required {
  background: #e74c3c;
  color: #fff;
  padding: 2px 8px;
  border-radius: 3px;
  font-size: 10px;
}

.badge-recommended {
  background: #f39c12;
  color: #fff;
  padding: 2px 8px;
  border-radius: 3px;
  font-size: 10px;
}

.badge-optional {
  background: #95a5a6;
  color: #fff;
  padding: 2px 8px;
  border-radius: 3px;
  font-size: 10px;
}

/* Accordion Sections */
.cco-cataloguing-form .accordion-item {
  border: none;
  margin-bottom: 10px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #ddd;
}

.cco-cataloguing-form .accordion-button {
  background: #1a5c4c !important;
  color: #fff !important;
}

.cco-cataloguing-form .accordion-button:not(.collapsed) {
  background: #1a5c4c !important;
  color: #fff !important;
}

.cco-cataloguing-form .accordion-button.collapsed {
  background-color: #1a5c4c;
  color: #fff;
}

.cco-cataloguing-form .accordion-button::after {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'//%3e%3c/svg%3e");
}

.cco-cataloguing-form .accordion-button:focus {
  box-shadow: none;
}

.cco-cataloguing-form .accordion-body {
  padding: 20px;
  background: #fff;
}

.cco-chapter {
  margin-left: 15px;
  float: right;
  font-size: 11px;
  opacity: 1;
  font-weight: normal;
}

.category-description {
  font-size: 13px;
  color: #666;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
}

/* Form Fields */
.cco-field {
  margin-bottom: 20px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 6px;
  border-left: 3px solid #ddd;
}

.cco-field.level-required {
  border-left-color: #e74c3c;
}

.cco-field.level-recommended {
  border-left-color: #f39c12;
}

.field-header {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
  flex-wrap: wrap;
  gap: 8px;
}

.field-header label {
  font-weight: 700;
  color: #333;
  margin: 0;
  flex: 1;
}

.field-header .required {
  color: #e74c3c;
}

.field-badges {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
}

.badge-cco {
  background: #3498db;
  color: #fff;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 10px;
}

.badge-vocab {
  background: #9b59b6;
  color: #fff;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 10px;
}

.btn-help {
  background: none;
  border: none;
  color: #1a5c4c;
  cursor: pointer;
  padding: 2px 6px;
  font-size: 14px;
}

.btn-help:hover, .btn-help.active {
  color: #145043;
}

.field-input input,
.field-input select,
.field-input textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.field-input input:focus,
.field-input select:focus,
.field-input textarea:focus {
  border-color: #1a5c4c;
  outline: none;
  box-shadow: 0 0 0 3px rgba(26, 92, 76, 0.1);
}

.field-help {
  margin-top: 10px;
  padding: 12px;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.help-text {
  font-size: 13px;
  color: #666;
  margin: 0;
}

.help-examples {
  margin-top: 10px;
  font-size: 12px;
}

.help-examples ul {
  margin: 5px 0 0 20px;
  padding: 0;
}

.help-spectrum {
  margin-top: 8px;
  font-size: 12px;
  color: #1a5c4c;
}

/* Actions */
.actions {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #e9ecef;
}

.actions ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.actions .btn {
  padding: 10px 25px;
}

/* Validation Alerts */
.alert {
  border-radius: 8px;
  margin-bottom: 20px;
}

.alert h5 {
  margin-bottom: 10px;
}

.alert ul {
  margin: 0;
  padding-left: 20px;
}

/* Autocomplete */
.autocomplete-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 4px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  z-index: 1000;
  max-height: 200px;
  overflow-y: auto;
}

.autocomplete-option {
  padding: 8px 12px;
  cursor: pointer;
}

.autocomplete-option:hover {
  background: #f8f9fa;
}
</style>
<div class="alert alert-danger">
  <h5><i class="fa fa-exclamation-triangle"></i> <?php echo __('Validation Errors'); ?></h5>
  <ul>
    <?php foreach ($validationErrors as $error): ?>
      <li><?php echo $error['message']; ?></li>
    <?php endforeach; ?>
  </ul>
  <p class="mt-2">
    <button type="submit" name="saveAnyway" value="1" form="cco-form" class="btn btn-sm btn-warning">
      <?php echo __('Save anyway'); ?>
    </button>
  </p>
</div>

<?php if (isset($validationWarnings) && !empty($validationWarnings)): ?>
<div class="alert alert-warning">
  <h5><i class="fa fa-exclamation-circle"></i> <?php echo __('Recommended Fields Missing'); ?></h5>
  <ul>
    <?php foreach ($validationWarnings as $warning): ?>
      <li><?php echo $warning['message']; ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php echo $form->renderFormTag(url_for(['module' => 'ahgMuseumPlugin', 'action' => 'edit']), [
  'id' => 'cco-form',
  'class' => 'cco-cataloguing-form'
]); ?>

<?php echo $form->renderHiddenFields(); ?>
<input type="hidden" name="template" value="<?php echo $templateId; ?>">
<?php if ($resourceId): ?>
  <input type="hidden" name="id" value="<?php echo $resourceId; ?>">
<?php endif; ?>
<div class="accordion" id="ccoAccordion">
  <?php
  $accordionIndex = 0;
  foreach ($fieldDefinitions as $categoryId => $category):
    $categoryHasVisibleFields = false;
    foreach ($category['fields'] as $fieldName => $fieldDef) {
      if (ahgCCOTemplates::isFieldVisible($templateId, $fieldName)) {
        $categoryHasVisibleFields = true;
        break;
      }
    }
    if (!$categoryHasVisibleFields) continue;
    $accordionIndex++;
    $collapseId = 'collapse-' . $categoryId;
    $isFirst = ($accordionIndex === 1);
  ?>
  <div class="accordion-item">
    <h2 class="accordion-header" id="heading-<?php echo $categoryId; ?>">
      <button class="accordion-button <?php echo $isFirst ? '' : 'collapsed'; ?>" type="button" 
              data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" 
              aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>" 
              aria-controls="<?php echo $collapseId; ?>">
        <?php echo __($category['label']); ?>
        <span class="cco-chapter"><?php echo __('CCO Chapter %chapter%', ['%chapter%' => $category['ccoChapter']]); ?></span>
      </button>
    </h2>
    <div id="<?php echo $collapseId; ?>" 
         class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>" 
         aria-labelledby="heading-<?php echo $categoryId; ?>" 
         data-bs-parent="#ccoAccordion">
      <div class="accordion-body">
        <?php if (!empty($category['description'])): ?>
          <p class="category-description"><?php echo __($category['description']); ?></p>
        <?php endif; ?>
        
        <?php foreach ($category['fields'] as $fieldName => $fieldDef): ?>
          <?php
          if (!ahgCCOTemplates::isFieldVisible($templateId, $fieldName)) continue;
          $level = ahgCCOTemplates::getFieldLevel($templateId, $fieldName);
          $hasWidget = isset($form[$fieldName]);
          if (!$hasWidget) continue;
          ?>
          <div class="cco-field level-<?php echo $level; ?>" data-field="<?php echo $fieldName; ?>">
            <div class="field-header">
              <label for="<?php echo $fieldName; ?>">
                <?php echo __($fieldDef['label']); ?>
                <?php if ($level === 'required'): ?>
                  <span class="required">*</span>
                <?php endif; ?>
              </label>
              <span class="field-badges">
                <?php if ($level === 'required'): ?>
                  <span class="badge badge-required"><?php echo __('Required'); ?></span>
                <?php elseif ($level === 'recommended'): ?>
                  <span class="badge badge-recommended"><?php echo __('Recommended'); ?></span>
                <?php endif; ?>
                <?php if (!empty($fieldDef['ccoRef'])): ?>
                  <span class="badge badge-cco" title="CCO Reference"><?php echo $fieldDef['ccoRef']; ?></span>
                <?php endif; ?>
                <?php if (!empty($fieldDef['vocabulary'])): ?>
                  <span class="badge badge-vocab" title="Controlled Vocabulary">
                    <i class="fa fa-book"></i> <?php echo strtoupper($fieldDef['vocabulary']); ?>
                  </span>
                <?php endif; ?>
              </span>
              <button type="button" class="btn-help" data-field="<?php echo $fieldName; ?>" title="<?php echo __('Help'); ?>">
                <i class="fa fa-question-circle"></i>
              </button>
            </div>
            <div class="field-input">
              <?php echo $form[$fieldName]->render(); ?>
            </div>
            <div class="field-help" id="help-<?php echo $fieldName; ?>" style="display: none;">
              <div class="help-content">
                <p class="help-text"><?php echo __($fieldDef['helpText']); ?></p>
                <?php if (!empty($fieldDef['longHelp'])): ?>
                  <p class="help-long"><?php echo __($fieldDef['longHelp']); ?></p>
                <?php endif; ?>
                <?php if (!empty($fieldDef['examples'])): ?>
                  <div class="help-examples">
                    <strong><?php echo __('Examples:'); ?></strong>
                    <ul>
                      <?php foreach ($fieldDef['examples'] as $example): ?>
                        <li><?php echo $example; ?></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endif; ?>
                <?php if (!empty($fieldDef['spectrumEquiv'])): ?>
                  <p class="help-spectrum">
                    <i class="fa fa-exchange"></i>
                    <?php echo __('Spectrum equivalent: %field%', ['%field%' => $fieldDef['spectrumEquiv']]); ?>
                  </p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
	  
    </div>
  </div>
  <?php endforeach; ?>
</div>
<!-- Item Physical Location -->
<?php include_partial("informationobject/itemPhysicalLocation", ["resource" => $resource ?? null, "itemLocation" => $itemLocation ?? []]); ?>
<?php include_partial('ahgMuseumPlugin/watermarkSettings', ['resource' => $resource ?? null, 'resourceId' => $resourceId ?? null]); ?>
<!-- Administration Area -->
<div class="accordion mb-3">
<?php include_partial('ahgMuseumPlugin/adminInfo', ['form' => $form, 'resource' => $resource ?? null]); ?>
</div>
<section class="actions">
  <ul>
    <li>
      <input type="submit" class="btn atom-btn-outline-success" value="<?php echo __('Save'); ?>">
    </li>
    <?php if ($resourceId): ?>
      <li>
        <a href="<?php echo '/index.php/' . $resourceSlug; ?>" class="btn atom-btn-outline-light">
          <?php echo __('Cancel'); ?>
        </a>
      </li>
    <?php else: ?>
      <li>
        <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']); ?>" class="btn atom-btn-outline-light">
          <?php echo __('Cancel'); ?>
        </a>
      </li>
    <?php endif; ?>
  </ul>
</section>

</form>
<?php end_slot(); ?>

<?php slot('after-content'); ?>
<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    initCCOForm();
  });

  function initCCOForm() {
    // Help button toggles
    document.querySelectorAll('.btn-help').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        var fieldName = this.dataset.field;
        var helpDiv = document.getElementById('help-' + fieldName);
        
        if (helpDiv.style.display === 'none') {
          document.querySelectorAll('.field-help').forEach(function(h) {
            h.style.display = 'none';
          });
          document.querySelectorAll('.btn-help').forEach(function(b) {
            b.classList.remove('active');
          });
          helpDiv.style.display = 'block';
          this.classList.add('active');
        } else {
          helpDiv.style.display = 'none';
          this.classList.remove('active');
        }
      });
    });

    // Template selector - switch without losing data
    document.querySelectorAll(".template-option").forEach(function(opt) {
      opt.addEventListener("click", function(e) {
        e.preventDefault();
        var newTemplate = this.getAttribute("data-template");
        var form = document.getElementById("cco-form");
        if (form) {
          var templateField = form.querySelector("input[name=\"template\"]");
          if (templateField) {
            templateField.value = newTemplate;
          }
          var switchFlag = document.createElement("input");
          switchFlag.type = "hidden";
          switchFlag.name = "switch_template";
          switchFlag.value = "1";
          form.appendChild(switchFlag);
          form.submit();
        }
      });
    });

    // Mark form as dirty on change
    var form = document.getElementById('cco-form');
    if (form) {
      form.addEventListener('change', function() {
        this.classList.add('dirty');
      });
      form.addEventListener('input', debounce(updateCompleteness, 500));
    }

    updateCompleteness();
    initVocabularyAutocomplete();
  }

  function updateCompleteness() {
    var form = document.getElementById('cco-form');
    if (!form) return;
    
    var fields = form.querySelectorAll('.cco-field:not(.level-hidden)');
    var total = fields.length;
    var filled = 0;
    
    fields.forEach(function(field) {
      var input = field.querySelector('input, select, textarea');
      if (input && input.value && input.value.trim() !== '') {
        filled++;
      }
    });
    
    var pct = total > 0 ? Math.round((filled / total) * 100) : 0;
    var bar = document.querySelector('.progress-bar');
    var value = document.querySelector('.completeness-value');
    
    if (bar && value) {
      bar.style.width = pct + '%';
      value.textContent = pct + '%';
      bar.className = 'progress-bar';
      if (pct >= 80) {
        bar.classList.add('bg-success');
      } else if (pct >= 50) {
        bar.classList.add('bg-warning');
      } else {
        bar.classList.add('bg-danger');
      }
    }
  }

  function getGettyVocab(vocab) {
    if (!vocab) return "aat";
    if (vocab.indexOf("ulan") !== -1) return "ulan";
    if (vocab.indexOf("tgn") !== -1 || vocab.indexOf("place") !== -1) return "tgn";
    return "aat";
  }

  function initVocabularyAutocomplete() {
    document.querySelectorAll(".cco-autocomplete").forEach(function(input) {
      var vocabulary = input.dataset.vocabulary;
      var gettyVocab = getGettyVocab(vocabulary);
      var fieldName = input.name;
      
      new TomSelect(input, {
        valueField: "label",
        labelField: "label",
        searchField: ["label", "scopeNote"],
        create: true,
        maxOptions: 20,
        placeholder: "Type to search or select existing...",
        preload: "focus",
        load: function(query, callback) {
          var self = this;
          var results = [];
          
          // First fetch local values
          fetch("/index.php/ahgMuseumPlugin/vocabulary?field=" + encodeURIComponent(fieldName) + "&query=" + encodeURIComponent(query))
            .then(function(r) { return r.json(); })
            .then(function(localData) {
              if (localData.success && localData.results) {
                localData.results.forEach(function(item) {
                  item.source = "local";
                  item.sourceLabel = "Used " + (item.count || 1) + "x";
                  results.push(item);
                });
              }
              
              // Then fetch Getty if query is long enough
              if (query.length >= 2) {
                return fetch("/index.php/ahgMuseumPlugin/getty?vocabulary=" + gettyVocab + "&query=" + encodeURIComponent(query) + "&limit=10");
              }
              return Promise.resolve(null);
            })
            .then(function(response) {
              if (response) return response.json();
              return null;
            })
            .then(function(gettyData) {
              if (gettyData && gettyData.success && gettyData.results) {
                gettyData.results.forEach(function(item) {
                  // Check if already in local results
                  var exists = results.some(function(r) { 
                    return r.label.toLowerCase() === item.label.toLowerCase(); 
                  });
                  if (!exists) {
                    item.source = "getty";
                    item.sourceLabel = "Getty " + gettyVocab.toUpperCase();
                    results.push(item);
                  }
                });
              }
              callback(results);
            })
            .catch(function(err) {
              console.error("Vocabulary load error:", err);
              callback(results);
            });
        },
        render: {
          option: function(item, escape) {
            var html = '<div class="vocab-option">';
            html += '<div class="d-flex justify-content-between align-items-start">';
            html += '<span class="vocab-label">' + escape(item.label) + '</span>';
            if (item.source === "local") {
              html += '<span class="badge bg-success ms-2">' + escape(item.sourceLabel) + '</span>';
            } else if (item.source === "getty") {
              html += '<span class="badge bg-info ms-2">' + escape(item.sourceLabel) + '</span>';
            }
            html += '</div>';
            if (item.broader) {
              html += '<div class="text-muted small">' + escape(item.broader) + '</div>';
            }
            if (item.scopeNote) {
              html += '<div class="text-muted small" style="max-width:350px;white-space:normal;">' + escape(item.scopeNote.substring(0, 80)) + (item.scopeNote.length > 80 ? '...' : '') + '</div>';
            }
            html += '</div>';
            return html;
          },
          item: function(item, escape) {
            return '<div>' + escape(item.label) + '</div>';
          },
          no_results: function(data, escape) {
            return '<div class="no-results p-2 text-muted">No results for "' + escape(data.input) + '" - type to add custom value</div>';
          }
        }
      });
    });
  }
  function debounce(func, wait) {
    var timeout;
    return function() {
      var context = this, args = arguments;
      clearTimeout(timeout);
      timeout = setTimeout(function() {
        func.apply(context, args);
      }, wait);
    };
  }
})();
</script>
<!-- Select2 for searchable dropdowns -->
<!-- Tom Select for Getty autocomplete -->
<?php end_slot(); ?>
