<?php
/**
 * RiC Search Widget Integration
 * 
 * Add this include to your AtoM layout template to display the
 * semantic search widget in the sidebar.
 * 
 * Integration points:
 * 
 * 1. Layout sidebar (apps/qubit/templates/layout.php):
 *    <?php include_component('ahgRicExplorer', 'searchWidget'); ?>
 * 
 * 2. Browse page (plugins/arDominionB5Plugin/templates/layout.php):
 *    Add after the main search form
 * 
 * 3. Information object sidebar (_sidebar.php):
 *    <?php include_component('ahgRicExplorer', 'searchWidget', ['compact' => true]); ?>
 */

// Check if plugin is enabled
if (!sfConfig::get('app_ric_enable_search_widget', true)) {
    return;
}

$compact = isset($compact) ? $compact : false;
$searchApiUrl = sfConfig::get('app_ric_search_api', 'http://localhost:5001/api');
?>

<div id="ric-sidebar-search" class="ric-widget <?php echo $compact ? 'ric-compact' : ''; ?>">
  <div class="ric-widget-header">
    <h5>
      <i class="fa fa-search"></i>
      <?php echo __('Semantic Search'); ?>
    </h5>
    <span class="ric-badge">RiC</span>
  </div>
  
  <div class="ric-widget-body">
    <div class="ric-search-form">
      <input 
        type="text" 
        id="ric-sidebar-input"
        class="ric-input"
        placeholder="<?php echo __('e.g., records about mining'); ?>"
      />
      <button type="button" id="ric-sidebar-btn" class="ric-btn">
        <i class="fa fa-arrow-right"></i>
      </button>
    </div>
    
    <?php if (!$compact): ?>
    <div class="ric-examples">
      <span class="ric-examples-label"><?php echo __('Try:'); ?></span>
      <a href="#" class="ric-example" data-q="all fonds">fonds</a>
      <a href="#" class="ric-example" data-q="heritage assets">heritage</a>
    </div>
    <?php endif; ?>
    
    <div id="ric-sidebar-results" class="ric-results" style="display: none;"></div>
  </div>
  
  <div class="ric-widget-footer">
    <a href="<?php echo url_for('@ric_semantic_search'); ?>" class="ric-advanced-link">
      <i class="fa fa-external-link"></i>
      <?php echo __('Advanced Search'); ?>
    </a>
  </div>
</div>

<style>
.ric-widget {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 6px;
  margin-bottom: 15px;
  overflow: hidden;
}

.ric-widget-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  background: linear-gradient(135deg, #1B4F72, #2E86AB);
  color: white;
}

.ric-widget-header h5 {
  margin: 0;
  font-size: 13px;
  font-weight: 600;
}

.ric-widget-header h5 i {
  margin-right: 6px;
}

.ric-badge {
  background: rgba(255,255,255,0.2);
  padding: 2px 6px;
  border-radius: 8px;
  font-size: 9px;
  font-weight: 600;
}

.ric-widget-body {
  padding: 12px;
}

.ric-search-form {
  display: flex;
  gap: 6px;
}

.ric-input {
  flex: 1;
  padding: 8px 10px;
  font-size: 13px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.ric-input:focus {
  border-color: #2E86AB;
  outline: none;
}

.ric-btn {
  padding: 8px 12px;
  background: #1B4F72;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.ric-btn:hover {
  background: #154360;
}

.ric-examples {
  margin-top: 8px;
  font-size: 11px;
}

.ric-examples-label {
  color: #888;
}

.ric-example {
  color: #2E86AB;
  margin-left: 5px;
}

.ric-results {
  margin-top: 10px;
  max-height: 200px;
  overflow-y: auto;
}

.ric-result-item {
  padding: 8px 0;
  border-bottom: 1px solid #eee;
  font-size: 12px;
}

.ric-result-item:last-child {
  border-bottom: none;
}

.ric-result-item a {
  color: #1B4F72;
  font-weight: 500;
}

.ric-widget-footer {
  padding: 8px 12px;
  background: #f8f9fa;
  border-top: 1px solid #eee;
  text-align: center;
}

.ric-advanced-link {
  font-size: 11px;
  color: #666;
}

.ric-advanced-link:hover {
  color: #1B4F72;
}

.ric-compact .ric-widget-body {
  padding: 8px;
}

.ric-loading {
  text-align: center;
  padding: 15px;
  color: #888;
}
</style>

<script>
(function() {
  var API = '<?php echo $searchApiUrl; ?>';
  var input = document.getElementById('ric-sidebar-input');
  var btn = document.getElementById('ric-sidebar-btn');
  var results = document.getElementById('ric-sidebar-results');
  
  function search(q) {
    if (!q) return;
    results.innerHTML = '<div class="ric-loading"><i class="fa fa-spinner fa-spin"></i></div>';
    results.style.display = 'block';
    
    fetch(API + '/search', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({query: q})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.results || data.results.length === 0) {
        results.innerHTML = '<div class="ric-no-results">No results</div>';
        return;
      }
      var html = '';
      data.results.slice(0, 5).forEach(function(r) {
        html += '<div class="ric-result-item"><a href="' + (r.atomUrl || '#') + '">' + r.title + '</a></div>';
      });
      if (data.count > 5) {
        html += '<div class="ric-result-item"><a href="/ric/search?q=' + encodeURIComponent(q) + '">View all ' + data.count + ' results →</a></div>';
      }
      results.innerHTML = html;
    })
    .catch(function() {
      results.innerHTML = '<div class="ric-error">Search failed</div>';
    });
  }
  
  btn.addEventListener('click', function() { search(input.value); });
  input.addEventListener('keypress', function(e) { if (e.key === 'Enter') search(input.value); });
  
  document.querySelectorAll('.ric-example').forEach(function(el) {
    el.addEventListener('click', function(e) {
      e.preventDefault();
      input.value = this.dataset.q;
      search(this.dataset.q);
    });
  });
})();
</script>
