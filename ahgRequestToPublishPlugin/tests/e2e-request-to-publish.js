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
// Note on this module's security posture: security.yml declares `delete` and
// `edit` only, with no `all:` catch, so every other action inherits
// `default: is_secure: false`. The staff screens are guarded in code instead,
// which is why they refuse anonymous callers - these checks assert that, so the
// day someone adds an action without its own guard, this fails.

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

  // Deliberately public: a requester checks their own request by token without
  // holding an account.
  const rec = await anon.goto(`${BASE}/requesttopublish/receipt`, { waitUntil: 'domcontentloaded' });
  check('receipt lookup is public by design', rec.status(), 200);
  check('receipt page has no error', /Oops|error occurred/i.test(await anon.textContent('body')), false);

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

  console.log('\n--- 4. the per-record request form ---');
  //
  // NOT asserted, because it is an open question rather than a known-good
  // behaviour. config registers `requesttopublish_edit` at /requesttopublish/:slug
  // pointing at action "edit", but the module ships browseAction,
  // editRequestToPublishAction and receiptAction - there is no "edit" action, and
  // the URL 404s. The record page links only to /requesttopublish/browse, so the
  // route may simply be vestigial. Reported so a change here is visible, without
  // failing the suite over something undiagnosed.
  const r = await admin.goto(`${BASE}/requesttopublish/${SLUG}`, { waitUntil: 'domcontentloaded' });
  console.log(`       /requesttopublish/<slug> -> HTTP ${r.status()}`
    + (r.status() === 404 ? '  (route names an action the module does not define)' : ''));

  await browser.close();
  console.log(`\n  ${pass} passed, ${fail} failed`);
  process.exit(fail > 0 ? 1 : 0);
})().catch(e => { console.error('  HARNESS ERROR:', e.message); process.exit(2); });
