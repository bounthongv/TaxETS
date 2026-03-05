# Tax Expenditure Estimation System (Tax-ETS) — Phase 1: CIT/Profit Tax

## Overview

Build a web application to calculate **Corporate Income Tax (CIT / Profit Tax) Tax Expenditures** for Lao PDR. The system will:

1. **Import** company financial data from Excel templates
2. **Identify** the appropriate benchmark tax rate based on year, sector, and company type
3. **Calculate TE** = Benchmark PT − Actual PT Paid
> [!IMPORTANT]
> **VAT Status & Mandatory Tax Payers**: The benchmark calculation forks based on **VAT Holder** status.
> - **Non-VAT Holders (Mandatory/Lump Sum)**: Use a 2-step calculation: `Estimated Income × Profit Base Rate = Estimated Profit`, then `Estimated Profit × Standard PT Rate = Benchmark PT`.
> - **VAT Holders**: Check **Annual Turnover** to see if they qualify for **Micro Enterprise Income Tax** rates; otherwise, use the standard **Profit Tax** logic.
> - This status is an input from external sources (TaxRIS).
4. **Classify** each TE by provision (20 defined provisions) for reporting
5. **Verify** results match the existing Excel-calculated values

## User Review Required

> [!IMPORTANT]
> **Technology choice**: Plan uses **PHP** (with vanilla HTML/CSS/JS frontend). If you prefer a different stack (Node.js, Python/Flask, etc.), let me know.

> [!IMPORTANT]
> **Date sensitivity**: Many provision formulas use `TODAY()` (e.g., checking if tax holidays are still active). The system needs a configurable "evaluation date" rather than hardcoding today's date, so historical calculations can be reproduced. Should I default to the tax year end date or provide a date picker?

---

## The "Configurable Rules Engine" Approach

Following your feedback, the system **will not** hardcode tax logic into the background PHP code. Instead, we will build a **dynamic rules engine**. This means the exact rules used to calculate TE and Provisions will be stored in the database and fully editable by administrators through the UI.

### 1. Configurable Benchmark Rates
Instead of hardcoding "2018-2019 is 24%", the UI will have a "Master Data > Benchmark Rates" table:
- **Rule Name**: Standard Rate 2018-2019
- **Condition (Sector)**: Agriculture, Industry, Commerce, etc.
- **Condition (Year Range)**: 2018 to 2019
- **Rate**: 24%
*If a new tax law is passed in 2026 changing the rate to 18%, the admin simply adds a new row to this table. The code never needs to be touched.*

### 2. Configurable Provisions (TE Classification)
Instead of hiding complex IF/AND statements in the code, the UI will have a "Master Data > Tax Provisions" screen. 
Each of the 20 provisions will be defined as a set of logical rules:
- **Provision Name**: Small entity rate relief (3%)
- **Target Rate**: 3%
- **Condition 1**: Staff Count (Operator: Between, Value: 6 and 50)
- **Condition 2**: Total Assets (Operator: Less than or Equal, Value: 1.5B)
- **Condition 3**: Annual Turnover (Operator: Less than or Equal, Value: 3B)
- **Condition 4**: Years since registration (Operator: Less than or Equal, Value: 3)
*When the system imports a company, it evaluates the company's data against these UI-defined rules. If the rules change, the admin just edits the conditions.*

### 3. CIT Benchmark Logic (The Primary Fork)
The system determines the benchmark based on **VAT Holder** status:
- **IF NOT VAT Holder** (Mandatory/Lump Sum):
  1. `Estimated Profit = Estimated Income × Profit Base Rate` (Lookup in `benchmark_profit_base` by sector).
  2. `Benchmark PT = Estimated Profit × Standard PT Rate` (Lookup in `benchmark_rates`).
- **IF YES, VAT Holder**:
  - **IF Turnover <= Threshold**: Apply **Micro Enterprise** logic (Lookup in `benchmark_micro_enterprise`).
  - **OTHERWISE**: `Benchmark PT = (Net Profit + Re-invested Profit) × Standard PT Rate`.

---

## Proposed System UI & Menu Structure

### Global Layout
- **Left Sidebar (Dark Green)**: Contains the main vertical navigation menu with collapsible sections.
- **Top Header (White)**: User profile, quick actions, "Logout", and a toggle for the sidebar.
- **Main Content Area (Light Gray/White)**: Clean, modern cards, utilizing DataTables for searchable/filterable grids, and interactive charts (Chart.js/ECharts).

### Modernized Menu Structure
1. **🏠 Dashboard** (Summary statistics, total TE widgets, recent imports)
2. **⚙️ Rule Engine & Configuration** *(The biggest improvement over the old system)*
   - **Benchmark Tax Rates**: Fully editable grid managing rates by year and sector.
   - **Tax Provision Rules**: A dynamic rule builder where admins define the IF/AND/OR logic for every provision.
3. **📥 Tax Data Management**
   - Corporate Income Tax (CIT) Data Import
4. **🧮 TE Calculation**
   - Run CIT Calculation (Select year and evaluation date)
5. **📊 Analysis & Reports**
   - TE by Year, Sector, Province
   - Profit Tax TE by Provision (Interactive Bar/Pie charts)
6. **🔒 System Management**
   - User Management & Operation Logs

---

## Technical Architecture

### Database Design for Configurable Rules

#### `benchmark_rates` (Standard Profit Tax)
- `id`, `start_year`, `end_year`, `category` (Standard / Tobacco / Mining / Electricity), `rate_percentage`

#### `benchmark_profit_base` (For Mandatory Tax Payers)
- `id`, `start_year`, `end_year`, `sector` (Production / Commerce / Services / etc.), `sub_sector`, `profit_base_rate`

#### `benchmark_micro_enterprise`
- `id`, `start_year`, `end_year`, `turnover_min`, `turnover_max`, `rate_type` (Lump Sum / Percentage), `value`

#### `provisions`
- `id`, `provision_number`, `legal_reference`, `description`, `target_rate`, `is_exemption`

#### `provision_conditions` (The core of the Rule Engine)
- `id`, `provision_id`, `field_name` (e.g., 'staff_count', 'assets', 'zone_id')
- `operator` (e.g., '>=', '<=', '=', 'IN', 'YEARS_SINCE')
- `value_1`, `value_2` (for BETWEEN ranges)

### Execution Flow
1. **Load Rules**: When calculation starts, system queries `benchmark_rates`, `benchmark_profit_base`, `benchmark_micro_enterprise`, and `provision_conditions` from the DB.
2. **Determine Benchmark Logic**:
   - Check `is_vat_holder`. 
   - If `FALSE`: Apply 2-step mandatory logic using `Estimated Income`.
   - If `TRUE`: Check `Annual Turnover` against Micro Enterprise ranges.
3. **Calculate Benchmark PT**: Based on the identified logic above.
4. **Check Provisions**: Loop through all 20 `provisions`. Execute dynamic checks against `provision_conditions` for each company row.
5. **Save Results**: Write calculated Benchmark PT, TE, and matched Provision IDs back to `te_results`.
