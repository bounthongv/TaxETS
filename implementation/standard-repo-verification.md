# Standard Repository Pattern: Verification Report

**Generated:** 2026-05-29
**Purpose:** Audit all "Get Tax Data by Import from Excel" implementations against the standard pattern defined in `standard-repo-pattern.md`

---

## Summary

| Status | Count | Description |
|--------|-------|-------------|
| ✅ Compliant | 5 | Fully follows standard pattern |
| ⚠️ Partial | 5 | Follows most of the pattern, minor deviations |
| ❌ Non-Compliant | 4 | Significant deviations from standard pattern |

---

## Standard Pattern Requirements Checklist

| Requirement | Reference |
|-------------|-----------|
| Batch ID: `BATCH_[TYPE]_[YYYYMMDDHHMMSS]` for Excel imports | §3.1 |
| Batch ID: `MANUAL_ENTRY_[TYPE]_[YEAR]` for manual batches | §3.1 |
| Log persistence to `data/logs/[batch_id].log` | §2.2 |
| Log download icon in Recent Batches table | §2.2 UI |
| "Add Manual Entry" button with year selector modal | §3.2 |
| Manual entry redirects to view with `auto_add=1&year=[YEAR]` | §3.2 |
| Manual batch rows highlighted with `table-info` class + "MANUAL" badge | §3.2 Batch Display |
| Filter Bar: Search input + Province/Sector/Year dropdowns + Reset | §4.2 |
| DataTable enabled with `dom: 'rtip'` for sorting | §4.3 |
| Warning highlighting (`table-warning`) for rows with NULL IDs | §4.3 Visual Flags |
| Precision Edit: Global JS object + modal with chained selects | §4.3 + §4.4 |
| Context-aware "Add Record to Batch" button | §4.2 |

---

## Detailed Analysis by Page

### ✅ FULLY COMPLIANT

#### 1. `import_cit.php` / `view_companies.php` — Corporate Income Tax

| Check | Status | Details |
|-------|--------|---------|
| Batch ID prefix | ✅ | `BATCH_` (line 31) |
| Smart Mapping | ✅ | Province, District, Sector with aliases + fuzzy |
| Log persistence | ✅ | Saved to `data/logs/[batch_id].log` |
| Log icon in history | ✅ | Download button shown when log exists |
| Manual Entry modal | ✅ | Year selector, redirects with `auto_add=1` |
| Manual batch format | ✅ | `MANUAL_ENTRY_[YEAR]` → `view_companies.php` |
| Manual batch highlight | ✅ | `table-info` class + "MANUAL" badge |
| Filter Bar | ✅ | Search, Province, Sector, Year dropdowns, Reset |
| DataTable | ✅ | `dom: 'rtip'` |
| Warning flags | ✅ | `table-warning` for unmapped pro_id/dis_id/sector_id |
| Precision Edit | ✅ | `companyData` JS object + `editCompany()` + modal-xl |
| Chained selects | ✅ | Province→District filtering in edit modal |
| Add to batch | ✅ | "Add Record to Batch" button |

**Verdict: Reference implementation — fully compliant.**

---

#### 2. `import_vat.php` / `view_vat.php` — Domestic VAT

| Check | Status | Details |
|-------|--------|---------|
| Batch ID prefix | ✅ | `BATCH_` (line 56) |
| Smart Mapping | ✅ | Province with aliases + fuzzy (line 17-39) |
| Log persistence | ✅ | Saved to `data/logs/[batch_id].log` |
| Log icon in history | ✅ | Download button shown when log exists |
| Manual Entry modal | ✅ | Year selector, redirects with `auto_add=1` |
| Manual batch format | ✅ | `MANUAL_ENTRY_VAT_[YEAR]` |
| Manual batch highlight | ✅ | `table-info` class + "MANUAL" badge |
| Template download | ✅ | Link to `generate_vat_template.php` |

**Verdict: Compliant. Minor: no District mapping (not needed for VAT data).**

---

#### 3. `import_individual.php` / `view_individual.php` — Individual Tax (PIT)

| Check | Status | Details |
|-------|--------|---------|
| Batch ID prefix | ✅ | `BATCH_` (line 32) |
| Smart Mapping | N/A | No province/district in PIT data |
| Log persistence | ✅ | Saved to `data/logs/[batch_id].log` |
| Validation logging | ✅ | Social Security field validated (line 62-63) |
| Manual Entry modal | ✅ | Year selector, redirects with `auto_add=1` |
| Manual batch format | ✅ | `MANUAL_ENTRY_PIT_[YEAR]` |
| Manual batch highlight | ✅ | `table-info` class + "MANUAL" badge |
| Template download | ✅ | Link to `generate_individual_template.php` |

**Verdict: Compliant.**

---

#### 4. `import_salary.php` / `view_salary.php` — Salary Tax

| Check | Status | Details |
|-------|--------|---------|
| Batch ID prefix | ✅ | `BATCH_` (line 32) |
| Smart Mapping | N/A | No province/district in salary data |
| Log persistence | ✅ | Saved to `data/logs/[batch_id].log` |
| Manual Entry modal | ✅ | Year selector, redirects with `auto_add=1` |
| Manual batch format | ✅ | `MANUAL_ENTRY_SALARY_[YEAR]` |
| Manual batch highlight | ✅ | `table-info` class + "MANUAL" badge |
| Template download | ✅ | Link to `generate_salary_template.php` |

**Verdict: Compliant.**

---

#### 5. `import_sez_dev.php` — SEZ Developer

| Check | Status | Details |
|-------|--------|---------|
| Batch ID prefix | ✅ | `BATCH_` (line 31) |
| Smart Mapping | ✅ | Province + District with aliases + fuzzy |
| Log persistence | ✅ | Saved to `data/logs/[batch_id].log` |
| Manual Entry modal | ✅ | Year selector, redirects with `auto_add=1` |
| Manual batch format | ✅ | `MANUAL_ENTRY_SEZDEV_[YEAR]` |
| Manual batch highlight | ✅ | `table-info` class + "MANUAL" badge |
| Template download | ✅ | Link to SEZ Developers template |

**Verdict: Compliant.**

---

### ⚠️ PARTIALLY COMPLIANT

#### 6. `import_royalty.php` / `view_royalty.php` — Non-Tax: Royalty Fee

| Check | Status | Details | Issue |
|-------|--------|---------|-------|
| Batch ID prefix | ❌ | `ROYALTY-` (line 27) | Should be `BATCH_ROYALTY_` |
| Smart Mapping | ❌ | None implemented | Province/district not applicable but no validation |
| Log persistence | ⚠️ | Code exists but `error_log[]` never populated | Log will never be created |
| Log icon in history | ✅ | Uses `$log_check` array | Works correctly |
| Manual Entry modal | ✅ | Year selector present | |
| Manual batch format | ❌ | `ROYALTY_MANUAL_[YEAR]` | Should be `MANUAL_ENTRY_ROYALTY_[YEAR]` |
| Manual batch highlight | ✅ | `table-info` class + "MANUAL" badge | |
| Filter Bar | ⚠️ | Only Search + Year | Missing Province/Sector dropdowns |
| DataTable | ✅ | `dom: 'rtip'` | |
| Warning flags | ❌ | None | No visual indicators for data issues |
| Precision Edit | ✅ | `royData` JS object + `editRecord()` | |
| Add to batch | ✅ | "Add Record to Batch" button | |

**Verdict: Partially compliant. Key fixes needed: batch ID prefix, manual batch format, error log population.**

---

#### 7. `import_resource.php` / `view_resource.php` — Non-Tax: Resource Fee

| Check | Status | Details | Issue |
|-------|--------|---------|-------|
| Batch ID prefix | ❌ | `RESOURCE-` (line 27) | Should be `BATCH_RESOURCE_` |
| Smart Mapping | ❌ | None implemented | No validation for resource type |
| Log persistence | ⚠️ | Code exists but `error_log[]` never populated | Log will never be created |
| Manual Entry modal | ✅ | Year selector present | |
| Manual batch format | ❌ | `RESOURCE_MANUAL_[YEAR]` | Should be `MANUAL_ENTRY_RESOURCE_[YEAR]` |
| Manual batch highlight | ✅ | `table-info` class + "MANUAL" badge | |

**Verdict: Partially compliant. Same issues as Royalty Fee.**

---

#### 8. `import_sez_inv.php` / `view_sez_inv.php` — SEZ Investor

| Check | Status | Details | Issue |
|-------|--------|---------|-------|
| Batch ID prefix | ❌ | `SEZ-INV-` (line 27) | Should be `BATCH_SEZINV_` |
| Smart Mapping | ✅ | Province + District with aliases + fuzzy | |
| Log persistence | ✅ | `error_log[]` populated on mapping failures | |
| Manual Entry modal | ✅ | Year selector present | |
| Manual batch format | ❌ | `SEZ_INV_MANUAL_[YEAR]` | Should be `MANUAL_ENTRY_SEZINV_[YEAR]` |
| Manual batch highlight | ✅ | `table-info` class + "MANUAL" badge | |

**Verdict: Partially compliant. Fix batch ID prefix and manual batch format.**

---

#### 9. `import_land_concession.php` — Land Concession

| Check | Status | Details | Issue |
|-------|--------|---------|-------|
| Batch ID prefix | ⚠️ | `BATCH_LAND_` (line 22) | Close but should be `BATCH_LANDCONC_` or similar |
| Smart Mapping | ⚠️ | Province + District implemented | Uses wrong table names (`province` vs `provinces`) |
| Log persistence | ✅ | `error_log[]` populated on mapping failures | |
| Manual Entry modal | ✅ | Year selector present | |
| Manual batch format | ✅ | `MANUAL_ENTRY_LAND_[YEAR]` | |
| Manual batch highlight | ✅ | `table-info` class + "MANUAL" badge | |
| DB table naming | ❌ | Uses `province` and `district` | Standard tables are `provinces` and `districts` |

**Verdict: Partially compliant. Fix DB table names.**

---

### ❌ NON-COMPLIANT

#### 10. `import_asycuda.php` / `view_asycuda.php` — Data from ASYCUDA

| Check | Status | Details | Issue |
|-------|--------|---------|-------|
| Batch ID prefix | ❌ | `ASY_` (line 20) | Should be `BATCH_ASYCUDA_` |
| Smart Mapping | ❌ | None | Province stored as raw text, no ID resolution |
| Log persistence | ❌ | No error logging | Mapping failures silently ignored |
| Log icon in history | ❌ | No log check | No way to see import issues |
| Manual Entry modal | ❌ | Not implemented | Cannot add records manually |
| Manual batch highlight | ❌ | N/A | No manual entry support |
| Filter Bar | ❌ | Not implemented | No search or filter capability |
| DataTable | ❌ | Not implemented | Table is basic HTML only |
| Warning flags | ❌ | Not implemented | No visual indicators |
| Precision Edit | ❌ | Not implemented | View is read-only |
| Add to batch | ❌ | Not implemented | No way to add records |
| Delete from view | ❌ | Not implemented | Only delete from import page |
| auto_add handling | ❌ | Not implemented | |

**Verdict: Non-compliant. Requires full standardization.**

---

## Additional Issues Found

### 1. DB Column Naming Inconsistency

| Table | Column Used | Standard |
|-------|-------------|----------|
| `companies` | `import_batch_id` | ✅ Standard |
| `import_vat_data` | `batch_id` | ⚠️ Should be `import_batch_id` |
| `import_pit_data` | `batch_id` | ⚠️ Should be `import_batch_id` |
| `import_salary_tax_data` | `batch_id` | ⚠️ Should be `import_batch_id` |
| `import_royalty_data` | `batch_id` | ⚠️ Should be `import_batch_id` |
| `import_resource_data` | `batch_id` | ⚠️ Should be `import_batch_id` |
| `import_sez_data` | `batch_id` | ⚠️ Should be `import_batch_id` |

**Recommendation:** Standardize on `import_batch_id` across all tables. This is a breaking change requiring migration script.

### 2. Province/District Table Names

| File | Table Name Used | Standard |
|------|-----------------|----------|
| `import_cit.php` | `provinces`, `districts` | ✅ |
| `import_vat.php` | `provinces`, `districts` | ✅ |
| `import_sez_inv.php` | `provinces`, `districts` | ✅ |
| `import_sez_dev.php` | `provinces`, `districts` | ✅ |
| `import_land_concession.php` | `province`, `district` | ❌ |

---

## Priority Fixes

### P1 — Critical (ASYCUDA Standardization)

1. **`import_asycuda.php`**:
   - Change batch ID to `BATCH_ASYCUDA_` + timestamp
   - Add Smart Mapping for Province (if applicable)
   - Add log persistence for mapping failures
   - Add Manual Entry modal with year selector

2. **`view_asycuda.php`**:
   - Add Filter Bar (Search by TIN/Importer, Province filter, Date range)
   - Enable DataTable with `dom: 'rtip'`
   - Add Edit/Delete functionality with modal editor
   - Add "Add Record to Batch" button
   - Add warning highlighting for unmapped data
   - Add auto_add handling

### P2 — Batch ID Consistency

3. **`import_royalty.php`**: Change `ROYALTY-` to `BATCH_ROYALTY_`
4. **`import_resource.php`**: Change `RESOURCE-` to `BATCH_RESOURCE_`
5. **`import_sez_inv.php`**: Change `SEZ-INV-` to `BATCH_SEZINV_`
6. **`import_land_concession.php`**: Fix table names (`provinces`/`districts`)

### P3 — Minor Fixes

7. **`import_royalty.php`**: Populate `error_log[]` array during import
8. **`import_resource.php`**: Populate `error_log[]` array during import
9. **Standardize manual batch format**: All should use `MANUAL_ENTRY_[TYPE]_[YEAR]`

### P4 — Future Enhancement

10. **DB migration**: Standardize `batch_id` → `import_batch_id` column names

---

## Files Reference

| Import Page | View Page | Standard Pattern |
|-------------|-----------|------------------|
| `import_cit.php` | `view_companies.php` | ✅ Reference |
| `import_vat.php` | `view_vat.php` | ✅ |
| `import_individual.php` | `view_individual.php` | ✅ |
| `import_salary.php` | `view_salary.php` | ✅ |
| `import_sez_dev.php` | `view_sez_dev.php` | ✅ |
| `import_sez_inv.php` | `view_sez_inv.php` | ⚠️ |
| `import_royalty.php` | `view_royalty.php` | ⚠️ |
| `import_resource.php` | `view_resource.php` | ⚠️ |
| `import_land_concession.php` | `repo_land_concession.php` | ⚠️ |
| `import_asycuda.php` | `view_asycuda.php` | ❌ |
