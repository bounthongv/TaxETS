# Benchmark & Provision Effectiveness by Date

> Created: 2026-07-28
> Status: Revised — Phase 1 is now VAT (Customs/Excise already date-based)

---

## Problem

Current system uses `start_year`/`end_year` exclusively for all benchmark rates and provisions. Some tax types need date-level granularity (e.g., a VAT rate change on July 1).

## Discovery: Customs/Excise Already Date-Based

- ASYCUDA import provides `doc_date` + `receipt_date` per row
- Benchmark values (`exemp_customs`, `exemp_excise`) come from expert's Excel, NOT from DB tables
- `bm_customs_*` tables are reference data only, not queried during TE calculation
- **No changes needed for Customs/Excise** — already supports date granularity

## Plan (Revised)

### Phase 1: VAT + Salary Tax (Date-based) — NEXT

| Item | Detail |
|------|--------|
| **Import data** | Already has `filing_period` (date) |
| **Benchmark tables** | `bm_vat_*`, `bm_salary_*` — add `valid_from`/`valid_to` DATE columns |
| **Engine** | VAT TE engine, Salary Tax TE engine — use date-based lookup |
| **Config UI** | Update benchmark rate config pages to show date fields |
| **Backward compat** | Keep `start_year`/`end_year` — if `valid_from` is NULL, fall back to year |

### Phase 2: Resource + Royalty (Optional)

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

## Technical Design (Phase 1 — VAT)

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
$stmt = $pdo->prepare("SELECT rate FROM bm_vat WHERE start_year <= ? AND end_year >= ?");
$stmt->execute([$year, $year]);

// After (with backward compat)
$stmt = $pdo->prepare("SELECT rate FROM bm_vat WHERE 
    (valid_from IS NOT NULL AND ? BETWEEN valid_from AND valid_to) OR
    (valid_from IS NULL AND start_year <= YEAR(?) AND end_year >= YEAR(?))");
$stmt->execute([$filing_date, $filing_date]);
```
