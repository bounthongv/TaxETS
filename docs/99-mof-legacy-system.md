# MOF Server — Legacy System Investigation

> **Date**: June 8, 2026
> **Server**: `taxetsprod` — accessed via user `taxets` at `172.16.0.193` (LAN IP)
> **Purpose**: Document the existing production system so we can deploy our Tax-ETS alongside it without disruption.

---

## 1. Server Overview

| Property | Value |
|----------|-------|
| Hostname | `taxetsprod` |
| OS | Ubuntu 24.04 |
| LAN IP | `172.16.0.193` |
| Docker gateway | `172.17.0.1` |
| Disk | 393G total, 14G used, 360G free (4%) |
| User | `taxets` (has sudo) |

## 2. Existing Web Application (Legacy System)

The MOF currently runs a **Laravel-based CMS application** that handles tax data. This is the system we are **replacing** (but we cannot inform the subcontractor yet).

### Web Server

- **Apache 2.4.58** (Ubuntu)
- Listens on port **80**, default VirtualHost
- Site config: `/etc/apache2/sites-available/000-default.conf`
- DocumentRoot: `/var/www/taxets/public` (the Laravel app)
- The 302 redirect to `http://localhost/cms/user` suggests the app uses a CMS framework (possibly "CB" — CodeBrick or similar)
- **No existing Tax-ETS** at `/var/www/html/tax-ets/`
- `/var/www/html/` contains only `index.html` (placeholder)

### Legacy Database

- **MariaDB 10.11.13** (`10.11.13-MariaDB-0ubuntu0.24.04.1`)
- **Database name**: `taxets`
- **User**: `taxets_user@%` (password: `taxets@080716`)
- **Root auth**: `mysql_native_password` (sudo-accessible)
- **Port**: 3306

The legacy `taxets` database has a completely **different schema** from our Tax-ETS:

- Uses CB framework tables (`cb_menus`, `cb_modules`, `cb_pages`, `cb_queries`, `cb_roles`, `cb_role_users`, `cb_settings`)
- Uses Laravel migrations table
- All data tables use `tbl_` prefix (e.g., `tbl_Enterprise_info`, `tbl_Profit_Tax`, `tbl_VATax`, `tbl_Result_TE_VAT_detail`, etc.)
- Contains ~150+ tables covering customs, excise tax, profit tax, VAT, individual tax, SEZO data, enterprise data, salary tax, etc.
- Has actual production data

**Key finding**: While the legacy DB has similar domain concepts (enterprise info, profit tax, VAT, etc.), the table structure and column names differ from our Tax-ETS schema. Our tables (`companies`, `provisions`, `bm_profit_standard`, `te_*`) do **not conflict** with the legacy tables.

## 3. Deployment Decision

- **Our Tax-ETS** runs via Docker alongside the legacy system
- **Separate database**: `tax_ets` (new, empty) with the same user `taxets_user@%`
- **Port**: `8080` (free — confirmed via `ss -tlnp`)
- **Connection**: Our container connects to host MariaDB via `172.17.0.1` (Docker gateway)

## 4. Deployment Plan

1. Move cloned repo from `/tmp/tax-ets-test` to `/opt/tax-ets-docker/`
2. Create database `tax_ets` and grant `taxets_user` access
3. Create `.env` with `DB_HOST=172.17.0.1`, `DB_USER=taxets_user`, `DB_PASS=taxets@080716`
4. `docker compose build && docker compose up -d`
5. Load our schema into `tax_ets` from `db/*.sql`
6. Seed reference/benchmark data
7. Verify `http://localhost:8080/login.php`

## 5. Legacy System Credentials

| Item | Value |
|------|-------|
| DB host | `localhost` (legacy) / `172.17.0.1` (from container) |
| DB port | 3306 |
| Legacy DB | `taxets` |
| Our DB | `tax_ets` |
| DB user | `taxets_user` |
| DB password | `taxets@080716` |
| Root MySQL | sudo-accessible |

## 6. Risks & Notes

- The legacy system is live — do NOT modify its database or files
- Apache currently serves the legacy app on port 80; our Docker runs on 8080
- If port 8080 needs to be opened externally, add UFW rule: `sudo ufw allow 8080/tcp`
- The subcontractor is not informed — leave legacy system fully operational
- Our Docker container must be restarted if the server reboots (`restart: unless-stopped` is set in compose)
- MariaDB needs a user grant for `172.17.0.%` or `172.19.0.%` (Docker network range) — our `.env` uses `172.17.0.1` (host gateway), which should work with the `%` wildcard on `taxets_user`
