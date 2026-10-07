/**
 * Ask the Archive - launcher and chat panel.
 *
 * Built with DOM calls only (no innerHTML of server text), so an answer can
 * never inject markup. Configuration comes from data- attributes on
 * #ahg-chatbot. The conversation lives in sessionStorage for this tab only.
 *
 * Other plugins can open it: window.AhgChatbot.open('optional question').
 */
(function () {
  'use strict';

  var root = document.getElementById('ahg-chatbot');
  if (!root || root.dataset.ready) {
    return;
  }
  root.dataset.ready = '1';

  var cfg = root.dataset;
  var STORE = 'ahgChatbot';
  var state = load();
  var busy = false;

  function load() {
    try {
      var s = JSON.parse(sessionStorage.getItem(STORE) || 'null');
      if (s && Array.isArray(s.turns)) {
        return s;
      }
    } catch (e) { /* private window or blocked storage */ }
    return { session_id: '', turns: [], open: false, mode: 'collection' };
  }

  function save() {
    try {
      sessionStorage.setItem(STORE, JSON.stringify({ session_id: state.session_id, turns: state.turns.slice(-30), open: state.open, mode: state.mode }));
    } catch (e) { /* not essential */ }
  }

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) { n.className = cls; }
    if (text !== undefined && text !== null) { n.textContent = text; }
    return n;
  }

  // Launcher
  var label = cfg.label || 'Ask the archive';
  var button = el('button', 'ahg-chatbot-launcher');
  button.type = 'button';
  button.setAttribute('aria-label', label);
  button.setAttribute('aria-expanded', 'false');
  button.setAttribute('aria-controls', 'ahg-chatbot-panel');
  button.title = label;
  button.appendChild(el('span', 'ahg-chatbot-launcher-icon', '?'));
  button.appendChild(el('span', 'ahg-chatbot-launcher-text', label));

  // Panel
  // A div, not a section: themes style section generically (AHG theme: position relative !important).
  var panel = el('div', 'ahg-chatbot-panel');
  panel.id = 'ahg-chatbot-panel';
  panel.setAttribute('role', 'dialog');
  panel.setAttribute('aria-label', label);
  panel.hidden = true;

  var head = el('header', 'ahg-chatbot-head');
  head.appendChild(el('h2', 'ahg-chatbot-title', label));
  var reset = el('button', 'ahg-chatbot-icon-btn', 'New');
  reset.type = 'button';
  reset.title = 'Start a new conversation';
  var close = el('button', 'ahg-chatbot-icon-btn', '×');
  close.type = 'button';
  close.setAttribute('aria-label', 'Close');
  head.appendChild(reset);
  head.appendChild(close);

  // Two modes when the help articles are installed: the catalogue, and how to use the site.
  var MODES = {
    collection: { tab: 'Ask the collection', placeholder: 'Ask about the collection...', hint: 'Ask a question about the records in this catalogue - for example a person, a place or a subject. You can also ask when you can visit or who to contact. Answers link to the records they come from.' },
    help: { tab: 'Help using the site', placeholder: 'How do I...?', hint: 'Ask how to use this website - for example how to search, request access, or use the image viewer. Answers link to the help articles they come from.' }
  };
  var helpOn = cfg.help === '1';
  if (!helpOn || !MODES[state.mode]) { state.mode = 'collection'; }
  var tabs = null;
  if (helpOn) {
    tabs = el('div', 'ahg-chatbot-tabs');
    tabs.setAttribute('role', 'tablist');
    Object.keys(MODES).forEach(function (m) {
      var t = el('button', 'ahg-chatbot-tab', MODES[m].tab);
      t.type = 'button';
      t.setAttribute('role', 'tab');
      t.dataset.mode = m;
      t.addEventListener('click', function () { setMode(m); });
      tabs.appendChild(t);
    });
  }

  var log = el('div', 'ahg-chatbot-log');
  log.setAttribute('aria-live', 'polite');

  var notice = cfg.notice ? el('p', 'ahg-chatbot-notice', cfg.notice) : null;

  var form = el('form', 'ahg-chatbot-form');
  var input = el('textarea', 'ahg-chatbot-input');
  input.rows = 2;
  input.maxLength = 1000;
  input.placeholder = 'Ask about the collection...';
  input.setAttribute('aria-label', 'Your question');
  var send = el('button', 'ahg-chatbot-send', 'Ask');
  send.type = 'submit';
  form.appendChild(input);
  form.appendChild(send);

  panel.appendChild(head);
  if (tabs) { panel.appendChild(tabs); }
  panel.appendChild(log);
  if (notice) { panel.appendChild(notice); }
  panel.appendChild(form);
  root.appendChild(button);
  root.appendChild(panel);

  // Rendering
  // Light formatting only: paragraphs, "- " bullets, "1." steps and **bold**, all as text nodes.
  function formatted(text) {
    var box = el('div', 'ahg-chatbot-text');
    var list = null;
    String(text).split(/\n/).forEach(function (line) {
      var bullet = /^\s*[-*]\s+(.*)$/.exec(line);
      var step = /^\s*\d+[.)]\s+(.*)$/.exec(line);
      var item = bullet || step;
      if (item) {
        var tag = step ? 'OL' : 'UL';
        if (!list || list.tagName !== tag) { list = el(tag.toLowerCase()); box.appendChild(list); }
        list.appendChild(inline(el('li'), item[1]));
        return;
      }
      if (line.trim() !== '') { list = null; box.appendChild(inline(el('p'), line)); }
    });
    return box;
  }

  function inline(node, line) {
    line.split(/\*\*(.+?)\*\*/).forEach(function (part, i) {
      node.appendChild(i % 2 ? el('strong', null, part) : document.createTextNode(part));
    });
    return node;
  }

  function renderTurn(turn) {
    var item = el('div', 'ahg-chatbot-msg ahg-chatbot-' + turn.role);
    if (turn.role === 'user') {
      item.appendChild(el('p', 'ahg-chatbot-text', turn.content));
      log.appendChild(item);
      return;
    }
    item.appendChild(formatted(turn.content));

    if (turn.sources && turn.sources.length) {
      var src = el('div', 'ahg-chatbot-sources');
      src.appendChild(el('div', 'ahg-chatbot-sources-head', turn.mode === 'help' ? 'Help articles used' : 'Sources'));
      turn.sources.forEach(function (s) {
        var a = el('a', 'ahg-chatbot-source');
        a.href = s.url;
        if (s.thumbnail) {
          var img = el('img');
          img.src = s.thumbnail;
          img.alt = '';
          img.loading = 'lazy';
          a.appendChild(img);
        }
        var t = el('span', null, s.title);
        if (s.identifier) { t.appendChild(el('small', null, ' ' + s.identifier)); }
        a.appendChild(t);
        src.appendChild(a);
      });
      item.appendChild(src);
    }

    if (turn.message_id) {
      var rate = el('div', 'ahg-chatbot-rate');
      rate.appendChild(el('span', null, 'Was this helpful?'));
      [['Yes', 1], ['No', -1]].forEach(function (opt) {
        var b = el('button', 'ahg-chatbot-rate-btn', opt[0]);
        b.type = 'button';
        b.setAttribute('aria-pressed', turn.rating === opt[1] ? 'true' : 'false');
        b.addEventListener('click', function () { rateTurn(turn, opt[1], rate); });
        rate.appendChild(b);
      });
      item.appendChild(rate);
    }
    log.appendChild(item);
  }

  function renderAll() {
    log.textContent = '';
    if (!state.turns.length) {
      log.appendChild(el('p', 'ahg-chatbot-hint', MODES[state.mode].hint));
    }
    input.placeholder = MODES[state.mode].placeholder;
    if (tabs) {
      Array.prototype.forEach.call(tabs.children, function (t) {
        t.setAttribute('aria-selected', t.dataset.mode === state.mode ? 'true' : 'false');
      });
    }
    state.turns.forEach(renderTurn);
    log.scrollTop = log.scrollHeight;
  }

  // Server calls
  // The ahgCorePlugin fetch wrapper adds the CSRF header to same-origin POSTs.
  function post(url, body) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(body)
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (data) {
        if (!r.ok && !data.answer) {
          throw new Error('HTTP ' + r.status);
        }
        return data;
      });
    });
  }

  function ask(question) {
    question = String(question || '').trim();
    if (!question || busy) {
      return;
    }
    busy = true;
    send.disabled = true;

    var history = state.turns.slice(-4).map(function (t) { return { role: t.role, content: t.content }; });
    state.turns.push({ role: 'user', content: question });
    renderAll();
    var wait = el('div', 'ahg-chatbot-msg ahg-chatbot-assistant ahg-chatbot-wait', state.mode === 'help' ? 'Searching the help...' : 'Searching the catalogue...');
    log.appendChild(wait);
    log.scrollTop = log.scrollHeight;

    var mode = state.mode;
    post(cfg.askUrl, { message: question, history: history, session_id: state.session_id, page_slug: cfg.pageSlug || '', mode: mode })
      .then(function (data) {
        state.session_id = data.session_id || state.session_id;
        state.turns.push({ role: 'assistant', content: data.answer || 'No answer.', sources: data.sources || [], message_id: data.message_id || null, mode: mode });
      })
      .catch(function () {
        state.turns.push({ role: 'assistant', content: 'Sorry, the assistant could not be reached. Please try again later.' });
      })
      .then(function () {
        busy = false;
        send.disabled = false;
        save();
        renderAll();
        input.focus();
      });
  }

  function rateTurn(turn, rating, box) {
    turn.rating = rating;
    save();
    Array.prototype.forEach.call(box.querySelectorAll('button'), function (b, i) {
      b.setAttribute('aria-pressed', (i === 0 ? 1 : -1) === rating ? 'true' : 'false');
    });
    post(cfg.feedbackUrl, { session_id: state.session_id, message_id: turn.message_id, rating: rating }).catch(function () {});
  }

  // A new mode starts a new conversation: history from one would confuse the other.
  function setMode(m) {
    if (!MODES[m] || (m === 'help' && !helpOn) || m === state.mode) {
      return;
    }
    state.mode = m;
    state.turns = [];
    save();
    renderAll();
    input.focus();
  }

  // Open / close
  function setOpen(open) {
    state.open = open;
    panel.hidden = !open;
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
    root.classList.toggle('is-open', open);
    save();
    if (open) {
      renderAll();
      input.focus();
    } else {
      button.focus();
    }
  }

  button.addEventListener('click', function () { setOpen(panel.hidden); });
  close.addEventListener('click', function () { setOpen(false); });
  reset.addEventListener('click', function () {
    state = { session_id: '', turns: [], open: true, mode: state.mode };
    save();
    renderAll();
    input.focus();
  });
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var q = input.value;
    input.value = '';
    ask(q);
  });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit', { cancelable: true }));
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !panel.hidden) { setOpen(false); }
  });

  window.AhgChatbot = {
    open: function (question, mode) {
      if (panel.hidden) { setOpen(true); }
      if (mode) { setMode(mode); }
      if (question) { ask(question); }
    }
  };

  if (state.open) {
    setOpen(true);
  }
})();
