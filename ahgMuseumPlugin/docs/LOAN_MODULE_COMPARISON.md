# GLAM/DAM Loan Module Comparison

## Industry Research Summary

### Spectrum 5.0 (UK Museum Standard)
**Source:** [Collections Trust](https://collectionstrust.org.uk/spectrum/procedures/loans-out-spectrum-5-0/)

Spectrum is the UK collection management standard used internationally. Key procedures:

**Loans Out (Lending Objects):**
- Request assessment and approval
- Agreement negotiation and signing
- Insurance confirmation
- Pre-loan condition checking
- Packing and dispatch
- Transit monitoring
- Receipt confirmation
- On-loan monitoring
- Return processing
- Post-return condition verification

**Loans In (Borrowing Objects):**
- Request submission
- Approval tracking
- Delivery coordination
- Receipt and unpacking
- Installation
- Monitoring
- Return preparation

### CollectiveAccess Features
**Source:** [CollectiveAccess Documentation](https://docs.collectiveaccess.org/)

- Customizable loan metadata and workflows
- Object location tracking through loan lifecycle
- Library loan module with overdue email alerts
- Order management for paid loans and rights
- Condition reporting integration
- Multiple borrower/lender tracking
- Cost and fee management

### Other GLAM Systems (Axiell, Vernon CMS, Muselera)

Common features across enterprise museum software:
- Facility reports (borrower venue assessment)
- Courier/transport management
- Insurance certificate tracking
- Loan agreement template generation
- Calendar/scheduling views
- Comprehensive reporting dashboards
- External borrower request portals
- Multi-currency support
- Government indemnity tracking
- Batch object handling

---

## AtoM AHG Framework Implementation

### Current Features (Implemented)

| Feature | Status | Notes |
|---------|--------|-------|
| Loan Out Workflow | ✅ Complete | 24 Spectrum 5.0 compliant states |
| Loan In Workflow | ✅ Complete | Full borrowing workflow |
| Partner Institution Management | ✅ Complete | Contact details, addresses |
| Object Tracking | ✅ Complete | Add/remove objects from loans |
| Insurance Management | ✅ Complete | 5 insurance types, value tracking |
| Loan Fees | ✅ Complete | Fee and currency tracking |
| Document Uploads | ✅ Complete | Agreement, condition reports, etc. |
| Loan Extensions | ✅ Complete | History tracking with reasons |
| Overdue Monitoring | ✅ Complete | Dashboard alerts |
| Due Soon Alerts | ✅ Complete | 30-day warning |
| Statistics Dashboard | ✅ Complete | Active loans, insurance value |
| Search & Filtering | ✅ Complete | By type, status, partner, dates |
| Agreement Generation | ✅ Complete | HTML/PDF export |
| Workflow Transitions | ✅ Complete | Role-based with confirmations |
| Object Search (AJAX) | ✅ Complete | Live search for adding objects |
| Exhibition Integration | ✅ Complete | Link loans to exhibitions |

### Features to Add (Based on Industry Comparison)

| Feature | Priority | Industry Standard |
|---------|----------|-------------------|
| Facility Reports | High | Spectrum 5.0 |
| Condition Report Module | High | All systems |
| Courier Management | High | All systems |
| Email Notifications | High | CollectiveAccess |
| Calendar View | Medium | Enterprise systems |
| Reports Dashboard | Medium | Enterprise systems |
| Cost Tracking Detail | Medium | CollectiveAccess |
| Packing Lists | Medium | Spectrum 5.0 |
| External Request Portal | Low | Enterprise systems |
| Batch Operations | Low | Enterprise systems |

---

## Implementation Plan

### Phase 1: Core Enhancements (Current Sprint)
1. **Facility Report Service** - Assess borrower venue suitability
2. **Condition Report Service** - Document object condition with images
3. **Courier Management Service** - Track transport providers and shipments
4. **Notification Service** - Email alerts for due dates and status changes

### Phase 2: User Experience
5. **Calendar View** - Visual loan scheduling
6. **Packing List Generator** - Export packing documentation
7. **Enhanced Reports** - Management dashboards and exports

### Phase 3: Integration
8. **External Portal** - Borrower self-service requests
9. **API Endpoints** - REST API for third-party integration
10. **Batch Operations** - Multi-object loan processing

---

## Database Schema Additions

```sql
-- Facility Reports (borrower venue assessment)
CREATE TABLE loan_facility_report (...)

-- Condition Reports with images
CREATE TABLE loan_condition_report (...)
CREATE TABLE loan_condition_image (...)

-- Courier/Transport Management
CREATE TABLE loan_courier (...)
CREATE TABLE loan_shipment (...)

-- Notifications
CREATE TABLE loan_notification (...)
CREATE TABLE loan_notification_log (...)

-- Packing
CREATE TABLE loan_packing_list (...)
CREATE TABLE loan_packing_item (...)
```

---

## South African Compliance Notes

- **POPIA**: Personal data in borrower contacts must be handled per protection requirements
- **Insurance**: Support for ZAR currency and local providers
- **NARSSA**: Loan documentation contributes to archival records
- **Heritage Assets (GRAP 103)**: Loan tracking supports asset valuation reporting

---

*Document Version: 1.0*
*Author: Johan Pieterse (johan@theahg.co.za)*
*Date: 2026-01-19*
