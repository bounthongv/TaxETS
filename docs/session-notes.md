# Session Notes

## About This File

This file is your **persistent memory** between Codebuff sessions. When a session ends (timeout, credit depletion, or manual close), the AI loses all conversation history. This file preserves the key context so you can resume seamlessly.

---

## Current Session: 2026-05-28

### What Was Discussed

- **Understanding Codebuff sessions**: Sessions are stateless — each new `codebuff` start is a fresh conversation with no memory of previous ones.
- **Session closure**: The free plan may close sessions without warning due to inactivity timeouts, credit limits, or context window limits.
- **Context preservation strategy**: Using Git for code + session notes file for conversation context.
- **Key difference**: `git commit` saves code changes only — decisions, plans, and reasoning are lost unless saved to a file like this one.

### How to Resume

In a new session, start with this prompt:

> Read `docs/session-notes.md`, `AGENTS.md`, `implementation/3-project-status.md`, and check the git log — then continue where I left off.

---

## How to Keep This File Updated

### Option 1: Ask me to do it (Recommended)

At any natural stopping point during a session, just say:

> **"Update the session notes"**

or

> **"Save a summary of what we just did to docs/session-notes.md"**

I will read the current file, append a new entry with:
- What was accomplished
- Key decisions made
- Files changed
- Next steps

### Option 2: Update it yourself

Open `docs/session-notes.md` and add a new section like:

```markdown
## Session: 2026-05-28 (Afternoon)

### Done
- Fixed the VAT calculation engine

### Decisions
- Using DECIMAL(15,2) for all monetary columns

### Next Steps
- Implement Land Concession engine
```

### When to Update

Good triggering moments:
- ✅ After completing a feature or bug fix
- ✅ Before stepping away from your computer
- ✅ When you notice the conversation is getting long
- ✅ After making an important decision about the project
- ✅ Right before you close the terminal

### Then Commit It

After updating, run:

```bash
git add docs/session-notes.md
git commit -m "docs: update session notes"
```

This way the notes are saved permanently alongside your code.

---

## Session: 2026-05-28 (Work Session) - Continued

### Accomplished
- **Standardized Salary Tax import page** (`import_salary.php`) to match the CIT/Individual Tax pattern:
  - Rewrote layout: upload form on left (col-md-5), recent batches table on right (col-md-7)
  - Added **View** button → opens `view_salary.php?batch=...`
  - Added **Delete** (trash) button → posts to `delete_batch.php` with `type=salary`
  - Changed batch prefix from `SALARY_BATCH_` to `SALARY_TAX_` for clarity
  - Added `datatable w-100` class, consistent alert styling with `border-start border-4`
- **Updated `delete_batch.php`** — added `salary` case that deletes from `import_salary_tax_data` table
- **`view_salary.php`** was already created in the previous sub-session

### Files Changed
| File | Action | Description |
|------|--------|-------------|
| `pages/import_salary.php` | Rewritten | Standardized with View/Calculate/Delete + `SALARY_TAX_` prefix |
| `pages/delete_batch.php` | Modified | Added `salary` case for batch deletion |

### Synced to Web Root
All files synced to `D:\\xampp\\htdocs\\Tax-ETS\\pages\\`

---

## Session: 2026-05-28 (Work Session)

### Accomplished
- **Standardized Individual Tax (PIT) import page** (`import_individual.php`) to match the CIT profit tax interface pattern:
  - Added **View** button → opens `view_individual.php?batch=...`
  - Added **Delete** (trash) button → posts to `delete_batch.php` with `type=pit`
  - Improved alert styling with `border-start border-4` accent (matching CIT)
  - Added `datatable w-100` class to the batch table
- **Created `view_individual.php`** — new view/edit page for PIT records (analogous to `view_companies.php`):
  - Data table: #, Year, PTIN, Employee Name, Filing Date, Total Income, SS Member, Expert TE, Actions
  - Edit button per record with full modal (basic info + 11 income provisions + 11 expert TE fields + SS flag)
  - Delete button per record for individual record removal
  - Add Record button for manual entry
  - Back navigation to import page + Run TE Calculation button

### Key Decisions
- **Batch naming convention**: Changed prefix from `PIT_BATCH_` to `INDIVIDUAL_TAX_` for clarity. Going forward, all import modules should use descriptive batch name prefixes related to the module/menu name.
- **Interface consistency**: The PIT import now follows the same UI pattern as CIT (View → Calculate → Delete buttons in Actions column).
- **Per-record delete**: Added individual record delete in `view_individual.php` (an enhancement beyond `view_companies.php` which only has Edit).

### Files Changed/Created
| File | Action | Description |
|------|--------|-------------|
| `pages/import_individual.php` | Modified | Added View/Delete buttons, improved styling, changed batch prefix |
| `pages/view_individual.php` | Created | New view/edit page for individual tax records |
| `docs/session-notes.md` | Modified | This update |

### Synced to Web Root
Both files synced to `D:\xampp\htdocs\Tax-ETS\pages\`

### Next Steps / Notes
- Standard pattern reference document: `docs/standard-repo-pattern.md`
- Project status: `implementation/3-project-status.md`
- Old `PIT_BATCH_` batches in DB remain functional — only new imports use `INDIVIDUAL_TAX_` prefix

---

## Session: 2026-05-28 (Domestic VAT Standardization)

### Accomplished
- **Standardized Domestic VAT import page** (`import_vat.php`) to match the CIT/Individual/Salary pattern:
  - Rewrote layout: two-column (upload left col-md-5, batch table right col-md-7)
  - Added **View** button → opens `view_vat.php?batch=...`
  - Added **Delete** (trash) button → posts to `delete_batch.php` with `type=vat`
  - Changed batch prefix from `VAT-` to `VAT_` (underscore, matching convention)
  - Added `datatable w-100` class, `border-start border-4` alert styling, `border-top border-4` card accent
  - Added loading spinner on import button, empty state message
- **Created `view_vat.php`** — new view/edit page for VAT records:
  - Data table: #, TIN, Name, Province, Filing Period, Exempt Sales, Expert TE, Provision, Actions
  - Edit button per record with full modal (Basic Info, Sales Data, Purchase Data, VAT Summary sections)
  - Delete button per record for individual record removal
  - Add Record button for manual entry
  - Back navigation to import page + Run TE Calculation button
- **Updated `delete_batch.php`** — added `vat` case that deletes from `import_vat_data` table

### Key Decisions
- Batch prefix: `VAT_` (underscore, consistent with `SALARY_TAX_`, `INDIVIDUAL_TAX_`, `BATCH_` pattern)
- The VAT view page follows the `view_salary.php` pattern with organized modal sections

### Files Changed/Created
| File | Action | Description |
|------|--------|-------------|
| `pages/import_vat.php` | Rewritten | Standardized with View/Calculate/Delete + `VAT_` prefix |
| `pages/view_vat.php` | Created | New view/edit page for VAT records with Edit modal |
| `pages/delete_batch.php` | Modified | Added `vat` case for batch deletion |
| `docs/session-notes.md` | Modified | This update |

### Synced to Web Root
All files synced to `D:\xampp\htdocs\Tax-ETS\pages\`

---

## Project Quick Reference

| Item | Location |
|------|----------|
| Project docs | `docs/` |
| Implementation plans | `implementation/` |
| Project status | `implementation/3-project-status.md` |
| Standard repo pattern | `implementation/standard-repo-pattern.md` |
| AGENTS.md (project guide) | `AGENTS.md` (project root) |

