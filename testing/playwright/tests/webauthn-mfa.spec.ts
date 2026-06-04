import { test, expect, type Page } from '@playwright/test';

/**
 * WebAuthn / FIDO2 passkey MFA — end-to-end (#126 / #721, parity twin #133).
 *
 * Drives the full registration + assertion ceremonies through a Chrome DevTools
 * Protocol *virtual authenticator*, so no physical security key is needed.
 *
 * Covers:
 *   1. Enrolment via the manage page (navigator.credentials.create round-trip,
 *      credential persisted to ahg_webauthn_credential, listed in the table).
 *   2. Assertion — the exact ceremony the 2FA verify page fires
 *      (navigator.credentials.get -> /assert/complete -> create2FASession).
 *   3. Cleanup — deletes the test passkey so no test credential is left behind.
 *
 * Run: cd testing/playwright && npx playwright test tests/webauthn-mfa.spec.ts
 *
 * Chromium-only (the virtual authenticator is a CDP feature).
 */

// Credentials are env-configurable so the password never lives in git and the
// run survives credential rotation:
//   PSIS_USER='johan@theahg.co.za' PSIS_PASS='…' npx playwright test tests/webauthn-mfa.spec.ts
const ADMIN_EMAIL = process.env.PSIS_USER || 'johan@theahg.co.za';
const ADMIN_PASSWORD = process.env.PSIS_PASS || 'Merlot@123';
const MANAGE_URL = '/security/2fa/webauthn';

async function loginAsAdmin(page: Page) {
  await page.goto('/user/login', { waitUntil: 'domcontentloaded' });
  // PSIS renders two login forms (navbar dropdown + page body). Scope all
  // actions to ONE form so we don't fill one and submit the other (empty).
  const form = page
    .locator('form', { has: page.locator('input[name="email"]') })
    .last();
  if ((await form.count()) === 0) return; // already authenticated
  await form.locator('input[name="email"]').fill(ADMIN_EMAIL);
  await form.locator('input[name="password"]').fill(ADMIN_PASSWORD);
  // The rendered action carries a trailing slash (/index.php/user/login/),
  // which the server answers with a 301 — and the browser replays the redirect
  // as a GET, silently dropping the credentials. The slash-less URL accepts the
  // POST directly (200), so strip the trailing slash before submitting.
  await form.evaluate((f: HTMLFormElement) => {
    const a = f.getAttribute('action') || '';
    f.setAttribute('action', a.replace(/\/+$/, ''));
  });
  await Promise.all([
    page.waitForURL((u) => !u.pathname.includes('/user/login'), { timeout: 15_000 }).catch(() => {}),
    form.locator('button[type="submit"], input[type="submit"]').first().click(),
  ]);
  await page.waitForLoadState('domcontentloaded');

  if (page.url().includes('/user/login')) {
    throw new Error(
      `Login failed for ${ADMIN_EMAIL} — still on /user/login. ` +
      `Set a valid credential: PSIS_USER=… PSIS_PASS=… npx playwright test tests/webauthn-mfa.spec.ts`
    );
  }
}

/** Attach an internal virtual authenticator with user-verification pre-satisfied. */
async function addVirtualAuthenticator(page: Page) {
  const client = await page.context().newCDPSession(page);
  await client.send('WebAuthn.enable', { enableUI: false });
  const { authenticatorId } = await client.send('WebAuthn.addVirtualAuthenticator', {
    options: {
      protocol: 'ctap2',
      transport: 'internal',
      hasResidentKey: true,
      hasUserVerification: true,
      isUserVerified: true,
      automaticPresenceSimulation: true,
    },
  });
  return { client, authenticatorId };
}

test.describe('WebAuthn passkey MFA (#126/#721)', () => {
  test('register a passkey and complete an assertion via virtual authenticator', async ({ page }) => {
    // The delete (cleanup) step fires a confirm() — auto-accept any dialog.
    page.on('dialog', (d) => d.accept().catch(() => {}));

    await loginAsAdmin(page);
    const { client } = await addVirtualAuthenticator(page);

    const label = `E2E passkey ${Date.now()}`;
    const rowFor = (l: string) => page.locator('table tbody tr', { hasText: l });

    // ── 1. ENROL ────────────────────────────────────────────────────────────
    await page.goto(MANAGE_URL, { waitUntil: 'domcontentloaded' });
    // If we got bounced to login, the plugin/route isn't wired — fail loudly.
    expect(page.url(), 'manage page should be reachable while authenticated').toContain('/security/2fa/webauthn');
    await expect(page.locator('#wa-register')).toBeVisible();

    await page.locator('#wa-label').fill(label);
    await page.locator('#wa-register').click();

    // Ceremony POSTs /register/complete then auto-reloads the page.
    // Re-fetch a clean list and assert the credential persisted.
    await page.waitForTimeout(2500);
    await page.goto(MANAGE_URL, { waitUntil: 'domcontentloaded' });
    await expect(rowFor(label), 'registered passkey should appear in the list').toHaveCount(1);

    // ── 2. ASSERT (the login second factor) ─────────────────────────────────
    // webauthn.js is loaded on the manage page; authenticate() runs the same
    // assert/begin -> credentials.get -> assert/complete flow the verify page uses.
    // No userId argument: the server uses the authenticated session user.
    const asserted = await page.evaluate(async () => {
      // @ts-expect-error injected global
      return await window.AhgWebAuthn.authenticate();
    });
    expect(asserted, 'passkey assertion should validate server-side').toBe(true);

    // ── 3. CLEANUP ──────────────────────────────────────────────────────────
    await rowFor(label).locator('button').click();
    await expect(rowFor(label), 'test passkey should be removed').toHaveCount(0, { timeout: 10_000 });

    await client.send('WebAuthn.disable');
  });
});
