# Benchmark & Provision Effectiveness by Date

> Created: 2026-07-28
> Status: ✅ Complete — Salary Tax date-based. 4 tables already date-based.

---

## Problem

Current system uses `start_year`/`end_year` exclusively for all benchmark rates and provisions. Some tax types need date-level granularity (e.g., a VAT rate change on July 1).

## Discovery: Customs/Excise Already Date-Based

- ASYCUDA import provides `doc_date` + `receipt_date` per row
- Benchmark values (`exemp_customs`, `exemp_excise`) come from expert's Excel, NOT from DB tables
- `bm_customs_*` tables are reference data only, not queried during TE calculation
- **No changes needed for Customs/Excise** — already supports date granularity

## Discovery: Most Tables Already Date-Based

After scanning all 21 benchmark tables:

| Table | Currently Uses | Status |
|-------|---------------|--------|
| `bm_vat` | `start_date`/`end_date` | ✅ Already date-based |
| `bm_msme_definition` | `effective_date_from`/`effective_date_to` | ✅ Already date-based |
| `bm_payment_condition_codes` | `effective_date_from`/`effective_date_to` | ✅ Already date-based |
| `bm_customs_regime_codes` | `effective_date_from`/`effective_date_to` | ✅ Already date-based |
| **`bm_salary_rates`** | `start_year`/`end_year` | ❌ **Only one needing conversion** |

All other tables (CIT, PIT, SEZ, Land, Resource, Royalty, Excise, etc.) either:
- Should stay year-based per regulation (CIT, PIT, SEZ, Land), OR
- Benchmark comes from Excel import (Customs/Excise), OR  
- Are Phase 2 lower priority (Resource, Royalty)

## Plan (Revised)

### Phase 1: Salary Tax (Date-based) — DONE ✅

| Item | Detail |
|------|--------|
| **Import data** | Already has `filing_period` (date) |
| **Benchmark tables** | `bm_vat_*`, `bm_salary_*` — add `valid_from`/`valid_to` DATE columns |
| **Engine** | VAT TE engine, Salary Tax TE engine — use date-based lookup |
| **Config UI** | Update benchmark rate config pages to show date fields |
| **Backward compat** | Keep `start_year`/`end_year` — if `valid_from` is NULL, fall back to year |

### Phase 2: PIT done ✅ — Resource + Royalty (Optional)

Lower priority since yearly rates rarely change mid-year.

### Phase 3: Leave Year-based

| Tax Type | Reason |
|----------|--------|
| **CIT** | Annual filing, annual regulation. Year-based is correct. |
| **PIT** | Annual individual filing. |
| **SEZ** | Based on years since license. Inherently annual. |
| **Land Concession** | Fixed annual rate. Very stable. |
| **Customs/Excise** | Already date-based (per-transaction from Excel). No change needed. |

---

## Technical Design (Phase 1 — Salary Tax)

### Benchmark Table Migration

```sql
-- Add new columns (nullable for backward compat)
ALTER TABLE bm_vat
ADD COLUMN valid_from DATE NULL AFTER end_year,
ADD COLUMN valid_to   DATE NULL AFTER valid_from;

-- Engine: priority to date-based, fall back to year
-- If valid_from IS NOT NULL: WHERE ? BETWEEN valid_from AND valid_to
-- If valid_from IS NULL:     WHERE start_year <= YEAR(?) AND end_year >= YEAR(?)
```

### Engine Change

```php
// Before
# After (date-only — year rates use Jan 1 / Dec 31)
$stmt = $pdo->prepare("SELECT rate_percentage FROM bm_salary_rates
    WHERE provision_number = ? AND ? BETWEEN start_date AND end_date
    ORDER BY id DESC LIMIT 1");
$stmt->execute([$provision_number, $ref_date]);
```
