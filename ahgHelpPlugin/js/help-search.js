/**
 * ahgHelpPlugin — Client-side Instant Search
 *
 * Lazy-loads FlexSearch JSON index on first keystroke,
 * provides debounced instant search with dropdown results.
 * Falls back to server-side AJAX search if FlexSearch unavailable.
 */
(function () {
  'use strict';

  var HelpSearch = {
    index: null,
    documents: [],
    documentsById: {},
    indexLoading: false,
    indexLoaded: false,

    init: function () {
      var searchInputs = document.querySelectorAll(
        '#help-search-main, #help-search-sidebar, #help-search-article, #help-search-results-page'
      );

      for (var i = 0; i < searchInputs.length; i++) {
        this.bindInput(searchInputs[i]);
      }
    },

    bindInput: function (input) {
      var self = this;
      var debounce = null;

      // Find or create dropdown relative to input's parent
      var parent = input.closest('.input-group') || input.parentElement;
      parent.style.position = 'relative';

      var dropdown = parent.querySelector('.help-search-dropdown');
      if (!dropdown) {
        dropdown = document.createElement('div');
        dropdown.className = 'help-search-dropdown d-none';
        parent.appendChild(dropdown);
      }

      input.addEventListener('input', function () {
        clearTimeout(debounce);
        var val = this.value.trim();

        if (val.length < 2) {
          dropdown.classList.add('d-none');
          dropdown.innerHTML = '';
          return;
        }

        debounce = setTimeout(function () {
          self.performSearch(val, dropdown);
        }, 300);
      });

      input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          dropdown.classList.add('d-none');
          dropdown.innerHTML = '';
        }
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function (e) {
        if (!parent.contains(e.target)) {
          dropdown.classList.add('d-none');
        }
      });
    },

    performSearch: function (query, dropdown) {
      var self = this;

      if (!this.indexLoaded && !this.indexLoading) {
        this.loadIndex(function () {
          self.doSearch(query, dropdown);
        });
      } else if (this.indexLoaded) {
        this.doSearch(query, dropdown);
      }
      // If loading, the callback will trigger search
    },

    loadIndex: function (callback) {
      var self = this;
      this.indexLoading = true;

      // Load FlexSearch library if not already loaded
      if (typeof FlexSearch === 'undefined') {
        var script = document.createElement('script');
        script.src = '/plugins/ahgHelpPlugin/js/flexsearch.min.js';
        script.onload = function () {
          self.fetchIndexData(callback);
        };
        script.onerror = function () {
          self.indexLoading = false;
          // Fall back to AJAX search
          if (callback) callback();
        };
        document.head.appendChild(script);
      } else {
        this.fetchIndexData(callback);
      }
    },

    fetchIndexData: function (callback) {
      var self = this;
      var xhr = new XMLHttpRequest();
      xhr.open('GET', '/help/api/search-index');
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.onload = function () {
        if (xhr.status === 200) {
          try {
            var data = JSON.parse(xhr.responseText);
            self.buildIndex(data);
          } catch (e) {
            console.warn('Failed to parse help search index');
          }
        }
        self.indexLoading = false;
        self.indexLoaded = true;
        if (callback) callback();
      };
      xhr.onerror = function () {
        self.indexLoading = false;
        self.indexLoaded = true;
        if (callback) callback();
      };
      xhr.send();
    },

    buildIndex: function (data) {
      if (!data || !data.documents || typeof FlexSearch === 'undefined') return;

      this.documents = data.documents;
      this.documentsById = {};

      for (var i = 0; i < this.documents.length; i++) {
        this.documentsById[this.documents[i].id] = this.documents[i];
      }

      this.index = new FlexSearch.Document({
        document: {
          id: 'id',
          index: ['title', 'headings', 'content'],
          store: ['title', 'slug', 'category', 'subcategory']
        },
        tokenize: 'forward',
        resolution: 9,
        cache: 100
      });

      for (var j = 0; j < this.documents.length; j++) {
        this.index.add(this.documents[j]);
      }
    },

    doSearch: function (query, dropdown) {
      if (this.index) {
        this.flexSearch(query, dropdown);
      } else {
        this.ajaxSearch(query, dropdown);
      }
    },

    flexSearch: function (query, dropdown) {
      var results = this.index.search(query, { limit: 10, enrich: true });

      // Collect unique IDs from all field results
      var seen = {};
      var items = [];

      for (var i = 0; i < results.length; i++) {
        var fieldResults = results[i].result;
        for (var j = 0; j < fieldResults.length; j++) {
          var item = fieldResults[j];
          var id = item.id !== undefined ? item.id : item;
          if (!seen[id]) {
            seen[id] = true;
            var doc = item.doc || this.documentsById[id];
            if (doc) {
              items.push(doc);
            }
          }
        }
      }

      this.renderDropdown(items.slice(0, 10), query, dropdown);
    },

    ajaxSearch: function (query, dropdown) {
      var self = this;
      var xhr = new XMLHttpRequest();
      xhr.open('GET', '/help/api/search?q=' + encodeURIComponent(query) + '&limit=10');
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.onload = function () {
        if (xhr.status === 200) {
          try {
            var data = JSON.parse(xhr.responseText);
            self.renderDropdown(data.results || [], query, dropdown);
          } catch (e) {
            dropdown.classList.add('d-none');
          }
        }
      };
      xhr.send();
    },

    renderDropdown: function (items, query, dropdown) {
      if (!items.length) {
        dropdown.innerHTML = '<div class="search-no-results">No results found</div>';
        dropdown.classList.remove('d-none');
        return;
      }

      var html = '';
      for (var i = 0; i < items.length; i++) {
        var item = items[i];
        var slug = item.slug || '';
        var title = item.title || '';
        var category = item.category || '';

        html += '<a href="/help/article/' + encodeURIComponent(slug) + '" class="search-result-item">';
        html += '<div class="search-result-title">' + this.escapeHtml(title) + '</div>';
        html += '<div class="search-result-category">' + this.escapeHtml(category);
        if (item.subcategory) {
          html += ' / ' + this.escapeHtml(item.subcategory);
        }
        html += '</div>';
        html += '</a>';
      }

      // "View all results" link
      html += '<a href="/help/search?q=' + encodeURIComponent(query) + '" class="search-result-item" style="text-align:center;">';
      html += '<strong>View all results &rarr;</strong>';
      html += '</a>';

      dropdown.innerHTML = html;
      dropdown.classList.remove('d-none');
    },

    escapeHtml: function (str) {
      var div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }
  };

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      HelpSearch.init();
    });
  } else {
    HelpSearch.init();
  }
})();
