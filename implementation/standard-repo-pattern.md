# Standard Repository Pattern: Data Requirement to Estimate TE

This document defines the standardized implementation pattern for tax-specific data repositories in the Tax-ETS system. It ensures 100% data precision through "Smart Mapping" while providing a flexible, transparent, and highly interactive workflow for data management.

---

## 1. Code-First Database Strategy

### 1.1 Dual-Column Mapping
Every repository table stores both the **Formal ID** (source of truth) and the **Display Name** (historical archive):
- **Formal IDs** (`pro_id`, `dis_id`, `sector_id`): These link directly to the Data Dictionary and are used for all logic, filtering, and calculations.
- **Display Names** (`province`, `district`, `sector`): These preserve the original text for history and reporting performance.

---

## 2. The Smart Import Pipeline

### 2.1 Layer 1: Immediate ID Resolution (Smart Mapping)
The import script MUST resolve names to IDs at the point of ingestion using normalization, aliases, and fuzzy matching.

### 2.2 Layer 2: Persistent Diagnostic Logs
Mapping failures (or any import validation errors) are saved as a `.log` file in `data/logs/[batch_id].log`.

#### Log Format
```
IMPORT DIAGNOSTIC LOG - 2026-05-29 14:30:00
Batch: BATCH_20260529143000
----------------------------------------
Row 5: Unknown Province 'Vientiane'
Row 12: Unknown District 'Chanthabuly' in Province 'Vientiane'
Row 18: Invalid Social Security value 'Y' (expected YES/NO)
```

#### Implementation (import_*.php)
```php
$error_log = [];
// ... during import, collect errors:
if (!$pro_id && !empty($raw_prov)) {
    $error_log[] = "Row $row: Unknown Province '$raw_prov'";
}
// After all rows processed:
if (!empty($error_log)) {
    $log_content = "IMPORT DIAGNOSTIC LOG - " . date("Y-m-d H:i:s") . "\r\n";
    $log_content .= "Batch: $batch_id\r\n";
    $log_content .= "----------------------------------------\r\n";
    $log_content .= implode("\r\n", $error_log);
    if (!is_dir(__DIR__ . "/../data/logs")) mkdir(__DIR__ . "/../data/logs", 0777, true);
    file_put_contents(__DIR__ . "/../data/logs/$batch_id.log", $log_content);
}
```

#### UI (Recent Batches table)
```php
$log_file = __DIR__ . "/../data/logs/" . $r["batch_id"] . ".log";
$has_log = file_exists($log_file);
// Show download icon if log exists:
<?php if($has_log): ?>
    <a href="download_log.php?log_id=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-danger" title="Download Log"><i class="fas fa-file-alt"></i></a>
<?php endif; ?>
```

#### Cleanup
`delete_batch.php` MUST delete the associated `.log` file when a batch is removed:
```php
$log_path = __DIR__ . "/../data/logs/" . $batch_id . ".log";
if (file_exists($log_path)) {
    unlink($log_path);
}
```

#### Batch Naming (BATCH_ prefix)
The import batch_id uses `"BATCH_" . date("YmdHis")` (without type suffix) for consistency across the codebase. Manual entries use `"MANUAL_ENTRY_"` prefix instead.

---

## 3. Unified Batch Management

### 3.1 Batch ID Convention
- **Excel Import**: `BATCH_[TYPE]_[YYYYMMDDHHMMSS]`
- **Manual Entry**: `MANUAL_ENTRY_[TYPE]_[YEAR]` (Virtual Manual Batch)

### 3.2 Dual Manual Entry Strategy
1. **Global Add**: "Add Manual Entry" button on the main import page opens a modal to select year, then redirects to the view page with `batch=MANUAL_ENTRY_[TYPE]_[YEAR]&auto_add=1&year=[YEAR]`.
2. **Contextual Add**: "Add Record to Batch" button inside a batch view binds strictly to the current `batch_id` (opens the same add modal without redirect).

#### Implementation (import_*.php — Manual Entry Modal)
```php
<!-- Button in header -->
<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manualEntryModal">
    <i class="fas fa-plus me-1"></i> Add Manual Entry
</button>

<!-- Modal -->
<div class="modal fade" id="manualEntryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Manual Data Entry</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Select the Tax Year for the manual records you want to manage.</p>
        <div class="mb-3">
          <label class="form-label fw-bold">Tax Year</label>
          <select id="manualTaxYear" class="form-select">
            <?php for ($y = date("Y"); $y >= 2015; $y--): ?>
            <option value="<?= $y ?>"><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="goToManualEntry()">Manage Records</button>
      </div>
    </div>
  </div>
</div>

<script>
function goToManualEntry() {
    const year = document.getElementById('manualTaxYear').value;
    window.location.href = `view_individual.php?batch=MANUAL_ENTRY_PIT_${year}&auto_add=1&year=${year}`;
}
</script>
```

#### auto_add handling (view_*.php)
```php
// In JavaScript's DOMContentLoaded:
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('auto_add') === '1') {
    const manualYear = urlParams.get('year');
    addRecord(manualYear);
}
```

#### Batch Display (view_*.php — Recent Batches table)
Manual batches are highlighted with `table-info` class and a "MANUAL" badge:
```php
$is_manual = (strpos($r["batch_id"], 'MANUAL') !== false);
// ...
<tr class="<?= $is_manual ? 'table-info' : '' ?>">
    <td>
        <small class="font-monospace"><?= htmlspecialchars($r["batch_id"]) ?></small>
        <?php if($is_manual): ?><span class="badge bg-info ms-1">MANUAL</span><?php endif; ?>
    </td>
```

---

## 4. Integrated Management UI

### 4.1 Page Header & Context
Every view page must clearly state the **Source** (Excel vs Manual) and provide context-aware action buttons.

### 4.2 Advanced Filtering & Search
Every record view MUST include an interactive **Filter Bar** above the table:
- **Custom Search**: A text input for real-time filtering of names or IDs (TIN).
- **Categorical Dropdowns**: Instant filtering by Province, Sector, and Tax Year.
- **Reset Action**: A button to clear all filters instantly.

### 4.3 Interactive Data Table (Sorting)
- **Native Sorting**: Tables MUST use DataTables (dom: 'rtip') to allow users to click headers and sort/toggle any column.
- **Visual Flags**: Rows with `NULL` IDs must be highlighted (`table-warning`) with a ⚠️ icon.
- **Precision Edit**: Use an `editCompany(id)` function that pulls data from a global `companyData` JS object to prevent JSON escaping errors.

### 4.4 Unified Modal Editor
Use a single `modal-xl` for both Add and Edit with ID-based selects and chained district filtering.

---

## 12. System-Generated Excel Templates with Data Validation

Generate import-ready Excel templates with data validation dropdowns using PhpSpreadsheet to prevent bad dictionary input.

### 12.1 Accepted Standard: Expert CIT Template
Accepted standard CIT import template:
`docs/final-template-expert/profit-tax-template-apis (Toukta).xlsx`

All future CIT import/generation code must match the expert template column order. Any template drift must be reconciled with this accepted standard before merging.

### 12.2 File Naming Convention
```
pages/generate_{tax_type}_template.php
```

### 12.3 Accepted CIT Columns
Use the expert template ordering and labels as source of truth for CIT imports and exports.

### 12.4 Data Validation Dropdowns
Use PhpSpreadsheet's DataValidation with `TYPE_LIST` for constrained fields:

```php
$validation = $sheet->getCell("AB2")->getDataValidation();
$validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
$validation->setFormula1('"YES,NO"');
$validation->setAllowBlank(true);
$validation->setShowDropDown(false);
$sheet->setDataValidation("AB2:AB101", $validation);
```

### 12.4 Number Formatting
- Amount columns: `#,##0.00` (comma-separated thousands, 2 decimals)
- Date columns: `YYYY-MM-DD`

### 12.5 Integration
- Import page must link to the template: "Download Template with Dropdowns"
- Link uses `BASE_URL` / `pages/generate_{type}_template.php`
- Template file uses `php://output` for direct download

---

## 5. Implementation Checklist for New Tax Types

### Backend & Storage
- [ ] Create repository table with ID columns and `import_batch_id`.
- [ ] Create `data/logs` folder and ensure write permissions.

### Main Import Page (import_*.php)
- [ ] Excel upload with Smart Mapping (Normalization + Aliases + Fuzzy).
- [ ] **Log Persistence**: Save failures to `data/logs/` and show 📋 icon in history table.
- [ ] "Add Manual Entry" button with `auto_add=1` redirect to virtual batch.

### Batch View Page (repo_*.php / view_*.php)
- [ ] **Filter Bar**: Search input + Dropdowns (Province, Sector, Year) + Reset button.
- [ ] **DataTable**: Enabled for sorting and custom search.
- [ ] **Context-Aware Add**: "Add Record to Batch" button.
- [ ] **Precision Edit**: Global JS object mapping + Modal with chained selects.

### Deployment
- [ ] Sync all code and assets to the XAMPP web root (`D:\xampp\htdocs\Tax-ETS`).
