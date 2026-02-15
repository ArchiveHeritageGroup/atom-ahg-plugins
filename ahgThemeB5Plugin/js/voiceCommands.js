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
    this.confidenceThreshold = 0.7;
    this.language = 'en-US';
    this.speechRate = 1.0;

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

  /**
   * Process a recognized command transcript.
   */
  processCommand(transcript, confidence) {
    const text = transcript.toLowerCase().trim();

    if (confidence < this.confidenceThreshold) {
      this.showToast('Low confidence: "' + transcript + '"', 'warning');
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
      this.showToast('Not recognized: "' + transcript + '"', 'warning');
      this.speak('Command not recognized');
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

  _bindRecognitionEvents() {
    this.recognition.addEventListener('result', (event) => {
      const result = event.results[event.results.length - 1];
      if (result.isFinal) {
        const transcript = result[0].transcript;
        const confidence = result[0].confidence;
        this.processCommand(transcript, confidence);
      }
    });

    this.recognition.addEventListener('end', () => {
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
      } else {
        this.floatingBtn.classList.remove('voice-active');
      }
    }

    // Indicator bar
    if (this.indicator) {
      this.indicator.classList.toggle('voice-indicator-active', listening);
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
