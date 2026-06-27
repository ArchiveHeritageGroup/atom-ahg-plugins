# #178 — security audit (two classes) + ahgRdmPlugin IDOR fix

**Date:** 2026-06-27
**Issue:** ArchiveHeritageGroup/atom-ahg-plugins#178 (Heratio E2E parity sweep)

## Audit
Parallel audit of the high-risk AHG plugins against two classes: (1) public read
paths missing the published-only filter (draft leak), (2) mutation routes missing
ACL + IDOR. Spot-verified the most severe claims. **11 plugins with real gaps;
6 clean.** Tracking issues filed:
- #179 ahgVendorPlugin (CRITICAL — no auth anywhere; anon CRUD/IDOR on banking PII)
- #180 ahgCartPlugin (CRITICAL — public payment-notify no ITN validation; order/payment IDOR PII)
- #181 ahgExhibitionPlugin (HIGH — exhibition module CRUD unauth, actor forged user 1)
- #182 ahgProvenancePlugin (HIGH — unauth deleteDocument+unlink; login-only POPIA writes; non-public read leak)
- #183 ahgConditionPlugin (HIGH — unauth INSERT; deletePhoto ignores userId)
- #184 gallery/library/museum/display (HIGH — Class-1 draft leaks)
- #185 3d-model/spectrum/marketplace (MEDIUM — by-id media/listing IDOR + listing draft leak)
Clean: ahgDAM, ahgPortableExport, ahgFederation, ahgTermTaxonomy, ahgImageAr, ahgRequestToPublish (minor notes). Findings note: #178 comment.

## ahgRdmPlugin self-fix (v0.9.1)
This session's own new plugin had the same Class-2 IDOR — mutations were
`requireAuth()` only. Fixed in `modules/rdm/actions/actions.class.php`:
- New helpers `isAdmin()`, `requireAdmin()`, `requireDatasetOwner($id)` (owner = `rdm_dataset.created_by`, or admin).
- `requireDatasetOwner` on deposit / scan / resolveFinding / disposition / linkDmp / unlinkDmp / show.
- Index scoped to the depositor for non-admins (`DatasetService::list(?int $ownerId)`).
- compliance + dashboard → `requireAdmin()` (cross-faculty POPIA oversight).
- fileDownload tightened: owner/admin bypass; non-owner only after a disposition is set AND ODRL permits (closes the pre-gate ODRL default-allow window).

Verified: php -l clean; cache+restart; `rdm:demo --fresh` (admin/owner path) still
passes end-to-end.

## Deliverable status
Audit + tracking issues complete (no code changes required by #178 itself except
the ahgRdmPlugin self-fix). Remediation of #179–#185 is separate follow-up work.
