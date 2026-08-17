// End-to-end researcher journey on rari-dev.
//
// Usage:
//
//   BASE=http://192.168.0.133 RU=<admin email> RP=<admin password> \
//   STAMP=$(date +%s) SITE_SLUG=<slug> SITE_SHEET=<map sheet> \
//   NODE_PATH=<path to playwright> node e2e-researcher.js
//
// SITE_SLUG / SITE_SHEET should name a record whose locality is restricted, so
// the test can prove an approved researcher does not receive it.
//
// CREATES REAL DATA: one user account and one research_researcher row per run,
// named e2e_researcher_<STAMP>. There is no delete in the interface, so remove
// them with:
//
//   DELETE r, u FROM user u
//     LEFT JOIN research_researcher r ON r.user_id = u.id
//    WHERE u.username LIKE 'e2e_researcher_%';
//
// register -> account inert -> administrator approves -> researcher logs in ->
// and the check that matters most here: an approved researcher must NOT see
// exact site locality. rari-dev carries 22 real researcher accounts, so
// "authenticated" must not mean "cleared".
//
// Creates one test account; the shell step removes it afterwards.

const { chromium } = require('playwright');

const BASE = (process.env.BASE || 'http://127.0.0.1').replace(/\/$/, '') + '/index.php';
const STAMP = process.env.STAMP;
const USER = 'e2e_researcher_' + STAMP;
const EMAIL = `e2e.researcher.${STAMP}@example.org`;
const PASS = 'E2eTestPassword!' + STAMP;
const SITE_SLUG = process.env.SITE_SLUG;
const SITE_SHEET = process.env.SITE_SHEET;

let pass = 0, fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  ok ? pass++ : fail++;
  console.log(`  ${ok ? 'ok  ' : 'FAIL'} ${label.padEnd(50)} got=${JSON.stringify(got)} want=${JSON.stringify(want)}`);
};

// AtoM serves the login page as HTTP 200, so a status code proves nothing.
// Decide on what the page actually is.
async function login(page, user, password) {
  await page.goto(`${BASE}/user/login`, { waitUntil: 'domcontentloaded' });
  await page.locator('#email:visible').first().fill(user);
  const pw = page.locator('#password:visible').first();
  await pw.fill(password);
  await pw.press('Enter');
  await page.waitForLoadState('domcontentloaded');
  const cls = (await page.locator('body').getAttribute('class')) || '';
  return !/\buser login\b/.test(cls) && !/user\/login/.test(page.url());
}

(async () => {
  const browser = await chromium.launch();
  const ctx = () => browser.newContext({ ignoreHTTPSErrors: true }).then(c => c.newPage());

  console.log('--- 1. self-service registration ---');
  const anon = await ctx();
  const reg = await anon.goto(`${BASE}/research/register-researcher`, { waitUntil: 'domcontentloaded' });
  check('registration reachable anonymously', reg.status(), 200);

  // Scope to the registration form. AtoM also renders a hidden login form in the
  // header, so bare name selectors match twice and resolve to the invisible one.
  const form = anon.locator('form').filter({ has: anon.locator('input[name="first_name"]') }).first();

  for (const [name, val] of [
    ['first_name', 'E2E'],
    ['last_name', 'Researcher'],
    ['email', EMAIL],
    ['username', USER],
    ['password', PASS],
    ['confirm_password', PASS],
    ['institution', 'E2E Test Institution'],
  ]) {
    const field = form.locator(`input[name="${name}"]`).first();
    if (await field.count()) await field.fill(val);
  }

  await form.locator('button[type=submit], input[type=submit]').first().click();
  await anon.waitForLoadState('domcontentloaded');
  // Look for an error the page is REPORTING, not for words that also appear in
  // the form's own help text - "must be at least 8 characters" sits next to the
  // password field whether or not anything went wrong.
  const errorBox = await anon.locator('.alert-danger, .alert-error, ul.error-list').allTextContents();
  check('accepted without validation errors', errorBox.join(' ').trim(), '');

  console.log('\n--- 2. the account must be inert until approved ---');
  check('login REFUSED before approval', await login(await ctx(), EMAIL, PASS), false);

  console.log('\n--- 3. administrator approves ---');
  const admin = await ctx();
  check('administrator logs in', await login(admin, process.env.RU, process.env.RP), true);

  await admin.goto(`${BASE}/research/researchers?status=pending`, { waitUntil: 'domcontentloaded' });
  const pendingList = await admin.textContent('body');
  check('applicant is listed as pending', pendingList.includes('E2E') || pendingList.includes(USER), true);

  // Find this applicant's id from the page rather than being told it, so the
  // test exercises the route an administrator actually follows.
  const rid = await admin.evaluate((email) => {
    const rows = Array.from(document.querySelectorAll('tr'));
    const row = rows.find(r => r.textContent.includes(email));
    if (!row) return null;
    const link = row.querySelector('a[href*="/research/researcher"]');
    const m = link && link.getAttribute('href').match(/researcher(?:-view)?\/(\d+)/);
    return m ? m[1] : null;
  }, EMAIL);
  check('applicant id discoverable from the registry', rid !== null, true);

  const appr = await admin.goto(`${BASE}/research/researcher/${rid}/approve`, { waitUntil: 'domcontentloaded' });
  check('approval accepted', appr.status() < 400, true);

  console.log('\n--- 4. the researcher can now use the site ---');
  const researcher = await ctx();
  check('login SUCCEEDS after approval', await login(researcher, EMAIL, PASS), true);

  await researcher.goto(`${BASE}/research`, { waitUntil: 'domcontentloaded' });
  const ws = await researcher.textContent('body');
  check('research area reachable', /Oops|error occurred/i.test(ws), false);

  console.log('\n--- 5. an approved researcher is NOT cleared for locality ---');
  await researcher.goto(`${BASE}/${SITE_SLUG}`, { waitUntil: 'domcontentloaded' });
  const sitePage = await researcher.content();
  check('exact map sheet withheld from researcher', sitePage.includes(SITE_SHEET), false);
  check('raw locality withheld from researcher', /How to find the site|location th i/i.test(sitePage), false);

  await admin.goto(`${BASE}/${SITE_SLUG}`, { waitUntil: 'domcontentloaded' });
  const adminPage = await admin.content();
  check('administrator DOES see the map sheet', adminPage.includes(SITE_SHEET), true);

  console.log('\n--- 6. researcher must not reach staff management ---');
  for (const [label, path] of [
    ['researcher registry', 'research/researchers'],
    ['site record browse', 'site-record'],
    ['access request queue', 'security/access-requests'],
  ]) {
    const r = await researcher.goto(`${BASE}/${path}`, { waitUntil: 'domcontentloaded' });
    const cls = (await researcher.locator('body').getAttribute('class')) || '';
    const landed = researcher.url();

    // Refusal takes three shapes here, and only counting the first two reports
    // a false positive: the login page, the secure page, or a redirect that
    // bounces the caller to the homepage. Judge on where we ended up, not on the
    // status code, since AtoM answers all of them with HTTP 200.
    const refused = r.status() === 403
      || /\b(secure|user login)\b/.test(cls)
      || !landed.includes(path);

    check(`refused: ${label}`, refused, true);
    if (!refused) {
      const rows = await researcher.locator('table tbody tr').count();
      console.log(`       ^ landed on ${landed} with ${rows} rows visible`);
    }
  }

  await browser.close();
  console.log(`\n  ${pass} passed, ${fail} failed`);
  process.exit(fail > 0 ? 1 : 0);
})().catch(e => { console.error('  HARNESS ERROR:', e.message); process.exit(2); });
