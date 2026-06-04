# Tax-ETS Database Blueprint

## 1. Purpose

This document describes the database structure used by Tax-ETS at a system-design level. It is not a full schema dump. Its purpose is to help developers and administrators understand where data is stored, how batches connect tables, and which tables feed TE calculations and reports.

For exact column definitions, use the SQL files in `db/` and the live database schema.

## 2. Database Design Overview

The database is organized around five main groups:

| Group | Purpose |
| --- | --- |
| Import / input tables | Store imported records by batch |
| TE result tables | Store calculated TE outputs |
| Benchmark tables | Store standard rates and benchmark rules |
| Provision tables | Store legal provisions and classification logic |
| Dictionary / repository tables | Store reference data used by imports, calculations, and reports |

The central operational key is the batch ID. Most imported records are grouped by either `batch_id` or `import_batch_id`.

## 3. Batch ID Columns

Batch ID naming is not fully standardized. This is important for queries, reports, deletion, and batch management.

| Module | Main Table | Batch Column |
| --- | --- | --- |
| Profit Tax / CIT | `companies` | `import_batch_id` |
| Individual Tax / PIT | `import_pit_data` | `batch_id` |
| Salary Tax | `import_salary_tax_data` | `batch_id` |
| Domestic VAT | `import_vat_data` | `batch_id` |
| ASYCUDA | `asycuda_imports` | `import_batch_id` |
| SEZ Developer / Investor | `import_sez_data` | `batch_id` |
| Land Concession | `repo_land_concession_data` | `import_batch_id` |
| Resource Fee | `import_resource_data` | `batch_id` |
| Royalty Fee | `import_royalty_data` | `batch_id` |
| GDP / Revenue | `repo_gdp_revenue` | `import_batch_id` |

Manual-entry batches use timestamped IDs so each manual-entry session is separated from earlier/later manual work. The current pattern is:

```text
MANUAL_ENTRY_{TYPE}_{YEAR}_{YYYYMMDDHHMMSS}
```

Examples:

```text
MANUAL_ENTRY_CIT_2026_20260604153022
MANUAL_ENTRY_VAT_2026_20260604153022
MANUAL_ENTRY_RESOURCE_2026_20260604153022
```

Multiple records added from the opened view page stay in the same batch. A new click on `Add Manual Entry` creates a new timestamped batch.

Recommendation: future schema cleanup should introduce a central `batches` metadata table and gradually standardize batch references.

## 4. Import / Input Tables

These tables store raw or semi-processed input data used for TE estimation.

| Table | Module | Notes |
| --- | --- | --- |
| `companies` | Profit Tax / CIT and some land legacy paths | Main enterprise tax input table |
| `import_pit_data` | Individual Tax / PIT | Source for PIT engine |
| `import_salary_tax_data` | Salary Tax | Source and calculation output table for salary TE |
| `import_vat_data` | Domestic VAT | Source and calculation output table for VAT TE |
| `asycuda_imports` | Customs Duty, Excise Tax, Import VAT | Imported customs declaration records |
| `import_sez_data` | SEZ Developer / Investor | Uses `type` column to separate Developer and Investor |
| `repo_land_concession_data` | Land Concession | Land concession input and calculated non-tax TE |
| `import_resource_data` | Resource Fee | Resource fee input and calculated TE |
| `import_royalty_data` | Royalty Fee | Royalty fee input and calculated TE |

Some import tables also store calculated values directly, such as `te_amount`, `system_te`, `benchmark_fee`, or `calculated_at`.

## 5. TE Result Tables

Some engines write calculation output to dedicated result tables. Other modules update TE fields directly in the import table.

| Result Table | Source Table | Relationship | Notes |
| --- | --- | --- | --- |
| `te_profit_result` | `companies` | `te_profit_result.company_id = companies.id` | CIT calculation output |
| `te_individual_result` | `import_pit_data` | Matched by `tin/ptin` and `tax_year` | PIT calculation output |
| `te_asycuda_result` | `asycuda_imports` | `te_asycuda_result.asycuda_id = asycuda_imports.id` | Customs, Excise, Import VAT output |
| `te_land_concession_result` | `companies` | `te_land_concession_result.company_id = companies.id` | Legacy/alternate land concession output |

Modules that primarily store results in import tables:

| Module | Table | Output Columns |
| --- | --- | --- |
| Salary Tax | `import_salary_tax_data` | `benchmark_tax`, `te_amount`, `provision_number`, `calculated_at` |
| Domestic VAT | `import_vat_data` | `system_te`, `benchmark_output_vat`, `calculated_vat_payable`, `provision_number` |
| SEZ | `import_sez_data` | `benchmark_tax`, `te_amount`, `provision_number`, `calculated_at` |
| Resource Fee | `import_resource_data` | `benchmark_rate`, `benchmark_fee`, `te_amount`, `calculated_at` |
| Royalty Fee | `import_royalty_data` | `benchmark_rate`, `benchmark_fee`, `te_amount`, `calculated_at` |
| Land Concession | `repo_land_concession_data` | `non_tax_te_usd` and related benchmark/payment fields |

## 6. Main Relationships

### Profit Tax / CIT

```text
companies.id
  -> te_profit_result.company_id
```

Provision matching:

```text
profit_provisions.id
  -> profit_provision_conditions.provision_id
```

`te_profit_result.matched_provisions` stores provision numbers as text, used by provision reports.

### Individual Tax / PIT

PIT imports and results are matched by TIN and tax year:

```text
import_pit_data.ptin + import_pit_data.tax_year
  -> te_individual_result.tin + te_individual_result.tax_year
```

Because these columns may use different collations, cross-table comparisons should explicitly apply collation.

### ASYCUDA

```text
asycuda_imports.id
  -> te_asycuda_result.asycuda_id
```

One ASYCUDA import batch supports three TE outputs:

- Customs Duty TE
- Excise Tax TE
- Import VAT TE

### SEZ

```text
import_sez_data.batch_id
import_sez_data.type = 'Developer' or 'Investor'
```

Developer and Investor use the same table and are separated by the `type` column.

### Non-Tax

Resource Fee and Royalty Fee are stored in separate import tables.

Land Concession currently uses `repo_land_concession_data` for imported land concession data. Some older calculation/report paths may also use `companies` and `te_land_concession_result`.

The active Land Concession import path maps locations through the current dictionary tables:

```text
provinces.province_code / provinces.province_name
districts.district_code / districts.district_name
```

The generated Land Concession import template is header-based and uses:

```text
TIN, CompanyName, District, Province, TaxItem, Year, Receiptdate,
Concessionarea, BenchmarkRate, ContractedRate, ConcessionFeePaid, ProvisionName
```

`Benchmark Value` and `Non-Tax TE` are not import columns in the current template. They are calculated by the Land Concession TE page.

## 7. Benchmark Tables

Benchmark tables store rates and rules used by calculation engines.

| Table | Module |
| --- | --- |
| `bm_profit_standard` | Profit Tax standard rates |
| `bm_profit_mandatory` | Profit Tax mandatory sector rates |
| `bm_profit_sme` | Profit Tax SME rates |
| `bm_pit_employment` | PIT employment progressive rates |
| `bm_pit_flat_rates` | PIT flat rates by income type |
| `bm_salary_rates` | Salary tax benchmark rates |
| `bm_vat` | VAT benchmark rates |
| `bm_customs_regime_codes` | Customs regime code references |
| `bm_payment_condition_codes` | Customs payment condition references |
| `bm_customs_sections` | Customs section hierarchy |
| `bm_customs_chapters` | Customs chapter hierarchy |
| `bm_excise` | Excise benchmark rates |
| `bm_msme_definition` | MSME thresholds |
| `bm_land_concession` | Land concession benchmark rates |
| `bm_natural_resource` | Resource fee benchmark rates |

Benchmark data should be maintained through the relevant Benchmark pages when possible.

## 8. Provision Tables

Provision tables describe legal basis, TE type, purpose, and classification rules.

| Table | Module |
| --- | --- |
| `profit_provisions` | CIT provisions |
| `profit_provision_conditions` | CIT condition logic |
| `individual_provisions` | PIT provisions |
| `salary_provisions` | Salary tax provisions |
| `vat_provisions` | Domestic VAT provisions |
| `excise_provisions` | Excise provisions |
| `config_sez_provisions` or related SEZ provision tables | SEZ provisions |
| `provision_land_concession` or land benchmark/provision tables | Land concession provisions |

Some modules store matched provision numbers directly in import/result rows. Provision reports aggregate TE by these provision numbers.

## 9. Dictionary and Reference Tables

Dictionary tables normalize commonly used names and codes.

| Table | Purpose |
| --- | --- |
| `provinces` | Province list used by newer pages |
| `districts` | District list used by newer pages |
| `province` | Legacy province table used by some older pages |
| `district` | Legacy district table used by some older pages |
| `village` | Village reference data |
| `business_sectors` | Business sector dictionary |
| `enterprise_type` or related tables | Enterprise type references |
| `repo_gdp_revenue` | GDP and revenue denominator/reference values |

Important caveat: some pages use `provinces/districts`, while older pages may still use `province/district`. The active Land Concession import/view path now uses `provinces/districts`. Remaining legacy references should be consolidated in a future schema cleanup.

## 10. Repository Tables

Repository tables store source/reference data used for identification, cross-checking, or reporting.

| Table | Purpose |
| --- | --- |
| `repo_gdp_revenue` | GDP and revenue values |
| `repo_moic` | MOIC source data |
| `repo_taxris` | TaxRIS source data |
| `repo_mpi` | MPI source data |
| `repo_molsw` | MOLSW source data |
| `repo_lse` | Lao Stock Exchange source data |
| `repo_sezo` | SEZO source data |
| `repo_land_concession_data` | Land concession input data |

Repository imports are separate from TE estimation imports, except where a repository table directly feeds a TE module, such as Land Concession.

## 11. System and Security Tables

| Table | Purpose |
| --- | --- |
| `users` | User accounts |
| `roles` | Role definitions |
| `role_permissions` | Role-level permissions |
| `user_history` | Operation logs |
| `user_sessions` | Online/session tracking |
| `system_settings` | System configuration |
| `ip_access` | IP access management |
| `alert_recipients` | Notification recipient settings |
| `notifications` | Notification records |

These tables are managed through the System and Notification sections.

## 12. Report Source Mapping

### TE by Tax Type

| Tax Type | Source |
| --- | --- |
| Profit Tax | `companies` + `te_profit_result` |
| Individual Tax | `te_individual_result` |
| Salary Tax | `import_salary_tax_data.te_amount` |
| Domestic VAT | `import_vat_data.expert_te` or calculated VAT fields depending on report |
| Customs Duty | `asycuda_imports` + `te_asycuda_result.customs_te` |
| Excise Tax | `asycuda_imports` + `te_asycuda_result.excise_te` |
| Import VAT | `asycuda_imports` + `te_asycuda_result.vat_te` |
| SEZ Developer | `import_sez_data` where `type = 'Developer'` |
| SEZ Investor | `import_sez_data` where `type = 'Investor'` |
| Resource Fee | `import_resource_data.te_amount` |
| Royalty Fee | `import_royalty_data.te_amount` |
| Land Concession | `repo_land_concession_data.non_tax_te_usd` or legacy land result path |

### GDP / Revenue Comparison Reports

| Component | Source |
| --- | --- |
| TE numerator | Same TE sources as TE by Tax Type |
| GDP denominator | `repo_gdp_revenue.gdp_value` |
| Revenue denominator | `repo_gdp_revenue.revenue_value` |

Import-date filters apply only to TE numerator data. GDP and revenue are static reference values.

### Sector and Location Reports

Sector and location reports combine multiple TE sources:

- CIT uses `companies.sector` and `companies.province`.
- PIT and salary link back to `companies` by TIN where possible.
- VAT, ASYCUDA, and non-tax data may report directly or fall into unclassified/other depending on available location/sector fields.

## 13. Import Date Filtering

Reports use date filtering from `includes/report_filters.php`.

Import-date source priority:

1. Use explicit import date column if available.
2. Fall back to parsing a timestamp from batch ID.

Batch timestamp parsing assumes a 14-digit timestamp:

```text
YYYYMMDDHHMMSS
```

Example batch IDs:

```text
BATCH_20260603104127
VAT_BATCH_20260603104127
LAND_BATCH_20260603104127
BATCH_ASYCUDA_20260603104127
MANUAL_ENTRY_VAT_2026_20260604153022
```

## 14. Collation Caveats

The database contains columns with different UTF-8 collations, commonly:

- `utf8mb4_unicode_ci`
- `utf8mb4_general_ci`

When comparing text columns across tables, especially TIN and provision numbers, use explicit collation to avoid SQL errors.

Example:

```sql
ipd.ptin COLLATE utf8mb4_unicode_ci = r.tin COLLATE utf8mb4_unicode_ci
```

Known sensitive comparisons:

| Comparison | Use Case |
| --- | --- |
| `import_pit_data.ptin` to `te_individual_result.tin` | PIT import-date filter |
| `te_individual_result.tin` to `companies.tin` | Sector/location reports |
| `import_salary_tax_data.tin` to `companies.tin` | Sector/location reports |
| provision numbers in `FIND_IN_SET` | Provision reports |

## 15. Deletion Relationships

Batch deletion is handled by `pages/delete_batch.php`.

| Type | Tables Deleted |
| --- | --- |
| `cit` | `companies`; `te_profit_result` cascades by FK |
| `pit` | `import_pit_data` |
| `salary` | `import_salary_tax_data` |
| `vat` | `import_vat_data` |
| `sez_dev` | `import_sez_data` where `type = 'Developer'` |
| `sez_inv` | `import_sez_data` where `type = 'Investor'` |
| `resource` | `import_resource_data` |
| `royalty` | `import_royalty_data` |
| `land` | `repo_land_concession_data` |
| `asy` | `te_asycuda_result` then `asycuda_imports` |

Important caveat: PIT delete currently removes import rows only. PIT calculated results are matched by TIN/year rather than batch ID, so cleanup behavior should be reviewed before production hardening.

## 16. Blueprint Diagram

High-level data flow:

```text
Excel Template
  -> Import Page
    -> Import/Input Table
      -> Batch Management Hub
      -> View/Edit Page
      -> TE Calculation Page
        -> TE Result Table or TE columns on import table
          -> Reports
```

Key relationship sketch:

```text
companies
  -> te_profit_result
  -> te_land_concession_result

import_pit_data
  -> te_individual_result by TIN + tax_year

import_salary_tax_data
  -> TE fields on same table

import_vat_data
  -> TE fields on same table

asycuda_imports
  -> te_asycuda_result

import_sez_data
  -> TE fields on same table

repo_land_concession_data
  -> TE fields on same table

import_resource_data
  -> TE fields on same table

import_royalty_data
  -> TE fields on same table
```

## 17. Schema Files

Main schema and update files are in `db/`.

Important files include:

| File | Purpose |
| --- | --- |
| `schema.sql` | Core schema for CIT and base system tables |
| `users_schema.sql` | Users, roles, permissions, sessions |
| `vat_schema.sql` | VAT benchmark/provision/import schema |
| `salary_tax_schema.sql` | Salary tax schema |
| `schema_repo_land_concession.sql` | Land concession repository data |
| `repo_gdp_revenue_schema.sql` | GDP and revenue repository |
| `repo_*_schema.sql` | External source repositories |
| `update_*.sql` | Incremental schema updates |
| `seed_*.sql` | Seed benchmark/provision/reference data |

Because schema changes have accumulated across multiple files, always compare the live database with these files before making structural changes.

## 18. Recommended Database Improvements

1. Add a central `batches` table:

```text
batches
  id
  batch_id
  module
  source
  imported_at
  imported_by
  row_count
  status
  log_path
```

2. Standardize batch column names:

```text
batch_id
```

3. Normalize collations across all tables to one project-wide collation.

4. Add indexes on common report and batch columns:

```text
batch_id
import_batch_id
tax_year
tin
ptin
filing_period
doc_date
import_date
```

5. Add explicit foreign keys where stable relationships exist.

6. Move all TE outputs to either consistent result tables or consistently named TE columns.

7. Add migration/version tracking so schema updates are reproducible.
