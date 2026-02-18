/**
 * ahgHelpPlugin — Contextual Help Button & Offcanvas Panel
 *
 * Injects a floating "?" help button on pages that have contextual help mappings.
 * On click, opens a Bootstrap 5 offcanvas panel with the relevant help article.
 * Fetches context mappings from /help/api/context-map on first load.
 */
(function () {
  'use strict';

  var HelpContext = {
    mappings: null,
    offcanvas: null,
    button: null,

    init: function () {
      // Don't show on help pages themselves
      if (window.location.pathname.indexOf('/help') === 0) return;

      this.fetchContextMap();
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
          } catch (e) {
            // Silently fail
          }
        }
      };
      xhr.onerror = function () {
        // Silently fail — contextual help is optional
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
      html += '<a href="/help" class="btn btn-sm btn-outline-primary">Open Help Center</a>';
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
