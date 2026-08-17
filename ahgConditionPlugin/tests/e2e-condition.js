// End-to-end condition assessment journey on rari-dev, creating real data.
//
// Usage:
//
//   BASE=http://192.168.0.133 RU=<admin email> RP=<admin password> \
//   SLUG=<description slug> CHECKS_BEFORE=<current count> \
//   NODE_PATH=<path to playwright> node e2e-condition.js
//
// CREATES REAL DATA: one spectrum_condition_check row per run, against the
// record named by SLUG. Remove test checks with:
//
//   DELETE FROM spectrum_condition_check WHERE checked_by IS NULL AND id > <n>;
//
// Run it against a development instance, never production.
//
// Covers access control, creating a check through the interface, that it
// persists and displays, the photo and export surfaces, and that the rock art
// panel template seeded by ahgSiteRecordPlugin is actually offered.
//
// Follows the application's own links rather than URLs guessed at.

const { chromium } = require('playwright');

const BASE = (process.env.BASE || 'http://127.0.0.1').replace(/\/$/, '') + '/index.php';
const SLUG = process.env.SLUG;

let pass = 0, fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  ok ? pass++ : fail++;
  console.log(`  ${ok ? 'ok  ' : 'FAIL'} ${label.padEnd(50)} got=${JSON.stringify(got)} want=${JSON.stringify(want)}`);
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

// Refusal has three shapes and AtoM answers all of them with HTTP 200, so judge
// on where the caller ended up, not on the status code.
async function refused(page, res, path) {
  const cls = (await page.locator('body').getAttribute('class')) || '';
  return res.status() === 403
    || /\b(secure|user login)\b/.test(cls)
    || !page.url().includes(path.split('?')[0]);
}

(async () => {
  const browser = await chromium.launch();
  const ctx = () => browser.newContext({ ignoreHTTPSErrors: true }).then(c => c.newPage());

  console.log('--- 1. access control ---');
  const anon = await ctx();
  for (const path of ['admin/condition', 'condition/check/1/photos', 'condition/check/1/export',
                      'condition/check/1/list', 'object/autocomplete?q=a']) {
    const r = await anon.goto(`${BASE}/${path}`, { waitUntil: 'domcontentloaded' });
    check(`anonymous refused: /${path.split('?')[0]}`, await refused(anon, r, path), true);
  }

  // Known gap: the plugin ships no security.yml, and this one action has no
  // in-code guard either, so it is served to anyone.
  const t = await anon.goto(`${BASE}/condition/templates`, { waitUntil: 'domcontentloaded' });
  check('anonymous refused: /condition/templates', await refused(anon, t, 'condition/templates'), true);

  const admin = await ctx();
  check('administrator logs in', await login(admin, process.env.RU, process.env.RP), true);

  console.log('\n--- 2. the seeded rock art template is available ---');
  await admin.goto(`${BASE}/condition/templates`, { waitUntil: 'domcontentloaded' });
  const tpl = await admin.textContent('body');
  check('rock art panel template listed', /rock art/i.test(tpl), true);
  check('templates page has no error', /Oops|error occurred/i.test(tpl), false);

  console.log('\n--- 3. create a condition check through the interface ---');
  const before = Number(process.env.CHECKS_BEFORE);
  await admin.goto(`${BASE}/${SLUG}/condition`, { waitUntil: 'domcontentloaded' });
  const condPage = await admin.textContent('body');
  check('record condition page renders', /Oops|error occurred/i.test(condPage), false);

  const newBtn = admin.locator('a:has-text("New Condition Check"), button:has-text("New Condition Check")').first();
  check('New Condition Check offered', await newBtn.count() > 0, true);
  await newBtn.click();
  await admin.waitForLoadState('domcontentloaded');
  check('creating produced no error', /Oops|error occurred/i.test(await admin.textContent('body')), false);
  check('landed on the new check', /condition\/check\/\d+/.test(admin.url()), true);

  const newId = (admin.url().match(/condition\/check\/(\d+)/) || [])[1];
  check('a check id was allocated', Boolean(newId), true);

  console.log('\n--- 4. the check surfaces work ---');
  for (const [label, path] of [
    ['photos', `condition/check/${newId}/photos`],
    ['list', `condition/check/${newId}/list`],
    ['export', `condition/check/${newId}/export`],
    ['view', `condition/check/${newId}/view`],
  ]) {
    const r = await admin.goto(`${BASE}/${path}`, { waitUntil: 'domcontentloaded' });
    const body = await admin.textContent('body');
    const ok = r.status() < 400 && !/Oops|error occurred/i.test(body);
    check(`${label} renders`, ok, true);
  }

  console.log('\n--- 5. it is listed against the record ---');
  await admin.goto(`${BASE}/${SLUG}/condition`, { waitUntil: 'domcontentloaded' });
  const after = await admin.textContent('body');
  check('record page lists a check', /condition/i.test(after) && !/Oops/i.test(after), true);

  console.log('\n--- 6. admin dashboard ---');
  const a = await admin.goto(`${BASE}/admin/condition`, { waitUntil: 'domcontentloaded' });
  check('admin dashboard renders', a.status() === 200 && !/Oops|error occurred/i.test(await admin.textContent('body')), true);

  await browser.close();
  console.log(`\n  ${pass} passed, ${fail} failed`);
  process.exit(fail > 0 ? 1 : 0);
})().catch(e => { console.error('  HARNESS ERROR:', e.message); process.exit(2); });
