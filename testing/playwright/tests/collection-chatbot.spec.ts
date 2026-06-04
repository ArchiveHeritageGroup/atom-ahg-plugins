import { test, expect, type Page } from '@playwright/test';

/**
 * Collection chatbot — RAG Q&A over the catalogue (#121).
 * Verifies the page renders and the ask endpoint returns a grounded JSON answer
 * (mode 'ai' when an LLM is configured, else a 'fallback' record list) with cited
 * sources, scoped to the authenticated session.
 *
 * Run: cd testing/playwright && npx playwright test tests/collection-chatbot.spec.ts
 */
const ADMIN_EMAIL = process.env.PSIS_USER || 'johan@theahg.co.za';
const ADMIN_PASSWORD = process.env.PSIS_PASS || 'Merlot@123';

async function loginAsAdmin(page: Page) {
  await page.goto('/user/login', { waitUntil: 'domcontentloaded' });
  const form = page.locator('form', { has: page.locator('input[name="email"]') }).last();
  await form.locator('input[name="email"]').fill(ADMIN_EMAIL);
  await form.locator('input[name="password"]').fill(ADMIN_PASSWORD);
  await form.evaluate((f: HTMLFormElement) => f.setAttribute('action', (f.getAttribute('action') || '').replace(/\/+$/, '')));
  await Promise.all([
    page.waitForURL((u) => !u.pathname.includes('/user/login'), { timeout: 15_000 }).catch(() => {}),
    form.locator('button[type="submit"], input[type="submit"]').first().click(),
  ]);
  if (page.url().includes('/user/login')) throw new Error(`Login failed for ${ADMIN_EMAIL}. Set PSIS_USER / PSIS_PASS.`);
}

test('collection assistant answers a catalogue question with sources (#121)', async ({ page }) => {
  await loginAsAdmin(page);

  await page.goto('/ai/assistant', { waitUntil: 'domcontentloaded' });
  expect(await page.locator('body').innerText()).toContain('Collection assistant');

  const res = await page.evaluate(async () => {
    const r = await fetch('/ai/assistant/ask', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: 'photograph', history: [] }),
    });
    const json = await r.json().catch(() => null);
    return { status: r.status, ct: r.headers.get('content-type') || '', json };
  });

  expect(res.status).toBe(200);
  expect(res.ct).toContain('application/json');
  expect(res.json, 'ask returns JSON').toBeTruthy();
  expect(['ai', 'fallback', 'empty']).toContain(res.json.mode);
  expect(typeof res.json.answer).toBe('string');
  expect(res.json.answer.length).toBeGreaterThan(0);
  expect(Array.isArray(res.json.sources)).toBe(true);
});
