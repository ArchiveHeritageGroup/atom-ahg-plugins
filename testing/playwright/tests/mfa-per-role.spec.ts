import { test, expect, type Page } from '@playwright/test';

/**
 * Per-role MFA enforcement — end-to-end (#738).
 *
 * Verifies the session-wide gate: when the admin enables MFA for the
 * administrator role, the signed-in admin is redirected to /security/2fa on the
 * next normal page until they hold a valid 2FA session. The policy page itself
 * (securityClearance module) stays exempt so an admin can always turn it back off.
 *
 * Run: cd testing/playwright && npx playwright test tests/mfa-per-role.spec.ts
 */

const ADMIN_EMAIL = process.env.PSIS_USER || 'johan@theahg.co.za';
const ADMIN_PASSWORD = process.env.PSIS_PASS || 'Merlot@123';
const BROWSE = '/index.php/informationobject/browse';
const POLICY = '/security/2fa/policy';

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

async function setPolicy(page: Page, enable: boolean) {
  await page.goto(POLICY, { waitUntil: 'domcontentloaded' });
  const form = page.locator('form', { has: page.locator('#mfa_enabled') });
  await form.evaluate((f: HTMLFormElement) => {
    f.setAttribute('action', (f.getAttribute('action') || '').replace(/\/+$/, ''));
  });
  const toggle = page.locator('#mfa_enabled');
  const adminRole = page.locator('#role_100');
  if (enable) {
    await toggle.check();
    await adminRole.check();
  } else {
    await toggle.uncheck();
    await adminRole.uncheck();
  }
  await Promise.all([
    page.waitForLoadState('domcontentloaded'),
    form.locator('button[type="submit"]').click(),
  ]);
}

test.describe('Per-role MFA enforcement (#738)', () => {
  test('admin role gate redirects to /security/2fa, and disables cleanly', async ({ page }) => {
    await loginAsAdmin(page);

    try {
      // Policy off (default): a normal page is NOT gated.
      await page.goto(BROWSE, { waitUntil: 'domcontentloaded' });
      expect(page.url(), 'browse should load with policy off').toContain('informationobject');

      // The admin policy page renders with the toggle + role checkboxes.
      await page.goto(POLICY, { waitUntil: 'domcontentloaded' });
      await expect(page.locator('#mfa_enabled')).toBeVisible();
      await expect(page.locator('#role_100')).toBeVisible();

      // Enable MFA for the administrator role.
      await setPolicy(page, true);

      // A normal page is now gated → redirected to the 2FA flow.
      await page.goto(BROWSE, { waitUntil: 'domcontentloaded' });
      expect(page.url(), 'browse should redirect to 2FA when gated').toContain('/security/2fa');

      // The policy page stays exempt (escape hatch) even while gated.
      await page.goto(POLICY, { waitUntil: 'domcontentloaded' });
      await expect(page.locator('#mfa_enabled')).toBeVisible();
    } finally {
      // ALWAYS disable so the real admin account is never left gated.
      await setPolicy(page, false).catch(() => {});
    }

    // Gate is off again.
    await page.goto(BROWSE, { waitUntil: 'domcontentloaded' });
    expect(page.url(), 'browse should load after disabling policy').toContain('informationobject');
  });
});
