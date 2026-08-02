/**
 * ahgHelpPlugin — Contextual Help Button & Offcanvas Panel
 *
 * Injects a floating "?" help button on pages that have contextual help mappings.
 * On click, opens a Bootstrap 5 offcanvas panel with the relevant help article.
 * Fetches context mappings from /help/api/context-map on first load.
 */
(function () {
  'use strict';

  function esc(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  var HelpContext = {
    mappings: null,
    offcanvas: null,
    button: null,
    currentMapping: null,
    pendingOpen: false,

    init: function () {
      // Don't show on help pages themselves
      if (window.location.pathname.indexOf('/help') === 0) return;

      this.bindShortcut();
      this.fetchContextMap();
    },

    /**
     * F1 opens help for the current page in the offcanvas panel, and closes it
     * again on a second press. Where the page has no contextual mapping we fall
     * back to the full help centre in a new tab.
     */
    bindShortcut: function () {
      var self = this;

      document.addEventListener('keydown', function (e) {
        if (e.key !== 'F1') return;

        // Leave the browser's own help alone when a modifier is held.
        if (e.ctrlKey || e.altKey || e.shiftKey || e.metaKey) return;

        e.preventDefault();
        self.toggleHelp();
      });
    },

    toggleHelp: function () {
      // Already open: close it.
      if (this.offcanvas && this.offcanvas.classList.contains('show')) {
        var instance = bootstrap.Offcanvas.getInstance(this.offcanvas);
        if (instance) {
          instance.hide();

          return;
        }
      }

      // The context map may not have arrived yet; open as soon as it does.
      if (this.mappings === null) {
        this.pendingOpen = true;

        return;
      }

      if (this.currentMapping) {
        this.openOffcanvas(this.currentMapping);
      } else {
        this.openSuggestions();
      }
    },

    /**
     * Pages outside the curated context map still get in-page help: ask the
     * server what is relevant to this path. A confident single match opens
     * directly, anything else is offered as a list inside the panel.
     */
    openSuggestions: function () {
      var self = this;

      if (!this.offcanvas) {
        this.createOffcanvas();
      }

      var titleEl = document.getElementById('helpOffcanvasLabel');
      if (titleEl) {
        titleEl.textContent = 'Help for this page';
      }

      var bodyEl = document.getElementById('helpOffcanvasBody');
      if (bodyEl) {
        bodyEl.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
      }

      bootstrap.Offcanvas.getOrCreateInstance(this.offcanvas).show();

      var xhr = new XMLHttpRequest();
      xhr.open('GET', '/help/api/suggest?path=' + encodeURIComponent(window.location.pathname));
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.onload = function () {
        if (xhr.status !== 200) {
          self.renderNoHelp();

          return;
        }

        var data;
        try {
          data = JSON.parse(xhr.responseText);
        } catch (e) {
          self.renderNoHelp();

          return;
        }

        if (data.best) {
          self.openOffcanvas(data.best);
        } else if (data.results && data.results.length) {
          self.renderSuggestions(data.results);
        } else {
          self.renderNoHelp();
        }
      };
      xhr.onerror = function () {
        self.renderNoHelp();
      };
      xhr.send();
    },

    renderSuggestions: function (results) {
      var bodyEl = document.getElementById('helpOffcanvasBody');
      if (!bodyEl) return;

      var html = '<p class="text-muted small">No help article is mapped to this page. These look relevant:</p>';
      html += '<div class="list-group list-group-flush">';

      for (var i = 0; i < results.length; i++) {
        html += '<a href="#" class="list-group-item list-group-item-action help-suggestion"'
          + ' data-slug="' + esc(results[i].slug) + '"'
          + ' data-title="' + esc(results[i].title) + '">'
          + esc(results[i].title)
          + (results[i].category ? ' <span class="badge bg-light text-muted ms-1">' + esc(results[i].category) + '</span>' : '')
          + '</a>';
      }

      html += '</div>';
      bodyEl.innerHTML = html;

      var self = this;
      var links = bodyEl.querySelectorAll('.help-suggestion');
      for (var j = 0; j < links.length; j++) {
        links[j].addEventListener('click', function (e) {
          e.preventDefault();
          self.openOffcanvas({
            slug: this.getAttribute('data-slug'),
            title: this.getAttribute('data-title')
          });
        });
      }
    },

    renderNoHelp: function () {
      var bodyEl = document.getElementById('helpOffcanvasBody');
      if (!bodyEl) return;

      bodyEl.innerHTML = '<p class="text-muted">No help article matches this page yet.</p>'
        + '<a class="btn btn-sm btn-outline-primary" href="/help" target="_blank" rel="noopener">Browse the help centre</a>';
    },

    resolvePendingOpen: function () {
      if (!this.pendingOpen) return;

      this.pendingOpen = false;
      this.toggleHelp();
    },

    fetchContextMap: function () {
      var self = this;
      var xhr = new XMLHttpRequest();
      xhr.open('GET', '/help/api/context-map');
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.onload = function () {
        if (xhr.status === 200) {
          try {
            var data = JSON.parse(xhr.responseText);
            self.mappings = data.mappings || [];
            self.checkCurrentPage();
            self.resolvePendingOpen();
          } catch (e) {
            // Silently fail
          }
        } else {
          // No map available; F1 can still reach the full help centre.
          self.mappings = [];
          self.resolvePendingOpen();
        }
      };
      xhr.onerror = function () {
        // Contextual help is optional, but keep F1 working.
        self.mappings = [];
        self.resolvePendingOpen();
      };
      xhr.send();
    },

    checkCurrentPage: function () {
      if (!this.mappings || !this.mappings.length) return;

      var path = window.location.pathname;
      var match = null;

      for (var i = 0; i < this.mappings.length; i++) {
        var m = this.mappings[i];
        // Prefix match (e.g., /research/annotation-studio matches /research/annotation-studio/*)
        if (path === m.pattern || path.indexOf(m.pattern + '/') === 0 || path.indexOf(m.pattern) === 0) {
          match = m;
          break;
        }
      }

      this.currentMapping = match;

      if (match) {
        this.showButton(match);
      }
    },

    showButton: function (mapping) {
      var self = this;

      // Create floating button
      this.button = document.createElement('button');
      this.button.className = 'help-floating-btn';
      this.button.setAttribute('title', mapping.title || 'Help');
      this.button.setAttribute('aria-label', 'Open contextual help');
      this.button.innerHTML = '<i class="bi bi-question-lg"></i>';

      this.button.addEventListener('click', function () {
        self.openOffcanvas(mapping);
      });

      document.body.appendChild(this.button);
    },

    openOffcanvas: function (mapping) {
      var self = this;

      // Create offcanvas if it doesn't exist
      if (!this.offcanvas) {
        this.createOffcanvas();
      }

      // Set title
      var titleEl = document.getElementById('helpOffcanvasLabel');
      if (titleEl) {
        titleEl.textContent = mapping.title || 'Help';
      }

      // Load article content
      var bodyEl = document.getElementById('helpOffcanvasBody');
      if (bodyEl) {
        bodyEl.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading help article...</p></div>';
      }

      // Show offcanvas
      var bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(this.offcanvas);
      bsOffcanvas.show();

      // Fetch article
      var url = '/help/article/' + encodeURIComponent(mapping.slug);
      var xhr = new XMLHttpRequest();
      xhr.open('GET', url);
      xhr.onload = function () {
        if (xhr.status === 200 && bodyEl) {
          // Extract article content from the full page response
          var parser = new DOMParser();
          var doc = parser.parseFromString(xhr.responseText, 'text/html');
          var articleContent = doc.querySelector('.help-article-content');

          if (articleContent) {
            bodyEl.innerHTML = articleContent.innerHTML;
          } else {
            // Fallback: show raw content
            bodyEl.innerHTML = '<div class="alert alert-info">Unable to load article content. <a href="' + url + '" target="_blank">Open in new tab</a></div>';
          }

          // Scroll to anchor if specified
          if (mapping.anchor) {
            setTimeout(function () {
              var anchor = bodyEl.querySelector('#' + mapping.anchor);
              if (anchor) {
                anchor.scrollIntoView({ behavior: 'smooth' });
              }
            }, 200);
          }
        }
      };
      xhr.onerror = function () {
        if (bodyEl) {
          bodyEl.innerHTML = '<div class="alert alert-warning">Could not load help article. <a href="' + url + '" target="_blank">Open in new tab</a></div>';
        }
      };
      xhr.send();
    },

    createOffcanvas: function () {
      var html = '<div class="offcanvas offcanvas-end help-offcanvas" tabindex="-1" id="helpOffcanvas" aria-labelledby="helpOffcanvasLabel">';
      html += '<div class="offcanvas-header">';
      html += '<h5 class="offcanvas-title" id="helpOffcanvasLabel">Help</h5>';
      html += '<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>';
      html += '</div>';
      html += '<div class="offcanvas-body" id="helpOffcanvasBody">';
      html += '</div>';
      html += '<div class="offcanvas-footer border-top p-2 text-center">';
      html += '<a href="/help" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">Open Help Center</a>';
      html += '</div>';
      html += '</div>';

      document.body.insertAdjacentHTML('beforeend', html);
      this.offcanvas = document.getElementById('helpOffcanvas');
    }
  };

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      HelpContext.init();
    });
  } else {
    HelpContext.init();
  }
})();
