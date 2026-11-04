// Multi-tenancy isolation check (#210, #217). Run against a TEST instance only.
// Needs: tenants A (repository 456) and B (records tenant-b-*), users
// mt-admin (administrator), mt-editor (editor, tenant A only) and mt-plain
// (editor, no tenant), password in MT_PW. Run it twice: with both tenants
// active, and with tenant B suspended; the output must be the same.
// Expected: admin and mt-plain see everything; mt-editor gets 403 on every
// tenant B record and no B rows in browse or search; anonymous gets 403 on
// the B draft only. Each line must have no "LOGIN FAILED".
const { chromium } = require('playwright');
const BASE = process.env.MT_BASE || 'http://192.168.122.170';
const PW = process.env.MT_PW;
const paths = {
  home: '/index.php/',
  'A record': '/index.php/thirty-six-views-of-mount-fuji',
  'B published': '/index.php/tenant-b-published-fonds',
  'B draft': '/index.php/tenant-b-draft-fonds',
  'B draft edit': '/index.php/tenant-b-draft-fonds/edit',
  'B repository': '/index.php/tenant-b-archive',
};
async function run(b, email) {
  const p = await (await b.newContext()).newPage();
  let who = 'anonymous';
  if (email) {
    await p.goto(BASE + '/index.php/user/login', { waitUntil: 'domcontentloaded' });
    await p.locator('input[type=email], input[name=email]').last().fill(email);
    await p.locator('input[type=password]').last().fill(PW);
    await Promise.all([p.waitForLoadState('domcontentloaded'), p.locator('input[type=password]').last().press('Enter')]);
    const out = await p.locator('a[href*="/user/logout"]').count();
    who = email + (out ? '' : ' (LOGIN FAILED)');
  }
  const row = [who];
  for (const [k, path] of Object.entries(paths)) {
    const r = await p.goto(BASE + path, { waitUntil: 'domcontentloaded' });
    row.push(`${k}=${r.status()}`);
  }
  for (const [k, path] of [['browse', '/index.php/informationobject/browse?topLod=0&limit=100'], ['search', '/index.php/informationobject/browse?topLod=0&query=tenant']]) {
    const r = await p.goto(BASE + path, { waitUntil: 'domcontentloaded' });
    const t = await p.locator('body').innerText();
    row.push(`${k}=${r.status()} Bdraft:${t.includes('Tenant B draft fonds') ? 'Y' : 'n'} Bpub:${t.includes('Tenant B published fonds') ? 'Y' : 'n'} A:${/Mount Fuji/.test(t) || k === 'search' ? 'ok' : 'MISSING'}`);
  }
  console.log(row.join(' | '));
}
(async () => {
  const b = await chromium.launch();
  for (const e of ['mt-admin@test.local', 'mt-editor@test.local', 'mt-plain@test.local', null]) await run(b, e);
  await b.close();
})().catch(err => { console.log('FATAL', err.message); process.exit(1); });
