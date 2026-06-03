# Tax-ETS: Hybrid Implementation Summary

**Date:** 2026-06-03
**Context:** Discussion on the practical architecture of the Tax Expenditure Estimation System for Lao PDR.

---

## 1. Core Concept (Still Accurate)

Tax-ETS is a PHP/MySQL web application for calculating Tax Expenditures (TE) — the revenue lost through tax incentives, exemptions, and preferential rates — for Lao PDR. It processes company financial data against configurable benchmark rates and legal provisions across multiple tax types.

## 2. The Architecture Reality: Hybrid System

The original vision was an end-to-end **rules engine** that derives TE from first principles (`Benchmark Tax − Actual Tax Paid`). What has actually been built is a **hybrid** that combines calculation and data-management approaches depending on data availability.

The system functions primarily as a **Tax Expenditure Data Management System** — it ingests TE data from multiple sources (expert Excel files, ASYCUDA system, calculated benchmark comparisons), stores it in a structured warehouse, classifies it by legal provision, and generates consolidated reports across tax types. The "calculation engine" label is fully accurate for some modules but not all.

This compromise is pragmatic: getting perfect benchmark data and provision-mapping logic for every tax type would require data that does not exist yet. The proven approach is: **where benchmark data exists, calculate; where it does not, import the expert's already-determined TE and use the system for aggregation, audit trail, and reporting.**

---

## 3. Module Classification

### A. Where TE IS calculated from benchmark (rules engine works)

| Tax Type | Engine Class | How |
|----------|--------------|-----|
| **CIT / Profit Tax** | `TEEngine` | Full 3-path benchmark logic: Mandatory (Revenue x Profit Base Rate x Standard Rate), SME (Revenue x SME Tier Rate), Standard ((Net Profit + Re-invested Profit) x Standard Rate) |
| **Domestic VAT** | `TEVatEngine` | Benchmark output VAT = total sales x rate, minus input VAT. Result compared to actual VAT payable. |
| **PIT / Individual Tax** | `TEPitEngine` | Progressive tax brackets on employment income + flat rates on other income (rental, dividends, interest, share transfers). Full provision classification. |
| **SEZ Developer/Investor** | `TESEZEngine` | VAT not paid on infrastructure/utility at 10% standard benchmark. Different provision codes for Developers (VAT-D1, VAT-D2) vs Investors (VAT-I1, VAT-I2). |
| **ASYCUDA (Customs/Excise/VAT)** | `TEAsycudaEngine` | TE = Exempt Amount minus Paid Amount, derived from customs declaration data fields. |

### B. Where TE is essentially pre-determined (imported from expert's Excel)

| Tax Type | Engine Class | How |
|----------|--------------|-----|
| **Salary Tax** | `TESalaryTaxEngine` | Simplified: TE = tax_exempt_amount x 10% flat rate. The real provision mapping and exemption logic is determined by the domain expert before data entry. |
| **Royalty Fee** | `TERoyaltyEngine` | Hardcoded 10% benchmark rate on electricity sale value. No proper benchmark table lookup. Seat filler until proper rate data is available. |
| **Resource Fee** | `TEResourceEngine` | Attempts benchmark lookup from `bm_natural_resource`; falls back to 5% default. Actual rates and resource classifications need expert validation. |
| **Land Concession** | `TELandConcessionEngine` | Looks up benchmark rates by article/item from `bm_land_concession`, but exemption value uses a simplified placeholder (50% of land_value). The real TE was determined by the domain expert in the Excel source. |

---

## 4. Practical Implications

### 4.1 Data Flow

```
Expert Excel Files -> Import Pages -> Raw Data Tables -> Engine -> Result Tables -> Reports
                                                              (calc or passthrough)
ASYCUDA System   -> Import Pages -> Raw Data Tables ---------->
TaxRIS/MOIC/MPI  -> Repository Pages -> Reference Data --------> (context for calculation)
```

### 4.2 What the System Does Well

- **CIT calculation** with full 3-path logic (Mandatory, SME, Standard)
- **VAT benchmark estimation** (domestic)
- **PIT progressive tax** with bracket lookup
- **Consolidated reporting** across all tax types
- **Provision classification** for CIT and PIT
- **ASYCUDA integration** for customs duty/excise/import VAT
- **Import diagnostics** with log persistence and Smart Mapping
- **Manual data entry** for cases where Excel import is not available
- **Data Dictionary** with provinces, districts, sectors, zones for cross-module consistency
- **User and role management** with session tracking and audit logs

### 4.3 Areas Where the Engine is a Placeholder

- **Salary Tax:** Currently a flat 10% assumption on exempt amounts. Needs reformulation based on actual PIT progressive brackets applied to salary income.
- **Royalty Fee:** Hardcoded 10% rate for electricity. Needs proper benchmark table sourced from Ministry of Finance.
- **Resource Fee:** 5% default fallback risks incorrect TE values. Needs complete rate table by resource type.
- **Land Concession:** Exemption value formula (50% placeholder) is acknowledged as demonstrative, not authoritative.

### 4.4 Standardization Progress

Per the `standard-repo-verification.md` audit, the UI/UX standardization across modules is:

| Status | Count | Modules |
|--------|-------|---------|
| Fully Compliant | 5 | CIT, VAT, PIT, Salary Tax, SEZ Developer |
| Partially Compliant | 5 | Royalty, Resource, SEZ Investor, Land Concession, customs data |
| Non-Compliant | 1 | ASYCUDA import/view (needs full rewrite) |

Batch ID naming and manual batch format inconsistencies also remain in several modules.

---

## 5. Key Design Decisions (Updated)

| Decision | Original Plan | Current Reality |
|----------|--------------|-----------------|
| TE Calculation | Pure rules engine for all taxes | Hybrid: calculate where possible, import where necessary |
| Provision Matching | Dynamic condition evaluation for all | CIT and PIT use dynamic matching; others use pre-classified provision numbers from source data |
| Benchmark Rates | Fully configurable via DB | Implemented for CIT/VAT/PIT; other modules use defaults or hardcoded values |
| UI Pattern | Consistent across all modules | Largely achieved with 5 fully compliant modules; 5 partial; 1 non-compliant |
| Data Sources | Single Excel import pattern | Expanded to include ASYCUDA, stakeholder APIs, manual entry, consolidated import |
| Notifications | Planned automated EWS | `notification_helper.php` exists; triggers not fully wired to import/calculation endpoints |

---

## 6. What This Means for Future Work

### If better data becomes available:

- Replace placeholder rates in `TERoyaltyEngine` and `TEResourceEngine` with proper benchmark table lookups
- Rebuild salary tax calculation using actual PIT progressive brackets
- Strengthen land concession TE formulas with expert-validated logic

### If better data does NOT become available (the likely path):

- The system still works perfectly as a **data aggregation and reporting platform**
- Engines for CIT, VAT, and PIT provide genuine value through transparent, reproducible calculation
- Other modules fulfill the critical role of **structured storage, provision classification, and consolidated reporting** even without full calculation logic
- The investment should focus on report quality, data validation, and user workflows rather than forcing calculation where the inputs don't exist

### General priorities (from the `standard-repo-verification.md` audit):

- **P1:** Standardize ASYCUDA module (full rewrite of import and view pages)
- **P2:** Fix batch ID prefixes and manual batch naming across Royalty, Resource, SEZ Investor
- **P3:** Populate error logs during import for Royalty and Resource
- **P4:** Database migration to standardize `batch_id` to `import_batch_id` column names

---

*Document based on codebase analysis and discussion with project lead, 2026-06-03.*
