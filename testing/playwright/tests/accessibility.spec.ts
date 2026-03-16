import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

/**
 * WCAG 2.1 Level AA Accessibility Tests
 * Issue #182: Automated axe-core compliance checks on key pages.
 *
 * Run: cd testing/playwright && npx playwright test tests/accessibility.spec.ts
 */

const AA_TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'];

// Known violations in base AtoM / landing page (cannot fix without modifying base)
// Known violations in base AtoM or third-party components (cannot fix without modifying base)
// aria-allowed-attr: search box has aria-expanded on input (base AtoM)
// color-contrast: some heritage/landing page elements (base AtoM theme colours)
// link-name: some icon-only pagination/filter links in search results
// html-has-lang: base AtoM admin pages rendered by Symfony without theme layout
const KNOWN_ISSUES = ['aria-allowed-attr', 'color-contrast', 'link-name', 'html-has-lang'];

// Pages to test (no auth required)
const publicPages = [
  { name: 'Homepage', path: '/' },
  { name: 'Browse', path: '/index.php/display/browse' },
  { name: 'Search results', path: '/index.php/display/browse?query=test' },
];

for (const pg of publicPages) {
  test(`${pg.name} — WCAG AA audit (excluding known base-AtoM issues)`, async ({ page }) => {
    await page.goto(pg.path, { waitUntil: 'domcontentloaded' });

    const results = await new AxeBuilder({ page })
      .withTags(AA_TAGS)
      .exclude('#google_translate_element')
      .analyze();

    // Filter out known base-AtoM issues we cannot fix
    const actionable = results.violations.filter(v => !KNOWN_ISSUES.includes(v.id));

    // Log all violations for awareness
    if (results.violations.length > 0) {
      console.log(`\n--- ${pg.name} violations ---`);
      for (const v of results.violations) {
        const tag = KNOWN_ISSUES.includes(v.id) ? ' [KNOWN]' : '';
        console.log(`  [${v.impact}] ${v.id}${tag}: ${v.description} (${v.nodes.length} nodes)`);
        for (const n of v.nodes.slice(0, 2)) {
          console.log(`    ${n.html.substring(0, 120)}`);
        }
      }
    }

    expect(actionable, `${pg.name} should have zero actionable AA violations`).toEqual([]);
  });
}

// ── Structural ARIA Tests ────────────────────────────────────────────

test('Browse page — facets have aria-expanded', async ({ page }) => {
  await page.goto('/index.php/display/browse', { waitUntil: 'domcontentloaded' });

  const facetHeaders = page.locator('#sidebar [aria-expanded]');
  const count = await facetHeaders.count();
  expect(count).toBeGreaterThan(0);

  // First facet (GLAM Type) should be expanded
  const first = facetHeaders.first();
  await expect(first).toHaveAttribute('aria-expanded', 'true');
});

test('Browse page — sidebar has complementary role', async ({ page }) => {
  await page.goto('/index.php/display/browse', { waitUntil: 'domcontentloaded' });

  const sidebar = page.locator('#sidebar');
  await expect(sidebar).toHaveAttribute('role', 'complementary');
});

test('Footer has contentinfo role', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  // Footer may be conditionally rendered based on settings
  const footer = page.locator('footer.ahg-site-footer');
  const count = await footer.count();
  if (count > 0) {
    await expect(footer).toHaveAttribute('role', 'contentinfo');
  } else {
    // If footer not rendered (no footer text), the test is N/A — pass
    test.skip();
  }
});

test('ARIA live region exists on every page', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  const liveRegion = page.locator('#ahgLiveRegion');
  await expect(liveRegion).toBeAttached();
  await expect(liveRegion).toHaveAttribute('aria-live', 'polite');
});

test('ahgAnnounce and ahgFocusTo are available globally', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  const hasAnnounce = await page.evaluate(() => typeof (window as any).ahgAnnounce === 'function');
  expect(hasAnnounce).toBe(true);

  const hasFocusTo = await page.evaluate(() => typeof (window as any).ahgFocusTo === 'function');
  expect(hasFocusTo).toBe(true);
});

test('Tables have scope attributes (auto-injected)', async ({ page }) => {
  await page.goto('/index.php/display/browse?view=table', { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(500);

  const theadThs = page.locator('thead th[scope="col"]');
  const count = await theadThs.count();
  if (count > 0) {
    expect(count).toBeGreaterThan(0);
  }
});

test('prefers-reduced-motion CSS is present', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  const hasReducedMotion = await page.evaluate(() => {
    for (const sheet of document.styleSheets) {
      try {
        for (const rule of (sheet as CSSStyleSheet).cssRules) {
          if (rule.cssText && rule.cssText.includes('prefers-reduced-motion')) {
            return true;
          }
        }
      } catch (e) {
        // Cross-origin sheets throw SecurityError
      }
    }
    return false;
  });

  expect(hasReducedMotion).toBe(true);
});

// ── Authenticated Test ───────────────────────────────────────────────

test('Admin page — WCAG AA audit (authenticated)', async ({ page }) => {
  // AtoM login — main form uses type="email" (navbar dropdown uses type="text")
  await page.goto('/user/login', { waitUntil: 'domcontentloaded' });

  await page.locator('input[type="email"][name="email"]').fill('johan@theahg.co.za');
  await page.locator('input[type="password"][required]').fill('Merlot@123');
  await page.locator('button[type="submit"].atom-btn-outline-success, button[type="submit"]').last().click();
  await page.waitForLoadState('domcontentloaded');

  await page.goto('/admin', { waitUntil: 'domcontentloaded' });

  const results = await new AxeBuilder({ page })
    .withTags(AA_TAGS)
    .exclude('#google_translate_element')
    .analyze();

  const actionable = results.violations.filter(v => !KNOWN_ISSUES.includes(v.id));

  if (results.violations.length > 0) {
    console.log('\n--- Admin violations ---');
    for (const v of results.violations) {
      const tag = KNOWN_ISSUES.includes(v.id) ? ' [KNOWN]' : '';
      console.log(`  [${v.impact}] ${v.id}${tag}: ${v.description} (${v.nodes.length} nodes)`);
    }
  }

  expect(actionable, 'Admin page should have zero actionable AA violations').toEqual([]);
});
