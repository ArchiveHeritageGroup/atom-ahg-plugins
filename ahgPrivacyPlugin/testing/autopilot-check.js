// Compliance autopilot page (#153). Run against a TEST instance only: it creates a
// ROPA entry and retention proposals. Seed it first so the scan finds, in four
// records' scope and content: an email address in two, a valid SA ID number in
// one, an NHS-valid 10-digit number in another, and one record titled
// "[SYNTHETIC] ..." holding a card number. Start with no auto-drafted ROPA, no
// rows in ahg_retention_proposal and no gateway key (the fixed-wording path).
//   BASE=... ADMIN_EMAIL=... EDITOR_EMAIL=... PW=... node autopilot-check.js
const { chromium } = require('playwright');
const BASE = process.env.BASE || 'http://192.168.122.170';
const ok = (c, m) => { console.log((c ? 'PASS ' : 'FAIL ') + m); if (!c) process.exitCode = 1; };
(async () => {
  const b = await chromium.launch(); const p = await (await b.newContext()).newPage();
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.goto(BASE + '/index.php/user/login');
  await p.locator('input[type=email], input[name=email]').last().fill(process.env.ADMIN_EMAIL);
  await p.locator('input[type=password]').last().fill(process.env.PW);
  await Promise.all([p.waitForLoadState('domcontentloaded'), p.locator('input[type=password]').last().press('Enter')]);
  const AP = BASE + '/index.php/admin/privacy/autopilot';
  await p.goto(AP); let t = await p.innerText('body');
  ok(t.includes('Email addresses') && t.includes('National identifiers') && !t.includes('Health service'), 'categories: email, national id; no health');
  ok(/WITH PERSONAL DATA\s*4/.test(t) && /LEFT OUT\s*1/.test(t), 'four records with PII, one demonstration record left out');
  ok(!t.includes('Payment card'), 'demonstration card number not counted');
  ok(t.includes('A DPIA is not required'), 'screen: no DPIA needed');
  ok((await p.inputValue('#ap-cats')).split('\n').sort().join('|') === 'Email addresses|National identifiers', 'ROPA draft lists the categories');
  await Promise.all([p.waitForLoadState('domcontentloaded'), p.click('button:has-text("Create draft processing activity")')]);
  ok(/ropaView/.test(p.url()) || (await p.content()).includes('auto-drafted'), 'ROPA created: ' + p.url().replace(BASE, ''));
  await p.goto(AP);
  ok((await p.innerText('body')).includes('Already created from an earlier draft'), 'page links the created ROPA');
  await Promise.all([p.waitForLoadState('domcontentloaded'), p.click('button:has-text("Draft retention proposals")')]);
  t = await p.innerText('body');
  ok(t.includes('fixed wording; the AI gateway was not available') && (t.match(/Proposed/g) || []).length >= 2, 'two proposals, fixed wording without a gateway');
  await Promise.all([p.waitForLoadState('domcontentloaded'), p.locator('button:has-text("Accept")').first().click()]);
  t = await p.innerText('body');
  ok(t.includes('Retention proposal accepted') && t.includes('Accepted'), 'accepted one');
  await Promise.all([p.waitForLoadState('domcontentloaded'), p.click('button:has-text("Redraft from a new scan")')]);
  t = await p.innerText('body');
  ok((t.match(/Accepted/g) || []).length >= 1, 'redraft keeps the accepted proposal');
  await Promise.all([p.waitForLoadState('domcontentloaded'), p.click('button:has-text("Screen and draft")')]);
  ok((await p.innerText('body')).includes('No DPIA needed'), 'DPIA: not needed, nothing saved');
  const st = (await p.request.post(AP, { form: { form_action: 'draft_retention' }, maxRedirects: 0 })).status();
  ok(st === 403, 'POST without token refused: ' + st);
  const e = await (await b.newContext()).newPage();
  await e.goto(BASE + '/index.php/user/login');
  await e.locator('input[type=email], input[name=email]').last().fill(process.env.EDITOR_EMAIL);
  await e.locator('input[type=password]').last().fill(process.env.PW);
  await Promise.all([e.waitForLoadState('domcontentloaded'), e.locator('input[type=password]').last().press('Enter')]);
  await e.goto(AP); ok(!(await e.content()).includes('Personal data found'), 'editor kept out');
  await p.setViewportSize({ width: 390, height: 800 }); await p.goto(AP);
  ok(!(await p.evaluate(() => document.documentElement.scrollWidth > innerWidth)), 'no sideways scroll at phone width');
  ok(errs.length === 0, 'no page errors ' + errs.join('; '));
  await b.close();
})().catch(e => { console.log('FATAL', e.message); process.exit(1); });
