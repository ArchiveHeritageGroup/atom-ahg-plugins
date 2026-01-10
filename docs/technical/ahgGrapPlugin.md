# ahgGrapPlugin - Technical Documentation

**Version:** 1.0.0  
**Category:** Financial/Compliance  
**Dependencies:** atom-framework

---

## Overview

GRAP 103 (Generally Recognised Accounting Practice) heritage asset accounting module for South African public sector compliance. Supports asset recognition, valuation, depreciation tracking, and financial reporting.

---

## Database Schema

### ERD Diagram

```
┌─────────────────────────────────────────┐
│          grap_heritage_asset            │
├─────────────────────────────────────────┤
│ PK id INT                              │
│ FK information_object_id INT UNIQUE    │──────┐
│                                         │      │
│ -- RECOGNITION --                       │      │
│    recognition_status ENUM              │      │
│    recognition_status_reason VARCHAR    │      │
│    recognition_date DATE                │      │
│    measurement_basis ENUM               │      │
│                                         │      │
│ -- ACQUISITION --                       │      │
│    acquisition_method ENUM              │      │
│    acquisition_date DATE                │      │
│    cost_of_acquisition DECIMAL(15,2)    │      │
│    fair_value_at_acquisition DECIMAL    │      │
│    nominal_value DECIMAL(15,2)          │      │
│    donor_name VARCHAR                   │      │
│    donor_restrictions TEXT              │      │
│                                         │      │
│ -- CARRYING AMOUNT --                   │      │
│    current_carrying_amount DECIMAL      │      │
│    accumulated_impairment DECIMAL       │      │
│    last_valuation_date DATE             │      │
│    last_valuation_amount DECIMAL        │      │
│    valuation_method ENUM                │      │
│    valuer_name VARCHAR                  │      │
│    valuer_qualifications VARCHAR        │      │
│    next_valuation_due DATE              │      │
│                                         │      │
│ -- ASSET CLASS --                       │      │
│    asset_class ENUM                     │      │
│    asset_subclass VARCHAR               │      │
│    is_collection TINYINT                │      │
│    collection_id INT                    │      │
│                                         │      │
│ -- INSURANCE --                         │      │
│    insured_value DECIMAL                │      │
│    insurance_policy VARCHAR             │      │
│    insurance_expiry DATE                │      │
│                                         │      │
│ -- STATUS --                            │      │
│    status ENUM                          │      │
│    disposal_date DATE                   │      │
│    disposal_method ENUM                 │      │
│    disposal_proceeds DECIMAL            │      │
│                                         │      │
│    created_at TIMESTAMP                 │      │
│    updated_at TIMESTAMP                 │      │
└─────────────────────────────────────────┘      │
              │                                   │
              │ 1:N                               │
              ▼                                   │
┌─────────────────────────────────────────┐      │
│        grap_valuation_history           │      │
├─────────────────────────────────────────┤      │
│ PK id INT                              │      │
│ FK heritage_asset_id INT               │──────┤
│    valuation_date DATE                  │      │
│    valuation_type ENUM                  │      │
│    previous_value DECIMAL               │      │
│    new_value DECIMAL                    │      │
│    change_amount DECIMAL                │      │
│    valuation_method ENUM                │      │
│    valuer_name VARCHAR                  │      │
│    valuer_organization VARCHAR          │      │
│    valuer_qualifications VARCHAR        │      │
│    valuation_report_ref VARCHAR         │      │
│    justification TEXT                   │      │
│    approved_by INT                      │      │
│    approved_at TIMESTAMP                │      │
│    created_at TIMESTAMP                 │      │
└─────────────────────────────────────────┘      │
                                                  │
┌─────────────────────────────────────────┐      │
│         grap_impairment_record          │      │
├─────────────────────────────────────────┤      │
│ PK id INT                              │      │
│ FK heritage_asset_id INT               │──────┤
│    impairment_date DATE                 │      │
│    impairment_type ENUM                 │      │
│    impairment_amount DECIMAL            │      │
│    carrying_before DECIMAL              │      │
│    carrying_after DECIMAL               │      │
│    reason TEXT                          │      │
│    reversible TINYINT                   │      │
│    reversal_date DATE                   │      │
│    reversal_amount DECIMAL              │      │
│    approved_by INT                      │      │
│    created_at TIMESTAMP                 │      │
└─────────────────────────────────────────┘      │
                                                  │
┌─────────────────────────────────────────┐      │
│        grap_movement_record             │      │
├─────────────────────────────────────────┤      │
│ PK id INT                              │      │
│ FK heritage_asset_id INT               │──────┘
│    movement_date DATE                   │
│    movement_type ENUM                   │
│    from_location VARCHAR                │
│    to_location VARCHAR                  │
│    reason TEXT                          │
│    authorized_by INT                    │
│    condition_before VARCHAR             │
│    condition_after VARCHAR              │
│    created_at TIMESTAMP                 │
└─────────────────────────────────────────┘
```

---

## Asset Classes (GRAP 103)

| Class | Description |
|-------|-------------|
| artwork | Paintings, sculptures, etc. |
| antiquities | Archaeological items |
| museum_collections | Curated collections |
| library_collections | Rare books, manuscripts |
| archival_records | Historical documents |
| natural_heritage | Natural specimens |
| monuments | Buildings, structures |
| memorabilia | Historical objects |

---

## Valuation Methods

| Method | Code | Description |
|--------|------|-------------|
| Cost | cost | Original acquisition cost |
| Fair Value | fair_value | Current market value |
| Replacement Cost | replacement | Cost to replace |
| Insurance Value | insurance | Insured amount |
| Nominal | nominal | R1 token value |

---

## Service Methods

### GrapService

```php
namespace ahgGrapPlugin\Service;

class GrapService
{
    // Asset Management
    public function createAsset(int $objectId, array $data): int
    public function updateAsset(int $id, array $data): bool
    public function getAsset(int $objectId): ?array
    public function listAssets(array $filters): Collection
    
    // Valuations
    public function recordValuation(int $assetId, array $data): int
    public function getValuationHistory(int $assetId): Collection
    public function getAssetsForRevaluation(int $days = 90): Collection
    
    // Impairments
    public function recordImpairment(int $assetId, array $data): int
    public function reverseImpairment(int $impairmentId, array $data): bool
    
    // Movements
    public function recordMovement(int $assetId, array $data): int
    public function getMovementHistory(int $assetId): Collection
    
    // Reporting
    public function getAssetRegister(array $filters): Collection
    public function getBalanceSheet(string $date): array
    public function getMovementReport(string $startDate, string $endDate): array
    public function getValuationReport(): array
    public function getComplianceSummary(): array
    
    // Export
    public function exportAssetRegister(string $format): string
    public function generateAnnualReport(int $year): string
}
```

---

## Reports

| Report | Description |
|--------|-------------|
| Asset Register | Complete list with values |
| Balance Sheet | Financial position |
| Movement Schedule | Additions/disposals |
| Valuation Summary | Current valuations |
| Compliance Report | GRAP 103 checklist |

---

## Views (Database)

```sql
-- Summary view
CREATE VIEW v_grap_103_summary AS
SELECT 
    asset_class,
    COUNT(*) as asset_count,
    SUM(current_carrying_amount) as total_value,
    SUM(CASE WHEN recognition_status = 'recognised' THEN 1 ELSE 0 END) as recognised_count
FROM grap_heritage_asset
WHERE status = 'active'
GROUP BY asset_class;

-- Balance sheet view
CREATE VIEW v_grap_balance_sheet AS
SELECT
    asset_class,
    SUM(cost_of_acquisition) as gross_cost,
    SUM(accumulated_impairment) as impairment,
    SUM(current_carrying_amount) as net_value
FROM grap_heritage_asset
WHERE status = 'active' AND recognition_status = 'recognised'
GROUP BY asset_class;
```

---

*Part of the AtoM AHG Framework*
