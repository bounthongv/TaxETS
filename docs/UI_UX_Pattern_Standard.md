# Tax-ETS Standard UI/UX Pattern (May 2026)

This document defines the standardized workflow and interface design for all Tax Expenditure (TE) modules within the Tax-ETS system. Following this pattern ensures consistency, data safety, and a superior user experience.

---

## 1. Module Structure
Every tax type should be split into two distinct functional areas:

### A. Data Import & Management (`import_*.php`)
**Purpose:** Data ingestion and raw record management.
- **Top Actions:** Template Download button + Upload File form.
- **Sidebar:** Recent Batch History with a **Trash Icon** for full batch deletion.
- **Main View:** A preview of records from the selected/latest batch.
- **Row Actions:** Link to an **Add/Edit** page (`add_edit_*.php`) for manual corrections.
- **Safety Rule:** No calculation logic or results are shown here. This page is for *what was reported*.

### B. TE Calculation & Analysis (`te_*.php`)
**Purpose:** Estimation of tax expenditures and result tracking.
- **Sidebar:** Batch History with status badges:
    - <span style="color:gray">●</span> **Pending**: Imported but not yet calculated.
    - <span style="color:green">●</span> **Calculated**: Results are ready.
- **Header Actions:** 
    - **Run TE Calculation** (Primary button).
    - **Clear Results** (Eraser icon): Resets TE values to 0 and status to Pending.
- **Main View:** Data table showing:
    - Reported amounts (Read-only).
    - **Benchmark Tax** (Calculated).
    - **TE Amount** (Calculated).
    - **Provision Number** (Matched).
- **Safety Rule:** No record deletion allowed here. Only resetting of calculation results.

---

## 2. Technical Requirements

### Database Schema
Every import table (`import_tax_data`) must include these tracking columns:
| Column | Type | Purpose |
| :--- | :--- | :--- |
| `batch_id` | VARCHAR | Groups records from a single Excel upload. |
| `calculated_at` | DATETIME | NULL if pending; timestamp if calculated. Used for status badges. |
| `te_amount` | DECIMAL | The lost revenue figure calculated by the engine. |
| `benchmark_tax`| DECIMAL | What should have been paid under standard rules. |

### Excel Templates
- **Standardized Naming:** `[Tax Name]_Template.xlsx`.
- **Column B Rule:** Column B should always be the **Fiscal Year**.
- **Clean Header:** First row bold, auto-fitted, no sample data.

---

## 3. The User Journey
1. **Download:** User gets the clean template from the Import page.
2. **Fill:** User enters company data and specifies the Year for every row.
3. **Import:** User uploads the file. System creates a `batch_id`.
4. **Verify:** User reviews the preview on the Import page and makes manual edits if needed.
5. **Calculate:** User navigates to the TE Calculation page, selects the batch, and clicks "Run TE Calculation".
6. **Analyze:** User reviews the calculated TE amounts and summaries.
7. **Refine:** If data was wrong, user goes back to Import, edits the row, then returns to Calculation and clicks "Re-calculate".

---

## 4. Implementation Checklist for Migration
When updating older modules (Profit Tax, VAT, Customs), use this checklist:
- [ ] Add `calculated_at` to the database.
- [ ] Separate the Import UI from the Calculation UI.
- [ ] Implement the "Clear Results" (Eraser) logic.
- [ ] Update the Sidebar navigation to reflect the split.
- [ ] Update the Excel Engine to handle the new column mapping.
