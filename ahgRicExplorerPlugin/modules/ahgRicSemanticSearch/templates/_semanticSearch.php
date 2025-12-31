<?php
/**
 * RiC Semantic Search Component
 * 
 * Embeddable search interface for AtoM using natural language queries.
 * Integrates with RiC triplestore via semantic search API.
 */

// Get configuration
$searchApiUrl = sfConfig::get('app_ric_search_api', 'http://localhost:5001/api');
?>

<div id="ric-semantic-search" class="ric-search-widget">
  <!-- Search Header -->
  <div class="ric-search-header">
    <h4>
      <i class="fa fa-search"></i>
      Semantic Search
    </h4>
    <span class="ric-search-badge">RiC-O</span>
  </div>
  
  <!-- Search Input -->
  <div class="ric-search-input-wrapper">
    <input 
      type="text" 
      id="ric-search-input" 
      class="ric-search-input"
      placeholder="Try: 'records created by John Smith' or 'documents about mining'"
      autocomplete="off"
    />
    <button type="button" id="ric-search-btn" class="ric-search-button">
      <i class="fa fa-arrow-right"></i>
    </button>
    <!-- Suggestions dropdown -->
    <div id="ric-search-suggestions" class="ric-suggestions-dropdown"></div>
  </div>
  
  <!-- Example queries -->
  <div class="ric-search-examples">
    <span class="ric-examples-label">Try:</span>
    <button class="ric-example-btn" data-query="all fonds">All fonds</button>
    <button class="ric-example-btn" data-query="records from 1950-1990">1950-1990</button>
    <button class="ric-example-btn" data-query="heritage assets">Heritage assets</button>
  </div>
  
  <!-- Results container -->
  <div id="ric-search-results" class="ric-results-container" style="display: none;">
    <div class="ric-results-header">
      <span id="ric-results-count"></span>
      <button type="button" id="ric-clear-results" class="ric-clear-btn">
        <i class="fa fa-times"></i> Clear
      </button>
    </div>
    <div id="ric-results-list" class="ric-results-list"></div>
    <div id="ric-results-facets" class="ric-facets"></div>
  </div>
  
  <!-- Loading indicator -->
  <div id="ric-search-loading" class="ric-loading" style="display: none;">
    <i class="fa fa-spinner fa-spin"></i> Searching...
  </div>
  
  <!-- SPARQL toggle (advanced) -->
  <div class="ric-advanced-toggle">
    <button type="button" id="ric-show-sparql" class="ric-sparql-toggle">
      <i class="fa fa-code"></i> Show SPARQL
    </button>
  </div>
  <pre id="ric-sparql-display" class="ric-sparql-code" style="display: none;"></pre>
</div>

<style>
.ric-search-widget {
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.ric-search-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.ric-search-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #1B4F72;
}

.ric-search-header h4 i {
  margin-right: 8px;
  color: #2E86AB;
}

.ric-search-badge {
  background: linear-gradient(135deg, #1B4F72, #2E86AB);
  color: white;
  font-size: 10px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.ric-search-input-wrapper {
  position: relative;
  display: flex;
  gap: 8px;
  margin-bottom: 10px;
}

.ric-search-input {
  flex: 1;
  padding: 12px 16px;
  font-size: 14px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.ric-search-input:focus {
  border-color: #2E86AB;
  box-shadow: 0 0 0 3px rgba(46, 134, 171, 0.15);
}

.ric-search-input::placeholder {
  color: #999;
  font-size: 13px;
}

.ric-search-button {
  padding: 12px 20px;
  background: linear-gradient(135deg, #1B4F72, #2E86AB);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: transform 0.1s, box-shadow 0.2s;
}

.ric-search-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(27, 79, 114, 0.3);
}

.ric-search-button:active {
  transform: translateY(0);
}

.ric-suggestions-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 60px;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
  z-index: 1000;
  display: none;
  max-height: 200px;
  overflow-y: auto;
}

.ric-suggestion-item {
  padding: 10px 16px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.ric-suggestion-item:last-child {
  border-bottom: none;
}

.ric-suggestion-item:hover {
  background: #f8f9fa;
}

.ric-suggestion-type {
  font-size: 10px;
  background: #e8f4f8;
  color: #2E86AB;
  padding: 2px 6px;
  border-radius: 4px;
  text-transform: uppercase;
}

.ric-search-examples {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}

.ric-examples-label {
  font-size: 12px;
  color: #666;
}

.ric-example-btn {
  padding: 4px 10px;
  font-size: 12px;
  background: #f0f4f8;
  color: #1B4F72;
  border: 1px solid #d0dce8;
  border-radius: 15px;
  cursor: pointer;
  transition: all 0.2s;
}

.ric-example-btn:hover {
  background: #1B4F72;
  color: white;
  border-color: #1B4F72;
}

.ric-results-container {
  border-top: 1px solid #e0e0e0;
  padding-top: 12px;
  margin-top: 12px;
}

.ric-results-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.ric-results-header span {
  font-size: 14px;
  color: #666;
}

.ric-clear-btn {
  padding: 4px 10px;
  font-size: 12px;
  background: #f8f9fa;
  color: #666;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
}

.ric-clear-btn:hover {
  background: #e9ecef;
}

.ric-results-list {
  max-height: 400px;
  overflow-y: auto;
}

.ric-result-item {
  padding: 12px;
  border: 1px solid #e8e8e8;
  border-radius: 6px;
  margin-bottom: 8px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.ric-result-item:hover {
  border-color: #2E86AB;
  box-shadow: 0 2px 8px rgba(46, 134, 171, 0.1);
}

.ric-result-title {
  font-weight: 600;
  color: #1B4F72;
  margin-bottom: 4px;
  text-decoration: none;
  display: block;
}

.ric-result-title:hover {
  text-decoration: underline;
}

.ric-result-meta {
  font-size: 12px;
  color: #888;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.ric-result-meta span {
  display: flex;
  align-items: center;
  gap: 4px;
}

.ric-result-type {
  display: inline-block;
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 3px;
  text-transform: uppercase;
  font-weight: 500;
}

.ric-type-recordset { background: #e3f2fd; color: #1565c0; }
.ric-type-record { background: #e8f5e9; color: #2e7d32; }
.ric-type-person { background: #fff3e0; color: #e65100; }
.ric-type-corporatebody { background: #fce4ec; color: #c2185b; }
.ric-type-family { background: #f3e5f5; color: #7b1fa2; }
.ric-type-place { background: #e0f7fa; color: #00838f; }

.ric-facets {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #eee;
}

.ric-facet-group {
  margin-bottom: 8px;
}

.ric-facet-label {
  font-size: 11px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  margin-bottom: 4px;
}

.ric-facet-values {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.ric-facet-item {
  font-size: 11px;
  padding: 2px 8px;
  background: #f0f0f0;
  border-radius: 10px;
  color: #555;
}

.ric-loading {
  text-align: center;
  padding: 20px;
  color: #666;
}

.ric-loading i {
  margin-right: 8px;
  color: #2E86AB;
}

.ric-advanced-toggle {
  margin-top: 12px;
  text-align: right;
}

.ric-sparql-toggle {
  padding: 4px 10px;
  font-size: 11px;
  background: transparent;
  color: #888;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
}

.ric-sparql-toggle:hover {
  background: #f8f9fa;
  color: #555;
}

.ric-sparql-code {
  margin-top: 10px;
  padding: 12px;
  background: #1e1e1e;
  color: #d4d4d4;
  font-family: 'Monaco', 'Menlo', monospace;
  font-size: 11px;
  border-radius: 6px;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-word;
}

/* No results */
.ric-no-results {
  text-align: center;
  padding: 30px;
  color: #888;
}

.ric-no-results i {
  font-size: 32px;
  margin-bottom: 10px;
  display: block;
  color: #ccc;
}
</style>

<script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
(function() {
  'use strict';
  
  var API_URL = '<?php echo $searchApiUrl; ?>';
  var searchInput = document.getElementById('ric-search-input');
  var searchBtn = document.getElementById('ric-search-btn');
  var resultsContainer = document.getElementById('ric-search-results');
  var resultsList = document.getElementById('ric-results-list');
  var resultsCount = document.getElementById('ric-results-count');
  var resultsFacets = document.getElementById('ric-results-facets');
  var loadingIndicator = document.getElementById('ric-search-loading');
  var suggestionsDropdown = document.getElementById('ric-search-suggestions');
  var sparqlDisplay = document.getElementById('ric-sparql-display');
  var currentSparql = '';
  
  // Type colors
  var typeClasses = {
    'RecordSet': 'ric-type-recordset',
    'Record': 'ric-type-record',
    'RecordPart': 'ric-type-record',
    'Person': 'ric-type-person',
    'CorporateBody': 'ric-type-corporatebody',
    'Family': 'ric-type-family',
    'Place': 'ric-type-place'
  };
  
  // Perform search
  function performSearch(query) {
    if (!query.trim()) return;
    
    loadingIndicator.style.display = 'block';
    resultsContainer.style.display = 'none';
    suggestionsDropdown.style.display = 'none';
    
    fetch(API_URL + '/search', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ query: query })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
      loadingIndicator.style.display = 'none';
      displayResults(data);
      currentSparql = data.sparql || '';
      sparqlDisplay.textContent = currentSparql;
    })
    .catch(function(error) {
      loadingIndicator.style.display = 'none';
      console.error('Search error:', error);
      resultsList.innerHTML = '<div class="ric-no-results"><i class="fa fa-exclamation-circle"></i>Search failed. Please try again.</div>';
      resultsContainer.style.display = 'block';
    });
  }
  
  // Display results
  function displayResults(data) {
    resultsContainer.style.display = 'block';
    resultsCount.textContent = data.count + ' result' + (data.count !== 1 ? 's' : '') + ' found';
    
    if (!data.results || data.results.length === 0) {
      resultsList.innerHTML = '<div class="ric-no-results"><i class="fa fa-folder-open-o"></i>No results found.<br>Try a different search term.</div>';
      resultsFacets.innerHTML = '';
      return;
    }
    
    // Build results HTML
    var html = '';
    data.results.forEach(function(result) {
      var typeClass = typeClasses[result.type] || 'ric-type-recordset';
      
      html += '<div class="ric-result-item">';
      html += '<a href="' + escapeHtml(result.atomUrl || '#') + '" class="ric-result-title">' + escapeHtml(result.title) + '</a>';
      html += '<div class="ric-result-meta">';
      html += '<span class="ric-result-type ' + typeClass + '">' + escapeHtml(result.type) + '</span>';
      
      if (result.identifier) {
        html += '<span><i class="fa fa-tag"></i> ' + escapeHtml(result.identifier) + '</span>';
      }
      if (result.date) {
        html += '<span><i class="fa fa-calendar"></i> ' + escapeHtml(result.date) + '</span>';
      }
      if (result.creator) {
        html += '<span><i class="fa fa-user"></i> ' + escapeHtml(result.creator) + '</span>';
      }
      if (result.place) {
        html += '<span><i class="fa fa-map-marker"></i> ' + escapeHtml(result.place) + '</span>';
      }
      
      html += '</div></div>';
    });
    
    resultsList.innerHTML = html;
    
    // Build facets
    if (data.facets && Object.keys(data.facets).length > 0) {
      var facetHtml = '';
      
      if (data.facets.type) {
        facetHtml += '<div class="ric-facet-group"><div class="ric-facet-label">Types</div><div class="ric-facet-values">';
        for (var type in data.facets.type) {
          facetHtml += '<span class="ric-facet-item">' + type + ' (' + data.facets.type[type] + ')</span>';
        }
        facetHtml += '</div></div>';
      }
      
      if (data.facets.decade) {
        facetHtml += '<div class="ric-facet-group"><div class="ric-facet-label">Decades</div><div class="ric-facet-values">';
        var decades = Object.keys(data.facets.decade).sort();
        decades.forEach(function(decade) {
          facetHtml += '<span class="ric-facet-item">' + decade + ' (' + data.facets.decade[decade] + ')</span>';
        });
        facetHtml += '</div></div>';
      }
      
      resultsFacets.innerHTML = facetHtml;
    } else {
      resultsFacets.innerHTML = '';
    }
  }
  
  // Fetch suggestions
  var suggestionTimeout;
  function fetchSuggestions(query) {
    clearTimeout(suggestionTimeout);
    
    if (query.length < 2) {
      suggestionsDropdown.style.display = 'none';
      return;
    }
    
    suggestionTimeout = setTimeout(function() {
      fetch(API_URL + '/suggest?q=' + encodeURIComponent(query))
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.suggestions && data.suggestions.length > 0) {
            var html = '';
            data.suggestions.forEach(function(s) {
              html += '<div class="ric-suggestion-item" data-query="' + escapeHtml(s.text) + '">';
              html += '<span>' + escapeHtml(s.text) + '</span>';
              html += '<span class="ric-suggestion-type">' + escapeHtml(s.type) + '</span>';
              html += '</div>';
            });
            suggestionsDropdown.innerHTML = html;
            suggestionsDropdown.style.display = 'block';
            
            // Add click handlers
            suggestionsDropdown.querySelectorAll('.ric-suggestion-item').forEach(function(item) {
              item.addEventListener('click', function() {
                searchInput.value = this.dataset.query;
                suggestionsDropdown.style.display = 'none';
                performSearch(this.dataset.query);
              });
            });
          } else {
            suggestionsDropdown.style.display = 'none';
          }
        })
        .catch(function() {
          suggestionsDropdown.style.display = 'none';
        });
    }, 200);
  }
  
  // Escape HTML
  function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  // Event listeners
  searchBtn.addEventListener('click', function() {
    performSearch(searchInput.value);
  });
  
  searchInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
      performSearch(searchInput.value);
    }
  });
  
  searchInput.addEventListener('input', function() {
    fetchSuggestions(this.value);
  });
  
  // Click outside to close suggestions
  document.addEventListener('click', function(e) {
    if (!suggestionsDropdown.contains(e.target) && e.target !== searchInput) {
      suggestionsDropdown.style.display = 'none';
    }
  });
  
  // Example query buttons
  document.querySelectorAll('.ric-example-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      searchInput.value = this.dataset.query;
      performSearch(this.dataset.query);
    });
  });
  
  // Clear results
  document.getElementById('ric-clear-results').addEventListener('click', function() {
    resultsContainer.style.display = 'none';
    searchInput.value = '';
    searchInput.focus();
  });
  
  // Toggle SPARQL display
  document.getElementById('ric-show-sparql').addEventListener('click', function() {
    if (sparqlDisplay.style.display === 'none') {
      sparqlDisplay.style.display = 'block';
      this.innerHTML = '<i class="fa fa-code"></i> Hide SPARQL';
    } else {
      sparqlDisplay.style.display = 'none';
      this.innerHTML = '<i class="fa fa-code"></i> Show SPARQL';
    }
  });
})();
</script>
