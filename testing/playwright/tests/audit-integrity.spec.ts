import { test, expect, type Page } from '@playwright/test';

/**
 * Tamper-evident audit-trail integrity page — smoke (#126).
 *
 * Verifies the admin integrity view renders and reports the hash-chain status.
 * The chain logic itself (write → verify → detect content/structural tampering)
 * is exercised directly against SecurityClearanceService::verifyAuditChain(),
 * and from the CLI via `php symfony security:audit-verify`.
 *
 * Run: cd testing/playwright && npx playwright test tests/audit-integrity.spec.ts
 */

const ADMIN_EMAIL = process.env.PSIS_USER || 'johan@theahg.co.za';
const ADMIN_PASSWORD = process.env.PSIS_PASS || 'Merlot@123';

async function loginAsAdmin(page: Page) {
  await page.goto('/user/login', { waitUntil: 'domcontentloaded' });
  const form = page.locator('form', { has: page.locator('input[name="email"]') }).last();
  await form.locator('input[name="email"]').fill(ADMIN_EMAIL);
  await form.locator('input[name="password"]').fill(ADMIN_PASSWORD);
  await form.evaluate((f: HTMLFormElement) => {
    f.setAttribute('action', (f.getAttribute('action') || '').replace(/\/+$/, ''));
  });
  await Promise.all([
    page.waitForURL((u) => !u.pathname.includes('/user/login'), { timeout: 15_000 }).catch(() => {}),
    form.locator('button[type="submit"], input[type="submit"]').first().click(),
  ]);
  if (page.url().includes('/user/login')) {
    throw new Error(`Login failed for ${ADMIN_EMAIL}. Set PSIS_USER / PSIS_PASS.`);
  }
}

test('audit integrity page reports hash-chain status to admins (#126)', async ({ page }) => {
  await loginAsAdmin(page);

  await page.goto('/securityAudit/integrity', { waitUntil: 'domcontentloaded' });
  expect(page.url(), 'integrity page should be reachable by an admin').toContain('/securityAudit/integrity');

  const body = await page.locator('body').innerText();
  // Either verdict is a valid render; a healthy chain shows "Chain intact".
  expect(body).toMatch(/Chain intact|Tampering detected/);
  expect(body, 'should surface the CLI equivalent').toContain('security:audit-verify');
});
