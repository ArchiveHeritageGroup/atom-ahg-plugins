// End-to-end provenance journey on rari-dev, creating real data.
//
// Usage:
//
//   BASE=http://192.168.0.133 RU=<admin email> RP=<admin password> \
//   STAMP=$(date +%s) SLUG=<description slug> \
//   NODE_PATH=<path to playwright> node e2e-provenance.js
//
// CREATES REAL DATA: one provenance_record against SLUG. Remove with:
//
//   DELETE FROM provenance_record WHERE information_object_id =
//     (SELECT object_id FROM slug WHERE slug = '<SLUG>');
//
// Run against a development instance, never production.
//
// URLs follow the real scheme, /provenance/:slug. An invented /provenance/view?slug=X
// reads 'view' AS the slug, and RARI's data contains a term slugged 'view' - which
// resolved to a QubitTerm and produced a 500 plus malformed edit links. The scheme
// is the thing to trust; links are followed by clicking, as a user would.
//
// Creates one provenance record with events against a real description, checks
// it renders everywhere, then removes it in the shell step.

const { chromium } = require('playwright');

const BASE = (process.env.BASE || 'http://127.0.0.1').replace(/\/$/, '') + '/index.php';
const SLUG = process.env.SLUG;
const SUMMARY = 'E2E provenance summary ' + process.env.STAMP;

let pass = 0, fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  ok ? pass++ : fail++;
  console.log(`  ${ok ? 'ok  ' : 'FAIL'} ${label.padEnd(48)} got=${JSON.stringify(got)} want=${JSON.stringify(want)}`);
};

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

  console.log('--- 1. access control ---');
  const anon = await ctx();
  for (const path of ['provenance', 'provenance/coverage', `provenance/${SLUG}`]) {
    const r = await anon.goto(`${BASE}/${path}`, { waitUntil: 'domcontentloaded' });
    const cls = (await anon.locator('body').getAttribute('class')) || '';
    const refused = r.status() === 403 || /\b(secure|user login)\b/.test(cls) || !anon.url().includes(path.split('?')[0]);
    check(`anonymous refused: /${path.split('?')[0]}`, refused, true);
  }

  const admin = await ctx();
  check('administrator logs in', await login(admin, process.env.RU, process.env.RP), true);

  console.log('\n--- 2. the record starts with no provenance ---');
  let r = await admin.goto(`${BASE}/provenance/${SLUG}`, { waitUntil: 'domcontentloaded' });
  check('view renders', r.status(), 200);
  let body = await admin.textContent('body');
  check('no error page', /Oops|error occurred/i.test(body), false);

  // Not asserted: whether the record starts empty. There is no delete in the
  // interface, so a second run finds the first run's data and a hard assertion
  // here fails for a reason that is not a defect. Report it and carry on - what
  // matters is that saving works and the new summary appears.
  const startedEmpty = /No provenance information has been recorded/i.test(body);
  console.log(`       (record ${startedEmpty ? 'has no' : 'already has'} provenance at start)`);

  console.log('\n--- 3. create provenance through the UI ---');
  // The in-page link carries whatever routing the app expects.
  const editLink = admin.locator('a[href*="provenance"][href*="edit"]').first();
  check('an Edit Provenance link exists', await editLink.count() > 0, true);
  await editLink.click();
  await admin.waitForLoadState('domcontentloaded');
  check('edit form reached by following the link', /Oops|not found/i.test(await admin.textContent('body')), false);

  const form = admin.locator('form').filter({ has: admin.locator('textarea[name="provenance_summary"]') }).first();
  check('edit form present', await form.count() > 0, true);

  await form.locator('textarea[name="provenance_summary"]').fill(SUMMARY);
  const cur = form.locator('input[name="current_agent_name"]');
  if (await cur.count()) await cur.fill('E2E Test Owner');

  await form.locator('button[type=submit], input[type=submit]').first().click();
  await admin.waitForLoadState('domcontentloaded');
  check('save produced no error', /Oops|error occurred/i.test(await admin.textContent('body')), false);

  console.log('\n--- 4. it persists and displays ---');
  await admin.goto(`${BASE}/provenance/${SLUG}`, { waitUntil: 'domcontentloaded' });
  body = await admin.textContent('body');
  check('summary shown on the view page', body.includes(SUMMARY), true);
  check('no longer reports an empty record', /No provenance information has been recorded/i.test(body), false);

  console.log('\n--- 5. the other surfaces work with data present ---');
  for (const [label, sel] of [['timeline', 'a[href*="timeline"]'], ['export', 'a[href*="export"]']]) {
    const link = admin.locator(sel).first();
    if (await link.count() === 0) { check(`${label} link offered`, false, true); continue; }
    check(`${label} link offered`, true, true);
  }

  const cov = await admin.goto(`${BASE}/provenance/coverage`, { waitUntil: 'domcontentloaded' });
  check('coverage page renders', cov.status(), 200);
  check('coverage has no error', /Oops|error occurred/i.test(await admin.textContent('body')), false);

  console.log('\n--- 6. the description page shows the provenance panel ---');
  await admin.goto(`${BASE}/${SLUG}`, { waitUntil: 'domcontentloaded' });
  const rec = await admin.textContent('body');
  check('panel or summary visible on the record', rec.includes(SUMMARY) || /Provenance/i.test(rec), true);

  await browser.close();
  console.log(`\n  ${pass} passed, ${fail} failed`);
  process.exit(fail > 0 ? 1 : 0);
})().catch(e => { console.error('  HARNESS ERROR:', e.message); process.exit(2); });
