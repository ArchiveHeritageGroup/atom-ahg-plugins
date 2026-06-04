/* WebAuthn / FIDO2 passkey ceremonies (#126). Vanilla JS, no deps.
 * Bridges web-auth/webauthn-lib's base64url JSON options <-> the browser's
 * ArrayBuffer-based credential API. */
(function (w) {
  'use strict';

  function b64urlToBuf(s) {
    s = s.replace(/-/g, '+').replace(/_/g, '/');
    while (s.length % 4) s += '=';
    var bin = atob(s), buf = new Uint8Array(bin.length);
    for (var i = 0; i < bin.length; i++) buf[i] = bin.charCodeAt(i);
    return buf.buffer;
  }
  function bufToB64url(buf) {
    var bytes = new Uint8Array(buf), bin = '';
    for (var i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
    return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }
  function prepDescriptors(list) {
    return (list || []).map(function (c) { return Object.assign({}, c, { id: b64urlToBuf(c.id) }); });
  }
  function credToJSON(cred) {
    var r = cred.response, out = {
      id: cred.id, type: cred.type, rawId: bufToB64url(cred.rawId),
      response: { clientDataJSON: bufToB64url(r.clientDataJSON) }
    };
    if (r.attestationObject) out.response.attestationObject = bufToB64url(r.attestationObject);
    if (r.authenticatorData) {
      out.response.authenticatorData = bufToB64url(r.authenticatorData);
      out.response.signature = bufToB64url(r.signature);
      out.response.userHandle = r.userHandle ? bufToB64url(r.userHandle) : null;
    }
    return out;
  }
  async function postJSON(url, body) {
    var res = await fetch(url, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: body ? JSON.stringify(body) : '{}'
    });
    return { ok: res.ok, data: await res.json().catch(function () { return {}; }) };
  }

  async function register(label) {
    var begin = await postJSON('/security/2fa/webauthn/register/begin');
    if (!begin.ok) throw new Error('register/begin failed');
    var opts = begin.data;
    opts.challenge = b64urlToBuf(opts.challenge);
    opts.user.id = b64urlToBuf(opts.user.id);
    opts.excludeCredentials = prepDescriptors(opts.excludeCredentials);
    var cred = await navigator.credentials.create({ publicKey: opts });
    var payload = credToJSON(cred);
    payload._label = label || 'Passkey';
    var done = await postJSON('/security/2fa/webauthn/register/complete', payload);
    return done.ok && done.data.ok;
  }

  async function authenticate(userId) {
    var url = '/security/2fa/webauthn/assert/begin' + (userId ? ('?user_id=' + encodeURIComponent(userId)) : '');
    var begin = await postJSON(url);
    if (!begin.ok) throw new Error('assert/begin failed');
    var opts = begin.data;
    opts.challenge = b64urlToBuf(opts.challenge);
    opts.allowCredentials = prepDescriptors(opts.allowCredentials);
    var cred = await navigator.credentials.get({ publicKey: opts });
    var curl = '/security/2fa/webauthn/assert/complete' + (userId ? ('?user_id=' + encodeURIComponent(userId)) : '');
    var done = await postJSON(curl, credToJSON(cred));
    return done.ok && done.data.ok;
  }

  w.AhgWebAuthn = { register: register, authenticate: authenticate };
})(window);
