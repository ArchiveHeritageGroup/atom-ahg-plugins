# ahgGrapPlugin - GRAP 103 Heritage Asset Accounting for AtoM

Comprehensive GRAP 103 (Heritage Assets) accounting module for AtoM (Access to Memory) archival description software. Designed for South African public sector entities requiring compliance with Generally Recognised Accounting Practice (GRAP), PFMA, and NARSSA requirements.

## Features

### 1. Heritage Asset Recognition & Measurement (GRAP 103)

- **Initial Recognition** (GRAP 103.14-25)
  - Recognition criteria assessment
  - Asset classification into GRAP 103.74 classes
  - Measurement basis selection (Cost, Fair Value, Nominal)
  - Acquisition date and initial cost recording
  - Funding source and donor restrictions tracking

- **Subsequent Measurement** (GRAP 103.36-51)
  - Revaluation recording with valuer details
  - Revaluation surplus tracking
  - Valuation history maintenance
  - Fair value updates

- **Impairment** (GRAP 21/26)
  - Impairment indicator tracking
  - Impairment loss recording
  - Accumulated impairment tracking
  - Impairment reversal capability

- **De-recognition** (GRAP 103.52-56)
  - De-recognition workflow
  - Disposal proceeds recording
  - Gain/loss on disposal calculation
  - NARSSA disposal authority tracking

### 2. Spectrum Procedure Linkage

Direct integration with arSpectrumPlugin linking GRAP accounting events to Spectrum 5.0 procedures:

| Spectrum Procedure | GRAP Action |
|-------------------|-------------|
| Acquisition | Initial Recognition |
| Valuation | Revaluation |
| Object Condition | Impairment Assessment |
| Deaccession | Pending De-recognition |
| Object Exit | De-recognition |
| Loss/Damage | Impairment Loss |
| Insurance | Disclosure Update |

### 3. GRAP 103 / NARSSA Compliance Checklist

Comprehensive compliance checking against:

- **GRAP 103 Recognition** - Asset identification, criteria, classification
- **GRAP 103 Measurement** - Basis, cost, carrying amount, revaluation
- **GRAP 103 Disclosure** - Required note disclosures
- **Documentation** - Asset register, provenance, supporting documents
- **NARSSA** - Archives Act, disposal authority, access classification
- **PFMA** - Asset register, safeguarding, annual reporting

Each check includes:
- Reference to standard/regulation
- Severity rating (Critical, High, Medium, Low)
- Pass/Fail/Warning status
- Specific compliance message
- Automated recommendations

### 4. National Treasury Export Templates

Export reports matching NT return formats:

| Report | Format | Purpose |
|--------|--------|---------|
| **Heritage Asset Register** | CSV | NT compliant asset register for annual reporting |
| **GRAP 103 Disclosure Note** | CSV | AFS note format with class breakdown |
| **Impairment Schedule** | CSV | Detailed impairment losses |
| **De-recognition Schedule** | CSV | Asset disposals with NARSSA refs |
| **Revaluation Schedule** | CSV | Valuation changes |

### 5. Multi-Year Trend Analysis

Track heritage asset metrics over 3-10 financial years:
- Total assets and carrying amounts
- Recognition rates
- Impairment trends
- Acquisition and disposal volumes
- Year-on-year movements

### 6. Board Pack Exports

Executive summary reports in PDF format:
- Asset portfolio overview
- Compliance status
- High-value assets
- Recent activity
- Key metrics and KPIs

## Asset Classes (GRAP 103.74)

The plugin supports all GRAP 103 heritage asset classes:

- Art Collections
- Museum Collections
- Library Collections
- Archival Collections
- Natural Heritage
- Built Heritage
- Monuments and Memorials
- Archaeological Sites/Objects
- Other Heritage Assets

## Installation

### Prerequisites

- AtoM 2.6+ 
- PHP 7.4+
- MySQL 5.7+ with JSON support
- arSpectrumPlugin (optional, for workflow integration)

### Installation Steps

1. **Extract Plugin**
   ```bash
   tar -xvf ahgGrapPlugin.tar -C /path/to/atom/plugins/
   ```

2. **Enable Plugin**
   
   Edit `apps/qubit/config/settings.yml`:
   ```yaml
   all:
     .settings:
       enabled_modules:
         - default
         - ahgGrapPlugin
   ```

3. **Add Routes**
   
   Append contents of `ahgGrapPlugin/config/routing.yml` to `apps/qubit/config/routing.yml`

4. **Install Database Schema**
   
   Navigate to `/grap/install` and click "Install Now"
   
   Or via CLI:
   ```bash
   php symfony arGrap:install
   ```

5. **Clear Cache**
   ```bash
   php symfony cc
   ```

## Database Schema

### grap_heritage_asset

Main heritage asset accounting table:

| Column | Type | Description |
|--------|------|-------------|
| object_id | INT | Foreign key to information_object |
| recognition_status | VARCHAR(50) | Current GRAP status |
| asset_class | VARCHAR(50) | GRAP 103.74 classification |
| measurement_basis | VARCHAR(50) | cost/fair_value/nominal |
| initial_recognition_date | DATE | Date first recognised |
| initial_cost | DECIMAL(18,2) | Cost at recognition |
| carrying_amount | DECIMAL(18,2) | Current carrying value |
| accumulated_impairment | DECIMAL(18,2) | Total impairment losses |
| revaluation_surplus | DECIMAL(18,2) | Revaluation reserve |
| valuation_history | JSON | Historical valuations |
| impairment_history | JSON | Historical impairments |
| derecognition_date | DATE | Date de-recognised |
| disposal_proceeds | DECIMAL(18,2) | Proceeds from disposal |

### grap_transaction_log

Complete audit trail of all GRAP transactions.

### grap_financial_year_snapshot

Year-end snapshots for trend reporting.

### grap_compliance_assessment

Saved compliance assessment results.

## Usage

### Accessing GRAP Features

- **Dashboard**: `/grap/dashboard`
- **Asset Edit**: Click "GRAP 103" in object sidebar
- **Compliance Check**: `/grap/{slug}/compliance`
- **Export Reports**: `/grap/export`

### Initial Recognition Workflow

1. Navigate to object's GRAP page
2. Select Asset Class (e.g., "Archival Collections")
3. Select Measurement Basis (Cost/Fair Value/Nominal)
4. Enter Initial Cost/Value
5. Click "Recognise Heritage Asset"

### Recording Revaluation

1. Navigate to recognised asset's GRAP page
2. Enter new Fair Value
3. Provide valuer details and date
4. Click "Record Revaluation"

System automatically calculates revaluation surplus/deficit.

### Impairment Process

1. Navigate to asset's GRAP page
2. Select Impairment Indicator
3. Enter Impairment Amount
4. Click "Record Impairment"

Asset status changes to "Impaired" and carrying amount is reduced.

### De-recognition Workflow

1. **Initiate**: Select reason, enter expected proceeds
2. **Obtain Approvals**: NARSSA disposal authority if required
3. **Complete**: Enter actual proceeds, disposal date
4. System calculates gain/loss on disposal

## API Endpoints

### Asset API

```
GET  /api/grap/assets              - List all GRAP assets
GET  /api/grap/assets/:object_id   - Get specific asset
POST /api/grap/assets              - Create GRAP record
PUT  /api/grap/assets/:object_id   - Update GRAP record
```

### Statistics API

```
GET /api/grap/statistics?type=summary         - Overall summary
GET /api/grap/statistics?type=by_class        - Breakdown by class
GET /api/grap/statistics?type=by_status       - Breakdown by status
GET /api/grap/statistics?type=compliance      - Compliance metrics
```

## Financial Year

The plugin uses the South African government financial year:
- **Year End**: 31 March
- **Example**: 2024/2025 FY ends 31 March 2025

## Integration with arSpectrumPlugin

When arSpectrumPlugin is installed, GRAP automatically:
- Links to relevant Spectrum procedure statuses
- Uses condition assessments for impairment indicators
- Connects deaccession workflow to de-recognition
- References acquisition procedures for initial recognition

## Compliance Standards

The plugin addresses requirements from:

- **GRAP 103** - Heritage Assets (ASB South Africa)
- **GRAP 21** - Impairment of Non-cash-generating Assets
- **GRAP 26** - Impairment of Cash-generating Assets
- **PFMA** - Public Finance Management Act 1 of 1999
- **Archives Act** - National Archives and Record Service Act 43 of 1996
- **PAIA** - Promotion of Access to Information Act 2 of 2000
- **NARSSA Guidelines** - National Archives preservation standards

## File Structure

```
ahgGrapPlugin/
├── lib/
│   ├── arGrapHeritageAssetService.class.php    # Core accounting service
│   ├── arGrapComplianceService.class.php       # Compliance checking
│   ├── arGrapExportService.class.php           # Report generation
│   └── arGrapInstallService.class.php          # Database setup
├── modules/
│   ├── grap/
│   │   ├── actions/
│   │   │   ├── dashboardAction.class.php
│   │   │   ├── editAction.class.php
│   │   │   ├── complianceAction.class.php
│   │   │   ├── exportAction.class.php
│   │   │   └── installAction.class.php
│   │   └── templates/
│   │       ├── dashboardSuccess.php
│   │       ├── editSuccess.php
│   │       ├── complianceSuccess.php
│   │       ├── exportSuccess.php
│   │       └── installSuccess.php
│   └── api/
│       └── actions/
│           ├── assetApiAction.class.php
│           └── statisticsApiAction.class.php
├── config/
│   └── routing.yml
└── README.md
```

## Author

**Johan Pieterse**  
IT Manager (Archives, Library and Records Management)  
The Archives and Heritage Group (The AHG)  
Email: johan.pieterse@sita.co.za

## License

This plugin is released under the GNU Affero General Public License v3.0 (AGPL-3.0), consistent with AtoM's licensing.

## Version History

- **v1.0.0** (December 2024) - Initial release
  - GRAP 103 heritage asset accounting
  - NARSSA/PFMA compliance checking
  - NT export templates
  - Spectrum procedure linkage
  - Multi-year trend analysis
  - Board pack exports

## Support

For support, feature requests, or bug reports, please contact the author or submit issues through your organisation's support channels.
