# Tax-ETS Technical Documentation

## 1. System Overview

Tax-ETS is a PHP and MySQL/MariaDB web application used to import tax and non-tax data, manage imported batches, run tax expenditure (TE) calculations, and generate TE reports.

The system follows this operational flow:

1. Import data from Excel or repository/API sources.
2. Store imported records under a batch ID.
3. Review or edit imported batch data.
4. Run TE calculation for the selected batch.
5. Store or update TE results.
6. Generate reports by tax type, sector, location, provision, customs regime, GDP, and revenue.

The application is implemented without a PHP framework. Each page in `pages/` acts as both controller and view.

## 2. Technology Stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.x |
| Database | MySQL / MariaDB |
| Spreadsheet import/export | PhpSpreadsheet |
| Frontend | Bootstrap 5, Font Awesome, DataTables, Chart.js |
| Local runtime | XAMPP |

Key configuration is stored in `config.php`.

Important constants:

| Constant | Purpose |
| --- | --- |
| `BASE_URL` | Application base path, normally `/Tax-ETS` |
| `APP_NAME` | Browser title and application name |
| `EVALUATION_DATE` | Evaluation date used by TE logic where applicable |

## 3. Project Structure

| Path | Purpose |
| --- | --- |
| `config.php` | Application configuration |
| `includes/` | Shared PHP includes, database connection, auth, engines, report helpers |
| `pages/` | Application pages and page-level controllers |
| `db/` | Database schema and seed files |
| `docs/` | Documentation, templates, and source reference files |
| `assets/` | CSS, JavaScript, images |
| `tests/` | Plain PHP test scripts |
| `data/logs/` | Import diagnostic logs by batch ID |
| `backups/` | Backup files created by system backup features |

## 4. Core Includes

| File | Purpose |
| --- | --- |
| `includes/db.php` | Provides `getDbConnection()` and PDO access |
| `includes/auth.php` | Session and authentication checks |
| `includes/header.php` | Page header, CSS/JS includes, top bar, sidebar include |
| `includes/sidebar_FIXED.php` | Main navigation menu |
| `includes/footer.php` | Common footer and scripts |
| `includes/report_filters.php` | Shared import-date report filter logic |
| `includes/batch_nav.php` | Back-to-batch-hub helper for view pages |

All database access should use `getDbConnection()`.

## 5. Database Access Pattern

The project uses PDO. New code should use prepared statements for all user-controlled values.

Example:

```php
$stmt = $pdo->prepare("SELECT * FROM import_vat_data WHERE batch_id = ?");
$stmt->execute([$batch]);
$rows = $stmt->fetchAll();
```

Avoid direct interpolation of user input into SQL.

## 6. Batch Model

Imported TE estimation data is managed by batch. A batch ID links records imported together and is used for:

- viewing imported records,
- editing records,
- deleting imported records,
- running TE calculation,
- filtering reports by import/input date,
- downloading import diagnostic logs.

Batch IDs are stored in different column names depending on the source table:

| Data Type | Main Table | Batch Column |
| --- | --- | --- |
| Profit Tax / CIT | `companies` | `import_batch_id` |
| Individual Tax / PIT | `import_pit_data` | `batch_id` |
| Salary Tax | `import_salary_tax_data` | `batch_id` |
| Domestic VAT | `import_vat_data` | `batch_id` |
| SEZ Developer / Investor | `import_sez_data` | `batch_id` |
| Resource Fee | `import_resource_data` | `batch_id` |
| Royalty Fee | `import_royalty_data` | `batch_id` |
| Land Concession | `repo_land_concession_data` | `import_batch_id` |
| ASYCUDA | `asycuda_imports` | `import_batch_id` |

Most batch IDs contain a timestamp in `YYYYMMDDHHMMSS` format. Where an import date column is not available, report filters infer batch date from this timestamp.

Manual-entry batches follow the same pattern. When a user clicks `Add Manual Entry` from an import page, the system creates a new batch ID with the selected tax year and current timestamp, for example:

```text
MANUAL_ENTRY_VAT_2026_20260604153022
```

All records added while the user remains on that opened view page are saved into the same batch. Starting manual entry again later creates a separate batch.

## 7. Batch Management Hub

The central batch hub is implemented in:

```text
pages/batches.php
```

Navigation path:

```text
Get Tax Data by Import from Excel
  > Data Requirement to estimate TE
    > Batch Management
```

The hub aggregates imported batches across TE estimation data sources and provides:

- tax type filter,
- batch ID search,
- row count,
- year range,
- import date,
- recorded TE amount,
- view action,
- TE calculation action,
- ASYCUDA calculation dropdown for Customs, Excise, and Import VAT,
- diagnostic log download when a log exists,
- delete action through `pages/delete_batch.php`.

When a user opens a batch from the hub, the URL includes:

```text
from=batches
```

View pages use `includes/batch_nav.php` to show a `Batch Management` return button only in that context.

## 8. Deleting Batches

Batch deletion is centralized in:

```text
pages/delete_batch.php
```

The caller posts:

| Field | Purpose |
| --- | --- |
| `type` | Batch type, such as `cit`, `vat`, `salary`, `asy` |
| `batch_id` | Batch identifier |

The delete handler removes records from the relevant import table. For ASYCUDA, it deletes related TE results before deleting imported ASYCUDA rows.

Important: deletion currently redirects to the related import page, not back to the batch hub.

## 9. Import Workflow

Each tax type has an import page. The import page normally:

1. Shows a template download link.
2. Accepts an Excel upload.
3. Reads data using PhpSpreadsheet.
4. Creates a new batch ID.
5. Inserts rows into the relevant import table.
6. Writes a diagnostic log to `data/logs/{batch_id}.log` when needed.
7. Shows recent imported batches.

Each import page also supports manual record entry. Manual entry does not reuse a yearly batch. It creates a timestamped batch for that manual-entry session, using the pattern:

```text
MANUAL_ENTRY_{TYPE}_{YEAR}_{YYYYMMDDHHMMSS}
```

Examples include `MANUAL_ENTRY_CIT_2026_20260604153022`, `MANUAL_ENTRY_SALARY_2026_20260604153022`, and `MANUAL_ENTRY_ASYCUDA_2026_20260604153022`.

Main import pages:

| Data Type | Import Page | View Page | TE Calculation Page |
| --- | --- | --- | --- |
| Profit Tax | `import_cit.php` | `view_companies.php` | `calculator.php` |
| Individual Tax | `import_individual.php` | `view_individual.php` | `te_individual.php` |
| Salary Tax | `import_salary.php` | `view_salary.php` | `te_salary_tax.php` |
| Domestic VAT | `import_vat.php` | `view_vat.php` | `te_vat.php` |
| ASYCUDA | `import_asycuda.php` | `view_asycuda.php` | `te_asycuda_customs.php`, `te_asycuda_excise.php`, `te_asycuda_vat.php` |
| SEZ Developer | `import_sez_dev.php` | `view_sez_dev.php` | `te_sez_dev.php` |
| SEZ Investor | `import_sez_inv.php` | `view_sez_inv.php` | `te_sez_inv.php` |
| Land Concession | `import_land_concession.php` | `repo_land_concession.php` | `calculate_land_concession.php` |
| Resource Fee | `import_resource.php` | `view_resource.php` | `te_resource.php` |
| Royalty Fee | `import_royalty.php` | `view_royalty.php` | `te_royalty.php` |

Land Concession import uses a header-aware workbook mapping. The generated template columns are:

```text
TIN, CompanyName, District, Province, TaxItem, Year, Receiptdate,
Concessionarea, BenchmarkRate, ContractedRate, ConcessionFeePaid, ProvisionName
```

`TaxItem` is accepted but not stored in a separate database column yet. If `Year` is blank, the import page's selected tax year is used. Location matching uses the current `provinces` and `districts` dictionary tables.

## 10. TE Calculation Engines

TE calculation logic is implemented in engine classes under `includes/`.

| Engine | File | Main Method |
| --- | --- | --- |
| Profit Tax | `te_profit_tax_engine.php` | `calculateBatch(string $batch_id): array` |
| Individual Tax | `te_pit_engine.php` | `calculateBatch(string $batch_id): array` |
| Salary Tax | `te_salary_tax_engine.php` | `calculateBatch(string $batch_id): array` |
| Domestic VAT | `te_vat_engine.php` | `calculateBatch(string $batch_id): array` |
| ASYCUDA | `te_asycuda_engine.php` | `calculateBatch(string $batch_id): array` |
| SEZ | `te_sez_engine.php` | `calculateBatch(string $batch_id, string $type): array` |
| Land Concession | `te_land_concession_engine.php` | `calculateBatch(string $batch_id): array` |
| Resource Fee | `te_resource_engine.php` | `calculateBatch(string $batch_id): array` |
| Royalty Fee | `te_royalty_engine.php` | `calculateBatch(string $batch_id): array` |

Calculation pages call the relevant engine and display results for a selected batch.

Benchmark rates and provisions should be read from database tables rather than hardcoded in engine logic.

For Land Concession, `benchmark_value_usd` is calculated as `concession_area_ha * benchmark_rate_usd`. Because the legal/policy basis for an exemption is not yet confirmed for blank provisions, `non_tax_te_usd` is set to `0` when `ProvisionName` is blank. When `ProvisionName` is present, the current calculation uses `max(0, benchmark_value_usd - concession_fee_paid_usd)`.

## 11. Expert TE

Some templates include Expert TE values. Expert TE is used for verification and discrepancy analysis.

Current behavior:

- Expert TE is hidden from ordinary view pages.
- Expert TE is shown only in TE calculation pages where verification is needed.
- Expert TE visibility is restricted to the admin user where implemented.

The current admin verification email is:

```text
admin@example.com
```

## 12. Reports

Main report pages are under `pages/report_*.php`.

Primary report categories:

| Report | Page |
| --- | --- |
| TE by Tax Type | `report_tax_type.php` |
| TE by Sector | `report_sector.php` |
| TE by Location | `report_location.php` |
| TE by Tax Type (% of GDP) | `report_gdp.php` |
| TE by Tax Type (% of Revenue) | `report_revenue.php` |
| Total TE by Provision | `report_total_provision.php` |
| Profit Tax TE by Provision | `report_provisions.php` |
| Individual Income Tax TE by Provision | `report_individual_provision.php` |
| Salary Tax TE by Provision | `report_salary_tax_provision.php` |
| Domestic VAT TE by Provision | `report_vat_provision.php` |
| SEZ Developer TE by Provision | `report_sez_dev_provision.php` |
| SEZ Investor TE by Provision | `report_sez_inv_provision.php` |
| Non-Tax TE by Provision | `report_nontax_provision.php` |
| Total TE by Customs Regime | `report_total_customs.php` |
| Customs Duty Report | `report_customs_duty.php` |
| Excise Tax Report | `report_excise_tax.php` |
| Import VAT Report | `report_import_vat.php` |

## 13. Report Filters

Reports support year filters and import-date filters depending on the page.

Shared import-date filter functions are in:

```text
includes/report_filters.php
```

Important functions:

| Function | Purpose |
| --- | --- |
| `reportFilterInput()` | Reads and validates `import_from` and `import_to` from `$_GET` |
| `reportAppendFilters()` | Preserves filters in export links |
| `reportBatchDateExpression()` | Builds SQL date expression from import date or batch timestamp |
| `reportImportDateCondition()` | Adds SQL conditions for import date range |
| `reportImportDateFilterControl()` | Renders Import From / Import To controls |
| `reportTaxTypeData()` | Shared TE aggregation source for tax type, GDP, and revenue reports |

GDP and revenue values are static denominator/reference values from `repo_gdp_revenue`. Import-date filters apply to the TE numerator only.

## 14. Collation Notes

Some tables use different UTF-8 collations. Text comparisons across tables must use explicit collation where needed.

Known sensitive comparisons:

- `import_pit_data.ptin` to `te_individual_result.tin`
- PIT or salary TIN to `companies.tin`
- provision number comparisons in `FIND_IN_SET`

Use explicit collation for cross-table text comparisons, for example:

```sql
ipd.ptin COLLATE utf8mb4_unicode_ci = r.tin COLLATE utf8mb4_unicode_ci
```

## 15. Navigation

The main menu is in:

```text
includes/sidebar_FIXED.php
```

Major sections:

- Dashboard
- System
- Data Dictionary
- Benchmark
- Repository
- Get Tax Data by Import from Excel
- Get Tax Data by API
- TE Calculation
- TE Reports
- Notification

The sidebar uses `$cur = basename($_SERVER["PHP_SELF"])` to set active states.

## 16. Authentication and User Context

Authentication is handled by:

```text
includes/auth.php
```

Pages include `includes/header.php`, which loads auth checks and displays the current user name in the top bar.

Developer note: call `session_start()` only when no session is active:

```php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

## 17. Logs

Import diagnostic logs are written to:

```text
data/logs/{batch_id}.log
```

Users can download logs through:

```text
pages/download_log.php
```

The batch hub also shows a log-download action when a matching log file exists.

## 18. Backup and Restore

Backup and restore features are available under:

```text
System > Backup/Restore Data
```

Implementation page:

```text
pages/system_backup.php
```

Backup files are stored under:

```text
backups/
```

## 19. Development and Validation

Install dependencies:

```bash
composer install
```

Run the local PHP server if not using XAMPP:

```bash
php -S localhost:8000
```

Check PHP syntax:

```bash
php -l pages/example.php
```

Run the available engine test:

```bash
php tests/test_engine.php
```

There is no PHPUnit configuration at this stage. Tests are plain PHP scripts.

## 20. Deployment Notes

For the current XAMPP deployment, the working application is under:

```text
D:\xampp\htdocs\tax-ets
```

The development workspace is:

```text
D:\Tax-ETS
```

When changing source files in the development workspace, copy the changed files into the XAMPP folder for browser testing.

Typical copy examples:

```powershell
Copy-Item -LiteralPath pages\batches.php -Destination D:\xampp\htdocs\tax-ets\pages\batches.php -Force
Copy-Item -LiteralPath includes\sidebar_FIXED.php -Destination D:\xampp\htdocs\tax-ets\includes\sidebar_FIXED.php -Force
```

## 21. Coding Standards

Follow the existing project style:

- PHP files use `<?php`.
- Use `require_once` with `__DIR__`.
- Use PDO prepared statements for user-controlled input.
- Escape output with `htmlspecialchars()`.
- Keep page structure consistent with Bootstrap 5.
- Keep shared logic in `includes/` when multiple pages need it.
- Do not hardcode benchmark rates in calculation pages or engines.

## 22. Known Technical Considerations

- Some batch dates are inferred from batch ID timestamps.
- Report import-date filters depend on available import date columns or parseable batch IDs.
- GDP and revenue comparison reports use static denominator values.
- ASYCUDA import is one import batch but supports three TE views: Customs Duty, Excise Tax, and Import VAT.
- Some legacy pages and table names use older naming conventions.
- Collation differences exist between some text columns, so cross-table text joins should be checked carefully.

## 23. Recommended Future Improvements

- Add a formal migration system for schema changes.
- Add automated integration tests for report pages.
- Add a batch metadata table to avoid parsing dates from batch IDs.
- Add redirect-back support to `delete_batch.php` for the batch hub.
- Normalize database collations across all tables.
- Convert repeated page patterns into reusable helper functions or components where practical.
