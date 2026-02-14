/**
 * Portable Catalogue Viewer — Main Application
 *
 * Loads catalogue data, initializes tree/search, handles routing and rendering.
 * Designed to run entirely client-side with zero server dependency.
 */
(function () {
  'use strict';

  var App = {
    config: null,
    catalogue: [],
    catalogueById: {},
    searchIndex: null,
    currentView: 'browse',
    currentDescriptionId: null,

    /**
     * Initialize the application.
     */
    init: function () {
      var self = this;

      // Load config first, then data
      this.loadJSON('data/config.json', function (config) {
        self.config = config || {};
        self.applyBranding();

        self.loadJSON('data/catalogue.json', function (catalogue) {
          self.catalogue = catalogue || [];
          self.buildLookup();
          self.updateStats();

          // Initialize tree
          if (window.TreeNav) {
            TreeNav.init(self.config.hierarchy || [], function (id) {
              self.showDescription(id);
            });
          }

          // Load search index
          self.loadJSON('data/search-index.json', function (indexData) {
            if (window.SearchEngine) {
              SearchEngine.init(indexData, function (id) {
                self.showDescription(id);
              });
            }
          });

          // Initialize import module if in edit mode
          if (self.config.mode === 'editable' && window.ImportModule) {
            document.getElementById('nav-import-item').style.display = '';
            ImportModule.init(self);
          }
        });
      });

      this.bindNavigation();
    },

    /**
     * Load a JSON file relative to the viewer root.
     */
    loadJSON: function (path, callback) {
      var xhr = new XMLHttpRequest();
      xhr.open('GET', path, true);
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
          if (xhr.status === 200 || xhr.status === 0) {
            try {
              callback(JSON.parse(xhr.responseText));
            } catch (e) {
              console.warn('Failed to parse ' + path + ':', e);
              callback(null);
            }
          } else {
            console.warn('Failed to load ' + path + ': status ' + xhr.status);
            callback(null);
          }
        }
      };
      xhr.send();
    },

    /**
     * Build a lookup map by description ID.
     */
    buildLookup: function () {
      this.catalogueById = {};
      for (var i = 0; i < this.catalogue.length; i++) {
        var desc = this.catalogue[i];
        this.catalogueById[desc.id] = desc;
      }
    },

    /**
     * Apply branding from config.
     */
    applyBranding: function () {
      var b = this.config.branding || {};

      if (b.title || this.config.title) {
        var title = b.title || this.config.title;
        document.getElementById('nav-title').textContent = title;
        document.getElementById('welcome-title').textContent = title;
        document.title = title;
      }

      if (b.subtitle) {
        document.getElementById('nav-subtitle').textContent = b.subtitle;
        document.getElementById('subtitle-bar').style.display = '';
      }

      if (b.footer) {
        document.getElementById('footer-text').textContent = b.footer;
      }
    },

    /**
     * Update stats display in navbar.
     */
    updateStats: function () {
      var stats = document.getElementById('nav-stats');
      var totalDesc = this.config.total_descriptions || this.catalogue.length;
      var totalObj = this.config.total_objects || 0;
      stats.textContent = totalDesc + ' descriptions' + (totalObj ? ', ' + totalObj + ' objects' : '');
    },

    /**
     * Bind navigation link clicks.
     */
    bindNavigation: function () {
      var self = this;
      var navLinks = document.querySelectorAll('[data-view]');
      for (var i = 0; i < navLinks.length; i++) {
        navLinks[i].addEventListener('click', function (e) {
          e.preventDefault();
          self.switchView(this.getAttribute('data-view'));

          // Update active state
          var allLinks = document.querySelectorAll('[data-view]');
          for (var j = 0; j < allLinks.length; j++) {
            allLinks[j].classList.remove('active');
          }
          this.classList.add('active');
        });
      }

      // Brand click = home
      document.getElementById('nav-brand').addEventListener('click', function (e) {
        e.preventDefault();
        self.switchView('browse');
        self.currentDescriptionId = null;
        document.getElementById('welcome-screen').style.display = '';
        document.getElementById('description-detail').style.display = 'none';
      });
    },

    /**
     * Switch between views.
     */
    switchView: function (view) {
      this.currentView = view;
      document.getElementById('view-browse').style.display = view === 'browse' ? '' : 'none';
      document.getElementById('view-search').style.display = view === 'search' ? '' : 'none';
      document.getElementById('view-import').style.display = view === 'import' ? '' : 'none';

      if (view === 'search') {
        document.getElementById('search-input').focus();
      }
    },

    /**
     * Show a description by ID.
     */
    showDescription: function (id) {
      var desc = this.catalogueById[id];
      if (!desc) return;

      this.currentDescriptionId = id;
      this.switchView('browse');

      // Update nav active state
      var allLinks = document.querySelectorAll('[data-view]');
      for (var j = 0; j < allLinks.length; j++) {
        allLinks[j].classList.remove('active');
      }
      document.querySelector('[data-view="browse"]').classList.add('active');

      document.getElementById('welcome-screen').style.display = 'none';
      var detail = document.getElementById('description-detail');
      detail.style.display = '';
      detail.innerHTML = this.renderDescription(desc);

      // Highlight in tree
      if (window.TreeNav) {
        TreeNav.highlight(id);
      }

      // Scroll to top of content
      detail.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    /**
     * Render a full description detail view.
     */
    renderDescription: function (desc) {
      var html = '<div class="card">';
      html += '<div class="card-header bg-light">';

      // Breadcrumb
      var breadcrumb = this.buildBreadcrumb(desc);
      if (breadcrumb.length > 0) {
        html += '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-1 small">';
        for (var b = 0; b < breadcrumb.length; b++) {
          if (b === breadcrumb.length - 1) {
            html += '<li class="breadcrumb-item active">' + this.esc(breadcrumb[b].title) + '</li>';
          } else {
            html += '<li class="breadcrumb-item"><a href="#" data-desc-id="' + breadcrumb[b].id + '">' + this.esc(breadcrumb[b].title) + '</a></li>';
          }
        }
        html += '</ol></nav>';
      }

      html += '<h4 class="mb-0">' + this.esc(desc.title || '[Untitled]') + '</h4>';

      if (desc.level_of_description || desc.identifier) {
        html += '<div class="mt-1">';
        if (desc.level_of_description) {
          html += '<span class="badge bg-primary me-2">' + this.esc(desc.level_of_description) + '</span>';
        }
        if (desc.identifier) {
          html += '<span class="badge bg-secondary">' + this.esc(desc.identifier) + '</span>';
        }
        html += '</div>';
      }

      html += '</div><div class="card-body">';

      // Digital objects (images)
      if (desc.digital_objects && desc.digital_objects.length > 0) {
        html += this.renderDigitalObjects(desc.digital_objects);
      }

      // ISAD(G) fields
      var fields = [
        { key: 'scope_and_content', label: 'Scope and Content' },
        { key: 'extent_and_medium', label: 'Extent and Medium' },
        { key: 'archival_history', label: 'Archival History' },
        { key: 'acquisition', label: 'Immediate Source of Acquisition' },
        { key: 'arrangement', label: 'System of Arrangement' },
        { key: 'access_conditions', label: 'Conditions Governing Access' },
        { key: 'reproduction_conditions', label: 'Conditions Governing Reproduction' },
        { key: 'physical_characteristics', label: 'Physical Characteristics' },
        { key: 'finding_aids', label: 'Finding Aids' },
        { key: 'location_of_originals', label: 'Existence and Location of Originals' },
        { key: 'location_of_copies', label: 'Existence and Location of Copies' },
        { key: 'related_units_of_description', label: 'Related Units of Description' },
        { key: 'rules', label: 'Rules or Conventions' },
      ];

      for (var f = 0; f < fields.length; f++) {
        var val = desc[fields[f].key];
        if (val) {
          html += '<div class="mb-3">';
          html += '<h6 class="text-primary">' + fields[f].label + '</h6>';
          html += '<div>' + this.renderContent(val) + '</div>';
          html += '</div>';
        }
      }

      // Dates
      if (desc.dates && desc.dates.length > 0) {
        html += '<div class="mb-3"><h6 class="text-primary">Dates</h6>';
        html += '<table class="table table-sm"><thead><tr><th>Type</th><th>Date</th><th>Actor</th></tr></thead><tbody>';
        for (var d = 0; d < desc.dates.length; d++) {
          var dt = desc.dates[d];
          html += '<tr>';
          html += '<td>' + this.esc(dt.type || '') + '</td>';
          html += '<td>' + this.esc(dt.date || (dt.start_date ? dt.start_date + ' - ' + (dt.end_date || '') : '')) + '</td>';
          html += '<td>' + this.esc(dt.actor || '') + '</td>';
          html += '</tr>';
        }
        html += '</tbody></table></div>';
      }

      // Access points
      var accessPoints = [
        { key: 'creators', label: 'Creators', icon: 'bi-person' },
        { key: 'subjects', label: 'Subject Access Points', icon: 'bi-tag' },
        { key: 'places', label: 'Place Access Points', icon: 'bi-geo-alt' },
        { key: 'genres', label: 'Genre Access Points', icon: 'bi-bookmark' },
        { key: 'name_access_points', label: 'Name Access Points', icon: 'bi-people' },
      ];

      for (var a = 0; a < accessPoints.length; a++) {
        var ap = desc[accessPoints[a].key];
        if (ap && ap.length > 0) {
          html += '<div class="mb-3"><h6 class="text-primary"><i class="bi ' + accessPoints[a].icon + ' me-1"></i>' + accessPoints[a].label + '</h6>';
          for (var ai = 0; ai < ap.length; ai++) {
            html += '<span class="badge bg-light text-dark border me-1 mb-1">' + this.esc(ap[ai]) + '</span>';
          }
          html += '</div>';
        }
      }

      // Repository
      if (desc.repository_id && this.config.repositories && this.config.repositories[desc.repository_id]) {
        html += '<div class="mb-3"><h6 class="text-primary"><i class="bi bi-building me-1"></i>Repository</h6>';
        html += '<span>' + this.esc(this.config.repositories[desc.repository_id]) + '</span></div>';
      }

      // Children
      var children = this.getChildren(desc.id);
      if (children.length > 0) {
        html += '<div class="mb-3"><h6 class="text-primary"><i class="bi bi-diagram-3 me-1"></i>Sub-levels (' + children.length + ')</h6>';
        html += '<div class="list-group">';
        for (var c = 0; c < Math.min(children.length, 50); c++) {
          var child = children[c];
          html += '<a href="#" class="list-group-item list-group-item-action" data-desc-id="' + child.id + '">';
          if (child.level_of_description) {
            html += '<span class="badge bg-secondary me-2">' + this.esc(child.level_of_description) + '</span>';
          }
          html += this.esc(child.title || '[Untitled]');
          html += '</a>';
        }
        if (children.length > 50) {
          html += '<div class="list-group-item text-muted">... and ' + (children.length - 50) + ' more</div>';
        }
        html += '</div></div>';
      }

      // Edit mode: notes area
      if (this.config.mode === 'editable') {
        var notes = (window.ImportModule && ImportModule.getNotes(desc.id)) || '';
        html += '<div class="mb-3 mt-4 border-top pt-3">';
        html += '<h6 class="text-warning"><i class="bi bi-pencil me-1"></i>Research Notes</h6>';
        html += '<textarea class="form-control" rows="3" data-notes-id="' + desc.id + '" placeholder="Add your notes here...">' + this.esc(notes) + '</textarea>';
        html += '</div>';
      }

      // Actions
      html += '<div class="mt-3 border-top pt-3">';
      html += '<button class="btn btn-sm btn-outline-secondary me-2" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>';
      html += '</div>';

      html += '</div></div>';

      // Bind click events after render via delegation
      var self = this;
      setTimeout(function () {
        var detail = document.getElementById('description-detail');
        detail.querySelectorAll('[data-desc-id]').forEach(function (el) {
          el.addEventListener('click', function (e) {
            e.preventDefault();
            self.showDescription(parseInt(this.getAttribute('data-desc-id'), 10));
          });
        });

        // Save notes on change
        detail.querySelectorAll('[data-notes-id]').forEach(function (el) {
          el.addEventListener('input', function () {
            if (window.ImportModule) {
              ImportModule.saveNote(parseInt(this.getAttribute('data-notes-id'), 10), this.value);
            }
          });
        });
      }, 50);

      return html;
    },

    /**
     * Render digital objects section.
     */
    renderDigitalObjects: function (objects) {
      var html = '<div class="mb-3">';

      for (var i = 0; i < objects.length; i++) {
        var obj = objects[i];
        var isImage = obj.mime_type && obj.mime_type.indexOf('image/') === 0;
        var isPdf = obj.mime_type === 'application/pdf';

        if (isImage) {
          var src = obj.reference_file || obj.thumbnail_file || obj.master_file || '';
          if (src) {
            html += '<div class="mb-2">';
            html += '<img src="' + this.esc(src) + '" class="img-fluid rounded shadow-sm" alt="' + this.esc(obj.name || '') + '" style="max-height:400px;">';
            if (obj.name) {
              html += '<div class="small text-muted mt-1"><i class="bi bi-image me-1"></i>' + this.esc(obj.name) + '</div>';
            }
            html += '</div>';
          }
        } else if (isPdf) {
          var pdfSrc = obj.pdf_file || obj.master_file || '';
          if (pdfSrc) {
            html += '<div class="mb-2">';
            html += '<a href="' + this.esc(pdfSrc) + '" target="_blank" class="btn btn-outline-primary btn-sm">';
            html += '<i class="bi bi-file-earmark-pdf me-1"></i>' + this.esc(obj.name || 'View PDF');
            html += '</a></div>';
          }
        } else if (obj.thumbnail_file) {
          html += '<div class="mb-2">';
          html += '<img src="' + this.esc(obj.thumbnail_file) + '" class="img-thumbnail" alt="' + this.esc(obj.name || '') + '">';
          if (obj.name) {
            html += '<div class="small text-muted mt-1">' + this.esc(obj.name) + '</div>';
          }
          html += '</div>';
        }
      }

      html += '</div>';
      return html;
    },

    /**
     * Build breadcrumb trail for a description.
     */
    buildBreadcrumb: function (desc) {
      var trail = [];
      var current = desc;
      var maxDepth = 20;
      while (current && maxDepth-- > 0) {
        trail.unshift({ id: current.id, title: current.title || '[Untitled]' });
        if (current.parent_id && this.catalogueById[current.parent_id]) {
          current = this.catalogueById[current.parent_id];
        } else {
          break;
        }
      }
      return trail;
    },

    /**
     * Get direct children of a description.
     */
    getChildren: function (parentId) {
      var children = [];
      for (var i = 0; i < this.catalogue.length; i++) {
        if (this.catalogue[i].parent_id === parentId) {
          children.push(this.catalogue[i]);
        }
      }
      return children;
    },

    /**
     * Render content, preserving HTML if it contains tags, escaping otherwise.
     */
    renderContent: function (text) {
      if (!text) return '';
      if (/<[a-z][\s\S]*>/i.test(text)) {
        // Contains HTML — render it (was sanitized server-side)
        return text;
      }
      // Plain text — escape and preserve line breaks
      return this.esc(text).replace(/\n/g, '<br>');
    },

    /**
     * Escape HTML entities.
     */
    esc: function (str) {
      if (!str) return '';
      var div = document.createElement('div');
      div.appendChild(document.createTextNode(String(str)));
      return div.innerHTML;
    }
  };

  // Start the app when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { App.init(); });
  } else {
    App.init();
  }

  // Expose for other modules
  window.App = App;
})();
