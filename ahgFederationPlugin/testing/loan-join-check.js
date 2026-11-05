// Loan requests and joining the network (#151). Run against a TEST instance only:
// it creates loan and join requests. Needs a federation peer with at least one
// harvested record (a federation_harvest_log row), an administrator (ADMIN_EMAIL)
// and an editor (EDITOR_EMAIL), both with password PW.
//   BASE=http://test-host ADMIN_EMAIL=... EDITOR_EMAIL=... PW=... node loan-join-check.js
// Every line must say PASS.
const { chromium } = require('playwright');
const BASE = process.env.BASE || 'http://192.168.122.170';
const ok = (c, m) => { console.log((c ? 'PASS ' : 'FAIL ') + m); if (!c) process.exitCode = 1; };
async function login(ctx, email) {
  const p = await ctx.newPage();
  await p.goto(BASE + '/index.php/user/login');
  await p.locator('input[type=email], input[name=email]').last().fill(email);
  await p.locator('input[type=password]').last().fill(process.env.PW);
  await Promise.all([p.waitForLoadState('domcontentloaded'), p.locator('input[type=password]').last().press('Enter')]);
  return p;
}
(async () => {
  const b = await chromium.launch();
  const p = await login(await b.newContext(), process.env.ADMIN_EMAIL);
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.goto(BASE + '/index.php/admin/federation/union?source=harvested');
  const btn = p.locator('a:has-text("Request loan")').first();
  ok(await btn.count() === 1, 'union: Request loan button on harvested record');
  await btn.click(); await p.waitForLoadState('domcontentloaded');
  ok((await p.inputValue('#item_title')) !== '', 'loan form prefilled with title');
  ok(await p.inputValue('#peer_id') !== '', 'loan form prefilled with peer');
  await p.fill('#purpose', 'Exhibition test');
  await p.fill('#needed_from', '2026-12-01'); await p.fill('#needed_to', '2026-11-01');
  await Promise.all([p.waitForLoadState('domcontentloaded'), p.click('button:has-text("Create loan request")')]);
  ok((await p.content()).includes('cannot end before it starts'), 'rejects end before start');
  await p.fill('#needed_to', '2027-01-31');
  await Promise.all([p.waitForLoadState('domcontentloaded'), p.click('button:has-text("Create loan request")')]);
  ok(/loans\/\d+$/.test(p.url()), 'created, on detail page: ' + p.url().replace(BASE, ''));
  const loanUrl = p.url();
  ok((await p.content()).includes('Outgoing: we borrow from them'), 'outgoing direction');
  for (const [to, note] of [['approved', 'Agreed by registrar'], ['in_transit', ''], ['returned', '']]) {
    await p.selectOption('#to', to); if (note) await p.fill('#note', note);
    await Promise.all([p.waitForLoadState('domcontentloaded'), p.click('button:has-text("Update status")')]);
    ok((await p.content()).includes('moved to'), 'moved to ' + to);
  }
  ok((await p.content()).includes('final state'), 'returned is final');
  ok((await p.content()).includes('requested -&gt; approved] Agreed by registrar') || (await p.content()).includes('requested -> approved] Agreed by registrar'), 'note stamped');
  await p.goto(BASE + '/index.php/admin/federation/loans');
  const body = await p.content();
  ok(/\d+%/.test(body) && body.includes('Busiest partners'), 'analytics: approval rate and partners');

  // Public join, anonymous.
  const a = await (await b.newContext()).newPage();
  await a.goto(BASE + '/index.php/federation/join');
  await a.fill('#institution_name', 'Test Library ' + Date.now()); await a.fill('#contact_email', 'not-an-email');
  await a.waitForTimeout(3500);
  await Promise.all([a.waitForLoadState('domcontentloaded'), a.click('button:has-text("Send request")')]);
  ok(!a.url().includes('thanks'), 'bad email kept on form');
  await a.fill('#contact_email', 'librarian@example.org'); await a.fill('#what_they_share', '<script>alert(1)</script> maps');
  await a.waitForTimeout(3500);
  await Promise.all([a.waitForLoadState('domcontentloaded'), a.click('button:has-text("Send request")')]);
  ok(a.url().includes('join/thanks'), 'join submitted');
  // Bot: honeypot filled.
  await a.goto(BASE + '/index.php/federation/join'); await a.fill('#institution_name', 'Bot Inc');
  await a.evaluate(() => { document.getElementById('website').value = 'spam'; }); await a.waitForTimeout(3500);
  await Promise.all([a.waitForLoadState('domcontentloaded'), a.click('button:has-text("Send request")')]);
  ok(a.url().includes('join/thanks'), 'bot sees thanks');
  // Anonymous may not see the queue.
  const r = await a.goto(BASE + '/index.php/admin/federation/join-requests');
  ok(!(await a.content()).includes('Test Library'), 'anonymous cannot see the queue');

  await p.goto(BASE + '/index.php/admin/federation/join-requests');
  let q = await p.content();
  ok(q.includes('Test Library') && !q.includes('Bot Inc'), 'queue has the real request, not the bot');
  ok(!q.includes('<script>alert(1)</script>'), 'submitted HTML escaped');
  await p.locator('select[name=status]').first().selectOption('approved');
  await p.locator('textarea[name=review_note]').first().fill('Welcome');
  await Promise.all([p.waitForLoadState('domcontentloaded'), p.locator('button:has-text("Update")').first().click()]);
  q = await p.content();
  ok(q.includes('Request updated') && q.includes('Welcome'), 'moderated to approved with note');
  await p.goto(BASE + '/index.php/admin/federation/loans/new');
  await p.selectOption('#direction', 'incoming'); await p.selectOption('#peer_id', { index: 1 });
  await p.fill('#item_ref', String(process.env.LOCAL_ID || ''));
  if (!process.env.LOCAL_ID) await p.fill('#item_title', 'Test item');
  await Promise.all([p.waitForLoadState('domcontentloaded'), p.click('button:has-text("Create loan request")')]);
  const url = p.url(); const html = await p.content();
  ok(html.includes('Incoming: they borrow from us'), 'incoming loan created');
  if (process.env.LOCAL_ID) ok(html.includes('Open record'), 'links to the record');
  const tok = await p.inputValue('input[name=_ahg_csrf_token]');
  // p.request shares the session cookie but not the page's JS (the theme adds the token to fetch()).
  const post = async (body) => (await p.request.post(url, { form: body, maxRedirects: 0 })).status();
  await post({ to: 'returned', _ahg_csrf_token: tok });
  await p.goto(url);
  ok((await p.content()).includes('>Requested<'), 'requested -> returned refused with a valid token');
  const st = await post({ to: 'approved' });
  await p.goto(url);
  ok(st === 403 && (await p.content()).includes('>Requested<'), 'POST without token refused: ' + st);
  await post({ to: 'declined', _ahg_csrf_token: tok });
  await p.goto(url);
  ok((await p.content()).includes('>Declined<'), 'requested -> declined with token works');
  const e = await login(await b.newContext(), process.env.EDITOR_EMAIL);
  await e.goto(BASE + '/index.php/admin/federation/loans');
  ok(!(await e.content()).includes('Busiest partners'), 'editor kept out of loans');
  ok(errs.length === 0, 'no page errors ' + errs.join('; '));
  await b.close();
})().catch(e => { console.log('FATAL', e.message); process.exit(1); });
