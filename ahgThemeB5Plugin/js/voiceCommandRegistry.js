/**
 * AHG Voice Command Registry — Navigation + Action Commands
 *
 * Command definitions for voice-driven navigation and context-aware actions.
 * Each command has: patterns, action, mode, description, contextCheck (optional).
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
var AHGVoiceRegistry = (function () {
  'use strict';

  var commands = [
    // -- Navigation -------------------------------------------------------
    {
      patterns: ['go home', 'go to home', 'home', 'homepage'],
      action: function () { window.location.href = '/'; },
      mode: 'nav',
      description: 'Go to homepage',
      feedback: 'Going to homepage'
    },
    {
      patterns: [/^(?:go to )?browse(?: records)?$/, 'browse'],
      action: function () { window.location.href = '/informationobject/browse'; },
      mode: 'nav',
      description: 'Browse archival records',
      feedback: 'Opening archival records'
    },
    {
      patterns: ['go to admin', 'admin', 'admin panel'],
      action: function () { window.location.href = '/admin'; },
      mode: 'nav',
      description: 'Go to admin panel',
      feedback: 'Going to admin panel'
    },
    {
      patterns: ['go to settings', 'settings', 'ahg settings'],
      action: function () { window.location.href = '/ahgSettings'; },
      mode: 'nav',
      description: 'Go to settings',
      feedback: 'Opening settings'
    },
    {
      patterns: ['go to clipboard', 'clipboard', 'open clipboard'],
      action: function () { window.location.href = '/clipboard'; },
      mode: 'nav',
      description: 'Go to clipboard',
      feedback: 'Opening clipboard'
    },
    {
      patterns: ['go back', 'back', 'previous page'],
      action: function () { window.history.back(); },
      mode: 'nav',
      description: 'Go back',
      feedback: 'Going back'
    },
    {
      patterns: ['next page', 'go to next page'],
      action: function () {
        var link = document.querySelector('.pager .next a, .pagination .page-item:last-child a');
        if (link) { link.click(); }
        else { window.ahgVoice && window.ahgVoice.speak('No next page available'); }
      },
      mode: 'nav',
      description: 'Next page',
      feedback: 'Going to next page'
    },
    {
      patterns: ['previous page', 'go to previous page', 'prev page'],
      action: function () {
        var link = document.querySelector('.pager .previous a, .pagination .page-item:first-child a');
        if (link) { link.click(); }
        else { window.ahgVoice && window.ahgVoice.speak('No previous page available'); }
      },
      mode: 'nav',
      description: 'Previous page',
      feedback: 'Going to previous page'
    },
    {
      patterns: [/^search (?:for )?(.+)$/],
      action: function (text) {
        var match = text.match(/^search (?:for )?(.+)$/);
        if (!match) return;
        var term = match[1];
        window.ahgVoice && window.ahgVoice.speak('Searching for ' + term);
        var input = document.querySelector('#search-form-wrapper input[type="text"], #search-form-wrapper input[name="query"], input[name="query"]');
        var form = document.querySelector('#search-form-wrapper form, form[action*="search"]');
        if (input && form) {
          input.value = term;
          setTimeout(function() { form.submit(); }, 800);
        } else {
          setTimeout(function() { window.location.href = '/informationobject/browse?query=' + encodeURIComponent(term); }, 800);
        }
      },
      mode: 'nav',
      description: 'Search for a term',
      feedback: null // handled in action
    },
    {
      patterns: ['go to donors', 'donors', 'browse donors'],
      action: function () { window.location.href = '/donor/browse'; },
      mode: 'nav',
      description: 'Browse donors',
      feedback: 'Browsing donors'
    },
    {
      patterns: ['go to research', 'research', 'reading room'],
      action: function () { window.location.href = '/research'; },
      mode: 'nav',
      description: 'Go to research/reading room',
      feedback: 'Opening reading room'
    },
    {
      patterns: ['go to authorities', 'authorities', 'browse authorities', 'authority records'],
      action: function () { window.location.href = '/actor/browse'; },
      mode: 'nav',
      description: 'Browse authority records',
      feedback: 'Browsing authority records'
    },
    {
      patterns: ['go to places', 'places', 'browse places'],
      action: function () { window.location.href = '/taxonomy/browse?taxonomy=places'; },
      mode: 'nav',
      description: 'Browse places',
      feedback: 'Browsing places'
    },
    {
      patterns: ['go to subjects', 'subjects', 'browse subjects'],
      action: function () { window.location.href = '/taxonomy/browse?taxonomy=subjects'; },
      mode: 'nav',
      description: 'Browse subjects',
      feedback: 'Browsing subjects'
    },
    {
      patterns: ['go to digital objects', 'digital objects', 'browse digital objects'],
      action: function () { window.location.href = '/digitalobject/browse'; },
      mode: 'nav',
      description: 'Browse digital objects',
      feedback: 'Browsing digital objects'
    },
    {
      patterns: ['go to accessions', 'accessions', 'browse accessions'],
      action: function () { window.location.href = '/accession/browse'; },
      mode: 'nav',
      description: 'Browse accessions',
      feedback: 'Browsing accessions'
    },
    {
      patterns: ['go to repositories', 'repositories', 'institutions', 'browse repositories'],
      action: function () { window.location.href = '/repository/browse'; },
      mode: 'nav',
      description: 'Browse repositories',
      feedback: 'Browsing repositories'
    },

    // -- Actions: Edit screens --------------------------------------------
    {
      patterns: ['save', 'save record', 'save this'],
      action: function () {
        var v = window.ahgVoice;
        var btn = document.querySelector('form#editForm .btn-success[type="submit"], form.form-edit .btn-success[type="submit"], form#editForm button[type="submit"], form.form-edit button[type="submit"]');
        if (btn && v) {
          v.highlightElement(btn);
          setTimeout(function () { btn.click(); }, 150);
        } else if (v) {
          v.speak('No save button found');
        }
      },
      mode: 'action_edit',
      description: 'Save the current record',
      feedback: 'Saving record',
      contextCheck: function () { return !!document.querySelector('form#editForm, form.form-edit'); }
    },
    {
      patterns: ['cancel', 'cancel edit'],
      action: function () {
        var v = window.ahgVoice;
        var btn = document.querySelector('form#editForm a.btn-secondary, form.form-edit a.btn-secondary, a.btn[href*="cancel"], .actions a.btn-secondary');
        if (btn && v) {
          v.highlightElement(btn);
          setTimeout(function () { btn.click(); }, 150);
        } else if (v) {
          v.speak('No cancel button found');
        }
      },
      mode: 'action_edit',
      description: 'Cancel editing',
      feedback: 'Cancelling',
      contextCheck: function () { return !!document.querySelector('form#editForm, form.form-edit'); }
    },
    {
      patterns: ['delete', 'delete record', 'delete this'],
      action: function () {
        var v = window.ahgVoice;
        var btn = document.querySelector('a.btn-danger[href*="delete"], button.btn-danger, a.btn-danger, input[value="Delete"]');
        if (btn && v) {
          v.highlightElement(btn);
          setTimeout(function () { btn.click(); }, 150);
        } else if (v) {
          v.speak('No delete button found');
        }
      },
      mode: 'action_edit',
      description: 'Delete the current record',
      feedback: 'Deleting record',
      contextCheck: function () { return !!document.querySelector('form#editForm, form.form-edit'); }
    },

    // -- Actions: View screens --------------------------------------------
    {
      patterns: ['edit', 'edit record', 'edit this'],
      action: function () {
        var v = window.ahgVoice;
        var btn = document.querySelector('a[href*="/edit"], a.btn[href*="edit"], .actions a[href*="edit"]');
        if (btn && v) {
          v.highlightElement(btn);
          setTimeout(function () { btn.click(); }, 150);
        } else if (v) {
          v.speak('No edit button found');
        }
      },
      mode: 'action_view',
      description: 'Edit the current record',
      feedback: 'Opening editor',
      contextCheck: function () { return !document.querySelector('form#editForm, form.form-edit'); }
    },
    {
      patterns: ['print', 'print page', 'print this'],
      action: function () { window.print(); },
      mode: 'action_view',
      description: 'Print the current page',
      feedback: 'Opening print dialog'
    },
    {
      patterns: ['export csv', 'export to csv', 'download csv'],
      action: function () {
        var v = window.ahgVoice;
        var link = document.querySelector('a[href*="csv"], a[href*="CSV"]');
        if (link && v) {
          v.highlightElement(link);
          setTimeout(function () { link.click(); }, 150);
        } else if (v) {
          v.speak('No CSV export link found');
        }
      },
      mode: 'action_view',
      description: 'Export as CSV',
      feedback: 'Exporting as CSV',
      contextCheck: function () { return !!document.querySelector('a[href*="csv"], a[href*="CSV"]'); }
    },
    {
      patterns: ['export ead', 'export to ead', 'download ead'],
      action: function () {
        var v = window.ahgVoice;
        var link = document.querySelector('a[href*="ead"], a[href*="EAD"]');
        if (link && v) {
          v.highlightElement(link);
          setTimeout(function () { link.click(); }, 150);
        } else if (v) {
          v.speak('No EAD export link found');
        }
      },
      mode: 'action_view',
      description: 'Export as EAD',
      feedback: 'Exporting as EAD',
      contextCheck: function () { return !!document.querySelector('a[href*="ead"], a[href*="EAD"]'); }
    },

    // -- Actions: Browse screens ------------------------------------------
    {
      patterns: ['first result', 'open first', 'click first'],
      action: function () {
        var v = window.ahgVoice;
        var link = document.querySelector('.search-results article a, .result-count ~ * a, #content .search-result a, td a[href]');
        if (link && v) {
          v.highlightElement(link);
          setTimeout(function () { link.click(); }, 150);
        } else if (v) {
          v.speak('No results found');
        }
      },
      mode: 'action_browse',
      description: 'Open the first result',
      feedback: 'Opening first result',
      contextCheck: function () { return !!document.querySelector('.result-count, .pager, .pagination, .browse-results'); }
    },
    {
      patterns: ['sort by title', 'sort title'],
      action: function () {
        var v = window.ahgVoice;
        var opt = document.querySelector('select[name="sort"] option[value="alphabetic"], select[name="sort"] option[value="title"]');
        if (opt) {
          opt.parentElement.value = opt.value;
          if (v) v.highlightElement(opt.parentElement);
          var evt = new Event('change', { bubbles: true });
          opt.parentElement.dispatchEvent(evt);
          var form = opt.parentElement.closest('form');
          if (form) setTimeout(function () { form.submit(); }, 200);
        } else if (v) {
          v.speak('No sort option found');
        }
      },
      mode: 'action_browse',
      description: 'Sort results by title',
      feedback: 'Sorting by title',
      contextCheck: function () { return !!document.querySelector('select[name="sort"]'); }
    },
    {
      patterns: ['sort by date', 'sort date'],
      action: function () {
        var v = window.ahgVoice;
        var opt = document.querySelector('select[name="sort"] option[value="date"], select[name="sort"] option[value="startDate"]');
        if (opt) {
          opt.parentElement.value = opt.value;
          if (v) v.highlightElement(opt.parentElement);
          var evt = new Event('change', { bubbles: true });
          opt.parentElement.dispatchEvent(evt);
          var form = opt.parentElement.closest('form');
          if (form) setTimeout(function () { form.submit(); }, 200);
        } else if (v) {
          v.speak('No date sort option found');
        }
      },
      mode: 'action_browse',
      description: 'Sort results by date',
      feedback: 'Sorting by date',
      contextCheck: function () { return !!document.querySelector('select[name="sort"]'); }
    },

    // -- Global actions ---------------------------------------------------
    {
      patterns: ['toggle advanced search', 'advanced search', 'show advanced search'],
      action: function () {
        var v = window.ahgVoice;
        var toggle = document.querySelector('#toggle-advanced-search, a[href*="advanced"], .advanced-search-toggle, button[data-bs-target*="advanced"]');
        if (toggle && v) {
          v.highlightElement(toggle);
          setTimeout(function () { toggle.click(); }, 150);
        } else if (v) {
          v.speak('No advanced search toggle found');
        }
      },
      mode: 'global',
      description: 'Toggle advanced search',
      feedback: 'Toggling advanced search'
    },
    {
      patterns: ['clear search', 'clear the search'],
      action: function () {
        var input = document.querySelector('#search-form-wrapper input[type="text"], input[name="query"]');
        var form = document.querySelector('#search-form-wrapper form, form[action*="search"]');
        if (input && form) {
          input.value = '';
          form.submit();
        }
      },
      mode: 'global',
      description: 'Clear search and reload',
      feedback: 'Clearing search'
    },
    {
      patterns: ['scroll down', 'page down'],
      action: function () { window.scrollBy({ top: 500, behavior: 'smooth' }); },
      mode: 'global',
      description: 'Scroll down',
      feedback: null // no speech for scroll — too frequent
    },
    {
      patterns: ['scroll up', 'page up'],
      action: function () { window.scrollBy({ top: -500, behavior: 'smooth' }); },
      mode: 'global',
      description: 'Scroll up',
      feedback: null
    },
    {
      patterns: ['scroll to top', 'go to top', 'top of page'],
      action: function () { window.scrollTo({ top: 0, behavior: 'smooth' }); },
      mode: 'global',
      description: 'Scroll to top',
      feedback: 'Scrolling to top'
    },
    {
      patterns: ['scroll to bottom', 'go to bottom', 'bottom of page'],
      action: function () { window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }); },
      mode: 'global',
      description: 'Scroll to bottom',
      feedback: 'Scrolling to bottom'
    },

    // -- Metadata Reading (Phase 4) --------------------------------------
    {
      patterns: ['read image info', 'what is this image', 'read metadata', 'image details'],
      action: function () { var v = window.ahgVoice; if (v) v.readImageMetadata(); },
      mode: 'action_view',
      description: 'Read image metadata aloud',
      feedback: null, // action speaks the metadata directly
      contextCheck: function () {
        return !!document.querySelector('img.img-fluid, .digital-object-viewer, .converted-image-viewer, video, audio');
      }
    },
    {
      patterns: ['read title', 'what is the title'],
      action: function () { var v = window.ahgVoice; if (v) v.readTitle(); },
      mode: 'action_view',
      description: 'Read the record title aloud',
      feedback: null // action speaks the title directly
    },
    {
      patterns: ['read description', 'read scope and content', 'read the description'],
      action: function () { var v = window.ahgVoice; if (v) v.readDescription(); },
      mode: 'action_view',
      description: 'Read the description aloud',
      feedback: null // action speaks the description directly
    },
    {
      patterns: ['stop reading', 'stop speaking', 'shut up', 'be quiet', 'silence'],
      action: function () { var v = window.ahgVoice; if (v) v.stopSpeaking(); },
      mode: 'global',
      description: 'Stop speech output',
      feedback: null // can't speak while stopping speech
    },
    {
      patterns: ['slower', 'speak slower', 'slow down'],
      action: function () { var v = window.ahgVoice; if (v) v.adjustSpeechRate(-0.2); },
      mode: 'global',
      description: 'Decrease speech rate',
      feedback: 'Slowing down'
    },
    {
      patterns: ['faster', 'speak faster', 'speed up'],
      action: function () { var v = window.ahgVoice; if (v) v.adjustSpeechRate(0.2); },
      mode: 'global',
      description: 'Increase speech rate',
      feedback: 'Speeding up'
    },

    // -- AI Image Description (Phase 5) ----------------------------------
    {
      patterns: ['describe image', 'ai describe', 'what do you see', 'generate description', 'generate alt text'],
      action: function () { var v = window.ahgVoice; if (v) v.describeImage(); },
      mode: 'action_view',
      description: 'AI-generate image description',
      feedback: null, // action speaks its own feedback
      contextCheck: function () {
        return !!document.querySelector('img.img-fluid, .digital-object-viewer, .converted-image-viewer');
      }
    },
    {
      patterns: ['save to description', 'save description'],
      action: function () { var v = window.ahgVoice; if (v) v.saveDescription('description'); },
      mode: 'action_view',
      description: 'Save AI description to record',
      feedback: 'Saving to description'
    },
    {
      patterns: ['save to alt text', 'save alt text'],
      action: function () { var v = window.ahgVoice; if (v) v.saveDescription('alt_text'); },
      mode: 'action_view',
      description: 'Save AI description as alt text',
      feedback: 'Saving to alt text'
    },
    {
      patterns: ['save to both'],
      action: function () { var v = window.ahgVoice; if (v) v.saveDescription('both'); },
      mode: 'action_view',
      description: 'Save AI description to both fields',
      feedback: 'Saving to both fields'
    },
    {
      patterns: ['discard', 'discard description', 'nevermind', 'never mind'],
      action: function () { var v = window.ahgVoice; if (v) v.discardDescription(); },
      mode: 'action_view',
      description: 'Discard the AI description',
      feedback: null // action speaks its own feedback
    },

    // -- Dictation --------------------------------------------------------
    {
      patterns: ['start dictating', 'start dictation', 'dictate'],
      action: function () {
        var v = window.ahgVoice;
        if (!v) return;
        var field = document.activeElement;
        if (field && (field.tagName === 'INPUT' && field.type === 'text' || field.tagName === 'TEXTAREA')) {
          v.startDictation(field);
        } else {
          var firstField = document.querySelector('form#editForm textarea, form.form-edit textarea, form#editForm input[type="text"], form.form-edit input[type="text"]');
          if (firstField) {
            v.startDictation(firstField);
          } else {
            v.speak('No text field found. Focus a field first.');
            v.showToast('No text field found', 'warning');
          }
        }
      },
      mode: 'dictation',
      description: 'Start dictating into focused field',
      feedback: 'Starting dictation mode',
      contextCheck: function () { return !!document.querySelector('form#editForm, form.form-edit, textarea, input[type="text"]'); }
    },
    {
      patterns: ['stop dictating', 'stop dictation'],
      action: function () {
        var v = window.ahgVoice;
        if (v && v.mode === 'dictation') { v.stopDictation(); }
        else if (v) { v.speak('Not in dictation mode'); }
      },
      mode: 'dictation',
      description: 'Stop dictating',
      feedback: null // action speaks its own feedback
    },

    // -- Accessibility & Help ---------------------------------------------
    {
      patterns: ['help', 'show commands', 'voice help', 'what can you do'],
      action: function () {
        var modal = document.getElementById('voice-help-modal');
        if (modal && typeof bootstrap !== 'undefined') {
          var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
          bsModal.show();
        }
      },
      mode: 'nav',
      description: 'Show voice commands help',
      feedback: 'Here are the available commands'
    },
    {
      patterns: ['list commands', 'list help commands', 'list all commands', 'read commands', 'read all commands', 'read help'],
      action: function () { var v = window.ahgVoice; if (v) v.listCommands(); },
      mode: 'nav',
      description: 'Read all commands aloud',
      feedback: null // action handles its own speech
    },
    {
      patterns: [/^list (\w+) commands$/, /^read (\w+) commands$/],
      action: function (text) {
        var v = window.ahgVoice;
        if (!v) return;
        var match = text.match(/(?:list|read) (\w+) commands/);
        if (match) { v.listCommands(match[1]); }
        else { v.listCommands(); }
      },
      mode: 'nav',
      description: 'Read commands for a specific group',
      feedback: null
    },
    {
      patterns: ['where am i', 'what page is this', 'what page am i on', 'current page', 'announce page'],
      action: function () { var v = window.ahgVoice; if (v) v.whereAmI(); },
      mode: 'global',
      description: 'Announce current page and available actions',
      feedback: null // action handles its own speech
    },
    {
      patterns: ['how many results', 'result count', 'count results', 'how many records'],
      action: function () { var v = window.ahgVoice; if (v) v.howManyResults(); },
      mode: 'action_browse',
      description: 'Announce the number of results',
      feedback: null, // action handles its own speech
      contextCheck: function () { return !!document.querySelector('.result-count, .pager, .pagination'); }
    }
  ];

  return {
    getCommands: function () { return commands; },

    /**
     * Get commands grouped by mode for the help modal.
     */
    getGrouped: function () {
      var groups = {};
      commands.forEach(function (cmd) {
        var g = cmd.mode || 'other';
        if (!groups[g]) groups[g] = [];
        groups[g].push(cmd);
      });
      return groups;
    }
  };
})();
