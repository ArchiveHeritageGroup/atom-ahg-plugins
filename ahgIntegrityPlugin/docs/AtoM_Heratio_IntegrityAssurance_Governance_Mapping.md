# AtoM Heratio --- Integrity Assurance Plugin: Governance Mapping

**Plugin:** ahgIntegrityPlugin v1.2.0
**Author:** The Archive and Heritage Group (Pty) Ltd
**Date:** March 2026
**Classification:** Professional Reference Document

---

## Purpose

This document maps the features of the ahgIntegrityPlugin to the requirements of four international standards governing digital records integrity, preservation, and trusted repository operations. It is intended for compliance officers, archivists, auditors, and IT governance teams seeking to demonstrate how the plugin addresses specific regulatory and standards-based obligations.

---

## Standards Covered

| Abbreviation | Full Title | Scope |
|--------------|-----------|-------|
| **NARSSA/NARS** | National Archives and Records Service of South Africa Act (Act 43 of 1996) and associated regulations | Digital records integrity, retention schedules, disposition authority for South African public bodies and institutions |
| **ISO 15489** | ISO 15489-1:2016 --- Information and documentation --- Records management (Parts 1 & 2) | Authenticity, reliability, integrity, and usability of records throughout their lifecycle |
| **ISO 14721** | ISO 14721:2012 --- Space data and information transfer systems --- Open Archival Information System (OAIS) Reference Model | Fixity Information within Archival Information Packages (AIPs), Preservation Description Information (PDI) |
| **ISO 16363** | ISO 16363:2012 --- Space data and information transfer systems --- Audit and certification of trustworthy digital repositories | Requirements for fixity checking, monitoring, logging, and accountability in Trusted Digital Repositories (TDR) |

---

## Feature-to-Standard Mapping

### 1. Scheduled Verification (Fixity Checking)

Automated, configurable fixity verification of digital objects using SHA-256 or SHA-512. Supports daily, weekly, monthly, or custom cron schedules with scoped verification (global, per-repository, or per-hierarchy node).

| Standard | Requirement | How the Plugin Addresses It |
|----------|-------------|----------------------------|
| **NARSSA/NARS** | Regulation 5(2)(c): Government bodies must ensure the integrity of electronic records throughout their lifecycle. | Scheduled fixity checks continuously verify that digital records have not been altered, corrupted, or lost. Configurable per-repository scoping allows institutions to target schedules to specific record groups as required by their file plan. |
| **ISO 15489** | Clause 5.2.3 (Integrity): Records must be protected against unauthorized alteration. Clause 9.3: Records systems shall include integrity verification mechanisms. | Automated hash comparison detects any unauthorized or accidental modification. Batch processing with configurable frequency ensures ongoing integrity monitoring across all managed records. |
| **ISO 14721** | Section 4.2.1.3.2: Fixity Information --- PDI must include information that documents the mechanisms for ensuring the Content Information has not been altered in an undocumented manner. | Each verification computes a cryptographic hash and compares it against the baseline stored in `preservation_checksum`. Outcomes (pass, mismatch, missing, etc.) are recorded with the algorithm, expected hash, and computed hash --- constituting documented Fixity Information for each AIP. |
| **ISO 16363** | Criterion 4.3.3.1: The repository shall have mechanisms in place to ensure all AIPs are complete at the point of their creation. Criterion 4.4.1: The repository shall have mechanisms to verify each AIP. Criterion 4.4.1.1: The repository shall verify each AIP at the point of its creation. | Default schedules include a daily sample check (200 objects) and weekly full scan. Automatic baseline generation via the Preservation plugin ensures AIPs are checksummed at creation. Continuous verification confirms AIP completeness over time. |

---

### 2. Append-Only Ledger (Audit Trail)

Every verification attempt is recorded in the `integrity_ledger` table. Entries are never updated or deleted, providing a complete forensic history of all fixity events.

| Standard | Requirement | How the Plugin Addresses It |
|----------|-------------|----------------------------|
| **NARSSA/NARS** | Regulation 5(2)(d): An audit trail must be maintained for electronic records to verify their authenticity and integrity. | The append-only ledger records every verification event including timestamp, actor, hostname, file path, expected vs. computed hash, and outcome. No entries are ever modified or removed, preserving a complete chain of evidence. |
| **ISO 15489** | Clause 5.2.2 (Reliability): A reliable record has contents that can be trusted as a full and accurate representation. Clause 9.7: Audit trails must track all interactions with records. | The immutable ledger captures every interaction between the system and a digital object during integrity checking, including successes, failures, and system events (legal holds, dispositions). This provides full transparency for records management accountability. |
| **ISO 14721** | Section 4.2.1.3: Preservation Description Information (PDI) must be maintained over time. Section 6.1: The OAIS must maintain a complete history of preservation actions. | Ledger entries constitute a time-series record of all preservation fixity events, linked to specific digital objects and runs. This history persists independently of the objects themselves (no foreign key cascade on object deletion). |
| **ISO 16363** | Criterion 3.3.5: The repository shall maintain a documented log of all actions taken on objects. Criterion 4.4.2: The repository shall verify the integrity of all AIPs at a rate that ensures any problems are discovered in a timely fashion. | The append-only design ensures logs cannot be retroactively altered. Per-object verification history enables auditors to verify that checking frequency meets institutional policy. |

---

### 3. Dead Letter Queue (Failure Management)

Objects that fail verification repeatedly (default: 3 consecutive failures) are escalated to a persistent failure queue with workflow states (Open, Acknowledged, Investigating, Resolved, Ignored) and retry management.

| Standard | Requirement | How the Plugin Addresses It |
|----------|-------------|----------------------------|
| **NARSSA/NARS** | Regulation 5(3): Institutions must have procedures to detect and respond to threats to the integrity of electronic records. | The dead letter queue automatically escalates persistent failures, ensuring that systemic problems (storage failures, file corruption) are surfaced for human review rather than silently recurring. Workflow states enforce documented response procedures. |
| **ISO 15489** | Clause 9.6: Organizations must implement monitoring and reporting on the performance of records systems. | Dead letter entries track consecutive failure counts, failure types, retry history, and resolution notes --- providing a structured incident management process for records system failures. |
| **ISO 14721** | Section 6.2.1: The OAIS must manage exceptions and errors in preservation processes. | The queue isolates problematic objects from normal processing, preventing a single corrupt file from blocking verification of the entire collection. Per-failure-type tracking (mismatch, missing, unreadable, permission_error, path_drift) enables targeted remediation. |
| **ISO 16363** | Criterion 4.4.1.2: The repository shall actively monitor the integrity of AIPs. Criterion 4.6.1: The repository shall log and review all preservation actions and outcomes. | Configurable escalation thresholds, retry limits, and acknowledgment workflows demonstrate active monitoring. Resolution notes provide auditable documentation of how each failure was investigated and resolved. |

---

### 4. CSV/Auditor Pack Export (Reporting for Auditors)

Filtered CSV export of the verification ledger and a standalone Auditor Pack (ZIP containing summary.html, exceptions.csv, and config-snapshot.json) for compliance audits.

| Standard | Requirement | How the Plugin Addresses It |
|----------|-------------|----------------------------|
| **NARSSA/NARS** | Regulation 8: Records must be made available for audit and inspection by the National Archivist. Section 13(2)(b)(ii): Bodies must report on records management practices. | The Auditor Pack provides a self-contained evidence bundle that can be transmitted to the National Archives or external auditors without requiring system access. The standalone summary.html renders without external dependencies, suitable for offline review. |
| **ISO 15489** | Clause 6.2: Organizations must be able to demonstrate compliance with records management requirements. Clause 9.8: Reporting must support accountability and transparency. | CSV export with date, repository, and outcome filters allows compliance teams to produce evidence of ongoing integrity monitoring. Config snapshots document the system's verification parameters at the time of the report. |
| **ISO 14721** | Section 3.2.2: The OAIS must be able to provide evidence that it is fulfilling its preservation responsibilities. | The Auditor Pack captures the current state of fixity verification across the repository in a portable, verifiable format. The exceptions.csv isolates non-passing outcomes for focused review. |
| **ISO 16363** | Criterion 3.3.1: The repository shall have defined processes for receiving, ingest, archival storage, data management, and access. Criterion 3.5.1: The repository shall have short- and long-term plans for self-assessment. | Export functionality enables periodic self-assessment reporting. The CLI flags (`--export-csv`, `--auditor-pack`) support automated reporting pipelines integrated into institutional audit schedules. |

---

### 5. Retention Policies (Records Scheduling)

Configurable retention period definitions with trigger types (ingest date, last modified, closure date, last access), scope rules (global, per-repository, per-hierarchy), and MIME type filtering.

| Standard | Requirement | How the Plugin Addresses It |
|----------|-------------|----------------------------|
| **NARSSA/NARS** | Section 13(2)(a): Governmental bodies must manage records in accordance with an approved records classification system and retention schedule. Regulation 4: Retention periods must be defined and applied. | Retention policies map directly to NARS-mandated retention schedules. Scope types allow policies to be applied at repository (agency) or hierarchy (file plan) level, aligning with South African records classification structures. Multiple trigger types accommodate varying retention calculation methods. |
| **ISO 15489** | Clause 9.9: Retention and disposition must be systematically managed. Clause 8.5: Retention periods must be defined based on business requirements, legal obligations, and risk assessment. | Each policy defines a named retention period with explicit trigger logic. The MIME type filter allows format-specific retention rules (e.g., longer retention for preservation masters vs. access copies). |
| **ISO 14721** | Section 5.1.1.6: The OAIS must establish policies for retention of AIPs. | Retention policies govern the lifecycle of digital objects within the archival system, determining when objects become eligible for disposition review. The indefinite retention option (0 days) supports permanent preservation mandates. |
| **ISO 16363** | Criterion 3.4.1: The repository shall have policies in place to ensure that AIPs are retained for an appropriate period. Criterion 3.5.2: The repository shall have mechanisms for retention review. | Enabled policies are automatically evaluated by the `integrity:retention --scan-eligible` CLI command, surfacing objects that have exceeded their retention period for structured review. |

---

### 6. Legal Holds (Litigation Hold)

Placement and release of legal holds on information objects to block disposition, with automatic blocking of disposition queue entries and full audit trail logging.

| Standard | Requirement | How the Plugin Addresses It |
|----------|-------------|----------------------------|
| **NARSSA/NARS** | Section 13(5): No records under investigation or subject to litigation may be destroyed or disposed of. | Legal holds prevent any disposition action on affected records. Active holds automatically transition matching disposition queue entries to "held" status. Release workflow re-evaluates eligibility only when all holds on an object are lifted. |
| **ISO 15489** | Clause 9.9: Disposition must be suspended when records are subject to legal proceedings, regulatory investigations, or audits. | The hold mechanism captures the reason, the actor who placed the hold, and timestamps. Hold placement and release events are logged to the integrity ledger, creating an auditable trail of litigation hold actions. |
| **ISO 14721** | Section 5.1.1.6.1: The OAIS must ensure that no AIP is deleted or modified while subject to a preservation commitment or external constraint. | Legal holds create an explicit external constraint that the disposition engine checks before any status transition. Objects under hold cannot be moved to "approved" or "disposed" status regardless of retention policy eligibility. |
| **ISO 16363** | Criterion 3.4.2: The repository shall ensure that no digital object is deleted while subject to any hold or constraint. | Hold status is enforced at multiple points: during eligibility scanning (held objects are excluded), during disposition approval (active holds block transitions), and during processing of approved dispositions (re-check before marking as disposed). |

---

### 7. Disposition Queue (Disposition Authority)

Structured disposition review workflow with states (Eligible, Pending Review, Approved, Rejected, Held, Disposed) and safe disposition (marks records as "disposed" without actual deletion).

| Standard | Requirement | How the Plugin Addresses It |
|----------|-------------|----------------------------|
| **NARSSA/NARS** | Section 13(2)(b): Disposal of records requires written authorization from the National Archivist. Regulation 6: Disposition must follow an approved process with documented authorization. | The multi-step review workflow (eligible -> pending review -> approved -> disposed) ensures that no record is disposed without explicit human authorization. Review notes and reviewer identity are captured for each decision, providing the documented authorization chain required by NARS. |
| **ISO 15489** | Clause 9.9: Disposition actions must be documented, authorized, and carried out in a controlled manner. Clause 9.10: Disposition must be auditable. | Each disposition state transition is logged to the integrity ledger with the actor, action, and any notes. The "safe disposition" design (marking as disposed without deletion) preserves archival integrity while documenting the disposition decision. |
| **ISO 14721** | Section 5.1.1.6: Disposal of AIPs must follow established institutional policy. Section 6.1: All preservation actions must be recorded. | The disposition queue enforces institutional policy by linking each queue entry to a specific retention policy. Safe disposition ensures that even "disposed" objects remain accessible for audit, aligning with OAIS principles of long-term preservation. |
| **ISO 16363** | Criterion 3.4.3: The repository shall have documented processes for disposition, including authorization requirements. Criterion 4.6.2: Disposition actions must be logged. | The six-state workflow provides clear separation between eligibility determination, review, authorization, and execution. Each transition is individually logged. The "Rejected" state allows reviewers to override automated eligibility determinations. |

---

### 8. Threshold Alerting (Monitoring)

Configurable alert thresholds for pass rate, failure count, dead letter count, backlog size, and run failure. Supports email (SwiftMailer) and webhook (HMAC-SHA256 signed) notifications.

| Standard | Requirement | How the Plugin Addresses It |
|----------|-------------|----------------------------|
| **NARSSA/NARS** | Regulation 5(3): Institutions must detect and respond to threats to record integrity in a timely manner. | Threshold-based alerts notify designated staff immediately when integrity metrics fall below acceptable levels. Pass rate alerts detect systemic corruption. Backlog alerts identify verification coverage gaps. Run failure alerts flag operational problems. |
| **ISO 15489** | Clause 9.6: Monitoring must be systematic and ongoing. Clause 6.1: Management commitment includes ensuring adequate resources and responsiveness. | Alert configurations define explicit thresholds for acceptable system performance. Multiple notification channels (email, webhook) ensure alerts reach responsible parties through preferred communication methods. Alert failures are non-fatal, preventing monitoring infrastructure from disrupting preservation operations. |
| **ISO 14721** | Section 6.2: The OAIS must monitor and respond to changes in the preservation environment. | Alerts on dead letter counts and backlog size detect environmental changes (storage degradation, access permission changes) that could threaten preservation. Webhook integration enables connection to enterprise monitoring systems (SIEM, ticketing). |
| **ISO 16363** | Criterion 4.4.2: The repository shall have mechanisms for detecting and reporting integrity problems. Criterion 3.3.6: The repository shall have adequate staffing to support preservation activities. | Configurable comparisons (lt, lte, gt, gte, eq) allow institutions to define institution-specific thresholds aligned with their risk tolerance. HMAC-SHA256 webhook signatures prevent alert spoofing. Last-triggered timestamps enable audit of alert responsiveness. |

---

### 9. previous_hash Chain (Tamper Detection)

Each ledger entry stores the `previous_hash` value --- the last known good computed hash for the same digital object. This creates a hash chain enabling detection of retroactive tampering or hash substitution.

| Standard | Requirement | How the Plugin Addresses It |
|----------|-------------|----------------------------|
| **NARSSA/NARS** | Regulation 5(2)(c): The integrity of records must be verifiable over time. | The hash chain links successive verification events for each object. If a baseline hash were maliciously replaced in the `preservation_checksum` table, the chain of previous_hash values in the append-only ledger would reveal the discrepancy, as the historical computed hashes would not match the new baseline. |
| **ISO 15489** | Clause 5.2.1 (Authenticity): A record must be provably what it purports to be. Clause 5.2.3 (Integrity): Unauthorized changes must be detectable. | The previous_hash chain provides a secondary verification mechanism beyond the primary baseline comparison. Even if baseline checksums are compromised, the ledger's immutable hash chain preserves the historical record of actual computed values. |
| **ISO 14721** | Section 4.2.1.3.2: Fixity Information must support the detection of undocumented alterations. | The chain supplements simple hash comparison by creating a temporal link between verification events. Auditors can trace the hash history of any digital object through the ledger to confirm that no undocumented changes occurred between checks. |
| **ISO 16363** | Criterion 4.4.1.1: The repository must demonstrate that AIPs have not been altered. Criterion 4.4.2: Integrity verification must be comprehensive and documented. | The hash chain provides defense-in-depth against tampering. Even in a scenario where an attacker modifies both the file and its baseline checksum, the historical ledger entries (which cannot be modified) would reveal that the hash value changed, flagging the tampering for investigation. |

---

### 10. Actor/Hostname Tracking (Accountability)

Every ledger entry records the `actor` (user or system process) and `hostname` (server name) responsible for the verification event, supporting multi-server environments and individual accountability.

| Standard | Requirement | How the Plugin Addresses It |
|----------|-------------|----------------------------|
| **NARSSA/NARS** | Regulation 5(2)(d): Audit trails must identify who performed actions on records. | The `actor` field captures the authenticated user or system identity (e.g., "cli:admin", "scheduler", "retention_service"). The `hostname` field identifies which server performed the verification, critical for distributed or multi-instance deployments. |
| **ISO 15489** | Clause 5.2.1 (Authenticity): It must be possible to prove who created or sent a record and when. Clause 9.7: Audit trails must identify responsible agents. | Every verification, legal hold placement, disposition decision, and retention action is attributed to a specific actor. This enables records managers to answer "who verified this record, when, and from where?" for any digital object. |
| **ISO 14721** | Section 4.1.1.3: The OAIS must maintain information about the agents responsible for preservation actions. | Actor and hostname data constitute Agent Information within the OAIS model, documenting which entity performed each preservation fixity event. This information persists in the append-only ledger regardless of subsequent personnel or infrastructure changes. |
| **ISO 16363** | Criterion 3.3.5: The repository shall have documented accountability for all preservation actions. Criterion 4.6.1: Action logs must identify responsible parties. | Combined actor and hostname tracking enables full traceability from a specific ledger entry back to the responsible individual and the server that executed the verification. This is essential for TDR audit certification where auditors must verify chains of custody and responsibility. |

---

### 11. Format Breakdown (Format Risk Management)

Dashboard and API endpoint providing verification outcome statistics grouped by digital object format (MIME type), identifying format-specific integrity risks. Uses PRONOM format identification data from the Preservation plugin.

| Standard | Requirement | How the Plugin Addresses It |
|----------|-------------|----------------------------|
| **NARSSA/NARS** | Regulation 5(2)(b): Institutions must ensure that electronic records remain accessible and usable over time, including awareness of format obsolescence. | The format breakdown identifies which file formats have the highest failure rates, enabling institutions to prioritize format migration or additional preservation actions for at-risk formats. This supports proactive rather than reactive records management. |
| **ISO 15489** | Clause 5.2.4 (Usability): Records must remain usable for the purposes for which they were created. Clause 9.4: Organizations must assess and manage format risks. | By correlating verification outcomes with format types, institutions can identify formats that exhibit disproportionate integrity failures (potential indicators of format instability, storage incompatibility, or obsolescence risk) and plan mitigation strategies. |
| **ISO 14721** | Section 4.2.1.3.3: Representation Information --- the OAIS must maintain information about the formats of its holdings. Section 5.1.3.1: The OAIS must perform technology watch to identify format risks. | The format breakdown provides an operational "technology watch" by surfacing format-specific integrity trends. Integration with PRONOM format identification (via ahgPreservationPlugin) ensures format data is authoritative. The 20-format dashboard limit focuses attention on the most prevalent formats. |
| **ISO 16363** | Criterion 4.3.3: The repository shall have mechanisms to identify format-specific risks. Criterion 4.5.1: The repository shall have mechanisms for monitoring preservation risks. | Format-level pass/fail statistics enable risk-based prioritization of preservation actions. Formats with anomalous failure rates can be investigated for systemic issues (e.g., a storage subsystem that corrupts a specific file type, or a format that is no longer bit-stable). |

---

## Consolidated Cross-Reference Matrix

The following matrix provides a summary view of which plugin features address which standards requirements. Each cell indicates the primary compliance contribution.

| Plugin Feature | NARSSA/NARS | ISO 15489 | ISO 14721 (OAIS) | ISO 16363 (TDR) |
|---------------|-------------|-----------|-------------------|------------------|
| Scheduled verification | Reg 5(2)(c): Integrity assurance | Cl 5.2.3, 9.3: Integrity verification | S 4.2.1.3.2: Fixity Information | Cr 4.4.1, 4.4.1.1: AIP verification |
| Append-only ledger | Reg 5(2)(d): Audit trail | Cl 5.2.2, 9.7: Reliability, audit trail | S 4.2.1.3, 6.1: PDI history | Cr 3.3.5, 4.4.2: Action logging |
| Dead letter queue | Reg 5(3): Threat response | Cl 9.6: Monitoring and reporting | S 6.2.1: Exception management | Cr 4.4.1.2, 4.6.1: Active monitoring |
| CSV/Auditor pack export | Reg 8, S 13(2)(b)(ii): Audit reporting | Cl 6.2, 9.8: Compliance evidence | S 3.2.2: Preservation evidence | Cr 3.3.1, 3.5.1: Self-assessment |
| Retention policies | S 13(2)(a), Reg 4: Retention schedules | Cl 8.5, 9.9: Retention management | S 5.1.1.6: AIP retention policy | Cr 3.4.1, 3.5.2: Retention review |
| Legal holds | S 13(5): Litigation protection | Cl 9.9: Disposition suspension | S 5.1.1.6.1: External constraints | Cr 3.4.2: Hold enforcement |
| Disposition queue | S 13(2)(b), Reg 6: Authorized disposal | Cl 9.9, 9.10: Controlled disposition | S 5.1.1.6, 6.1: Policy-based disposal | Cr 3.4.3, 4.6.2: Documented disposition |
| Threshold alerting | Reg 5(3): Timely detection | Cl 6.1, 9.6: Systematic monitoring | S 6.2: Environment monitoring | Cr 4.4.2, 3.3.6: Problem detection |
| previous_hash chain | Reg 5(2)(c): Verifiable integrity | Cl 5.2.1, 5.2.3: Authenticity, integrity | S 4.2.1.3.2: Undocumented alteration detection | Cr 4.4.1.1, 4.4.2: Tamper detection |
| Actor/hostname tracking | Reg 5(2)(d): Agent identification | Cl 5.2.1, 9.7: Agent accountability | S 4.1.1.3: Agent Information | Cr 3.3.5, 4.6.1: Responsible party logging |
| Format breakdown | Reg 5(2)(b): Format awareness | Cl 5.2.4, 9.4: Usability, format risk | S 4.2.1.3.3, 5.1.3.1: Representation Information | Cr 4.3.3, 4.5.1: Format risk monitoring |

---

## Notes for Auditors

1. **Append-only guarantee.** The `integrity_ledger` table enforces append-only semantics at the application level. No UPDATE or DELETE operations are issued against ledger rows by any plugin service. Institutions requiring database-level enforcement should apply MySQL row-level security or read-only grants to the ledger table.

2. **Safe disposition.** The disposition workflow marks records as "disposed" in the `integrity_disposition_queue` table but does not delete or modify the underlying digital objects or their metadata. Actual physical destruction, if required, must be performed through a separate authorized process outside the plugin.

3. **Hash algorithm support.** The plugin supports SHA-256 (default) and SHA-512. SHA-256 is recommended for general use. SHA-512 may be required by institutional policy or for compliance with specific national standards.

4. **Dependency chain.** The plugin requires ahgPreservationPlugin for baseline checksums (stored in `preservation_checksum`) and format identification data (stored in `preservation_object_format`). Both tables are managed by the Preservation plugin and are treated as read-only by the Integrity plugin.

5. **Multi-server environments.** The `hostname` field in the ledger and the file-based lock mechanism with PID stale recovery support deployments where multiple servers share a common database but access digital objects via different storage paths (e.g., NAS mounts).

---

*AtoM Heratio is developed by The Archive and Heritage Group (Pty) Ltd for the international GLAM and DAM community.*
