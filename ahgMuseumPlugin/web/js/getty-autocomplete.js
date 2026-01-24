/**
 * Getty Vocabulary Autocomplete Widget.
 *
 * jQuery plugin for adding Getty vocabulary autocomplete to input fields.
 * Supports AAT, TGN, and ULAN vocabularies with category filtering.
 *
 * Usage:
 *   $('#material-input').gettyAutocomplete({
 *     vocabulary: 'aat',
 *     category: 'materials',
 *     onSelect: function(item) { console.log('Selected:', item); }
 *   });
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
(function($) {
  'use strict';

  var pluginName = 'gettyAutocomplete';
  var defaults = {
    vocabulary: 'aat',           // aat, tgn, or ulan
    category: null,              // materials, techniques, object_types, styles_periods
    minLength: 2,                // Minimum characters before searching
    delay: 300,                  // Debounce delay in ms
    limit: 10,                   // Max results to return
    endpoint: '/museum/getty/autocomplete',
    showUri: true,               // Show Getty URI in results
    showHierarchy: false,        // Show hierarchy path
    onSelect: null,              // Callback when item selected
    onClear: null,               // Callback when cleared
    linkedInputName: null,       // Name for hidden input storing URI
    formatResult: null           // Custom result formatter
  };

  function Plugin(element, options) {
    this.element = element;
    this.$element = $(element);
    this.options = $.extend({}, defaults, options);
    this._defaults = defaults;
    this._name = pluginName;
    this.searchTimeout = null;
    this.$dropdown = null;
    this.$hiddenInput = null;
    this.init();
  }

  Plugin.prototype = {
    init: function() {
      var self = this;

      // Wrap element and create dropdown
      this.$element.wrap('<div class="getty-autocomplete-wrapper"></div>');
      this.$wrapper = this.$element.parent();

      // Create dropdown container
      this.$dropdown = $('<div class="getty-autocomplete-dropdown"></div>')
        .hide()
        .appendTo(this.$wrapper);

      // Create hidden input for URI if specified
      if (this.options.linkedInputName) {
        this.$hiddenInput = $('<input type="hidden">')
          .attr('name', this.options.linkedInputName)
          .appendTo(this.$wrapper);
      }

      // Add clear button
      this.$clearBtn = $('<button type="button" class="getty-autocomplete-clear" title="Clear">&times;</button>')
        .hide()
        .appendTo(this.$wrapper);

      // Bind events
      this.$element.on('input.' + pluginName, function() {
        self.onInput();
      });

      this.$element.on('focus.' + pluginName, function() {
        if (self.$dropdown.children().length) {
          self.$dropdown.show();
        }
      });

      this.$element.on('blur.' + pluginName, function() {
        // Delay to allow click on dropdown
        setTimeout(function() {
          self.$dropdown.hide();
        }, 200);
      });

      this.$element.on('keydown.' + pluginName, function(e) {
        self.onKeydown(e);
      });

      this.$clearBtn.on('click.' + pluginName, function() {
        self.clear();
      });

      // Delegate click on results
      this.$dropdown.on('click', '.getty-autocomplete-item', function() {
        var data = $(this).data('getty');
        self.select(data);
      });

      // Add ARIA attributes
      this.$element.attr({
        'role': 'combobox',
        'aria-autocomplete': 'list',
        'aria-expanded': 'false'
      });
    },

    onInput: function() {
      var self = this;
      var query = this.$element.val().trim();

      clearTimeout(this.searchTimeout);

      // Show/hide clear button
      this.$clearBtn.toggle(query.length > 0);

      if (query.length < this.options.minLength) {
        this.$dropdown.empty().hide();
        return;
      }

      // Debounced search
      this.searchTimeout = setTimeout(function() {
        self.search(query);
      }, this.options.delay);
    },

    onKeydown: function(e) {
      var $items = this.$dropdown.find('.getty-autocomplete-item');
      var $active = $items.filter('.active');
      var index = $items.index($active);

      switch (e.keyCode) {
        case 40: // Down
          e.preventDefault();
          if (index < $items.length - 1) {
            $items.removeClass('active');
            $items.eq(index + 1).addClass('active');
          }
          break;

        case 38: // Up
          e.preventDefault();
          if (index > 0) {
            $items.removeClass('active');
            $items.eq(index - 1).addClass('active');
          }
          break;

        case 13: // Enter
          if ($active.length) {
            e.preventDefault();
            this.select($active.data('getty'));
          }
          break;

        case 27: // Escape
          this.$dropdown.hide();
          break;
      }
    },

    search: function(query) {
      var self = this;

      // Show loading state
      this.$dropdown
        .html('<div class="getty-autocomplete-loading">Searching...</div>')
        .show();

      $.ajax({
        url: this.options.endpoint,
        data: {
          vocabulary: this.options.vocabulary,
          query: query,
          category: this.options.category,
          limit: this.options.limit
        },
        dataType: 'json',
        success: function(response) {
          if (response.success && response.results.length) {
            self.renderResults(response.results);
          } else {
            self.renderNoResults();
          }
        },
        error: function() {
          self.renderError();
        }
      });
    },

    renderResults: function(results) {
      var self = this;
      var html = '';

      results.forEach(function(item, index) {
        var formatted = self.options.formatResult
          ? self.options.formatResult(item)
          : self.formatResult(item);

        html += '<div class="getty-autocomplete-item' + (index === 0 ? ' active' : '') + '" ' +
                'data-getty=\'' + JSON.stringify(item).replace(/'/g, '&#39;') + '\'>' +
                formatted +
                '</div>';
      });

      this.$dropdown.html(html).show();
      this.$element.attr('aria-expanded', 'true');
    },

    formatResult: function(item) {
      var html = '<div class="getty-result-label">' + this.escapeHtml(item.label) + '</div>';

      if (item.description) {
        html += '<div class="getty-result-description">' + this.escapeHtml(item.description) + '</div>';
      }

      if (this.options.showHierarchy && item.hierarchy) {
        html += '<div class="getty-result-hierarchy">' + this.escapeHtml(item.hierarchy) + '</div>';
      }

      if (this.options.showUri) {
        html += '<div class="getty-result-uri">' +
                '<span class="getty-vocab-badge getty-vocab-' + item.vocabulary + '">' +
                item.vocabulary.toUpperCase() + '</span> ' +
                this.escapeHtml(item.id) +
                '</div>';
      }

      return html;
    },

    renderNoResults: function() {
      this.$dropdown
        .html('<div class="getty-autocomplete-empty">No matches found</div>')
        .show();
    },

    renderError: function() {
      this.$dropdown
        .html('<div class="getty-autocomplete-error">Search failed. Please try again.</div>')
        .show();
    },

    select: function(item) {
      this.$element.val(item.label);
      this.$dropdown.hide();
      this.$element.attr('aria-expanded', 'false');
      this.$clearBtn.show();

      // Update hidden input
      if (this.$hiddenInput) {
        this.$hiddenInput.val(item.uri);
      }

      // Store selected data
      this.$element.data('selected', item);

      // Callback
      if (typeof this.options.onSelect === 'function') {
        this.options.onSelect.call(this.element, item);
      }

      // Trigger event
      this.$element.trigger('getty:select', [item]);
    },

    clear: function() {
      this.$element.val('');
      this.$dropdown.empty().hide();
      this.$clearBtn.hide();

      if (this.$hiddenInput) {
        this.$hiddenInput.val('');
      }

      this.$element.removeData('selected');

      if (typeof this.options.onClear === 'function') {
        this.options.onClear.call(this.element);
      }

      this.$element.trigger('getty:clear');
    },

    getSelected: function() {
      return this.$element.data('selected');
    },

    setValue: function(label, uri) {
      this.$element.val(label);
      if (this.$hiddenInput && uri) {
        this.$hiddenInput.val(uri);
      }
      this.$clearBtn.show();
    },

    escapeHtml: function(str) {
      if (!str) return '';
      return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    },

    destroy: function() {
      clearTimeout(this.searchTimeout);
      this.$element.off('.' + pluginName);
      this.$clearBtn.off('.' + pluginName);
      this.$dropdown.remove();
      this.$clearBtn.remove();
      if (this.$hiddenInput) {
        this.$hiddenInput.remove();
      }
      this.$element.unwrap();
      this.$element.removeData('plugin_' + pluginName);
    }
  };

  // jQuery plugin wrapper
  $.fn[pluginName] = function(options) {
    var args = arguments;

    if (options === undefined || typeof options === 'object') {
      return this.each(function() {
        if (!$.data(this, 'plugin_' + pluginName)) {
          $.data(this, 'plugin_' + pluginName, new Plugin(this, options));
        }
      });
    } else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {
      var returns;

      this.each(function() {
        var instance = $.data(this, 'plugin_' + pluginName);
        if (instance instanceof Plugin && typeof instance[options] === 'function') {
          returns = instance[options].apply(instance, Array.prototype.slice.call(args, 1));
        }
      });

      return returns !== undefined ? returns : this;
    }
  };

})(jQuery);
