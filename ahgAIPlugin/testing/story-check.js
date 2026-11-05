// Storytelling engine (#155). Run against a TEST instance only: it saves and
// publishes a story. Needs published records matching "Mount Fuji" and one titled
// "The Great Wave off Kanagawa", a draft titled "Tenant B draft fonds", an
// administrator (ADMIN_EMAIL, password PW) and a configured AI gateway key.
// Daytime only: it calls the GPU through the gateway.
//   BASE=... ADMIN_EMAIL=... PW=... node story-check.js
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
  const ST = BASE + '/index.php/admin/stories';
  await p.goto(ST);
  await p.fill('#st-find', 'Great Wave'); await p.waitForSelector('#st-hits button', { timeout: 10000 });
  await p.click('#st-hits button'); ok(await p.locator('#st-picked input').count() === 1, 'picked a record');
  await p.fill('#st-find', 'Tenant B draft'); await p.waitForTimeout(1200);
  ok(await p.locator('#st-hits button').count() === 0, 'draft records are not offered');
  await p.fill('#st-theme', 'Mount Fuji');
  await p.fill('#st-notes', 'Hokusai made the series in the 1830s.');
  await p.fill('#st-urls', 'http://127.0.0.1/\nhttp://192.168.122.1/');
  const t0 = Date.now();
  await Promise.all([p.waitForLoadState('domcontentloaded', { timeout: 180000 }), p.click('button:has-text("Write the story")')]);
  const body = await p.inputValue('#st-body').catch(() => '');
  ok(!/\*\S/.test(body), 'no markdown asterisks');
  ok(body.split(/\s+/).length > 100, `story written (${body.split(/\s+/).length} words, ${Math.round((Date.now() - t0) / 1000)}s)`);
  const html = await p.content();
  ok((html.match(/not on the public internet/g) || []).length === 2, 'private addresses refused, both');
  ok(await p.locator('input[name="object_ids[]"]').count() >= 2, 'records listed: ' + await p.locator('input[name="object_ids[]"]').count());
  ok(html.includes('Notes: Curator notes') && html.includes('Record: The Great Wave'), 'sources credited');
  ok(!html.includes('Tenant B draft'), 'no draft record in the story');
  const title = 'Fuji in the collection ' + Date.now();
  await p.fill('#st-title', title);
  await Promise.all([p.waitForLoadState('domcontentloaded'), p.click('button[value=draft]')]);
  ok((await p.content()).includes('Story saved as a draft'), 'saved as draft');
  const pub = BASE + '/index.php/stories/' + (await p.locator(`tr:has-text("${title}") a:has-text("View")`).getAttribute('href')).split('/stories/')[1];
  const anon = await (await b.newContext()).newPage();
  ok((await anon.goto(pub)).status() === 404, 'draft hidden from the public');
  await p.goto(pub); ok((await p.content()).includes('only staff can see this page'), 'staff see the draft');
  await p.goto(ST); await Promise.all([p.waitForLoadState('domcontentloaded'), p.click(`tr:has-text("${title}") a:has-text("Edit")`)]);
  await Promise.all([p.waitForLoadState('domcontentloaded'), p.click('button[value=published]')]);
  ok((await p.content()).includes('Story published'), 'published');
  const r = await anon.goto(pub); const a = await anon.content();
  ok(r.status() === 200 && a.includes(title) && a.includes('Records in this story') && a.includes('Curator notes'), 'public page with records and sources');
  ok((await p.request.post(ST, { form: { form_action: 'save', title: 'x', body: 'y' }, maxRedirects: 0 })).status() === 403, 'save without token refused');
  ok(!(await (await anon.goto(ST)).text()).includes('Saved stories'), 'anonymous kept out of the editor');
  await p.goto(ST);
  await Promise.all([p.waitForLoadState('domcontentloaded', { timeout: 180000 }), p.click('button:has-text("On this day")')]);
  const otd = await p.content();
  ok(otd.includes('No published records are dated') || (await p.inputValue('#st-body').catch(() => '')).length > 200, 'on this day: story or a clear message');
  await p.setViewportSize({ width: 390, height: 800 }); await p.goto(ST);
  ok(!(await p.evaluate(() => document.documentElement.scrollWidth > innerWidth)), 'no sideways scroll at phone width');
  ok(errs.length === 0, 'no page errors ' + errs.join('; '));
  console.log('--- story ---\n' + body.slice(0, 700));
  await b.close();
})().catch(e => { console.log('FATAL', e.message); process.exit(1); });
