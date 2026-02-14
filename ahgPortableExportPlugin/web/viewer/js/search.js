/**
 * Portable Catalogue Viewer — Search Engine
 *
 * Client-side full-text search using FlexSearch.
 * Loads pre-built search index from data/search-index.json.
 */
(function () {
  'use strict';

  var SearchEngine = {
    index: null,
    documents: [],
    documentsById: {},
    onSelect: null,

    /**
     * Initialize the search engine with index data.
     *
     * @param {Object} indexData Data from search-index.json
     * @param {Function} onSelect Callback when a result is selected: fn(id)
     */
    init: function (indexData, onSelect) {
      this.onSelect = onSelect;

      if (!indexData || !indexData.documents) {
        console.warn('No search index data available');
        return;
      }

      this.documents = indexData.documents;
      this.buildLookup();
      this.buildIndex();
      this.bindEvents();
    },

    /**
     * Build lookup map by document ID.
     */
    buildLookup: function () {
      this.documentsById = {};
      for (var i = 0; i < this.documents.length; i++) {
        this.documentsById[this.documents[i].id] = this.documents[i];
      }
    },

    /**
     * Build FlexSearch index from documents.
     */
    buildIndex: function () {
      if (typeof FlexSearch === 'undefined') {
        console.warn('FlexSearch library not loaded');
        return;
      }

      // Create a FlexSearch Document index
      this.index = new FlexSearch.Document({
        document: {
          id: 'id',
          index: ['title', 'identifier', 'content', 'creators', 'subjects', 'places', 'dates'],
          store: ['title', 'identifier', 'level', 'content']
        },
        tokenize: 'forward',
        resolution: 9,
        cache: 100
      });

      // Add all documents
      for (var i = 0; i < this.documents.length; i++) {
        this.index.add(this.documents[i]);
      }
    },

    /**
     * Bind search UI events.
     */
    bindEvents: function () {
      var self = this;
      var input = document.getElementById('search-input');
      var btn = document.getElementById('btn-search');

      if (input) {
        input.addEventListener('keyup', function (e) {
          if (e.key === 'Enter') {
            self.search(this.value);
          }
        });

        // Auto-search after typing pause
        var debounce = null;
        input.addEventListener('input', function () {
          clearTimeout(debounce);
          var val = this.value;
          debounce = setTimeout(function () {
            if (val.length >= 2) {
              self.search(val);
            } else {
              self.clearResults();
            }
          }, 300);
        });
      }

      if (btn) {
        btn.addEventListener('click', function () {
          self.search(input.value);
        });
      }
    },

    /**
     * Perform a search and display results.
     */
    search: function (query) {
      if (!query || query.length < 2) {
        this.clearResults();
        return;
      }

      var resultIds = this.executeSearch(query);
      this.displayResults(resultIds, query);
    },

    /**
     * Execute search against FlexSearch index.
     * Returns array of unique document IDs.
     */
    executeSearch: function (query) {
      if (!this.index) {
        // Fallback: simple text search
        return this.fallbackSearch(query);
      }

      var results = this.index.search(query, { limit: 100 });
      var idSet = {};
      var ids = [];

      // FlexSearch Document returns results per field
      for (var i = 0; i < results.length; i++) {
        var fieldResults = results[i].result || [];
        for (var j = 0; j < fieldResults.length; j++) {
          var id = fieldResults[j];
          if (!idSet[id]) {
            idSet[id] = true;
            ids.push(id);
          }
        }
      }

      return ids;
    },

    /**
     * Fallback search without FlexSearch (simple substring match).
     */
    fallbackSearch: function (query) {
      var q = query.toLowerCase();
      var ids = [];

      for (var i = 0; i < this.documents.length; i++) {
        var doc = this.documents[i];
        var text = (doc.title + ' ' + doc.identifier + ' ' + doc.content + ' ' +
                    doc.creators + ' ' + doc.subjects + ' ' + doc.places).toLowerCase();
        if (text.indexOf(q) !== -1) {
          ids.push(doc.id);
          if (ids.length >= 100) break;
        }
      }

      return ids;
    },

    /**
     * Display search results.
     */
    displayResults: function (ids, query) {
      var container = document.getElementById('search-results');
      if (!container) return;

      if (ids.length === 0) {
        container.innerHTML = '<div class="text-center py-3 text-muted"><i class="bi bi-search me-1"></i>No results found for "' + this.esc(query) + '"</div>';
        return;
      }

      var html = '<div class="mb-2 text-muted small">' + ids.length + ' result' + (ids.length !== 1 ? 's' : '') + ' for "' + this.esc(query) + '"</div>';
      html += '<div class="list-group">';

      for (var i = 0; i < ids.length; i++) {
        var doc = this.documentsById[ids[i]];
        if (!doc) continue;

        html += '<a href="#" class="list-group-item list-group-item-action" data-result-id="' + doc.id + '">';
        html += '<div class="d-flex justify-content-between align-items-start">';
        html += '<div>';
        html += '<h6 class="mb-1">' + this.highlightMatch(doc.title || '[Untitled]', query) + '</h6>';

        if (doc.identifier) {
          html += '<span class="badge bg-secondary me-2">' + this.esc(doc.identifier) + '</span>';
        }
        if (doc.level) {
          html += '<span class="badge bg-primary">' + this.esc(doc.level) + '</span>';
        }

        // Show snippet of content
        if (doc.content) {
          var snippet = this.getSnippet(doc.content, query, 150);
          if (snippet) {
            html += '<p class="mb-0 mt-1 small text-muted">' + this.highlightMatch(snippet, query) + '</p>';
          }
        }

        html += '</div>';
        html += '</div>';
        html += '</a>';
      }

      html += '</div>';
      container.innerHTML = html;

      // Bind result clicks
      var self = this;
      container.querySelectorAll('[data-result-id]').forEach(function (el) {
        el.addEventListener('click', function (e) {
          e.preventDefault();
          var id = parseInt(this.getAttribute('data-result-id'), 10);
          if (self.onSelect) self.onSelect(id);
        });
      });
    },

    /**
     * Clear search results.
     */
    clearResults: function () {
      var container = document.getElementById('search-results');
      if (container) container.innerHTML = '';
    },

    /**
     * Get a text snippet around the query match.
     */
    getSnippet: function (text, query, maxLen) {
      if (!text) return '';
      var lower = text.toLowerCase();
      var idx = lower.indexOf(query.toLowerCase());

      if (idx === -1) {
        return text.substring(0, maxLen) + (text.length > maxLen ? '...' : '');
      }

      var start = Math.max(0, idx - 50);
      var end = Math.min(text.length, idx + query.length + 100);
      var snippet = '';

      if (start > 0) snippet += '...';
      snippet += text.substring(start, end);
      if (end < text.length) snippet += '...';

      return snippet;
    },

    /**
     * Highlight query matches in text.
     */
    highlightMatch: function (text, query) {
      if (!text || !query) return this.esc(text);
      var escaped = this.esc(text);
      var q = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      var regex = new RegExp('(' + q + ')', 'gi');
      return escaped.replace(regex, '<mark>$1</mark>');
    },

    /**
     * Escape HTML.
     */
    esc: function (str) {
      if (!str) return '';
      var div = document.createElement('div');
      div.appendChild(document.createTextNode(String(str)));
      return div.innerHTML;
    }
  };

  window.SearchEngine = SearchEngine;
})();
