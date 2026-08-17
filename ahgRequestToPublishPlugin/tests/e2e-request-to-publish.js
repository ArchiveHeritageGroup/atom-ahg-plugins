// End-to-end request-to-publish journey, creating real data.
//
// Usage:
//
//   BASE=http://192.168.0.133 SLUG=<description slug> \
//   RU=<admin email> RP=<admin password> STAMP=$(date +%s) \
//   NODE_PATH=<path to playwright> node e2e-request-to-publish.js
//
// CREATES REAL DATA: one request_to_publish row per run, if submission is
// reachable. Remove test rows with:
//
//   DELETE FROM request_to_publish_i18n WHERE id IN
//     (SELECT id FROM request_to_publish WHERE id > <n>);
//   DELETE FROM request_to_publish WHERE id > <n>;
//
// Run against a development instance, never production.
//
// Requesting publication requires an account (2026-08-17, Johan).
//
// Note the module layout: routes are registered against 'requestToPublish'
// (camelCase), and modules/requestToPublish/ holds all seven actions. The
// lowercase modules/requesttopublish/ is an older partial copy. A security.yml
// placed in the lowercase one protects nothing - these checks would catch that.

const { chromium } = require('playwright');

const BASE = (process.env.BASE || 'http://127.0.0.1').replace(/\/$/, '') + '/index.php';
const SLUG = process.env.SLUG;

let pass = 0, fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  ok ? pass++ : fail++;
  console.log(`  ${ok ? 'ok  ' : 'FAIL'} ${label.padEnd(50)} got=${JSON.stringify(got)} want=${JSON.stringify(want)}`);
};

// AtoM answers refusals with HTTP 200, so judge on where the caller landed.
async function refused(page, res, path) {
  const cls = (await page.locator('body').getAttribute('class')) || '';
  return res.status() === 403
    || /\b(secure|user login)\b/.test(cls)
    || !page.url().includes(path.split('?')[0]);
}

async function login(page, user, password) {
  await page.goto(`${BASE}/user/login`, { waitUntil: 'domcontentloaded' });
  await page.locator('#email:visible').first().fill(user);
  const pw = page.locator('#password:visible').first();
  await pw.fill(password);
  await pw.press('Enter');
  await page.waitForLoadState('domcontentloaded');
  const cls = (await page.locator('body').getAttribute('class')) || '';
  return !/\buser login\b/.test(cls);
}

(async () => {
  const browser = await chromium.launch();
  const ctx = () => browser.newContext({ ignoreHTTPSErrors: true }).then(c => c.newPage());

  console.log('--- 1. staff screens are closed to an anonymous visitor ---');
  const anon = await ctx();
  for (const path of ['requesttopublish/inbox', 'requesttopublish/review/1', 'requesttopublish/browse']) {
    const r = await anon.goto(`${BASE}/${path}`, { waitUntil: 'domcontentloaded' });
    check(`anonymous refused: /${path}`, await refused(anon, r, path), true);
  }

  // The receipt lookup was public until 2026-08-17. It is now behind the login
  // with everything else; if external requesters need it back it should get its
  // own is_secure: false entry rather than the module being reopened.
  for (const path of ['requesttopublish/receipt', `requesttopublish/${SLUG}`, `requestToPublish/submit/${SLUG}`]) {
    const rec = await anon.goto(`${BASE}/${path}`, { waitUntil: 'domcontentloaded' });
    check(`anonymous refused: /${path}`, await refused(anon, rec, path), true);
  }

  console.log('\n--- 2. staff can reach the queues ---');
  const admin = await ctx();
  check('administrator logs in', await login(admin, process.env.RU, process.env.RP), true);

  for (const [label, path] of [['browse', 'requesttopublish/browse'], ['inbox', 'requesttopublish/inbox']]) {
    const r = await admin.goto(`${BASE}/${path}`, { waitUntil: 'domcontentloaded' });
    const body = await admin.textContent('body');
    check(`${label} renders`, r.status() < 400 && !/Oops|error occurred/i.test(body), true);
  }

  console.log('\n--- 3. legacy rows with no i18n row still render ---');
  // RARI carries 79 request_to_publish rows with no matching i18n row, so the
  // status arrives as null through the LEFT JOIN. A strict int type hint made
  // this page fatal; it must degrade to "Unknown" instead.
  await admin.goto(`${BASE}/requesttopublish/browse`, { waitUntil: 'domcontentloaded' });
  const browseBody = await admin.textContent('body');
  const rows = await admin.locator('table tbody tr').count();
  check('browse lists rows', rows > 0, true);
  check('no fatal on rows lacking a status', /Oops|error occurred/i.test(browseBody), false);

  console.log('\n--- 4. request slugs and description slugs are different things ---');
  //
  // /requesttopublish/:slug takes a REQUEST slug and opens that request for
  // editing - it is how browse links each row. Handing it a DESCRIPTION slug
  // must 404, because a description is not a request. Raising a new request
  // against a description is a separate URL.
  //
  // Both directions are asserted because confusing them cost real time: a 404
  // from passing the wrong slug type looks exactly like a broken route, and
  // "fixing" it by repointing this route at submit broke opening a request from
  // the queue.
  const newForm = await admin.goto(`${BASE}/requestToPublish/submit/${SLUG}`, { waitUntil: 'domcontentloaded' });
  check('new request form on a description slug', newForm.status(), 200);
  const fields = ((await admin.content()).match(/name="rtp_[a-z_]+"/g) || []).length;
  check('it serves the request form', fields > 0, true);

  const wrongType = await admin.goto(`${BASE}/requesttopublish/${SLUG}`, { waitUntil: 'domcontentloaded' });
  check('a description slug is refused here', wrongType.status(), 404);

  if (process.env.RS) {
    const open = await admin.goto(`${BASE}/requesttopublish/${process.env.RS}`, { waitUntil: 'domcontentloaded' });
    check('a request slug opens the request', open.status(), 200);
    const del = await admin.goto(`${BASE}/requesttopublish/delete/${process.env.RS}`, { waitUntil: 'domcontentloaded' });
    check('delete reachable for a real request', del.status(), 200);
  } else {
    console.log('       (set RS=<request slug> to also check opening and deleting a request)');
  }

  await browser.close();
  console.log(`\n  ${pass} passed, ${fail} failed`);
  process.exit(fail > 0 ? 1 : 0);
})().catch(e => { console.error('  HARNESS ERROR:', e.message); process.exit(2); });
