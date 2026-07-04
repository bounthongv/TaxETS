# Tax-ETS Technical Documentation — Module Addendum

> **Note**: This addendum covers modules added after the original documentation was written.
> Core system architecture remains as described in the main document.

## 10. Excel Template Generator Pattern

All import modules follow a standardized generator pattern in `pages/generate_*.php`. Each generator:

1. Queries the database for dropdown reference data (provinces, districts, resource types, etc.)
2. Creates a 5-sheet XLSX workbook:
   - **Main sheet** — Data entry columns with color-coded headers
   - **Instructions** — Column mapping and usage guide
   - **Validation Lists** — Dropdown source data (hidden via `SHEETSTATE_VERYHIDDEN`)
   - **Data Dictionary** — Reference data (hidden via `SHEETSTATE_VERYHIDDEN`)
   - **Change Log** — Version history
3. Applies data validations (dropdowns via `DataValidation::TYPE_LIST`, numeric via `TYPE_DECIMAL`)
4. Supports both CLI save (`PHP_SAPI === 'cli'`) and browser download
5. Sheet protection password: `TaxETS2026`

## 11. PIT (Individual Income Tax) Module

**Generator**: `pages/generate_pit_template.php` — 35 columns (A–AI)

| Group | Columns | Description |
|-------|---------|-------------|
| Identity | A–E | PTIN, National ID, Individual Name, Surname, Tax Org |
| Demographics | F–K | Gender, DOB, Nationality, Marital Status, Location |
| Filing | L–N | Filing Type, Income Year, Permit No. |
| Income | O–R | Income types (1–4) |
| Deductions | S–AA | Various deductible categories (self, spouse, children, insurance, etc.) |
| Tax | AB–AE | Tax Rate, Tax Paid, Calculated TE |
| Tracking | AF–AI | Source, Batch, Import Date |

**Import**: `pages/import_individual.php` — Uses smart header matching.
**View**: `pages/view_individual.php`
**TE Calculation**: `pages/te_individual.php` — Engine stores results in `te_individual_result`.

## 12. Domestic VAT Module

**Generator**: `pages/generate_domestic_vat_template.php` — 17 columns (A–Q)

| Col | Header | Description |
|-----|--------|-------------|
| A | Province | Dropdown from Validation Lists (18 provinces) |
| B | TIN | Taxpayer Identification Number |
| C | Company Name | Enterprise name |
| D | Filing Type | E.g., monthly, quarterly |
| E | Filing Period | Month/Year (MM/YYYY) |
| F | Input Date | Date of record entry |
| G | Description | Item description |
| H | VAT Rate % | VAT percentage rate |
| I | Domestic Sale Exempt LAK | Exempt domestic sales |
| J | User TE | User-entered TE value |
| K | Provision Number | Benchmark provision |
| L | User Benchmark Rate | User override rate |
| M | User Benchmark VAT | User override VAT |
| N | Use User Fallback? | Yes/No toggle |
| O | System Benchmark Rate % | System-calculated benchmark |
| P | User Fallback Reason | Reason for fallback |
| Q | User Comment | Free text |

**Import**: `pages/import_domestic_vat.php` (renamed from `import_vat.php` for clarity)
**View**: `pages/view_vat.php`
**TE Engine**: `includes/te_vat_engine.php` — Stores results in `import_vat_data.system_te`.
**Sidebar**: Found under *Data Requirement → Domestic VAT* (separate from *ASYCUDA → Import VAT*).

## 13. SEZ VAT Module (Developer + Investor)

**Generator**: `pages/generate_sez_vat_template.php` — 20 columns (A–T)

Combined template for both SEZ Developers and SEZ Investors, confirmed by expert.

| Col | Header | Description |
|-----|--------|-------------|
| A | Tax Year | Fiscal year |
| B | TIN | Taxpayer ID |
| C | Company Name | Enterprise name |
| D | License Date | Investment license date |
| E | Province | Dropdown (18 provinces) |
| F | District | Cascading dropdown (filters by province) |
| G | Village | Village name |
| H | SEZ Name | Special Economic Zone name |
| I | SEZ Developer | Yes/No — flags this row as Developer |
| J | SEZ Investor | Yes/No — flags this row as Investor |
| K | Sector | Industry sector |
| L | Basic Infrastructure LAK | Developer: road, electricity, water infrastructure |
| M | Other Infrastructure LAK | Developer: other infrastructure |
| N | Utility Usage LAK | Investor: electricity & water usage |
| O | Support Infrastructure LAK | Investor: infrastructure for non-export sectors |
| P–T | Fallback columns | Use User Fallback?, User Benchmark Rate, User Benchmark Fee, User TE, etc. |

**Import (Developer)**: `pages/import_sez_dev.php` — Only processes rows with Col I = Yes
**Import (Investor)**: `pages/import_sez_inv.php` — Only processes rows with Col J = Yes
**View**: `pages/view_sez_dev.php` / `pages/view_sez_inv.php`
**TE Calculation**: `pages/te_sez_dev.php` / `pages/te_sez_inv.php`
**Provisions**: `bm_sez_provisions` table (VAT-D1, VAT-D2, VAT-I1, VAT-I2)

## 14. Land Concession Module

**Generator**: `pages/generate_land_concession_template.php` — 19 columns (A–S)

| Col | Header | Description |
|-----|--------|-------------|
| A | TIN | Taxpayer ID |
| B | CompanyName | Enterprise name |
| C | Province | Dropdown — province first, then |
| D | District | Cascading dropdown (filters by province via OFFSET/MATCH) |
| E | Description | Concession type from `bm_land_concession` (Article/Item dropdown) |
| F | Year | Tax year |
| G | Receiptdate | Receipt/payment date |
| H | Concessionarea (ha) | Area in hectares |
| I | BenchmarkRate (USD) | Benchmark rate per ha |
| J | ContractedRate (USD) | Contracted rate per ha |
| K | ConcessionFeePaid | Fee actually paid |
| L | Paid Currency | Currency code |
| M | Exchange Rate | FX rate to USD |
| N–S | Fallback columns | Use User Fallback?, User Benchmark Rate, User Benchmark Value, User Non-Tax TE, etc. |

**Import**: `pages/import_land_concession.php` (uses `import_land_concession_data` smart header matching)
**View**: `pages/repo_land_concession.php`
**TE Engine**: `includes/te_land_concession_engine.php` — Calculates: TE = max(0, benchmarkValue - paid)
**Provisions**: `bm_land_concession` (Article/Item based rates by zone)

## 15. Resource Fee Module

**Generator**: `pages/generate_resource_fee_template.php` — 17 columns (A–Q)

| Col | Header | Source |
|-----|--------|--------|
| A | TIN | Required |
| B | Date_Investment_License | License date |
| C | Type_of_natural_resource | Dropdown from `bm_natural_resource` |
| D | Year | Tax year |
| E | Reciept Date | Payment receipt date |
| F | Resource_fee_rate (Benchmark) | System benchmark rate |
| G | Resource_fee_rate (Contracted) | Contracted rate |
| H | Sale quantity (Tons) | Quantity sold |
| I | Resource_fee_collected | Fee actually collected |
| J–K | Paid Currency, Exchange Rate | Currency info |
| L–Q | Fallback columns | User overrides |

**Import**: `pages/import_resource.php` — Supports old 6-col and new 17-col template
**View**: `pages/view_resource.php`
**TE Calculation**: `pages/te_resource.php` via `includes/te_resource_engine.php`
**Benchmark**: `bm_natural_resource` table (item_no, item_name, rate_percentage)

## 16. Royalty Fee Module

**Generator**: `pages/generate_royalty_fee_template.php` — 16 columns (A–P)

| Col | Header | Description |
|-----|--------|-------------|
| A | TIN | Required |
| B | Date_Investment_License | License date |
| C | Year | Tax year |
| D | Reciept Date | Payment receipt date |
| E | Royalty_fee_rate (Benchmark) | System benchmark rate |
| F | Royalty_fee_rate (Contracted) | Contracted rate |
| G | Electricity_sale_value (USD) | Value of electricity sold |
| H | Royalty_fee_collected | Fee collected |
| I–J | Paid Currency, Exchange Rate | Currency info |
| K–P | Fallback columns | User overrides |

**Import**: `pages/import_royalty.php` — Supports old 6-col and new 16-col template
**View**: `pages/view_royalty.php`
**TE Calculation**: `pages/te_royalty.php` via `includes/te_royalty_engine.php`
**Benchmark**: `bm_royalty_fees` table

## 17. TE Reports

All reports use `includes/report_filters.php` for data aggregation logic.

| Report | Page | Data Source |
|--------|------|-------------|
| TE by Tax Type | `report_tax_type.php` | Aggregated from all TE result/import tables |
| TE by Sector | `report_sector.php` | Companies sector + TE data |
| TE by Province | `report_location.php` | Province-level aggregates |
| TE by GDP | `report_gdp.php` | TE data vs GDP from `repo_gdp_revenue` |
| TE by Revenue | `report_revenue.php` | TE data vs Revenue from `repo_gdp_revenue` |
| TE by Provision | `report_total_provision.php` | Provision-level breakdown for all taxes |
| TE by Provision (CIT) | `report_provisions.php` | Profit Tax provisions only |
| TE by Provision (PIT) | `report_individual_provision.php` | PIT provisions only |
| TE by Provision (VAT) | `report_vat_provision.php` | Domestic VAT provisions only |
| TE by Provision (SEZ) | `report_sez_dev_provision.php` / `report_sez_inv_provision.php` | SEZ provisions |
| TE by Provision (Non-Tax) | `report_nontax_provision.php` | Land Concession, Resource Fee, Royalty Fee |
| TE by Customs Regime | `report_total_customs.php` | ASYCUDA data |
| Customs Duty | `report_customs_duty.php` | ASYCUDA customs |
| Excise Tax | `report_excise_tax.php` | ASYCUDA excise |
| Import VAT | `report_import_vat.php` | ASYCUDA import VAT |

## 18. ASYCUDA Module

Uses standard MOF import format (46 columns A–AT). Imports once via `import_asycuda.php`, data splits into:
- Customs Duty (`asycuda_customs.php`)
- Excise Tax (`asycuda_excise.php`)
- Import VAT (`asycuda_vat.php`)

TE results stored in `te_asycuda_result` table (columns: `customs_te`, `excise_te`, `vat_te`).

## 19. Sidebar Navigation Pattern

All modules follow a consistent sidebar pattern:
- **Import page**: menu item highlights when on import OR view page
- **View page**: button shows "Go to TE Calculation" (navigation, not calculation)
- **TE page**: "Run TE Calculation" button triggers actual engine via POST
- **Salary Tax**: code preserved, sidebar links commented out

Active state patterns:
```php
// Import menu active check
<li class="<?= $cur == 'import_xxx.php' || $cur == 'view_xxx.php' ? 'active' : '' ?>">
```
