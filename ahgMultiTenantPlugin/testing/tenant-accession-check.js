// Multi-tenancy: accessions (#217). Run against a TEST instance only, with
// ahgAccessionManagePlugin enabled and the same tenants and users as
// tenant-isolation-check.js, plus accession 2026-09-22/1 (id 538) linked to a
// tenant A description. Password in MT_PW. Vary the accession and compare:
//   no CAAIS repository, linked to tenant A  -> mt-editor sees it
//   accession_caais.repository_id = tenant B -> mt-editor 403, not in browse
//   accession_caais.repository_id = tenant A -> mt-editor sees it
//   no repository and no linked description  -> mt-editor 403, not in browse
// Administrators and mt-plain (no tenant) always see it. mt-editor gets 403
// on the intake queue in every case (it lists every tenant's accessions).
const { chromium } = require('playwright');
const BASE = process.env.MT_BASE || 'http://192.168.122.170';
const PW = process.env.MT_PW;
const pages = {
  view: '/index.php/accession/2026-09-22-1',
  edit: '/index.php/accession/2026-09-22-1/edit',
  timeline: '/index.php/admin/accessions/538/timeline',
  queue: '/index.php/admin/accessions/queue',
  home: '/index.php/',
  'A rec': '/index.php/thirty-six-views-of-mount-fuji',
  'B draft': '/index.php/tenant-b-draft-fonds',
};
(async () => {
  const b = await chromium.launch();
  for (const email of ['mt-admin@test.local', 'mt-editor@test.local', 'mt-plain@test.local']) {
    const p = await (await b.newContext()).newPage();
    await p.goto(BASE + '/index.php/user/login', { waitUntil: 'domcontentloaded' });
    await p.locator('input[type=email], input[name=email]').last().fill(email);
    await p.locator('input[type=password]').last().fill(PW);
    await Promise.all([p.waitForLoadState('domcontentloaded'), p.locator('input[type=password]').last().press('Enter')]);
    const ok = await p.locator('a[href*="/user/logout"]').count();
    const row = [email.split('@')[0] + (ok ? '' : ' LOGIN FAILED')];
    for (const [k, path] of Object.entries(pages)) {
      const r = await p.goto(BASE + path, { waitUntil: 'domcontentloaded' });
      row.push(`${k}=${r.status()}`);
      if (k === 'home') row.push(`switcher=${await p.locator('#tenantSwitcherDropdown').count()}`);
    }
    const r = await p.goto(BASE + '/index.php/accession/browse', { waitUntil: 'domcontentloaded' });
    const t = await p.locator('body').innerText();
    row.push(`browse=${r.status()} listed:${t.includes('2026-09-22/1') ? 'Y' : 'n'}`);
    console.log(row.join(' | '));
  }
  await b.close();
})().catch(err => { console.log('FATAL', err.message); process.exit(1); });
