// Multi-tenancy isolation check on the throwaway clone atom-mt-test (#210, Q6 b).
const { chromium } = require('playwright');
// Run against a TEST instance only. Needs: tenants A (repository 456) and B
// (repository with slugs tenant-b-*), editor mt-editor@test.local in A only,
// admin mt-admin@test.local, password in MT_PW.
const BASE = process.env.MT_BASE || 'http://192.168.122.170';
const PW = process.env.MT_PW || '';
async function login(p, email) {
  await p.goto(BASE + '/index.php/user/login', { waitUntil: 'domcontentloaded' });
  await p.locator('input[type=email], input[name=email]').last().fill(email);
  await p.locator('input[type=password]').last().fill(PW);
  await Promise.all([p.waitForLoadState('domcontentloaded'), p.locator('input[type=password]').last().press('Enter')]);
  console.log('login', email, '->', p.url().replace(BASE, ''));
}
async function status(p, path) {
  const r = await p.goto(BASE + path, { waitUntil: 'domcontentloaded' });
  const h1 = (await p.locator('h1').first().innerText().catch(() => '')).slice(0, 60);
  return `${r.status()} ${p.url().replace(BASE, '')} "${h1}"`;
}
(async () => {
  const b = await chromium.launch();
  const a = await (await b.newContext()).newPage();
  await login(a, 'mt-admin@test.local');
  console.log('ADMIN B draft    :', await status(a, '/index.php/tenant-b-draft-fonds'));
  const e = await (await b.newContext()).newPage();
  await login(e, 'mt-editor@test.local');
  console.log('switcher on home:', await e.locator('a[href*="/tenant/switch"]').count(), 'links');
  console.log('A record        :', await status(e, '/index.php/thirty-six-views-of-mount-fuji'));
  console.log('B published     :', await status(e, '/index.php/tenant-b-published-fonds'));
  console.log('B draft         :', await status(e, '/index.php/tenant-b-draft-fonds'));
  console.log('B draft edit    :', await status(e, '/index.php/tenant-b-draft-fonds/edit'));
  console.log('B repository    :', await status(e, '/index.php/tenant-b-archive'));
  await e.goto(BASE + '/index.php/informationobject/browse?topLod=0', { waitUntil: 'domcontentloaded' });
  const body = await e.locator('body').innerText();
  console.log('browse shows B draft:', body.includes('Tenant B draft fonds'), '| B published:', body.includes('Tenant B published fonds'));
  await b.close();
})().catch(err => { console.log('FATAL', err.message); process.exit(1); });
