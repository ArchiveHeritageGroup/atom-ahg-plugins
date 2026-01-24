# AtoM AHG Framework - Database ERD

**Version:** 2.1.17
**Last Updated:** January 2026

---

## 1. Core Extension Tables

### 1.1 Plugin Management

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           EXTENSION MANAGEMENT ERD                               │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─────────────────────────────┐                                                │
│  │        atom_plugin          │                                                │
│  ├─────────────────────────────┤                                                │
│  │ PK id            BIGINT     │                                                │
│  │    name          VARCHAR(255)│◄── Unique plugin identifier                   │
│  │    class_name    VARCHAR(255)│    e.g., "ahgThemeB5Plugin"                   │
│  │    version       VARCHAR(50) │                                                │
│  │    description   TEXT        │                                                │
│  │    category      VARCHAR(100)│    theme|security|sector|capability           │
│  │    is_enabled    TINYINT(1)  │    0=disabled, 1=enabled                      │
│  │    is_core       TINYINT(1)  │    1=cannot be disabled                       │
│  │    is_locked     TINYINT(1)  │    1=cannot be modified                       │
│  │    load_order    INT         │    Lower = loads first                        │
│  │    dependencies  JSON        │    Required plugins                           │
│  │    settings      JSON        │    Plugin configuration                       │
│  │    created_at    TIMESTAMP   │                                                │
│  │    updated_at    TIMESTAMP   │                                                │
│  └─────────────────────────────┘                                                │
│                                                                                  │
│  Example Data:                                                                   │
│  ┌────┬───────────────────────────┬─────────┬──────────┬──────────┬────────────┐│
│  │ id │ name                      │is_enabled│ is_core  │is_locked │ load_order ││
│  ├────┼───────────────────────────┼─────────┼──────────┼──────────┼────────────┤│
│  │  1 │ ahgThemeB5Plugin          │    1    │    1     │    1     │     1      ││
│  │  2 │ ahgSecurityClearancePlugin│    1    │    1     │    1     │     2      ││
│  │  3 │ ahgDisplayPlugin          │    1    │    0     │    0     │    10      ││
│  │  4 │ ahgPrivacyPlugin          │    1    │    0     │    0     │    20      ││
│  │  5 │ ahgIiifPlugin             │    1    │    0     │    0     │    30      ││
│  └────┴───────────────────────────┴─────────┴──────────┴──────────┴────────────┘│
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Audit Trail ERD

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              AUDIT TRAIL ERD                                     │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─────────────────────────────┐         ┌─────────────────────────────┐        │
│  │         audit_log           │         │          user               │        │
│  ├─────────────────────────────┤         ├─────────────────────────────┤        │
│  │ PK id            BIGINT     │         │ PK id            INT        │        │
│  │ FK user_id       INT        │────────►│    username      VARCHAR    │        │
│  │ FK object_id     INT        │         │    email         VARCHAR    │        │
│  │    object_type   VARCHAR(100)│         └─────────────────────────────┘        │
│  │    action        VARCHAR(50) │◄── create|update|delete|view|download         │
│  │    module        VARCHAR(100)│                                                │
│  │    changes       JSON        │◄── {field: {old: x, new: y}}                  │
│  │    ip_address    VARCHAR(45) │                                                │
│  │    user_agent    TEXT        │                                                │
│  │    session_id    VARCHAR(255)│                                                │
│  │    created_at    TIMESTAMP   │                                                │
│  └─────────────────────────────┘                                                │
│              │                                                                   │
│              │ 1:N                                                               │
│              ▼                                                                   │
│  ┌─────────────────────────────┐                                                │
│  │      audit_log_detail       │                                                │
│  ├─────────────────────────────┤                                                │
│  │ PK id            BIGINT     │                                                │
│  │ FK audit_log_id  BIGINT     │                                                │
│  │    field_name    VARCHAR(255)│                                                │
│  │    old_value     TEXT        │                                                │
│  │    new_value     TEXT        │                                                │
│  └─────────────────────────────┘                                                │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Privacy & Compliance ERD

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           PRIVACY COMPLIANCE ERD                                 │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─────────────────────────────┐                                                │
│  │      privacy_breach         │                                                │
│  ├─────────────────────────────┤                                                │
│  │ PK id              BIGINT   │                                                │
│  │    breach_date     DATE     │                                                │
│  │    discovery_date  DATE     │                                                │
│  │    notification_date DATE   │                                                │
│  │    breach_type     VARCHAR  │◄── unauthorized_access|disclosure|loss        │
│  │    severity        VARCHAR  │◄── low|medium|high|critical                   │
│  │    status          VARCHAR  │◄── open|investigating|contained|closed        │
│  │    description     TEXT     │                                                │
│  │    affected_count  INT      │                                                │
│  │    remediation     TEXT     │                                                │
│  │    reported_to     VARCHAR  │◄── Information Regulator reference            │
│  │    created_at      TIMESTAMP│                                                │
│  │    updated_at      TIMESTAMP│                                                │
│  └─────────────────────────────┘                                                │
│              │                                                                   │
│              │ 1:N                                                               │
│              ▼                                                                   │
│  ┌─────────────────────────────┐         ┌─────────────────────────────┐        │
│  │  privacy_breach_record      │         │    privacy_consent          │        │
│  ├─────────────────────────────┤         ├─────────────────────────────┤        │
│  │ PK id              BIGINT   │         │ PK id              BIGINT   │        │
│  │ FK breach_id       BIGINT   │         │ FK actor_id        INT      │        │
│  │ FK object_id       INT      │         │    consent_type    VARCHAR  │        │
│  │    record_type     VARCHAR  │         │    granted         TINYINT  │        │
│  │    pii_fields      JSON     │         │    granted_date    DATE     │        │
│  └─────────────────────────────┘         │    expires_date    DATE     │        │
│                                          │    consent_text    TEXT     │        │
│                                          │    ip_address      VARCHAR  │        │
│                                          └─────────────────────────────┘        │
│                                                                                  │
│  ┌─────────────────────────────┐         ┌─────────────────────────────┐        │
│  │    privacy_sar_request      │         │  privacy_data_retention     │        │
│  ├─────────────────────────────┤         ├─────────────────────────────┤        │
│  │ PK id              BIGINT   │         │ PK id              BIGINT   │        │
│  │    request_type    VARCHAR  │         │    data_category   VARCHAR  │        │
│  │    requester_name  VARCHAR  │         │    retention_years INT      │        │
│  │    requester_email VARCHAR  │         │    legal_basis     VARCHAR  │        │
│  │    status          VARCHAR  │         │    review_date     DATE     │        │
│  │    deadline        DATE     │         │    is_active       TINYINT  │        │
│  │    completed_date  DATE     │         └─────────────────────────────┘        │
│  │    response        TEXT     │                                                │
│  └─────────────────────────────┘                                                │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Security Classification ERD

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                         SECURITY CLASSIFICATION ERD                              │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─────────────────────────────┐                                                │
│  │   security_classification   │                                                │
│  ├─────────────────────────────┤                                                │
│  │ PK id              INT      │                                                │
│  │    name            VARCHAR  │◄── Unclassified|Restricted|Confidential|...   │
│  │    code            VARCHAR  │◄── U|R|C|S|TS                                  │
│  │    level           INT      │◄── 0-5 (higher = more restricted)             │
│  │    color           VARCHAR  │◄── #hex for UI display                        │
│  │    description     TEXT     │                                                │
│  │    handling_caveat TEXT     │                                                │
│  │    is_active       TINYINT  │                                                │
│  └─────────────────────────────┘                                                │
│              │                                                                   │
│              │ 1:N                                                               │
│              ▼                                                                   │
│  ┌─────────────────────────────┐         ┌─────────────────────────────┐        │
│  │ security_classification_    │         │  security_user_clearance    │        │
│  │        record               │         ├─────────────────────────────┤        │
│  ├─────────────────────────────┤         │ PK id              INT      │        │
│  │ PK id              BIGINT   │         │ FK user_id         INT      │        │
│  │ FK classification_id INT    │         │ FK classification_id INT    │        │
│  │ FK object_id       INT      │         │    granted_date    DATE     │        │
│  │    object_type     VARCHAR  │         │    expires_date    DATE     │        │
│  │    classified_by   INT      │         │    granted_by      INT      │        │
│  │    classified_date DATE     │         │    is_active       TINYINT  │        │
│  │    review_date     DATE     │         └─────────────────────────────┘        │
│  │    reason          TEXT     │                    │                           │
│  └─────────────────────────────┘                    │                           │
│                                                     │                           │
│                                                     │ Determines Access          │
│                                                     ▼                           │
│                                    User can access records where                 │
│                                    record.classification.level <= user.clearance │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Condition Assessment ERD

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                         CONDITION ASSESSMENT ERD                                 │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─────────────────────────────┐                                                │
│  │   condition_assessment      │                                                │
│  ├─────────────────────────────┤                                                │
│  │ PK id              BIGINT   │                                                │
│  │ FK object_id       INT      │────────► information_object                    │
│  │ FK assessor_id     INT      │────────► user                                  │
│  │    assessment_date DATE     │                                                │
│  │    overall_rating  VARCHAR  │◄── excellent|good|fair|poor|critical          │
│  │    stability       VARCHAR  │◄── stable|unstable|deteriorating              │
│  │    display_suitable TINYINT │                                                │
│  │    loan_suitable   TINYINT  │                                                │
│  │    priority        VARCHAR  │◄── low|medium|high|urgent                     │
│  │    next_review     DATE     │                                                │
│  │    notes           TEXT     │                                                │
│  │    created_at      TIMESTAMP│                                                │
│  └─────────────────────────────┘                                                │
│              │                                                                   │
│              │ 1:N                                                               │
│              ▼                                                                   │
│  ┌─────────────────────────────┐         ┌─────────────────────────────┐        │
│  │ condition_assessment_detail │         │condition_treatment_proposal │        │
│  ├─────────────────────────────┤         ├─────────────────────────────┤        │
│  │ PK id              BIGINT   │         │ PK id              BIGINT   │        │
│  │ FK assessment_id   BIGINT   │         │ FK assessment_id   BIGINT   │        │
│  │    component       VARCHAR  │         │    treatment_type  VARCHAR  │        │
│  │    condition       VARCHAR  │         │    description     TEXT     │        │
│  │    damage_type     VARCHAR  │         │    estimated_cost  DECIMAL  │        │
│  │    severity        VARCHAR  │         │    priority        VARCHAR  │        │
│  │    location        VARCHAR  │         │    approved        TINYINT  │        │
│  │    notes           TEXT     │         │    completed_date  DATE     │        │
│  └─────────────────────────────┘         └─────────────────────────────┘        │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 6. Loan Management ERD

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                            LOAN MANAGEMENT ERD                                   │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─────────────────────────────┐                                                │
│  │           loan              │                                                │
│  ├─────────────────────────────┤                                                │
│  │ PK id              BIGINT   │                                                │
│  │    loan_number     VARCHAR  │◄── Auto-generated reference                   │
│  │    loan_type       VARCHAR  │◄── incoming|outgoing                          │
│  │    status          VARCHAR  │◄── requested|approved|active|returned|closed  │
│  │ FK borrower_id     INT      │────────► actor (institution/person)           │
│  │ FK lender_id       INT      │────────► actor (institution/person)           │
│  │    purpose         TEXT     │                                                │
│  │    request_date    DATE     │                                                │
│  │    approval_date   DATE     │                                                │
│  │    start_date      DATE     │                                                │
│  │    end_date        DATE     │                                                │
│  │    return_date     DATE     │                                                │
│  │    insurance_value DECIMAL  │                                                │
│  │    insurance_policy VARCHAR │                                                │
│  │    special_conditions TEXT  │                                                │
│  │    created_at      TIMESTAMP│                                                │
│  └─────────────────────────────┘                                                │
│              │                                                                   │
│              │ 1:N                                                               │
│              ▼                                                                   │
│  ┌─────────────────────────────┐         ┌─────────────────────────────┐        │
│  │        loan_item            │         │      loan_condition         │        │
│  ├─────────────────────────────┤         ├─────────────────────────────┤        │
│  │ PK id              BIGINT   │         │ PK id              BIGINT   │        │
│  │ FK loan_id         BIGINT   │         │ FK loan_item_id    BIGINT   │        │
│  │ FK object_id       INT      │         │    check_type      VARCHAR  │        │
│  │    quantity        INT      │         │    check_date      DATE     │        │
│  │    status          VARCHAR  │         │    checked_by      INT      │        │
│  │    dispatch_date   DATE     │         │    condition       VARCHAR  │        │
│  │    return_date     DATE     │         │    notes           TEXT     │        │
│  │    condition_out   TEXT     │         │    photos          JSON     │        │
│  │    condition_in    TEXT     │         └─────────────────────────────┘        │
│  └─────────────────────────────┘                                                │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 7. Heritage Accounting ERD (GRAP 103)

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                         HERITAGE ACCOUNTING ERD                                  │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─────────────────────────────┐                                                │
│  │       heritage_asset        │                                                │
│  ├─────────────────────────────┤                                                │
│  │ PK id              BIGINT   │                                                │
│  │ FK object_id       INT      │────────► information_object                    │
│  │    asset_number    VARCHAR  │◄── Unique asset register number               │
│  │    asset_class     VARCHAR  │◄── artwork|artifact|archive|natural|monument  │
│  │    acquisition_date DATE    │                                                │
│  │    acquisition_method VARCHAR│◄── purchase|donation|bequest|transfer        │
│  │    acquisition_cost DECIMAL │                                                │
│  │    current_value   DECIMAL  │                                                │
│  │    valuation_date  DATE     │                                                │
│  │    valuation_method VARCHAR │◄── cost|market|replacement|nominal            │
│  │    is_insured      TINYINT  │                                                │
│  │    insurance_value DECIMAL  │                                                │
│  │    location_code   VARCHAR  │                                                │
│  │    custodian       VARCHAR  │                                                │
│  │    status          VARCHAR  │◄── active|deaccessioned|missing|destroyed     │
│  │    created_at      TIMESTAMP│                                                │
│  └─────────────────────────────┘                                                │
│              │                                                                   │
│              │ 1:N                                                               │
│         ┌────┴────┐                                                             │
│         ▼         ▼                                                             │
│  ┌─────────────────────┐  ┌─────────────────────────┐                          │
│  │ heritage_valuation  │  │ heritage_movement       │                          │
│  ├─────────────────────┤  ├─────────────────────────┤                          │
│  │ PK id        BIGINT │  │ PK id        BIGINT     │                          │
│  │ FK asset_id  BIGINT │  │ FK asset_id  BIGINT     │                          │
│  │    value     DECIMAL│  │    movement_type VARCHAR│                          │
│  │    date      DATE   │  │    from_location VARCHAR│                          │
│  │    method    VARCHAR│  │    to_location   VARCHAR│                          │
│  │    valuator  VARCHAR│  │    date          DATE   │                          │
│  │    notes     TEXT   │  │    authorized_by INT    │                          │
│  └─────────────────────┘  │    reason       TEXT    │                          │
│                           └─────────────────────────┘                          │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 8. IIIF Integration ERD

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                            IIIF INTEGRATION ERD                                  │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─────────────────────────────┐         ┌─────────────────────────────┐        │
│  │      iiif_manifest          │         │     iiif_annotation         │        │
│  ├─────────────────────────────┤         ├─────────────────────────────┤        │
│  │ PK id              BIGINT   │         │ PK id              BIGINT   │        │
│  │ FK object_id       INT      │         │ FK object_id       INT      │        │
│  │    manifest_uri    VARCHAR  │         │ FK canvas_id       BIGINT   │        │
│  │    manifest_type   VARCHAR  │         │    target_canvas   VARCHAR  │        │
│  │    label           VARCHAR  │         │    target_selector JSON     │        │
│  │    metadata        JSON     │         │    motivation      VARCHAR  │        │
│  │    thumbnail_uri   VARCHAR  │         │    created_at      TIMESTAMP│        │
│  │    rights          VARCHAR  │         └──────────┬──────────────────┘        │
│  │    created_at      TIMESTAMP│                    │                           │
│  │    updated_at      TIMESTAMP│                    │ 1:N                        │
│  └─────────────────────────────┘                    ▼                           │
│              │                           ┌─────────────────────────────┐        │
│              │ 1:N                       │   iiif_annotation_body      │        │
│              ▼                           ├─────────────────────────────┤        │
│  ┌─────────────────────────────┐         │ PK id              BIGINT   │        │
│  │       iiif_canvas           │         │ FK annotation_id   BIGINT   │        │
│  ├─────────────────────────────┤         │    body_type       VARCHAR  │        │
│  │ PK id              BIGINT   │         │    body_value      TEXT     │        │
│  │ FK manifest_id     BIGINT   │         │    body_format     VARCHAR  │        │
│  │ FK digital_object_id INT    │         │    body_language   VARCHAR  │        │
│  │    canvas_uri      VARCHAR  │         └─────────────────────────────┘        │
│  │    label           VARCHAR  │                                                │
│  │    width           INT      │                                                │
│  │    height          INT      │         ┌─────────────────────────────┐        │
│  │    sequence_number INT      │         │      iiif_ocr_text          │        │
│  └─────────────────────────────┘         ├─────────────────────────────┤        │
│                                          │ PK id              BIGINT   │        │
│                                          │ FK digital_object_id INT    │        │
│                                          │ FK object_id       INT      │        │
│                                          │    full_text       LONGTEXT │        │
│                                          │    format          VARCHAR  │        │
│                                          │    language        VARCHAR  │        │
│                                          │    confidence      DECIMAL  │        │
│                                          └─────────────────────────────┘        │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 9. Research Portal ERD

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           RESEARCH PORTAL ERD                                    │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─────────────────────────────┐         ┌─────────────────────────────┐        │
│  │      research_request       │         │    research_booking         │        │
│  ├─────────────────────────────┤         ├─────────────────────────────┤        │
│  │ PK id              BIGINT   │         │ PK id              BIGINT   │        │
│  │ FK user_id         INT      │         │ FK request_id      BIGINT   │        │
│  │    request_number  VARCHAR  │         │    booking_date    DATE     │        │
│  │    researcher_name VARCHAR  │         │    time_slot       VARCHAR  │        │
│  │    institution     VARCHAR  │         │    room            VARCHAR  │        │
│  │    research_topic  TEXT     │         │    status          VARCHAR  │        │
│  │    purpose         VARCHAR  │         │    check_in_time   TIME     │        │
│  │    status          VARCHAR  │         │    check_out_time  TIME     │        │
│  │    start_date      DATE     │         │    notes           TEXT     │        │
│  │    end_date        DATE     │         └─────────────────────────────┘        │
│  │    approved_by     INT      │                                                │
│  │    created_at      TIMESTAMP│                                                │
│  └─────────────────────────────┘                                                │
│              │                                                                   │
│              │ 1:N                                                               │
│              ▼                                                                   │
│  ┌─────────────────────────────┐         ┌─────────────────────────────┐        │
│  │   research_request_item     │         │   research_access_log       │        │
│  ├─────────────────────────────┤         ├─────────────────────────────┤        │
│  │ PK id              BIGINT   │         │ PK id              BIGINT   │        │
│  │ FK request_id      BIGINT   │         │ FK request_id      BIGINT   │        │
│  │ FK object_id       INT      │         │ FK object_id       INT      │        │
│  │    access_granted  TINYINT  │         │    access_date     DATETIME │        │
│  │    restriction     VARCHAR  │         │    access_type     VARCHAR  │        │
│  │    notes           TEXT     │         │    duration_mins   INT      │        │
│  └─────────────────────────────┘         └─────────────────────────────┘        │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 10. Table Relationships Summary

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                        CROSS-PLUGIN RELATIONSHIPS                                │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│                              ┌──────────────────┐                               │
│                              │information_object│                               │
│                              │   (AtoM Core)    │                               │
│                              └────────┬─────────┘                               │
│                                       │                                          │
│      ┌────────────────────────────────┼────────────────────────────────┐        │
│      │                 │              │              │                 │        │
│      ▼                 ▼              ▼              ▼                 ▼        │
│  ┌────────┐     ┌────────────┐  ┌──────────┐  ┌──────────┐     ┌──────────┐    │
│  │security│     │ condition_ │  │ heritage_│  │  loan_   │     │  iiif_   │    │
│  │classif.│     │ assessment │  │  asset   │  │   item   │     │ manifest │    │
│  │_record │     │            │  │          │  │          │     │          │    │
│  └────────┘     └────────────┘  └──────────┘  └──────────┘     └──────────┘    │
│      │                 │              │              │                 │        │
│      │                 │              │              │                 │        │
│      │          ┌──────┴──────┐ ┌─────┴─────┐  ┌────┴────┐      ┌─────┴─────┐  │
│      │          ▼             ▼ ▼           ▼  ▼         ▼      ▼           ▼  │
│      │    [details]    [treatment][valuation][movement][loan][canvas][annotation]│
│      │                                                                          │
│      └──────────────────────────────────────────────────────────────────────────│
│                                       │                                          │
│                                       ▼                                          │
│                              ┌──────────────────┐                               │
│                              │    audit_log     │                               │
│                              │ (tracks all ops) │                               │
│                              └──────────────────┘                               │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

*Part of the AtoM AHG Framework - v2.1.17*
