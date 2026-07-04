# Tax-ETS Database Blueprint — Module Addendum

> **Note**: This addendum documents new database tables and relationships added since the original blueprint was written. It follows the same grouping conventions as the main document.

## 1. New Import Tables

### `import_vat_data`

**Purpose**: Stores Domestic VAT import records and TE results.

**Batch column**: `batch_id` (prefix `VAT_BATCH_`)

**Key flow**:
1. Import via `import_domestic_vat.php` → inserts into `import_vat_data`
2. TE Engine (`te_vat_engine.php`) → reads from `import_vat_data`, writes back `system_te`
3. Reports → aggregate `COALESCE(system_te, expert_te, 0)`

**Relationships**: No direct FK. Reports aggregate by `YEAR(filing_period)`.

### `import_resource_data`

**Purpose**: Stores Resource Fee imports and TE results.

**Batch column**: `batch_id` (`RESOURCE_BATCH_`)

**TE calculation**: `te_resource_engine.php` → benchmark rate from `bm_natural_resource`, writes `te_amount`, `benchmark_rate`, `benchmark_fee`.

### `import_royalty_data`

**Purpose**: Stores Royalty Fee imports and TE results.

**Batch column**: `batch_id` (`ROYALTY_BATCH_`)

**TE calculation**: `te_royalty_engine.php` → benchmark rate from `bm_royalty_fees`, writes `te_amount`, `benchmark_rate`, `benchmark_fee`.

### `import_sez_data`

**Purpose**: Shared table for both SEZ Developers and SEZ Investors. Filtered by `type` column.

**Batch columns**: `batch_id` (`SEZDEV_BATCH_` for Developer, `SEZINV_BATCH_` for Investor)

**Notes**: Same table, different `type` values. Provisions reference `bm_sez_provisions`.

### `repo_land_concession_data`

**Purpose**: Stores Land Concession import records with TE results.

**Batch column**: `import_batch_id` (`LAND_BATCH_`)

**TE calculation**: `te_land_concession_engine.php` → calculates `benchmark_value_usd` (area × rate) and `non_tax_te_usd` (max(0, benchmark - paid)).

---

## 2. New Benchmark Tables

```
bm_natural_resource ──── import_resource_data
     │
     │ (item_no, rate_percentage)
     │
     └── te_resource_engine calculates: benchmark_fee = (fee / actual_rate) × benchmark_rate


bm_royalty_fees ──────── import_royalty_data
     │
     │ (rate_percentage by year range)
     │
     └── te_royalty_engine calculates: benchmark_fee = sale_value × (rate / 100)


bm_land_concession ────── repo_land_concession_data
     │
     │ (article_no, item_no, rate_zone1/2/3)
     │
     └── te_land_concession_engine calculates: benchmark = area × rate


bm_sez_provisions ─────── import_sez_data
     │
     │ (type='Developer'|'Investor', provision_number)
     │
     └── te_sez_dev / te_sez_inv engines
```

---

## 3. New Result Tables

| Table | FK | TE Column | Engine |
|-------|-----|-----------|--------|
| `te_individual_result` | — | `te_amount` | `te_pit_engine.php` |
| `te_land_concession_result` | `company_id` → `companies.id` | `te_land_concession` | `te_land_concession_engine.php` |
| `te_asycuda_result` | `asycuda_id` → `asycuda_imports.id` | `customs_te`, `excise_te`, `vat_te` | `te_asycuda_engine.php` |

---

## 4. Report Data Flow

```
repo_gdp_revenue ──→ report_gdp.php (GDP values in USD billions)
                 ──→ report_revenue.php (Revenue values in kip)
```

GDP/revenue data is loaded once via seed SQL and updated manually. 
- GDP: multiplied by 1,000,000,000,000 for kip conversion
- Revenue: multiplied by 1,000 for kip conversion

---

## 5. Batch ID Summary

| Module | Table | Batch Column | Prefix |
|--------|-------|-------------|--------|
| CIT | `companies` | `import_batch_id` | `BATCH_` |
| PIT | `import_pit_data` | `batch_id` | `BATCH_` |
| Domestic VAT | `import_vat_data` | `batch_id` | `VAT_BATCH_` |
| SEZ Developer | `import_sez_data` | `batch_id` | `SEZDEV_BATCH_` |
| SEZ Investor | `import_sez_data` | `batch_id` | `SEZINV_BATCH_` |
| Land Concession | `repo_land_concession_data` | `import_batch_id` | `LAND_BATCH_` |
| Resource Fee | `import_resource_data` | `batch_id` | `RESOURCE_BATCH_` |
| Royalty Fee | `import_royalty_data` | `batch_id` | `ROYALTY_BATCH_` |
| ASYCUDA | `asycuda_imports` | `import_batch_id` | `ASYCUDA_BATCH_` |
| Salary Tax | `import_salary_tax_data` | `batch_id` | `SALARY_BATCH_` |

**Note**: Batch deletion uses `delete_batch.php` which maps each table via `type` parameter.
