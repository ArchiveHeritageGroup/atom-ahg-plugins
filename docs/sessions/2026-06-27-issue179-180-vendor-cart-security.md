# #179 + #180 — CRITICAL security fixes: ahgVendorPlugin + ahgCartPlugin

**Date:** 2026-06-27
**Issues:** #179 (ahgVendorPlugin), #180 (ahgCartPlugin) — from the #178 audit.

## #179 ahgVendorPlugin (CRITICAL) — FIXED
**Was:** no security.yml, no inline auth anywhere → every action (vendor + contract
CRUD, banking/financial PII, transactions) anonymously reachable.
**Fix:** added a `preExecute()` gate to both `vendorActions` and `contractActions`
that calls `parent::preExecute()` then requires `isAuthenticated()` +
`hasCredential(['editor','administrator'], false)`, else `forward('admin','secure')`.
Single-point gate covering the whole module (the incidental Class-1 IO-join leak in
`VendorRepository::getTransactionItems` is now moot — module is staff-only).
**Verified live:** `/vendor/list|index|view` now return **HTTP 403** to anon (were 200 with PII).

## #180 ahgCartPlugin (CRITICAL) — FIXED
### Payment-status forgery (the worst)
**Was:** `processPayFastNotification()` trusted `payment_status=COMPLETE` with NO
validation → anyone could POST to mark any order paid + mint download tokens.
**Fix:** new `validatePayFastItn()` gate runs before acting:
1. **Signature** — rebuild the param string + passphrase, `hash_equals(md5(...), signature)`.
2. **Amount** — `amount_gross` must match `ahg_order.total` (cent tolerance).
3. **Source IP** — advisory (logged; reverse proxies can rewrite REMOTE_ADDR).
4. **Server validate-callback** — POST data back to PayFast `/eng/query/validate`,
   require first line `VALID` (the authoritative, no-false-positive check).
Fails closed (order not marked paid; PayFast retries).

### Order/payment IDOR (PII)
**Was:** ownership checks were null-conditional (`if ($order->user_id && … != $userId)`)
→ guest orders (user_id NULL) readable by any authed user; account orders
(session_id NULL) readable by any anon. Exposed name/email/billing+shipping/items;
order numbers enumerable.
**Fix:** centralised deny-by-default `EcommerceService::viewerOwnsOrder($order,$userId,$isAdmin,$sessionId)`
— admin OR (authed user_id === order.user_id) OR (anon session hash_equals order.session_id,
both non-empty). Wired into orderConfirmation / payment / paymentReturn (the last no
longer acts as an existence oracle).

## Notes / not done here
- `cart/config/security.yml` left `is_secure: false` intentionally — the storefront
  must serve guests; protection is per-action ownership + ITN validation, not blanket auth.
- ITN validation can't be exercised without a live PayFast callback; logic is
  fail-closed and lint-clean. Confirm against a real sandbox ITN when convenient.
- php -l clean on all 6 changed files; cache cleared + php-fpm restarted.

Plugin versions: ahgVendorPlugin 1.0.11, ahgCartPlugin 2.1.1.
Remaining #178 backlog: #181–#185 (#184 includes the LOCKED ahgLibraryPlugin — needs sign-off).
