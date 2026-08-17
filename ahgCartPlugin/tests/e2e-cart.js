// End-to-end cart journey, creating real data.
//
// Usage:
//
//   BASE=http://192.168.0.133 SLUG=<description slug> \
//   [RU=<admin email> RP=<admin password>] \
//   NODE_PATH=<path to playwright> node e2e-cart.js
//
// The cart is deliberately public - modules/cart/config/security.yml says
// `all: is_secure: false` - because a visitor requesting reproductions has no
// account. So the anonymous path is the main path here, and the checks are that
// it works AND that the staff screens behind it do not open up with it.
//
// CREATES REAL DATA: cart contents for the browser session only, cleared at the
// end of the run. Nothing persists once the session ends.
//
// Run against a development instance, never production.

const { chromium } = require('playwright');

const BASE = (process.env.BASE || 'http://127.0.0.1').replace(/\/$/, '') + '/index.php';
const SLUG = process.env.SLUG;

let pass = 0, fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  ok ? pass++ : fail++;
  console.log(`  ${ok ? 'ok  ' : 'FAIL'} ${label.padEnd(48)} got=${JSON.stringify(got)} want=${JSON.stringify(want)}`);
};

// AtoM answers refusals with HTTP 200, so judge on where the caller landed.
async function refused(page, res, path) {
  const cls = (await page.locator('body').getAttribute('class')) || '';
  return res.status() === 403
    || /\b(secure|user login)\b/.test(cls)
    || !page.url().includes(path.split('?')[0]);
}

(async () => {
  const browser = await chromium.launch();
  // One context throughout: the cart lives in the session.
  const page = await (await browser.newContext({ ignoreHTTPSErrors: true })).newPage();

  console.log('--- 1. an anonymous visitor can use the cart ---');
  let r = await page.goto(`${BASE}/cart`, { waitUntil: 'domcontentloaded' });
  check('cart reachable anonymously', r.status(), 200);
  check('cart has no error', /Oops|error occurred/i.test(await page.textContent('body')), false);

  console.log('\n--- 2. add a record to the cart ---');
  r = await page.goto(`${BASE}/cart/add/${SLUG}`, { waitUntil: 'domcontentloaded' });
  check('add accepted', r.status() < 400, true);
  check('add produced no error', /Oops|error occurred/i.test(await page.textContent('body')), false);

  await page.goto(`${BASE}/cart`, { waitUntil: 'domcontentloaded' });
  const withItem = await page.textContent('body');
  const rows = await page.locator('table tbody tr').count();
  check('cart is no longer empty', rows > 0 || !/empty/i.test(withItem), true);

  console.log('\n--- 3. the cart survives a page change ---');
  await page.goto(`${BASE}/${SLUG}`, { waitUntil: 'domcontentloaded' });
  await page.goto(`${BASE}/cart`, { waitUntil: 'domcontentloaded' });
  check('still populated after navigating away', await page.locator('table tbody tr').count() > 0 ||
        !/empty/i.test(await page.textContent('body')), true);

  console.log('\n--- 4. staff screens stay closed to an anonymous visitor ---');
  for (const path of ['cart/orders']) {
    const res = await page.goto(`${BASE}/${path}`, { waitUntil: 'domcontentloaded' });
    check(`anonymous refused: /${path}`, await refused(page, res, path), true);
  }

  console.log('\n--- 5. clearing the cart requires POST ---');
  // A GET must not empty the cart. The same protection the site record delete
  // has, and the one the legacy rock_forms application lacked, where following
  // a link destroyed a record.
  await page.goto(`${BASE}/cart/clear`, { waitUntil: 'domcontentloaded' });
  await page.goto(`${BASE}/cart`, { waitUntil: 'domcontentloaded' });
  const afterGet = await page.textContent('body');
  check('GET does NOT clear the cart', await page.locator('table tbody tr').count() > 0 || !/empty/i.test(afterGet), true);

  // Clearing also requires a login, because the cart is cleared by user_id.
  // So an anonymous visitor can fill a cart and look at it but not empty it -
  // worth asserting deliberately rather than discovering it as a puzzle.
  const anonPost = await page.evaluate(async (base) => {
    const res = await fetch(base + '/cart/clear', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      redirect: 'follow',
    });
    return res.url;
  }, BASE);
  check('anonymous POST clear sent to login', /user\/login/.test(anonPost), true);

  await page.goto(`${BASE}/cart`, { waitUntil: 'domcontentloaded' });
  check('anonymous cart survives the attempt',
    await page.locator('table tbody tr').count() > 0 || !/empty/i.test(await page.textContent('body')), true);

  console.log('\n--- 6. a signed-in user can clear their own cart ---');
  if (!process.env.RU) {
    console.log('       (skipped: no RU/RP given)');
  } else {
    await page.goto(`${BASE}/user/login`, { waitUntil: 'domcontentloaded' });
    await page.locator('#email:visible').first().fill(process.env.RU);
    const pw = page.locator('#password:visible').first();
    await pw.fill(process.env.RP);
    await pw.press('Enter');
    await page.waitForLoadState('domcontentloaded');

    await page.goto(`${BASE}/cart/add/${SLUG}`, { waitUntil: 'domcontentloaded' });
    await page.goto(`${BASE}/cart`, { waitUntil: 'domcontentloaded' });
    check('signed-in cart has the item',
      await page.locator('table tbody tr').count() > 0 || !/empty/i.test(await page.textContent('body')), true);

    await page.evaluate(async (base) => {
      await fetch(base + '/cart/clear', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
    }, BASE);

    await page.goto(`${BASE}/cart`, { waitUntil: 'domcontentloaded' });
    const afterPost = await page.textContent('body');
    check('cart empty after POST clear', await page.locator('table tbody tr').count() === 0 || /empty/i.test(afterPost), true);
    check('clear produced no error', /Oops|error occurred/i.test(afterPost), false);
  }

  await browser.close();
  console.log(`\n  ${pass} passed, ${fail} failed`);
  process.exit(fail > 0 ? 1 : 0);
})().catch(e => { console.error('  HARNESS ERROR:', e.message); process.exit(2); });
