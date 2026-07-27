# Tax-ETS — User & Role Management

> Last updated: 2026-07-27
> Applies to: Production (apis.com.la), MOF Server (172.16.0.193:5000), Local (Docker)

---

## 1. Role-Based Access Control (RBAC)

The system has **5 predefined roles** with **119 module-level permissions** per role. Each module has CRUD flags (Create, Read, Update, Delete), but the enforcement gate only checks **Read** permission to determine whether a user can access a page.

Permission enforcement is implemented in `includes/auth.php` — every page that includes `header.php` (which includes `auth.php`) will check the user's role permissions automatically. If access is denied, a styled **403 Access Denied** page is shown.

### 1.1 Role Definitions

| ID | Role | Permissions | Default Users |
|----|------|-------------|---------------|
| 1 | **SUPER ADMIN** | **Full access to ALL 119 modules** (CRUD) | Administrator |
| 2 | **ADMIN** | **Full access to ALL 119 modules** (CRUD) | Trainer, APIS User, bie98848566@gmail.com |
| 3 | **ACCOUNTING** | **Read-only** on 112 non-admin modules | — |
| 4 | **TECH_TEAM_MOF** | **Read-only** on 112 non-admin modules | mof1–mof5 |
| 5 | **USER** | **Read-only** on 36 report/repo modules | — |

> **Note:** ADMIN (ID 2) has the same permissions as SUPER ADMIN (ID 1) — both bypass all permission checks in `auth.php`.

### 1.2 Available Modules (119)

Below is the full list of modules organized by functional area. Permissions can be configured per role via **System → Role Management** (`/pages/system_roles.php`).

#### System Administration (7 modules)
| Module Key | Display Name |
|------------|-------------|
| `system_users` | System: User Management |
| `system_roles` | System: Role Management |
| `system_history` | System: Operation Logs |
| `system_logs` | System: Error Logs |
| `system_ip` | System: IP Access Control |
| `system_online` | System: Online Users |
| `system_backup` | System: Backup & Restore |

#### Configuration / Benchmark Rates (16 modules)
| Module Key | Display Name |
|------------|-------------|
| `config_rates` | Config: CIT Benchmark Rates |
| `config_provisions` | Config: CIT Provisions |
| `config_rules` | Config: CIT Manual Rules |
| `config_sez_provisions` | Config: SEZ Provisions |
| `benchmark_msme` | Config: MSME Benchmark |
| `benchmark_individual` | Config: PIT / Individual Benchmark |
| `benchmark_vat` | Config: VAT Benchmark |
| `benchmark_customs` | Config: Customs Benchmark |
| `benchmark_customs_regime` | Config: Customs Regime |
| `benchmark_excise` | Config: Excise Benchmark |
| `benchmark_art9` | Config: Article 9 Activities |
| `benchmark_land_concession` | Config: Land Concession |
| `benchmark_nontax` | Config: Non-Tax Revenue |
| `benchmark_payment_condition` | Config: Payment Condition |
| `vat_config_rules` | Config: VAT Rules |
| `customs_config_rules` | Config: Customs Rules |

#### Dictionaries (8 modules)
| Module Key | Display Name |
|------------|-------------|
| `dictionary_province` | Dictionary: Province |
| `dictionary_district` | Dictionary: District |
| `dictionary_village` | Dictionary: Village |
| `dictionary_zone` | Dictionary: Investment Zone |
| `dictionary_sector` | Dictionary: Sector |
| `dictionary_enterprise_type` | Dictionary: Enterprise Type |
| `dictionary_moic_categories` | Dictionary: MOIC Categories |
| `dictionary_status` | Dictionary: Status |

#### Import (20 modules)
| Module Key | Display Name |
|------------|-------------|
| `import_cit` | Import: Corporate Income Tax |
| `import_individual` | Import: Individual / PIT |
| `import_vat` | Import: VAT |
| `import_asycuda` | Import: ASYCUDA (Customs/Excise) |
| `import_sez_dev` | Import: SEZ Developer |
| `import_sez_inv` | Import: SEZ Investor |
| `import_salary` | Import: Salary Tax |
| `import_lse` | Import: LSE Data |
| `import_land` | Import: Land Tax |
| `import_land_concession` | Import: Land Concession |
| `import_resource` | Import: Natural Resource |
| `import_royalty` | Import: Royalty |
| `import_moic` | Import: MOIC (Enterprise) |
| `import_mpi` | Import: MPI (Investment) |
| `import_molsw` | Import: MOLSW |
| `import_taxris` | Import: TaxRIS |
| `import_gdp` | Import: GDP Data |
| `import_districts` | Import: Districts (CSV) |
| `import_tariff` | Import: Tariff |
| `import_new_data` | Import: Legacy Migration |

#### Data Entry (11 modules)
| Module Key | Display Name |
|------------|-------------|
| `view_companies` | Data: View Companies (CIT) |
| `view_individual` | Data: View Individual Tax |
| `view_vat` | Data: View VAT |
| `view_salary` | Data: View Salary Tax |
| `view_resource` | Data: View Resource |
| `view_royalty` | Data: View Royalty |
| `view_sez_dev` | Data: View SEZ Developer |
| `view_sez_inv` | Data: View SEZ Investor |
| `asycuda_customs` | Data: ASYCUDA Customs |
| `asycuda_excise` | Data: ASYCUDA Excise |
| `asycuda_vat` | Data: ASYCUDA VAT |

#### Calculation (3 + 13 modules)
| Module Key | Display Name |
|------------|-------------|
| `calculator` | Calc: TE Calculator |
| `recalculate_all` | Calc: Recalculate All |
| `calculate_land_concession` | Calc: Land Concession Calc |
| `te_asycuda_customs` | Calc: Customs TE |
| `te_asycuda_excise` | Calc: Excise TE |
| `te_asycuda_vat` | Calc: ASYCUDA VAT TE |
| `te_customs` | Calc: Customs TE (detailed) |
| `te_excise` | Calc: Excise TE (detailed) |
| `te_individual` | Calc: Individual TE |
| `te_nontax` | Calc: Non-Tax TE |
| `te_resource` | Calc: Resource TE |
| `te_royalty` | Calc: Royalty TE |
| `te_salary_tax` | Calc: Salary Tax TE |
| `te_sez_dev` | Calc: SEZ Developer TE |
| `te_sez_inv` | Calc: SEZ Investor TE |
| `te_vat` | Calc: VAT TE |

#### Reports (20 + 16 modules)
| Module Key | Display Name |
|------------|-------------|
| `report_tax_type` | Report: Tax Type Summary |
| `report_provisions` | Report: Provisions Summary |
| `report_summary` | Report: Overall Summary |
| `report_sector` | Report: By Sector |
| `report_location` | Report: By Location |
| `report_revenue` | Report: Revenue Impact |
| `report_customs_duty` | Report: Customs Duty |
| `report_customs_provision` | Report: Customs Provisions |
| `report_excise_tax` | Report: Excise Tax |
| `report_excise_provision` | Report: Excise Provisions |
| `report_import_vat` | Report: Import VAT |
| `report_vat_provision` | Report: VAT Provisions |
| `report_individual_provision` | Report: Individual/PIT Provisions |
| `report_salary_tax_provision` | Report: Salary Tax Provisions |
| `report_nontax_provision` | Report: Non-Tax Provisions |
| `report_sez_dev_provision` | Report: SEZ Developer Provisions |
| `report_sez_inv_provision` | Report: SEZ Investor Provisions |
| `report_total_customs` | Report: Total Customs |
| `report_total_provision` | Report: Total Provisions |
| `report_gdp` | Report: GDP Impact |
| `repo_individual` | Repo: Individual Provisions |
| `repo_vat` | Repo: VAT Provisions |
| `repo_customs` | Repo: Customs Data |
| `repo_excise` | Repo: Excise Data |
| `repo_land_concession` | Repo: Land Concession |
| `repo_lse` | Repo: LSE Data |
| `repo_moic` | Repo: MOIC Data |
| `repo_molsw` | Repo: MOLSW Data |
| `repo_mpi` | Repo: MPI Data |
| `repo_natural_resource` | Repo: Natural Resource |
| `repo_nontax` | Repo: Non-Tax Revenue |
| `repo_royalty` | Repo: Royalty Data |
| `repo_sezo` | Repo: SEZ Data |
| `repo_taxris` | Repo: TaxRIS Data |
| `repo_gdp` | Repo: GDP Data |
| `repo_milestones` | Repo: Milestones |

#### Utilities (5 modules)
| Module Key | Display Name |
|------------|-------------|
| `batches` | Utility: Batch Manager |
| `delete_batch` | Utility: Delete Batch |
| `download_log` | Utility: Download Log |
| `notification_mgmt` | Utility: Notification |
| `change_password` | Utility: Change Password |

### 1.3 Default Permissions (as of 2026-07-27)

| Role | Total Modules | Create | Read | Update | Delete |
|------|:------------:|:-----:|:---:|:-----:|:-----:|
| SUPER ADMIN | 119 | 119 | 119 | 119 | 119 |
| ADMIN | 119 | 119 | 119 | 119 | 119 |
| ACCOUNTING | 112 | 0 | 112 | 0 | 0 |
| TECH_TEAM_MOF | 112 | 0 | 112 | 0 | 0 |
| USER | 36 | 0 | 36 | 0 | 0 |

ACCOUNTING and TECH_TEAM_MOF have read-only access to all **non-admin** modules (112 modules). System admin modules (users, roles, history, logs, ip, online, backup) are excluded.  
USER has read-only access to **reports only** (36 modules).

### 1.4 Configuring Permissions

1. Go to **System → Role Management** (`/pages/system_roles.php`)
2. Click on a role from the left panel
3. Check or uncheck CRUD checkboxes for each module
4. Click **Save Permissions**

> **Important:** Permission changes take effect immediately. Users do not need to re-login — `auth.php` checks the database on every page load.

### 1.5 Permission Enforcement (How It Works)

The enforcement in `includes/auth.php` works as follows:

```
1. Auth.php is included via header.php on every page
2. Derive module name from the page filename (import_cit.php → "import_cit")
3. Skip permission check for login.php and API endpoints
4. Get user's role_id from session (stored on login)
5. SUPER ADMIN (id=1) and ADMIN (id=2) → bypass, full access
6. Other roles → query role_permissions table for can_read
7. If can_read = 0 → show 403 Access Denied page
```

The page-to-module mapping is automatic — the PHP filename without `.php` suffix becomes the module key. This means all pages in `/pages/` are automatically protected.

### 1.6 Concurrent Login Prevention

When a user logs in from a new device/browser, any existing active session for the same user is **automatically invalidated**. The old session is redirected to `login.php?reason=kicked` with a blue info banner:

> **"Your session was closed because your account was signed in from another device."**

This is implemented in:
- `login.php` — sets old sessions to `is_online = 0`, creates new session
- `auth.php` — checks `is_online` flag on every page load
- The check happens on every page load, not just login

---

## 2. Current Users

| ID | Name | Email | Role | Password | Active |
|----|------|-------|------|----------|--------|
| 1 | Administrator | admin@example.com | SUPER ADMIN | `admin123` | Yes |
| 3 | Trainer | trainer@example.com | ADMIN | `trainer123` | Yes |
| 4 | APIS User | apis@example.com | ADMIN | `apis@#2024` | Yes |
| 5 | bie98848566@gmail.com | bie98848566@gmail.com | ADMIN | *(trainer123)* | Yes |
| 6 | mof1 | mof1@mof.gov.la | TECH_TEAM_MOF | `mof1` | Yes |
| 7 | mof2 | mof2@mof.gov.la | TECH_TEAM_MOF | `mof2` | Yes |
| 8 | mof3 | mof3@mof.gov.la | TECH_TEAM_MOF | `mof3` | Yes |
| 9 | mof4 | mof4@mof.gov.la | TECH_TEAM_MOF | `mof4` | Yes |
| 10 | mof5 | mof5@mof.gov.la | TECH_TEAM_MOF | `mof5` | Yes |

> ⚠️ Change these passwords after training. Each user should set their own via **Utility → Change Password**.

*(User ID 2 was deleted or missing — IDs are not reused.)*

---

## 3. User Management Pages

| Page | URL | Purpose |
|------|-----|---------|
| User Management | `/pages/system_users.php` | Add, edit, delete users. Set name, email, role, active status. |
| Role Management | `/pages/system_roles.php` | Add, edit, delete roles. Set per-module CRUD permissions. |
| User History | `/pages/system_history.php` | View audit trail of user actions (login, import, calculate). |
| Online Users | `/pages/system_online.php` | View currently active sessions. |
| IP Access Control | `/pages/system_ip.php` | Restrict access by IP address. |
| Change Password | `/pages/change_password.php` | Let users change their own password. |

> All pages above require **SUPER ADMIN** or **ADMIN** role to access.

---

## 4. Role Recommendations

| Person/Team | Recommended Role | Rationale |
|------------|-----------------|-----------|
| System Administrator | SUPER ADMIN | Full control over users, roles, permissions, and all data |
| APIS Team / Trainers | ADMIN | Same as SUPER ADMIN — can manage everything |
| MOF Technical Staff | TECH_TEAM_MOF | Read-only access to benchmark rates, imports, calculators, reports |
| MOF Accountants | ACCOUNTING | Read-only access to imports, calculators, reports |
| External Viewers | USER | Read-only access to reports only |

---

## 5. Server URLs

| Server | URL | Type | Notes |
|--------|-----|------|-------|
| **Production** | `https://tax-ets.apis.com.la` | Ubuntu Docker | Public, auto-deploy from GitHub master |
| **MOF** | `http://172.16.0.193:5000/` | Behind Fortigate VPN | Offline bundle, manual update |
| **Legacy (MOF)** | `http://172.16.0.193:8080/` | Old system | Untouched, port 5000 is the new system |
| **Local** | `https://tax-ets.local` | Windows Docker | Uses local XAMPP MySQL |

### 5.1 Update Process

**Production:** Push to GitHub master → auto-build via GitHub Actions → `docker compose build && docker compose up -d`

**MOF Server:** Manual update via SCP:
```bash
scp /d/Tax-ETS/includes/auth.php taxets@172.16.0.193:/tmp/
scp /d/Tax-ETS/login.php taxets@172.16.0.193:/tmp/
ssh taxets@172.16.0.193 "
docker cp /tmp/auth.php tax-ets-mof-web:/var/www/html/includes/auth.php
docker cp /tmp/login.php tax-ets-mof-web:/var/www/html/login.php
"
```
