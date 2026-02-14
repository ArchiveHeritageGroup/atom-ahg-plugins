/**
 * Portable Catalogue Viewer — Tree Navigation
 *
 * Renders a hierarchical tree from the catalogue data.
 * Supports expand/collapse, highlighting, and lazy rendering for large trees.
 */
(function () {
  'use strict';

  var TreeNav = {
    hierarchy: [],
    onSelect: null,
    expandedNodes: {},

    /**
     * Initialize the tree with hierarchy data.
     *
     * @param {Array} hierarchy Nested hierarchy from config.json
     * @param {Function} onSelect Callback when a node is selected: fn(id)
     */
    init: function (hierarchy, onSelect) {
      this.hierarchy = hierarchy || [];
      this.onSelect = onSelect;

      var container = document.getElementById('tree-container');
      var loading = document.getElementById('tree-loading');
      if (loading) loading.style.display = 'none';

      if (this.hierarchy.length === 0) {
        container.innerHTML = '<div class="text-center py-3 text-muted">No descriptions found</div>';
        return;
      }

      // Render tree
      var html = '<ul class="tree-root list-unstyled mb-0">';
      html += this.renderNodes(this.hierarchy, 0);
      html += '</ul>';
      container.innerHTML = html;

      // Bind events
      this.bindEvents(container);
      this.bindExpandCollapse();
    },

    /**
     * Render tree nodes recursively.
     */
    renderNodes: function (nodes, depth) {
      var html = '';
      for (var i = 0; i < nodes.length; i++) {
        var node = nodes[i];
        var hasChildren = node.children && node.children.length > 0;
        var isExpanded = this.expandedNodes[node.id] || false;

        html += '<li class="tree-node" data-id="' + node.id + '">';
        html += '<div class="tree-item d-flex align-items-center px-2 py-1" style="padding-left: ' + (depth * 18 + 8) + 'px;">';

        // Toggle arrow
        if (hasChildren) {
          html += '<span class="tree-toggle me-1" data-toggle-id="' + node.id + '" role="button">';
          html += '<i class="bi ' + (isExpanded ? 'bi-chevron-down' : 'bi-chevron-right') + ' small"></i>';
          html += '</span>';
        } else {
          html += '<span class="tree-spacer me-1" style="width:16px; display:inline-block;"></span>';
        }

        // Icon
        var icon = this.getLevelIcon(node.level);
        html += '<i class="bi ' + icon + ' me-1 text-muted small"></i>';

        // Label
        html += '<a href="#" class="tree-label text-decoration-none text-truncate" data-select-id="' + node.id + '" title="' + this.esc(node.title || '') + '">';
        html += this.esc(node.title || '[Untitled]');
        html += '</a>';

        // Digital object indicator
        if (node.has_digital_objects) {
          html += ' <i class="bi bi-image small text-info ms-1"></i>';
        }

        html += '</div>';

        // Children
        if (hasChildren) {
          html += '<ul class="tree-children list-unstyled mb-0' + (isExpanded ? '' : ' d-none') + '" data-children-of="' + node.id + '">';
          html += this.renderNodes(node.children, depth + 1);
          html += '</ul>';
        }

        html += '</li>';
      }
      return html;
    },

    /**
     * Get an icon class for a level of description.
     */
    getLevelIcon: function (level) {
      if (!level) return 'bi-file-text';
      var l = level.toLowerCase();
      if (l === 'fonds') return 'bi-archive';
      if (l === 'subfonds' || l === 'sub-fonds') return 'bi-folder2';
      if (l === 'collection') return 'bi-collection';
      if (l === 'series') return 'bi-folder';
      if (l === 'subseries' || l === 'sub-series') return 'bi-folder';
      if (l === 'file') return 'bi-file-earmark';
      if (l === 'item') return 'bi-file-text';
      return 'bi-file-text';
    },

    /**
     * Bind click events on the tree.
     */
    bindEvents: function (container) {
      var self = this;

      container.addEventListener('click', function (e) {
        var target = e.target.closest('[data-toggle-id]');
        if (target) {
          e.preventDefault();
          e.stopPropagation();
          self.toggleNode(parseInt(target.getAttribute('data-toggle-id'), 10));
          return;
        }

        target = e.target.closest('[data-select-id]');
        if (target) {
          e.preventDefault();
          var id = parseInt(target.getAttribute('data-select-id'), 10);
          if (self.onSelect) self.onSelect(id);
        }
      });
    },

    /**
     * Toggle expand/collapse of a node.
     */
    toggleNode: function (id) {
      var children = document.querySelector('[data-children-of="' + id + '"]');
      var toggle = document.querySelector('[data-toggle-id="' + id + '"] i');
      if (!children) return;

      var isHidden = children.classList.contains('d-none');
      if (isHidden) {
        children.classList.remove('d-none');
        this.expandedNodes[id] = true;
        if (toggle) {
          toggle.classList.remove('bi-chevron-right');
          toggle.classList.add('bi-chevron-down');
        }
      } else {
        children.classList.add('d-none');
        this.expandedNodes[id] = false;
        if (toggle) {
          toggle.classList.remove('bi-chevron-down');
          toggle.classList.add('bi-chevron-right');
        }
      }
    },

    /**
     * Highlight a node in the tree (expand ancestors, scroll into view).
     */
    highlight: function (id) {
      // Remove previous highlight
      var prev = document.querySelector('.tree-item.active');
      if (prev) prev.classList.remove('active', 'bg-primary', 'bg-opacity-10');

      // Expand all ancestors
      this.expandAncestors(id);

      // Highlight current
      var node = document.querySelector('.tree-node[data-id="' + id + '"] > .tree-item');
      if (node) {
        node.classList.add('active', 'bg-primary', 'bg-opacity-10');
        node.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    },

    /**
     * Expand all ancestor nodes to make a node visible.
     */
    expandAncestors: function (id) {
      // Find the node's parents by walking up the DOM
      var nodeEl = document.querySelector('.tree-node[data-id="' + id + '"]');
      if (!nodeEl) return;

      var parent = nodeEl.parentElement;
      while (parent) {
        if (parent.hasAttribute && parent.hasAttribute('data-children-of')) {
          var parentId = parseInt(parent.getAttribute('data-children-of'), 10);
          if (parent.classList.contains('d-none')) {
            parent.classList.remove('d-none');
            this.expandedNodes[parentId] = true;
            var toggle = document.querySelector('[data-toggle-id="' + parentId + '"] i');
            if (toggle) {
              toggle.classList.remove('bi-chevron-right');
              toggle.classList.add('bi-chevron-down');
            }
          }
        }
        parent = parent.parentElement;
      }
    },

    /**
     * Bind expand/collapse all buttons.
     */
    bindExpandCollapse: function () {
      var self = this;

      var expandBtn = document.getElementById('btn-expand-all');
      if (expandBtn) {
        expandBtn.addEventListener('click', function () {
          var allChildren = document.querySelectorAll('.tree-children');
          for (var i = 0; i < allChildren.length; i++) {
            allChildren[i].classList.remove('d-none');
          }
          var allToggles = document.querySelectorAll('.tree-toggle i');
          for (var j = 0; j < allToggles.length; j++) {
            allToggles[j].classList.remove('bi-chevron-right');
            allToggles[j].classList.add('bi-chevron-down');
          }
        });
      }

      var collapseBtn = document.getElementById('btn-collapse-all');
      if (collapseBtn) {
        collapseBtn.addEventListener('click', function () {
          var allChildren = document.querySelectorAll('.tree-children');
          for (var i = 0; i < allChildren.length; i++) {
            allChildren[i].classList.add('d-none');
          }
          var allToggles = document.querySelectorAll('.tree-toggle i');
          for (var j = 0; j < allToggles.length; j++) {
            allToggles[j].classList.remove('bi-chevron-down');
            allToggles[j].classList.add('bi-chevron-right');
          }
          self.expandedNodes = {};
        });
      }
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

  window.TreeNav = TreeNav;
})();
