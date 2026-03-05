# Tax-ETS Detailed Implementation Plan (Phase 1: CIT)

This document provides the exact technical specifications, database schema, and step-by-step development roadmap to build the CIT module of the Tax Expenditure Estimation System.

---

## 1. Technology Stack
- **Backend**: **PHP** (Vanilla PHP with object-oriented/procedural mix, or a lightweight MVC structure if preferred).
- **Database**: **MySQL** or MariaDB (via PDO for security).
- **Frontend / UI**: HTML5, **Bootstrap 5** (for rapid, responsive UI components), Vanilla JS.
- **Styling**: Custom CSS for the **Dark Green Sidebar** and clean data cards.
- **Libraries**:
  - `PhpSpreadsheet` (for importing `.xlsx` templates).
  - `DataTables.js` (for searchable, paginated, sortable data grids).
  - `Chart.js` (for Dashboard and TE Reports).
  - `FontAwesome` (for menu icons).

---

## 2. Step-by-Step Development Phases

### Phase 1.1: Database Setup
Create the MySQL database (`tax_ets`) and run the schema script to create the tables for the Rules Engine, Companies, and Results.

### Phase 1.2: System Skeleton & UI
Build `index.php`, the main layout wrapper (`header.php`, `sidebar.php`, `footer.php`), and the CSS file (`style.css`). Implement the dark green sidebar navigation.

### Phase 1.3: Rules Engine (Master Data)
Build the CRUD (Create, Read, Update, Delete) interfaces for:
1. `bm_profit_standard`
2. `bm_profit_mandatory`
3. `bm_profit_sme`
4. `profit_provisions` & `profit_provision_conditions`

### Phase 1.4: Data Management (Excel Import)
Implement `import.php`. Build the processor that reads `CIT Test_Toukta.xlsx`, extracts columns A to AN, and saves them to the `companies` table.

### Phase 1.5: TE Calculation Engine
Implement `calculate.php`. This strictly follows the logic from `1-plan.md`:
1. Check VAT status.
2. Apply Mandatory logic OR SME logic OR Standard logic to find `Benchmark_PT`.
3. Check Profit Provisions against the dynamic rule conditions.
4. Save to `te_profit_result`.

### Phase 1.6: Results & Reports
Build the views to show the calculation results clearly using DataTables and the graphical reports using Chart.js.

---

## 3. Database Schema Design (SQL)

Here is the exact schema for the MySQL database required for the CIT Phase.

```sql
-- Creates the base database
CREATE DATABASE IF NOT EXISTS tax_ets CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tax_ets;

-- 1. STANDARD BENCHMARK RATES
CREATE TABLE bm_profit_standard (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    category VARCHAR(50) NOT NULL, -- e.g., 'Standard', 'Tobacco', 'Mining/Electricity'
    rate_percentage DECIMAL(5, 2) NOT NULL -- e.g., 20.00, 24.00
);

-- 2. MANDATORY PROFIT BASE RATES (For Non-VAT Holders)
CREATE TABLE bm_profit_mandatory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    sector VARCHAR(100) NOT NULL, -- e.g., 'Production', 'Commerce', 'Services'
    sub_sector VARCHAR(255),
    profit_base_rate DECIMAL(5, 2) NOT NULL -- e.g., 3.00, 5.00
);

-- 3. MICRO ENTERPRISE RATES (For VAT Holders under threshold / SME)
CREATE TABLE bm_profit_sme (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    sector VARCHAR(100) NOT NULL,
    turnover_min DECIMAL(15, 2) DEFAULT 0,
    turnover_max DECIMAL(15, 2),
    rate_percentage DECIMAL(5, 2) NOT NULL -- e.g., 1.00, 2.00
);

-- 4. TAX PROVISIONS (The 20 profit tax provisions)
CREATE TABLE profit_provisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provision_number VARCHAR(10) NOT NULL, -- e.g., '1', '2', '1A'
    legal_reference VARCHAR(255),
    description TEXT,
    target_rate DECIMAL(5, 2) DEFAULT NULL, -- Null if exemption (0%) or calculated elsewhere
    is_exemption BOOLEAN DEFAULT FALSE
);

-- 5. PROVISION CONDITIONS (Dynamic Rule Engine for Profit Tax)
CREATE TABLE profit_provision_conditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provision_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL, -- Matches columns in 'companies' table
    operator VARCHAR(20) NOT NULL, -- '=', '>=', '<=', 'BETWEEN', 'YEARS_PASSED'
    value_1 VARCHAR(255),
    value_2 VARCHAR(255),
    FOREIGN KEY (provision_id) REFERENCES profit_provisions(id) ON DELETE CASCADE
);

-- 6. IMPORTED COMPANIES (From Excel)
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_batch_id VARCHAR(50),
    tax_year INT NOT NULL,
    tin VARCHAR(50) NOT NULL,
    company_name VARCHAR(255),
    province VARCHAR(100),
    district VARCHAR(100),
    sector VARCHAR(100),
    is_vat_holder BOOLEAN DEFAULT FALSE,
    zone_1 BOOLEAN DEFAULT FALSE,
    zone_2 BOOLEAN DEFAULT FALSE,
    zone_3 BOOLEAN DEFAULT FALSE,
    revenue DECIMAL(20, 2) DEFAULT 0,
    expense DECIMAL(20, 2) DEFAULT 0,
    net_profit DECIMAL(20, 2) DEFAULT 0,
    re_invested_profit DECIMAL(20, 2) DEFAULT 0,
    pt_paid DECIMAL(20, 2) DEFAULT 0,
    activity_type VARCHAR(100),
    staff_count INT DEFAULT 0,
    total_assets DECIMAL(20, 2) DEFAULT 0,
    registration_date DATE,
    investment_license_date DATE
);

-- 7. TE RESULTS (Profit Tax)
CREATE TABLE te_profit_result (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    benchmark_rate_applied DECIMAL(5, 2),
    benchmark_pt DECIMAL(20, 2) DEFAULT 0,
    pt_te DECIMAL(20, 2) DEFAULT 0,
    matched_provisions VARCHAR(255), -- Comma separated provision numbers (e.g., "1, 6")
    profit_tax_te DECIMAL(20, 2) DEFAULT 0,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
```
