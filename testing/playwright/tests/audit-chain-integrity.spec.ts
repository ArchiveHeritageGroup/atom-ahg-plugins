import { test, expect, type Page } from '@playwright/test';

/**
 * ahg_audit_log tamper-evident hash chain — admin integrity view smoke (#126).
 * Chain logic (append → verify → detect tampering) is exercised directly against
 * ChainedAuditWriter and from the CLI (`php symfony audit:chain`).
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

test('ahg_audit_log integrity page reports chain status (#126)', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('/admin/audit/integrity', { waitUntil: 'domcontentloaded' });
  expect(page.url(), 'integrity page reachable by admin').toContain('/admin/audit/integrity');
  const body = await page.locator('body').innerText();
  expect(body).toMatch(/Chain intact|Tampering detected|not sealed/);
  expect(body).toContain('audit:chain');
});
