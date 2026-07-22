# 2026-07-22 - Fix: 500 error on non-YYYYMMDD event dates (circa / ranges)

**Repo:** atom-ahg-plugins. **Release:** v3.79.147.

## Symptom

Entering anything other than a full `YYYY-MM-DD` (circa dates like "c. 1905", or ranges
like "1900-1910") in an information object's date fields produced a **500 Internal Server
Error** on save. Full dates worked; partials/circa/ranges did not.

## Root cause

Two layers:

1. **Our stack:** `InformationObjectCrudService::saveEvents()` wrote the raw
   `startDate`/`endDate` form input **straight into the `event.start_date` /
   `event.end_date` DATE columns** with no validation. Any non-date value → MySQL
   error → uncaught → 500.
2. **Underlying / base AtoM:** the real trigger on modern deployments is **MySQL 8
   strict mode**. `SHOW VARIABLES LIKE 'sql_mode'` returns
   `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,...`. AtoM's `Qubit::parseDate`
   returns partial dates (`1900`, `1900-06`) and historically stored year-only as
   `1900-00-00`; older MySQL 5.x (lenient) accepted these, MySQL 8 strict rejects them
   → 500. So circa/ranges that work at other institutions rely on a lenient sql_mode.
   It is a MySQL-8/strict-default regression, NOT an AtoM PHP-code change.

## Fixes

- **AHG stack (code, v3.79.147):** added `normalizeEventDate()` in
  InformationObjectCrudService. Valid single dates pass through; `YYYYMMDD` and
  `YYYY-MM`/`YYYY` partials are expanded to valid dates; a `YYYY-YYYY` (or "YYYY to
  YYYY") range auto-populates structured start+end; anything unparseable (circa /
  free text) is preserved as the free-text display `date` instead of crashing. The
  DATE columns therefore never receive an invalid value, regardless of the server's
  sql_mode. No DB table or sql_mode was changed on our side.
- **Base/stock AtoM (external client):** the community fix is to relax MySQL
  `sql_mode` (drop `NO_ZERO_DATE`/`NO_ZERO_IN_DATE`, optionally add
  `ALLOW_INVALID_DATES`) so AtoM's partial/zero dates are accepted again. An external
  client resolved their instance this way.

## Design note

AtoM's model: **circa dates and ranges belong in the free-text display "Date" field**;
`start_date`/`end_date` are optional *structured* dates. That is how institutions show
circa/ranges. The fix keeps that model and stops the strict-mode crash.
