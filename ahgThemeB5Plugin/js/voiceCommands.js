/**
 * AHG Voice Commands — Core Engine
 *
 * Uses Web Speech API (SpeechRecognition + SpeechSynthesis) for
 * voice-driven navigation and actions in AtoM Heratio.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class AHGVoiceCommands {
  constructor() {
    this.recognition = null;
    this.synthesis = window.speechSynthesis || null;
    this.isListening = false;
    this.isSupported = false;
    this.mode = 'command'; // 'command' | 'dictation'
    this.confidenceThreshold = 0.4;
    this.language = 'en-US';
    this.speechRate = 1.0;

    // Dictation state
    this.dictationField = null;        // Currently focused field
    this.dictationHistory = [];        // Segments for undo
    this.dictationConfirmClear = false; // Waiting for yes/no after "clear field"

    // UI elements (set after DOM ready)
    this.navbarBtn = null;
    this.floatingBtn = null;
    this.indicator = null;
    this.toastContainer = null;

    // Detect support
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
      this.isSupported = true;
      this.recognition = new SpeechRecognition();
      this.recognition.continuous = false;
      this.recognition.interimResults = false;
      this.recognition.lang = this.language;
      this.recognition.maxAlternatives = 3;
      this._bindRecognitionEvents();
    }
  }

  /**
   * Initialize — call on DOMContentLoaded.
   */
  init() {
    if (!this.isSupported) {
      // Hide all voice UI if not supported
      document.querySelectorAll('.voice-ui').forEach(el => el.style.display = 'none');
      return;
    }

    this.floatingBtn = document.getElementById('voice-floating-btn');
    this.indicator = document.getElementById('voice-indicator');
    this.toastContainer = document.getElementById('voice-toast-container');

    // Inject navbar mic button next to the search box
    this._injectNavbarButton();

    // Bind click handlers
    if (this.navbarBtn) {
      this.navbarBtn.addEventListener('click', (e) => {
        e.preventDefault();
        this.toggle();
      });
    }
    if (this.floatingBtn) {
      this.floatingBtn.addEventListener('click', (e) => {
        e.preventDefault();
        this.toggle();
      });
    }

    // Inject field mic icons on edit pages
    this._injectFieldMics();

    // Show UI
    document.querySelectorAll('.voice-ui').forEach(el => el.style.display = '');
  }

  /**
   * Toggle listening on/off.
   */
  toggle() {
    if (this.isListening) {
      this.stopListening();
    } else {
      this.startListening();
    }
  }

  /**
   * Start listening for voice commands.
   */
  startListening() {
    if (!this.isSupported || this.isListening) return;

    try {
      this.recognition.start();
      this.isListening = true;
      this._updateUI(true);
    } catch (e) {
      // Already started or permission denied
      console.warn('Voice: could not start recognition', e);
    }
  }

  /**
   * Stop listening.
   */
  stopListening() {
    if (!this.isSupported || !this.isListening) return;

    try {
      this.recognition.stop();
    } catch (e) {
      // Ignore
    }
    this.isListening = false;
    this._updateUI(false);
  }

  // ---------------------------------------------------------------
  //  Dictation Mode (Phase 3)
  // ---------------------------------------------------------------

  /**
   * Start dictation into a specific text field.
   */
  startDictation(field) {
    if (!this.isSupported || !field) return;

    this.dictationField = field;
    this.dictationHistory = [];
    this.dictationConfirmClear = false;
    this.mode = 'dictation';

    // Switch recognition to continuous + interim
    try { this.recognition.stop(); } catch (e) { /* ignore */ }

    const self = this;
    setTimeout(function () {
      self.recognition.continuous = true;
      self.recognition.interimResults = true;

      // Mark field active
      field.classList.add('voice-dictation-active');
      field.focus();

      // Update field mic icon if present
      var mic = field.parentElement && field.parentElement.querySelector('.voice-field-mic');
      if (mic) mic.classList.add('active');

      try {
        self.recognition.start();
        self.isListening = true;
        self._updateUI(true);
        self.showToast('Dictation started — speak into field', 'info');
      } catch (e) {
        console.warn('Voice: could not start dictation', e);
      }
    }, 200);
  }

  /**
   * Stop dictation and return to command mode.
   */
  stopDictation() {
    if (this.mode !== 'dictation') return;

    // Remove interim text
    this._clearInterim();

    // Clean up field state
    if (this.dictationField) {
      this.dictationField.classList.remove('voice-dictation-active');
      var mic = this.dictationField.parentElement &&
        this.dictationField.parentElement.querySelector('.voice-field-mic');
      if (mic) mic.classList.remove('active');
    }

    this.mode = 'command';
    this.dictationField = null;
    this.dictationHistory = [];
    this.dictationConfirmClear = false;

    // Revert recognition to one-shot
    try { this.recognition.stop(); } catch (e) { /* ignore */ }

    const self = this;
    setTimeout(function () {
      self.recognition.continuous = false;
      self.recognition.interimResults = false;
      self.isListening = false;
      self._updateUI(false);
    }, 200);

    this.showToast('Dictation stopped', 'info');
    this.speak('Dictation stopped');
  }

  /**
   * Punctuation/sub-command map for dictation mode.
   */
  static get DICTATION_SUBS() {
    return {
      'new line': '\n',
      'newline': '\n',
      'new paragraph': '\n\n',
      'period': '. ',
      'full stop': '. ',
      'comma': ', ',
      'question mark': '? ',
      'exclamation mark': '! ',
      'exclamation point': '! ',
      'colon': ': ',
      'semicolon': '; ',
      'open quote': '\u201C',
      'close quote': '\u201D',
      'open bracket': '(',
      'close bracket': ')',
      'dash': ' \u2013 ',
      'hyphen': '-'
    };
  }

  /**
   * Process a dictation transcript segment.
   */
  _processDictation(transcript, isFinal) {
    if (!this.dictationField) return;

    var text = transcript.trim();
    var lower = text.toLowerCase();

    // Handle "clear field" confirmation flow
    if (this.dictationConfirmClear) {
      this.dictationConfirmClear = false;
      if (isFinal && (lower === 'yes' || lower === 'yeah' || lower === 'yep')) {
        this.dictationField.value = '';
        this.dictationHistory = [];
        this.showToast('Field cleared', 'success');
        this.speak('Field cleared');
      } else if (isFinal) {
        this.showToast('Clear cancelled', 'info');
        this.speak('Clear cancelled');
      }
      return;
    }

    // Check for dictation sub-commands (only on final results)
    if (isFinal) {
      // Stop dictating
      if (lower === 'stop dictating' || lower === 'stop dictation') {
        this.stopDictation();
        return;
      }

      // Undo last
      if (lower === 'undo' || lower === 'undo last' || lower === 'undo that') {
        if (this.dictationHistory.length > 0) {
          var last = this.dictationHistory.pop();
          var val = this.dictationField.value;
          if (val.endsWith(last)) {
            this.dictationField.value = val.slice(0, -last.length);
          }
          this.showToast('Undone: "' + last.trim().substring(0, 30) + '"', 'info');
        } else {
          this.speak('Nothing to undo');
        }
        return;
      }

      // Clear field
      if (lower === 'clear field' || lower === 'clear the field') {
        this.dictationConfirmClear = true;
        this.speak('Are you sure? Say yes or no');
        this.showToast('Say "yes" to clear or "no" to cancel', 'warning');
        return;
      }

      // Read back
      if (lower === 'read back' || lower === 'read it back' || lower === 'read field') {
        var content = this.dictationField.value.trim();
        if (content) {
          this.speak(content);
          this.showToast('Reading back...', 'info');
        } else {
          this.speak('Field is empty');
        }
        return;
      }

      // Check for punctuation sub-commands
      var subs = AHGVoiceCommands.DICTATION_SUBS;
      if (subs[lower] !== undefined) {
        this._clearInterim();
        var punct = subs[lower];
        this._insertAtCursor(this.dictationField, punct);
        this.dictationHistory.push(punct);
        return;
      }

      // Final text — insert it
      this._clearInterim();
      // Capitalize first letter of sentence
      var insertText = this._smartCapitalize(text);
      // Add trailing space
      insertText += ' ';
      this._insertAtCursor(this.dictationField, insertText);
      this.dictationHistory.push(insertText);
    } else {
      // Interim result — show grayed preview
      this._showInterim(text);
    }
  }

  /**
   * Insert text at cursor position in a field.
   */
  _insertAtCursor(field, text) {
    var start = field.selectionStart;
    var end = field.selectionEnd;
    var val = field.value;

    if (typeof start === 'number') {
      field.value = val.substring(0, start) + text + val.substring(end);
      var newPos = start + text.length;
      field.selectionStart = newPos;
      field.selectionEnd = newPos;
    } else {
      // Fallback: append
      field.value += text;
    }

    // Trigger input event for any listeners
    field.dispatchEvent(new Event('input', { bubbles: true }));
  }

  /**
   * Capitalize first letter if preceding context suggests start of sentence.
   */
  _smartCapitalize(text) {
    if (!text) return text;
    if (!this.dictationField) return text;

    var val = this.dictationField.value;
    // Capitalize at start of field or after sentence-ending punctuation
    if (!val || /[.!?]\s*$/.test(val) || /\n\s*$/.test(val)) {
      return text.charAt(0).toUpperCase() + text.slice(1);
    }
    return text;
  }

  /**
   * Show interim (not-yet-final) text as a grayed span after the field.
   */
  _showInterim(text) {
    if (!this.dictationField) return;

    var container = this.dictationField.parentElement;
    if (!container) return;

    var span = container.querySelector('.voice-interim-text');
    if (!span) {
      span = document.createElement('span');
      span.className = 'voice-interim-text';
      container.appendChild(span);
    }
    span.textContent = text;
  }

  /**
   * Remove interim text display.
   */
  _clearInterim() {
    if (!this.dictationField || !this.dictationField.parentElement) return;
    var span = this.dictationField.parentElement.querySelector('.voice-interim-text');
    if (span) span.remove();
  }

  /**
   * Inject small mic icons into text inputs and textareas on edit pages.
   */
  _injectFieldMics() {
    // Only inject on edit pages
    var form = document.querySelector('form#editForm, form.form-edit');
    if (!form) return;

    var self = this;
    var fields = form.querySelectorAll('input[type="text"], textarea');

    fields.forEach(function (field) {
      // Skip hidden/readonly fields
      if (field.type === 'hidden' || field.readOnly || field.disabled) return;
      // Skip fields that are too small (like date pickers)
      if (field.offsetWidth < 100) return;

      // Ensure parent has relative positioning
      var parent = field.parentElement;
      if (!parent) return;
      var pos = window.getComputedStyle(parent).position;
      if (pos === 'static') {
        parent.style.position = 'relative';
      }

      // Create mic icon
      var mic = document.createElement('button');
      mic.type = 'button';
      mic.className = 'voice-field-mic';
      mic.setAttribute('aria-label', 'Dictate into this field');
      mic.title = 'Dictate';
      mic.innerHTML = '<i class="bi bi-mic"></i>';
      mic.tabIndex = -1;

      // Position differently for textarea vs input
      if (field.tagName === 'TEXTAREA') {
        mic.classList.add('voice-field-mic-textarea');
      }

      // Click handler
      mic.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (self.mode === 'dictation' && self.dictationField === field) {
          self.stopDictation();
        } else {
          if (self.mode === 'dictation') {
            self.stopDictation();
          }
          self.startDictation(field);
        }
      });

      parent.appendChild(mic);
    });
  }

  /**
   * Process a recognized command transcript.
   */
  processCommand(transcript, confidence) {
    const text = transcript.toLowerCase().trim();

    console.log('[Voice] Heard: "' + text + '" (confidence: ' + (confidence * 100).toFixed(1) + '%)');

    if (confidence < this.confidenceThreshold) {
      this.showToast('Low confidence (' + (confidence * 100).toFixed(0) + '%): "' + transcript + '"', 'warning');
      return;
    }

    // Try matching against registered commands
    if (typeof AHGVoiceRegistry === 'undefined') {
      this.showToast('Voice registry not loaded', 'danger');
      return;
    }

    const commands = AHGVoiceRegistry.getCommands();
    let matched = false;

    for (const cmd of commands) {
      if (this._matchCommand(text, cmd)) {
        matched = true;

        // Context check — block if command not available on this page
        if (typeof cmd.contextCheck === 'function' && !cmd.contextCheck()) {
          this.showToast(cmd.description + ' — not available here', 'warning');
          this.speak('That command is not available on this page');
          break;
        }

        this.showToast(cmd.description, 'success');
        try {
          cmd.action(text);
        } catch (e) {
          console.error('Voice command error:', e);
          this.showToast('Command failed', 'danger');
        }
        break;
      }
    }

    if (!matched) {
      console.log('[Voice] No match for: "' + text + '" — tried ' + commands.length + ' commands');
      this.showToast('Not recognized: "' + transcript + '"', 'warning');
    }
  }

  /**
   * Speak text aloud via SpeechSynthesis.
   */
  speak(text) {
    if (!this.synthesis) return;

    // Cancel any current speech
    this.synthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.rate = this.speechRate;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;
    utterance.lang = this.language;

    // Prefer a natural voice if available
    const voices = this.synthesis.getVoices();
    const preferred = voices.find(v => v.lang.startsWith('en') && v.localService);
    if (preferred) {
      utterance.voice = preferred;
    }

    this.synthesis.speak(utterance);
  }

  /**
   * Show a toast notification.
   */
  showToast(message, type) {
    if (!this.toastContainer) return;

    const colorMap = {
      success: 'bg-success text-white',
      warning: 'bg-warning text-dark',
      danger: 'bg-danger text-white',
      info: 'bg-info text-white'
    };

    const toast = document.createElement('div');
    toast.className = 'toast show voice-toast ' + (colorMap[type] || 'bg-secondary text-white');
    toast.setAttribute('role', 'alert');
    toast.innerHTML = '<div class="toast-body d-flex align-items-center">' +
      '<i class="bi bi-mic-fill me-2"></i>' +
      '<span>' + this._escHtml(message) + '</span>' +
      '</div>';

    this.toastContainer.appendChild(toast);

    // Auto-dismiss after 2s
    setTimeout(() => {
      toast.classList.add('voice-toast-exit');
      setTimeout(() => toast.remove(), 300);
    }, 2000);
  }

  // ---------------------------------------------------------------
  //  Metadata Reading (Phase 4)
  // ---------------------------------------------------------------

  /**
   * Read image/digital object metadata from the current page.
   */
  readImageMetadata() {
    var meta = this._gatherPageMetadata();

    if (!meta.hasDigitalObject) {
      this.speak('No digital object found on this page');
      this.showToast('No digital object on this page', 'warning');
      return;
    }

    // Build natural language description
    var parts = [];
    if (meta.mediaType) {
      parts.push('This is a ' + meta.mediaType);
    }
    if (meta.title) {
      parts.push('titled "' + meta.title + '"');
    }
    if (parts.length) {
      var sentence = parts.join(' ') + '.';
      sentence = sentence.charAt(0).toUpperCase() + sentence.slice(1);
      this.speak(sentence);
    }
    if (meta.description) {
      // Pause briefly, then read description
      var self = this;
      setTimeout(function () { self.speak(meta.description); }, 500);
    }
    if (meta.fileSize || meta.dimensions) {
      var details = [];
      if (meta.fileSize) details.push('File size is ' + meta.fileSize);
      if (meta.dimensions) details.push('dimensions ' + meta.dimensions);
      var detailSentence = details.join(', ') + '.';
      var self = this;
      setTimeout(function () { self.speak(detailSentence); }, meta.description ? 3000 : 1000);
    }
    if (meta.rights) {
      var self = this;
      setTimeout(function () { self.speak(meta.rights); }, meta.description ? 5000 : 2000);
    }
    if (!meta.description) {
      var self = this;
      var hint = 'No description available.';
      setTimeout(function () { self.speak(hint); }, 1500);
    }

    this.showToast('Reading metadata...', 'info');
  }

  /**
   * Read just the title of the current record aloud.
   */
  readTitle() {
    var title = this._getPageTitle();
    if (title) {
      this.speak(title);
      this.showToast('Title: ' + title, 'info');
    } else {
      this.speak('No title found');
      this.showToast('No title found', 'warning');
    }
  }

  /**
   * Read the description/scope-and-content of the current record.
   */
  readDescription() {
    var desc = this._getPageDescription();
    if (desc) {
      this.speak(desc);
      this.showToast('Reading description...', 'info');
    } else {
      this.speak('No description found on this page');
      this.showToast('No description found', 'warning');
    }
  }

  /**
   * Stop any current speech synthesis.
   */
  stopSpeaking() {
    if (this.synthesis) {
      this.synthesis.cancel();
      this.showToast('Speech stopped', 'info');
    }
  }

  /**
   * Adjust speech rate.
   */
  adjustSpeechRate(delta) {
    this.speechRate = Math.max(0.5, Math.min(2.0, this.speechRate + delta));
    this.showToast('Speech rate: ' + this.speechRate.toFixed(1) + 'x', 'info');
  }

  /**
   * Gather all available metadata from the current page DOM.
   */
  _gatherPageMetadata() {
    var meta = {
      hasDigitalObject: false,
      title: null,
      description: null,
      mediaType: null,
      mimeType: null,
      fileSize: null,
      dimensions: null,
      rights: null,
      altText: null,
      digitalObjectId: null
    };

    // Check for digital object viewer
    var img = document.querySelector('.digital-object-reference img, .converted-image-viewer img.img-fluid, #content img.img-fluid, .digital-object-viewer img');
    var video = document.querySelector('#content video, .digital-object-viewer video');
    var audio = document.querySelector('#content audio, .digital-object-viewer audio');

    if (img || video || audio) {
      meta.hasDigitalObject = true;
    }

    // Alt text from image
    if (img) {
      meta.altText = img.getAttribute('alt') || null;
      meta.mediaType = 'image';
    }
    if (video) { meta.mediaType = 'video'; }
    if (audio) { meta.mediaType = 'audio'; }

    // Try to get digital object ID from DOM
    var doIdEl = document.querySelector('[id^="convert-img-"], [id^="archive-content-"], [id^="text-content-"], [id^="viewer-3d-"]');
    if (doIdEl) {
      var idMatch = doIdEl.id.match(/(\d+)$/);
      if (idMatch) meta.digitalObjectId = parseInt(idMatch[1], 10);
    }

    // Card header may show media type label
    var cardHeader = document.querySelector('.converted-image-viewer .card-header span, .card-header span');
    if (cardHeader) {
      var label = cardHeader.textContent.trim();
      if (label && label !== '') meta.mediaType = label.toLowerCase();
    }

    // Title
    meta.title = this._getPageTitle();

    // Description
    meta.description = this._getPageDescription();

    // File size — look in metadata fields
    var fields = document.querySelectorAll('#content .field, .row.mb-3, .digitalObjectMetadata tr, .field-group');
    fields.forEach(function (field) {
      var text = field.textContent || '';
      if (/file\s*size/i.test(text)) {
        var val = text.replace(/.*file\s*size[:\s]*/i, '').trim();
        if (val) meta.fileSize = val.split('\n')[0].trim();
      }
      if (/dimensions/i.test(text)) {
        var val = text.replace(/.*dimensions[:\s]*/i, '').trim();
        if (val) meta.dimensions = val.split('\n')[0].trim();
      }
      if (/mime\s*type/i.test(text)) {
        var val = text.replace(/.*mime\s*type[:\s]*/i, '').trim();
        if (val) meta.mimeType = val.split('\n')[0].trim();
      }
    });

    // Image dimensions from natural size
    if (img && img.naturalWidth) {
      meta.dimensions = meta.dimensions || (img.naturalWidth + ' by ' + img.naturalHeight + ' pixels');
    }

    // Rights
    var rightsEl = document.querySelector('.rights-area, [class*="rights"], .embargo-notice, #rights-collapse');
    if (rightsEl) {
      meta.rights = rightsEl.textContent.trim().substring(0, 200);
    }

    return meta;
  }

  /**
   * Get the page title from DOM.
   */
  _getPageTitle() {
    var titleEl = document.querySelector('h1.mb-0, .multiline-header h1, #content > h1, h1[property="dc:title"]');
    if (titleEl) return titleEl.textContent.trim();
    // Fallback to document title
    var docTitle = document.title.replace(/\s*[-|].*$/, '').trim();
    return docTitle || null;
  }

  /**
   * Get the page description from DOM.
   */
  _getPageDescription() {
    // Try scope and content first
    var desc = document.querySelector('.scope-and-content, div[class*="scope"], #description-collapse .field-value');
    if (desc) {
      var text = desc.textContent.trim();
      if (text) return text;
    }
    // Try general description
    desc = document.querySelector('.description, #content .field .description, div.description');
    if (desc) {
      var text = desc.textContent.trim();
      if (text) return text;
    }
    return null;
  }

  // ---------------------------------------------------------------
  //  AI Image Description (Phase 5)
  // ---------------------------------------------------------------

  /**
   * AI-powered image description via server-side Ollama/Cloud LLM.
   */
  describeImage() {
    var meta = this._gatherPageMetadata();

    if (!meta.hasDigitalObject) {
      this.speak('No digital object found on this page');
      this.showToast('No digital object on this page', 'warning');
      return;
    }

    // We need the digital object ID — try DOM, then URL
    var doId = meta.digitalObjectId;
    if (!doId) {
      // Try to find from the page URL or data attributes
      var doLink = document.querySelector('a[href*="digitalobject/"], a[download]');
      if (doLink) {
        var hrefMatch = doLink.href.match(/digitalobject\/(\d+)|\/uploads\/.*?(\d+)/);
        if (hrefMatch) doId = parseInt(hrefMatch[1] || hrefMatch[2], 10);
      }
    }

    if (!doId) {
      this.speak('Cannot identify the digital object. Try from a record view page.');
      this.showToast('Digital object ID not found', 'warning');
      return;
    }

    var self = this;
    this.speak('Analyzing image, please wait');
    this.showToast('AI analyzing image...', 'info');

    // Show processing state on indicator
    if (this.indicator) {
      this.indicator.classList.add('voice-indicator-processing');
    }

    // Get CSRF token if available
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfInput = document.querySelector('input[name="csrf_token"], input[name="_csrf_token"]');
    var csrfToken = (csrfMeta && csrfMeta.content) || (csrfInput && csrfInput.value) || '';

    var formData = new FormData();
    formData.append('digital_object_id', doId);
    if (csrfToken) formData.append('csrf_token', csrfToken);

    fetch('/index.php/ahgVoice/describeImage', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (self.indicator) self.indicator.classList.remove('voice-indicator-processing');

      if (data.success) {
        var source = data.source || 'AI';
        self.speak(data.description);
        self.showToast('Description generated (' + source + ')', 'success');

        // Store for save flow
        self._pendingDescription = data.description;
        self._pendingDoId = doId;
        self._pendingInfoObjectId = data.information_object_id || null;

        // After speaking, prompt for save
        setTimeout(function () {
          self._promptSaveDescription();
        }, Math.max(2000, data.description.length * 50));
      } else {
        self.speak(data.error || 'AI description failed');
        self.showToast(data.error || 'AI description failed', 'danger');
      }
    })
    .catch(function (err) {
      if (self.indicator) self.indicator.classList.remove('voice-indicator-processing');
      console.error('[Voice] AI describe error:', err);
      self.speak('Failed to contact AI service');
      self.showToast('AI service error', 'danger');
    });
  }

  /**
   * Prompt user to save the AI-generated description.
   */
  _promptSaveDescription() {
    if (!this._pendingDescription) return;

    this.speak('Would you like to save this description? Say save to description, save to alt text, save to both, or discard.');
    this.showToast('Say "save to description", "save to alt text", "save to both", or "discard"', 'info');
    this._awaitingSaveCommand = true;
  }

  /**
   * Execute save of AI description to server.
   */
  saveDescription(target, mode) {
    if (!this._pendingDescription || !this._pendingInfoObjectId) {
      this.speak('No pending description to save');
      return;
    }

    var self = this;
    var formData = new FormData();
    formData.append('information_object_id', this._pendingInfoObjectId);
    formData.append('description', this._pendingDescription);
    formData.append('save_target', target); // 'description' | 'alt_text' | 'both'
    formData.append('save_mode', mode || 'replace');

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfInput = document.querySelector('input[name="csrf_token"], input[name="_csrf_token"]');
    var csrfToken = (csrfMeta && csrfMeta.content) || (csrfInput && csrfInput.value) || '';
    if (csrfToken) formData.append('csrf_token', csrfToken);

    fetch('/index.php/ahgVoice/saveDescription', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (data.success) {
        self.speak('Description saved');
        self.showToast('Description saved to ' + target, 'success');
      } else {
        self.speak(data.error || 'Save failed');
        self.showToast(data.error || 'Save failed', 'danger');
      }
      self._pendingDescription = null;
      self._pendingInfoObjectId = null;
      self._awaitingSaveCommand = false;
    })
    .catch(function (err) {
      console.error('[Voice] Save description error:', err);
      self.speak('Failed to save description');
      self.showToast('Save error', 'danger');
    });
  }

  /**
   * Discard the pending AI description.
   */
  discardDescription() {
    this._pendingDescription = null;
    this._pendingInfoObjectId = null;
    this._awaitingSaveCommand = false;
    this.speak('Description discarded');
    this.showToast('Description discarded', 'info');
  }

  // ---------------------------------------------------------------
  //  Private
  // ---------------------------------------------------------------

  /**
   * Inject a mic button into the navbar (next to search).
   * Done via JS to avoid modifying the header template.
   */
  _injectNavbarButton() {
    // Find the navbar-nav ul that contains the main menu items
    var navbarNav = document.querySelector('#top-bar .navbar-nav');
    if (!navbarNav) return;

    var li = document.createElement('li');
    li.className = 'nav-item d-none d-sm-flex align-items-center';

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'voice-navbar-btn';
    btn.className = 'voice-navbar-btn';
    btn.setAttribute('aria-label', 'Voice commands');
    btn.title = 'Voice commands';
    btn.innerHTML = '<i class="bi bi-mic"></i>';

    li.appendChild(btn);

    // Insert as first child of the nav
    navbarNav.insertBefore(li, navbarNav.firstChild);

    this.navbarBtn = btn;
  }

  // ---------------------------------------------------------------
  //  Context Detection (Phase 2)
  // ---------------------------------------------------------------

  /**
   * Detect the current page context.
   * Returns an object with boolean flags for each context type.
   */
  static detectContext() {
    return {
      edit: !!document.querySelector('form#editForm, form.form-edit, body.edit form'),
      view: !!document.querySelector('.informationObject, .section, #content .field, body.index .h2, body.show'),
      browse: !!document.querySelector('.result-count, .pager, .pagination, .browse-results, #facets'),
      admin: /\/admin|\/ahgSettings/.test(window.location.pathname)
    };
  }

  /**
   * Briefly highlight an element to give visual feedback.
   */
  highlightElement(el) {
    if (!el) return;
    el.classList.add('voice-highlight');
    setTimeout(() => el.classList.remove('voice-highlight'), 600);
  }

  /**
   * Find and click an element, with highlight feedback.
   * Returns true if element was found and clicked.
   */
  clickElement(selector) {
    var el = document.querySelector(selector);
    if (el) {
      this.highlightElement(el);
      setTimeout(() => el.click(), 150);
      return true;
    }
    return false;
  }

  _bindRecognitionEvents() {
    this.recognition.addEventListener('result', (event) => {
      const result = event.results[event.results.length - 1];
      const transcript = result[0].transcript;
      const confidence = result[0].confidence;

      if (this.mode === 'dictation') {
        // In dictation mode, handle interim + final results
        this._processDictation(transcript, result.isFinal);
      } else if (result.isFinal) {
        // In command mode, only process final results
        this.processCommand(transcript, confidence);
      }
    });

    this.recognition.addEventListener('end', () => {
      if (this.mode === 'dictation' && this.isListening) {
        // In dictation mode, auto-restart recognition (continuous listening)
        try { this.recognition.start(); } catch (e) { /* ignore */ }
        return;
      }
      this.isListening = false;
      this._updateUI(false);
    });

    this.recognition.addEventListener('error', (event) => {
      if (event.error === 'not-allowed') {
        this.showToast('Microphone access denied. Please enable it in browser settings.', 'danger');
      } else if (event.error === 'no-speech') {
        this.showToast('No speech detected', 'warning');
      } else if (event.error !== 'aborted') {
        this.showToast('Recognition error: ' + event.error, 'danger');
      }
      this.isListening = false;
      this._updateUI(false);
    });
  }

  _updateUI(listening) {
    // Navbar button
    if (this.navbarBtn) {
      const icon = this.navbarBtn.querySelector('i');
      if (listening) {
        this.navbarBtn.classList.add('voice-active');
        if (icon) { icon.className = 'bi bi-mic-fill'; }
      } else {
        this.navbarBtn.classList.remove('voice-active');
        if (icon) { icon.className = 'bi bi-mic'; }
      }
    }

    // Floating button
    if (this.floatingBtn) {
      if (listening) {
        this.floatingBtn.classList.add('voice-active');
        this.floatingBtn.classList.toggle('voice-dictating', this.mode === 'dictation');
      } else {
        this.floatingBtn.classList.remove('voice-active', 'voice-dictating');
      }
    }

    // Indicator bar — blue for command mode, green for dictation
    if (this.indicator) {
      this.indicator.classList.toggle('voice-indicator-active', listening && this.mode !== 'dictation');
      this.indicator.classList.toggle('voice-indicator-dictation', listening && this.mode === 'dictation');
    }
  }

  /**
   * Match transcript against a command definition.
   */
  _matchCommand(text, cmd) {
    if (cmd.pattern instanceof RegExp) {
      return cmd.pattern.test(text);
    }
    if (typeof cmd.pattern === 'string') {
      return text === cmd.pattern;
    }
    if (Array.isArray(cmd.patterns)) {
      for (const p of cmd.patterns) {
        if (p instanceof RegExp && p.test(text)) return true;
        if (typeof p === 'string' && text === p) return true;
      }
    }
    return false;
  }

  _escHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function () {
  window.ahgVoice = new AHGVoiceCommands();
  window.ahgVoice.init();
});
