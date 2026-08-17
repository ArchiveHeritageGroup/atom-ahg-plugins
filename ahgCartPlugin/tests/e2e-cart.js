// End-to-end cart journey, creating real data.
//
// Usage:
//
//   BASE=http://192.168.0.133 SLUG=<description slug> \
//   RU=<account email> RP=<password> \
//   NODE_PATH=<path to playwright> node e2e-cart.js
//
// The cart requires an account (2026-08-17, Johan). It was public until then,
// which was already inconsistent: the cart is stored and cleared by user_id, so
// an anonymous visitor could fill one and then be sent to login to empty it.
//
// CREATES REAL DATA: cart contents for the signed-in account, emptied at the end
// of the run.
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

const isEmpty = async (page) =>
  (await page.locator('table tbody tr').count()) === 0 || /empty/i.test(await page.textContent('body'));

(async () => {
  const browser = await chromium.launch();
  const ctx = () => browser.newContext({ ignoreHTTPSErrors: true }).then(c => c.newPage());

  console.log('--- 1. the cart is closed to anonymous visitors ---');
  const anon = await ctx();
  for (const path of ['cart', `cart/add/${SLUG}`, 'cart/checkout', 'cart/orders']) {
    const res = await anon.goto(`${BASE}/${path}`, { waitUntil: 'domcontentloaded' });
    check(`anonymous refused: /${path}`, await refused(anon, res, path), true);
  }

  if (!process.env.RU) {
    console.log('\n  (no RU/RP given - stopping after the anonymous checks)');
    await browser.close();
    console.log(`\n  ${pass} passed, ${fail} failed`);
    process.exit(fail > 0 ? 1 : 0);
  }

  // One context from here on: the cart lives in the session.
  const page = await ctx();
  await page.goto(`${BASE}/user/login`, { waitUntil: 'domcontentloaded' });
  await page.locator('#email:visible').first().fill(process.env.RU);
  const pw = page.locator('#password:visible').first();
  await pw.fill(process.env.RP);
  await pw.press('Enter');
  await page.waitForLoadState('domcontentloaded');
  const cls = (await page.locator('body').getAttribute('class')) || '';
  check('signed in', !/\buser login\b/.test(cls), true);

  console.log('\n--- 2. add a record ---');
  let r = await page.goto(`${BASE}/cart/add/${SLUG}`, { waitUntil: 'domcontentloaded' });
  check('add accepted', r.status() < 400, true);
  check('add produced no error', /Oops|error occurred/i.test(await page.textContent('body')), false);

  await page.goto(`${BASE}/cart`, { waitUntil: 'domcontentloaded' });
  check('cart is no longer empty', await isEmpty(page), false);

  console.log('\n--- 3. it survives navigating away ---');
  await page.goto(`${BASE}/${SLUG}`, { waitUntil: 'domcontentloaded' });
  await page.goto(`${BASE}/cart`, { waitUntil: 'domcontentloaded' });
  check('still populated', await isEmpty(page), false);

  console.log('\n--- 4. clearing requires POST ---');
  // A GET must not empty the cart: the same protection the site record delete
  // has, and the one the legacy rock_forms application lacked, where following a
  // link destroyed a record.
  await page.goto(`${BASE}/cart/clear`, { waitUntil: 'domcontentloaded' });
  await page.goto(`${BASE}/cart`, { waitUntil: 'domcontentloaded' });
  check('GET does NOT clear the cart', await isEmpty(page), false);

  await page.evaluate(async (base) => {
    await fetch(base + '/cart/clear', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });
  }, BASE);

  await page.goto(`${BASE}/cart`, { waitUntil: 'domcontentloaded' });
  check('POST clears the cart', await isEmpty(page), true);
  check('clear produced no error', /Oops|error occurred/i.test(await page.textContent('body')), false);

  await browser.close();
  console.log(`\n  ${pass} passed, ${fail} failed`);
  process.exit(fail > 0 ? 1 : 0);
})().catch(e => { console.error('  HARNESS ERROR:', e.message); process.exit(2); });
