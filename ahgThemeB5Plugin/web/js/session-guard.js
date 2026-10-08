/*
 * Session guard (#207). Loaded only for signed-in users.
 *
 * - Two minutes before an idle session ends, offers "Stay signed in".
 * - Once it has ended, says so, and says that what was typed is still on the
 *   page.
 * - A save after that point is held instead of being sent to a server that
 *   would bounce it to the login page and lose the edits.
 *
 * The countdown is shared between tabs through localStorage, so working in one
 * tab keeps the others from warning. The server is asked only when the user
 * acts: "Stay signed in", or a save once the countdown has run out.
 */
(function () {
  var script = document.currentScript;
  if (!script || !window.fetch) { return; }

  var timeout = parseInt(script.getAttribute('data-timeout'), 10) * 1000;
  var statusUrl = script.getAttribute('data-status-url');
  var loginUrl = script.getAttribute('data-login-url');
  var text = {
    warn: script.getAttribute('data-text-warn'),
    stay: script.getAttribute('data-text-stay'),
    ended: script.getAttribute('data-text-ended'),
    signin: script.getAttribute('data-text-signin'),
    held: script.getAttribute('data-text-held')
  };
  if (!timeout || !statusUrl) { return; }

  var KEY = 'ahgSessionLastContact';
  var warnAt = Math.min(120000, timeout / 4);
  var last = Date.now();
  var box = null;
  var state = 'ok';
  var passing = null;

  function store(t) {
    last = Math.max(last, t);
    try { localStorage.setItem(KEY, String(last)); } catch (e) { /* private window: this tab only */ }
  }
  function lastContact() {
    try { var v = parseInt(localStorage.getItem(KEY), 10); if (v > last) { last = v; } } catch (e) {}
    return last;
  }
  function remaining() { return timeout - (Date.now() - lastContact()); }

  // Any same-origin fetch reaches the server, and every request counts as
  // activity there, so it counts here too.
  var nativeFetch = window.fetch;
  window.fetch = function (input) {
    var p = nativeFetch.apply(this, arguments);
    var url = typeof input === 'string' ? input : (input && input.url) || '';
    if (!/^https?:/i.test(url) || url.indexOf(location.origin) === 0) {
      p.then(function () { if ('ended' !== state) { store(Date.now()); } }, function () {});
    }
    return p;
  };

  function show(kind) {
    if (box && box.getAttribute('data-kind') === kind) { return box; }
    hide();
    box = document.createElement('div');
    box.setAttribute('data-kind', kind);
    box.setAttribute('role', 'alertdialog');
    box.setAttribute('aria-live', 'assertive');
    box.className = 'alert shadow position-fixed bottom-0 start-50 translate-middle-x mb-3 d-flex align-items-center gap-3 '
      + ('warn' === kind ? 'alert-warning' : 'alert-danger');
    box.style.zIndex = '2000';
    box.style.maxWidth = '42rem';
    var msg = document.createElement('div');
    msg.className = 'ahg-session-msg';
    box.appendChild(msg);
    var btn = document.createElement('warn' === kind ? 'button' : 'a');
    btn.className = 'btn btn-sm ' + ('warn' === kind ? 'btn-warning' : 'btn-danger') + ' text-nowrap';
    if ('warn' === kind) {
      btn.type = 'button';
      btn.textContent = text.stay;
      btn.addEventListener('click', function () { check().then(function (ok) { if (!ok) { ended(); } }); });
    } else {
      btn.href = loginUrl;
      btn.target = '_blank';
      btn.rel = 'noopener';
      btn.textContent = text.signin;
    }
    box.appendChild(btn);
    document.body.appendChild(box);
    btn.focus();
    return box;
  }
  function hide() { if (box) { box.remove(); box = null; } }

  function ended(held) {
    state = 'ended';
    show('ended').querySelector('.ahg-session-msg').textContent = held ? text.held : text.ended;
  }

  // Ask the server; true when still signed in (and the clock is reset).
  function check() {
    return nativeFetch(statusUrl, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.ok ? r.json() : { authenticated: false }; })
      .then(function (j) {
        if (j.authenticated) { state = 'ok'; store(Date.now()); hide(); return true; }
        return false;
      }, function () { return false; });
  }

  function tick() {
    var left = remaining();
    if (left <= 0) {
      if ('ended' !== state) { ended(); }
    } else if (left <= warnAt) {
      state = 'warn';
      var m = Math.floor(left / 60000);
      var s = Math.floor(left / 1000) % 60;
      show('warn').querySelector('.ahg-session-msg').textContent = text.warn.replace('%1%', m + ':' + (s < 10 ? '0' : '') + s);
    } else if ('ok' !== state) {
      state = 'ok';
      hide();
    }
  }

  // Hold a save once the countdown has run out, until the server confirms the
  // session. A live session submits as normal, with nothing added.
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (passing === form || 'post' !== (form.method || '').toLowerCase() || /login/i.test(form.action || '')) { return; }
    if (remaining() > 5000 && 'ended' !== state) { return; }
    e.preventDefault();
    var submitter = e.submitter || null;
    check().then(function (ok) {
      if (!ok) { ended(true); return; }
      passing = form;
      if (form.requestSubmit) { form.requestSubmit(submitter); } else { form.submit(); }
      passing = null;
    });
  }, true);

  // Another tab signed in or was active: pick that up straight away.
  window.addEventListener('storage', function (e) {
    if (KEY === e.key) {
      if ('ended' === state) { check(); } else { tick(); }
    }
  });

  store(Date.now());
  setInterval(tick, 5000);
})();
