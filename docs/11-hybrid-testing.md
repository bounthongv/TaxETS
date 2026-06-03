# Hybrid Testing Plan: Re-import, Compare, and Document

**Date:** 2026-06-03
**Status:** Plan — not yet started
**Goal:** Validate the entire import-to-calculation pipeline using real expert Excel files, compare system-calculated TE against expert-provided TE, and fix any application bugs discovered.

---

## Phase 0: Preparation & Baseline

### 0.1 Inventory Expert Excel Files
- [ ] Locate all initial Excel files from the earlier implementation
- [ ] For each file, identify which tax type it belongs to (CIT, VAT, PIT, Salary, Royalty, Resource, Land Concession, SEZ, Customs)
- [ ] For each file, note whether it contains a pre-calculated TE column (expert TE) — most do
- [ ] Document the source and date of each file for reference
- [ ] Make a backup copy of all Excel files before any import

### 0.2 Snapshot Current Database State
- [ ] Dump the current database (structure + data) as a restore point
  ```sql
  mysqldump -u root -p tax_ets > backups/pre_hybrid_test_YYYYMMDD.sql
  ```
- [ ] Record row counts for all key tables:
  - `import_batches`, `import_logs`
  - `te_profit_tax_result`, `te_vat_result`, `te_pit_result`
  - `te_salary_tax_result`, `te_royalty_result`, `te_resource_result`
  - `te_land_concession_result`, `te_sez_result`, `te_customs_result`
  - All `raw_*` tables
  - All `bm_*` benchmark tables
  - All provision tables
- [ ] Store this snapshot in `docs/` for future comparison

### 0.3 Code Review Before Changes
- [ ] Read through every import page (`import_*.php`) — note any known issues
- [ ] Read through every engine class — note placeholder logic and hardcoded values
- [ ] Read through every result table schema — check if an `expert_te` column already exists
- [ ] Review the `standard-repo-verification.md` for the full list of known non-compliances

---

## Phase 1: Schema Change — Add Expert TE Column

### 1.1 Database Migration
- [ ] For each result table that needs it, add an `expert_te` column (nullable DECIMAL, default NULL):

  | Table | Column | Type |
  |-------|--------|------|
  | `te_profit_tax_result` | `expert_te` | `DECIMAL(20,2) DEFAULT NULL` |
  | `te_vat_result` | `expert_te` | `DECIMAL(20,2) DEFAULT NULL` |
  | `te_pit_result` | `expert_te` | `DECIMAL(20,2) DEFAULT NULL` |
  | `te_salary_tax_result` | `expert_te` | `DECIMAL(20,2) DEFAULT NULL` |
  | `te_royalty_result` | `expert_te` | `DECIMAL(20,2) DEFAULT NULL` |
  | `te_resource_result` | `expert_te` | `DECIMAL(20,2) DEFAULT NULL` |
  | `te_land_concession_result` | `expert_te` | `DECIMAL(20,2) DEFAULT NULL` |
  | `te_sez_result` | `expert_te` | `DECIMAL(20,2) DEFAULT NULL` |
  | `te_customs_result` | `expert_te` | `DECIMAL(20,2) DEFAULT NULL` |

- [ ] Create a single migration SQL file in `db/migrations/` for traceability
  - Path: `db/migrations/001_add_expert_te_column.sql`
- [ ] Run the migration and verify column addition

### 1.2 Import Logic Update
- [ ] In each import page, detect if the Excel file contains an expert TE column:
  - Accept a configurable column name or header string (e.g. "Expert TE", "TE_calculated", "TE_expert")
  - Log whether the column was found (for diagnostic transparency)
- [ ] When found, import the expert TE value into the `raw_*` table alongside other data
- [ ] During the engine/pipeline step that populates the result table, carry the expert TE forward into `te_*_result.expert_te`

### 1.3 View/Report Update (Admin-Only Display)
- [ ] In every result/report view page (`report_*.php` or `te_*.php`), add an **Expert TE** column in the results table:
  - Show it **only** when `$_SESSION['user_role'] === 'admin'` or a similar dev-mode flag is set
  - Hide it from all other user roles
- [ ] For admin users, show the expert TE value **side-by-side** with the calculated TE
  - Columns: `Company | Period | Benchmark Tax | Actual Tax | System TE | Expert TE | Delta (Diff) | Notes`
- [ ] Apply a visual highlight when `|System TE - Expert TE| > threshold` (e.g. > 1,000 LAK or > 0.1% difference)

### 1.4 Admin-Only Dashboard or Page
- [ ] (Optional) Create a simple comparison dashboard accessible only to admin role
  - Shows per-tax-type aggregate: sum of system TE vs sum of expert TE
  - Highlight modules with high variance
  - Link through to detailed per-company breakdown

---

## Phase 2: Purge Old Data

### 2.1 Delete Existing Imported Data
- [ ] Clear all result tables (TE values will be regenerated)
- [ ] Clear all raw/import data tables
- [ ] Clear import_batches and import_logs
- [ ] Keep reference data intact: benchmark tables (`bm_*`), provisions, data dictionary (provinces, sectors, districts, zones), user accounts

### 2.2 Order of Deletion (cascade-safe)
1. All `te_*_result` tables
2. All `raw_*` tables
3. `import_logs`
4. `import_batches`
5. Verify no orphaned data remains

### 2.3 Scripted Cleanup
- [ ] Write a single PHP or SQL script (`db/cleanup_for_reimport.sql`) that can be run to reset
  - Include safety checks (confirm before delete)
  - Log what was deleted and how many rows

---

## Phase 3: Re-import Everything

### 3.1 Import Order
Import in dependency order — some engines depend on benchmark rates being loaded first:

1. **Reference / benchmark data** (if not already populated)
   - Provinces, districts, sectors, zones
   - All `bm_*` benchmark tables
   - Profit provisions and conditions
2. **CIT / Profit Tax** — import Excel, run calculation
3. **Domestic VAT** — import Excel, run calculation
4. **PIT** — import Excel, run calculation
5. **Salary Tax** — import Excel, run calculation
6. **SEZ (Developer + Investor)** — import Excel, run calculation
7. **Royalty Fee** — import Excel, run calculation
8. **Resource Fee** — import Excel, run calculation
9. **Land Concession** — import Excel, run calculation
10. **ASYCUDA (Customs)** — import Excel, run calculation

### 3.2 Per-Import Checklist
For each import file:
- [ ] Confirm the file format matches what the import page expects
- [ ] Document the column mapping: which Excel column maps to which DB field
- [ ] Record whether an expert TE column was found and its header name
- [ ] Check import logs for errors, warnings, skipped rows
- [ ] Verify row counts: Excel rows vs imported rows
- [ ] Verify smart mapping worked (display names resolved to DB IDs)

### 3.3 Per-Calculation Checklist
For each calculation/engine run:
- [ ] Verify the engine ran without PHP errors
- [ ] Check result row count matches imported row count (no silent drops)
- [ ] Compare system TE vs expert TE for sample rows
- [ ] Flag any rows where TE differs significantly (will be analyzed in Phase 5)

---

## Phase 4: Fix Bugs Found During Testing

### 4.1 Import Bugs
- [ ] Column mapping mismatches (wrong column read, wrong type)
- [ ] Smart mapping failures (display name not resolved to ID)
- [ ] Encoding issues (UTF-8 / Thai / Lao characters)
- [ ] Excel date serial number parsing failures
- [ ] Duplicate detection not working
- [ ] Batch ID naming inconsistencies

### 4.2 Calculation Bugs
- [ ] Wrong benchmark rate used (check lookup logic)
- [ ] Wrong tax base used (check what value feeds into calculation)
- [ ] Missing provision conditions causing incorrect classification
- [ ] Division by zero or null handling
- [ ] Wrong rounding or precision
- [ ] Placeholder formulas producing unrealistic values (e.g. Land Concession 50% placeholder)

### 4.3 UI/UX Bugs
- [ ] Report pages not displaying all columns
- [ ] Pagination broken for large result sets
- [ ] Filters not working
- [ ] Export functionality missing or broken

### 4.4 Fix Process
For each bug:
1. [ ] Isolate and reproduce with a minimal case
2. [ ] Fix the code
3. [ ] Re-run the relevant import/calculation
4. [ ] Verify fix resolved the issue without breaking other modules
5. [ ] Document in the discrepancy log (Phase 5)

---

## Phase 5: Compare & Document Discrepancies

### 5.1 Discrepancy Logging
For **every** tax module, compare System TE vs Expert TE:

| Module | Rows Compared | Matches | Minor Diff (>0 but <5%) | Major Diff (>=5%) | Notes |
|--------|--------------|---------|------------------------|-------------------|-------|
| CIT | — | — | — | — | — |
| VAT | — | — | — | — | — |
| PIT | — | — | — | — | — |
| Salary Tax | — | — | — | — | — |
| SEZ | — | — | — | — | — |
| Royalty | — | — | — | — | — |
| Resource | — | — | — | — | — |
| Land Concession | — | — | — | — | — |
| Customs | — | — | — | — | — |

### 5.2 Root Cause Analysis for Each Discrepancy
For each case where System TE differs significantly from Expert TE, determine:

- **Category A: Known limitation** — engine uses a placeholder formula (e.g. Land Concession 50%, Salary Tax 10% flat). Expert TE is authoritative.
- **Category B: Different benchmark rate** — system looked up one rate, expert assumed another. Needs resolution with domain expert.
- **Category C: Different tax base** — system computed base differently from expert's method. Source of truth needs to be established.
- **Category D: Calculation error** — genuine bug found and fixed (Phase 4).
- **Category E: Data quality** — the input data itself has issues (missing values, wrong units, typos in Excel).
- **Category F: Rounding / precision** — differences due to decimal rounding, negligible in aggregate.

### 5.3 Documentation File
Create `docs/12-hybrid-testing-results.md` structured as:

```markdown
# Hybrid Testing Results

## Test Run Metadata
- Date of re-import:
- Excel files used:
- PHP version:
- MySQL version:

## Per-Module Report
### 1. CIT / Profit Tax
- Import: [description of what was imported, how many rows]
- Calculation: [engine behavior, 3-path distribution]
- Discrepancies: [table of comparisons]
- Root causes: [analysis per Category A-F]
- Bugs found & fixed: [list]

## Summary
- Total rows compared:
- Exact matches:
- Minor differences:
- Major differences:
- Known limitations affecting results:
- Overall assessment:
```

### 5.4 Aggregate Dashboard
- [ ] Create a summary section showing total TE by tax type, both system and expert
- [ ] Show a bar or comparison table: `Tax Type | System TE Total | Expert TE Total | Variance %`
- [ ] This helps identify systematic bias (always over/under) vs random noise

---

## Phase 6: Interpret & Decide Path Forward

### 6.1 Go/No-Go Decisions
For each module with significant discrepancies, decide:

- **Use System TE** — engine is correct, expert TE was manual estimate (occurs when system uses actual company data while expert used sector averages)
- **Use Expert TE** — engine is placeholder, expert is authoritative (Salary Tax, Royalty, Resource, Land Concession currently)
- **Hybrid Display** — show both in reports with a flag indicating which is more reliable

### 6.2 Update System Documentation
- [ ] Update the relevant engine comments/PHPDoc to reflect how TE is derived
- [ ] For placeholder modules, add clear inline documentation: "TODO: Replace with proper benchmark lookup when data becomes available"
- [ ] Update `docs/10-summary-hybrid-implementation.md` with any new findings
- [ ] Update `implementation/te-rule.md` if CIT rules need refinement

### 6.3 Lock Down User Visibility
- [ ] Confirm expert TE column is hidden from non-admin users
- [ ] Test with a non-admin account to verify
- [ ] Consider adding a session-based toggle (`$_SESSION['show_expert_te']`) instead of role check for finer control

---

## Appendix: Supporting Files

| File | Purpose |
|------|---------|
| `db/migrations/001_add_expert_te_column.sql` | Schema migration |
| `db/cleanup_for_reimport.sql` | Data cleanup script |
| `docs/11-hybrid-testing.md` | This plan |
| `docs/12-hybrid-testing-results.md` | Results after testing |
| `docs/10-summary-hybrid-implementation.md` | Architecture overview (updated) |
| `backups/pre_hybrid_test_YYYYMMDD.sql` | Database restore point |

---

## Execution Checklist Summary

```
Phase 0: Preparation        [  ]  — inventory, snapshot, review
Phase 1: Expert TE column   [  ]  — schema, import, display, toggle
Phase 2: Purge old data     [  ]  — clear results + raw tables
Phase 3: Re-import          [  ]  — all modules, order-matters
Phase 4: Fix bugs           [  ]  — import, calc, UI bugs found
Phase 5: Compare & document [  ]  — discrepancy log + root causes
Phase 6: Interpret & decide [  ]  — go/no-go, update docs, lock down
```
