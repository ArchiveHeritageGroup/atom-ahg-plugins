<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <?php echo __('Semantic Search'); ?>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="card mb-4">
  <div class="card-header bg-primary text-white">
    <h5 class="mb-0">
      <i class="fas fa-project-diagram me-2"></i>
      <?php echo __('Semantic Search'); ?>
    </h5>
    <small><?php echo __('Search the archives using natural language'); ?></small>
  </div>
  <div class="card-body">
    <!-- Search Input -->
    <div class="row mb-3">
      <div class="col">
        <div class="input-group input-group-lg">
          <input 
            type="text" 
            id="ric-search-input" 
            class="form-control"
            placeholder="<?php echo __('e.g., records created by Hennie Pieterse'); ?>"
            autocomplete="off"
          />
          <button type="button" id="ric-search-btn" class="btn btn-primary">
            <i class="fas fa-search me-1"></i>
            <?php echo __('Search'); ?>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Quick Examples -->
    <div class="mb-3">
      <small class="text-muted me-2"><?php echo __('Try:'); ?></small>
      <button class="btn btn-sm btn-outline-secondary ric-example" data-query="all fonds">All fonds</button>
      <button class="btn btn-sm btn-outline-secondary ric-example" data-query="records from 1948-1994">Apartheid era</button>
      <button class="btn btn-sm btn-outline-secondary ric-example" data-query="records about mining">Mining</button>
      <button class="btn btn-sm btn-outline-secondary ric-example" data-query="heritage assets">Heritage assets</button>
    </div>
  </div>
</div>

<!-- Results -->
<div id="ric-results-container" class="card" style="display: none;">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span id="ric-result-count">0 results</span>
    <button type="button" id="ric-clear-btn" class="btn btn-sm btn-outline-secondary">
      <i class="fas fa-times me-1"></i><?php echo __('Clear'); ?>
    </button>
  </div>
  <div class="card-body">
    <div id="ric-results-list"></div>
  </div>
</div>

<!-- Loading -->
<div id="ric-loading" class="text-center py-5" style="display: none;">
  <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
  <p class="mt-2 text-muted"><?php echo __('Searching...'); ?></p>
</div>

<!-- Help Section -->
<div id="ric-help" class="row mt-4">
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body text-center">
        <i class="fas fa-user fa-2x text-primary mb-3"></i>
        <h6><?php echo __('By Creator'); ?></h6>
        <p class="small text-muted"><?php echo __('Find records by who created them'); ?></p>
        <code class="small">records created by John Smith</code>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body text-center">
        <i class="fas fa-book fa-2x text-primary mb-3"></i>
        <h6><?php echo __('By Subject'); ?></h6>
        <p class="small text-muted"><?php echo __('Find records about a topic'); ?></p>
        <code class="small">records about agriculture</code>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body text-center">
        <i class="fas fa-calendar fa-2x text-primary mb-3"></i>
        <h6><?php echo __('By Date'); ?></h6>
        <p class="small text-muted"><?php echo __('Find records from a time period'); ?></p>
        <code class="small">records between 1960-1980</code>
      </div>
    </div>
  </div>
</div>

<!-- SPARQL Display -->
<div class="mt-4">
  <button type="button" id="ric-sparql-toggle" class="btn btn-sm btn-link text-muted">
    <i class="fas fa-code me-1"></i><?php echo __('View SPARQL Query'); ?>
  </button>
  <pre id="ric-sparql-code" class="bg-dark text-light p-3 rounded mt-2" style="display: none; font-size: 12px;"></pre>
</div>

<script>
(function() {
  var API_URL = '<?php echo $searchApiUrl; ?>';
  var input = document.getElementById('ric-search-input');
  var btn = document.getElementById('ric-search-btn');
  var resultsContainer = document.getElementById('ric-results-container');
  var resultsList = document.getElementById('ric-results-list');
  var resultCount = document.getElementById('ric-result-count');
  var loading = document.getElementById('ric-loading');
  var help = document.getElementById('ric-help');
  var sparqlCode = document.getElementById('ric-sparql-code');
  
  function search(query) {
    if (!query.trim()) return;
    
    loading.style.display = 'block';
    resultsContainer.style.display = 'none';
    help.style.display = 'none';
    
    fetch(API_URL + '/search', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({query: query})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      loading.style.display = 'none';
      displayResults(data);
      sparqlCode.textContent = data.sparql || '';
    })
    .catch(function(err) {
      loading.style.display = 'none';
      resultsContainer.style.display = 'block';
      resultsList.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Search failed. Please try again.'); ?></div>';
    });
  }
  
  function displayResults(data) {
    resultsContainer.style.display = 'block';
    resultCount.textContent = data.count + ' result' + (data.count !== 1 ? 's' : '');
    
    if (!data.results || data.results.length === 0) {
      resultsList.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><?php echo __('No results found. Try different keywords.'); ?></div>';
      return;
    }
    
    var html = '<div class="list-group">';
    data.results.forEach(function(r) {
      var icon = getIcon(r.type);
      var badge = getBadge(r.type);
      
      html += '<a href="' + (r.atomUrl || '#') + '" class="list-group-item list-group-item-action">';
      html += '<div class="d-flex w-100 justify-content-between align-items-start">';
      html += '<div>';
      html += '<i class="fas ' + icon + ' me-2 text-muted"></i>';
      html += '<strong>' + escapeHtml(r.title) + '</strong>';
      html += '<span class="badge ' + badge + ' ms-2">' + r.type + '</span>';
      html += '</div>';
      html += '</div>';
      
      if (r.identifier || r.date || r.creator) {
        html += '<small class="text-muted">';
        if (r.identifier) html += '<i class="fas fa-tag me-1"></i>' + escapeHtml(r.identifier) + ' ';
        if (r.date) html += '<i class="fas fa-calendar me-1"></i>' + escapeHtml(r.date) + ' ';
        if (r.creator) html += '<i class="fas fa-user me-1"></i>' + escapeHtml(r.creator);
        html += '</small>';
      }
      
      html += '</a>';
    });
    html += '</div>';
    
    resultsList.innerHTML = html;
  }
  
  function getIcon(type) {
    var icons = {
      'RecordSet': 'fa-folder',
      'Record': 'fa-file-alt',
      'Person': 'fa-user',
      'CorporateBody': 'fa-building',
      'Family': 'fa-users',
      'Place': 'fa-map-marker-alt'
    };
    return icons[type] || 'fa-file';
  }
  
  function getBadge(type) {
    var badges = {
      'RecordSet': 'bg-primary',
      'Record': 'bg-success',
      'Person': 'bg-warning text-dark',
      'CorporateBody': 'bg-info',
      'Family': 'bg-secondary'
    };
    return badges[type] || 'bg-secondary';
  }
  
  function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  // Event listeners
  btn.addEventListener('click', function() { search(input.value); });
  input.addEventListener('keypress', function(e) { if (e.key === 'Enter') search(input.value); });
  
  document.querySelectorAll('.ric-example').forEach(function(el) {
    el.addEventListener('click', function() {
      input.value = this.dataset.query;
      search(this.dataset.query);
    });
  });
  
  document.getElementById('ric-clear-btn').addEventListener('click', function() {
    resultsContainer.style.display = 'none';
    help.style.display = 'flex';
    input.value = '';
    input.focus();
  });
  
  document.getElementById('ric-sparql-toggle').addEventListener('click', function() {
    var code = document.getElementById('ric-sparql-code');
    code.style.display = code.style.display === 'none' ? 'block' : 'none';
  });
  
  input.focus();
})();
</script>

<?php end_slot(); ?>
