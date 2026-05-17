# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer install                    # Install dependencies (PhpSpreadsheet)
php -S localhost:8000               # Start dev server
php tests/test_engine.php           # Run engine tests (single test file, no PHPUnit)
```

No linting, static analysis, or PHPUnit. Tests are plain PHP scripts with echo/ReflectionClass — verify output manually.

## Architecture

Tax-ETS is a PHP 8.x/MySQL web application for calculating tax expenditure provisions in Lao PDR. No framework — plain PHP with PDO.

### Core Workflow

**Import** (Excel → DB) → **Configure** (benchmark rates + provisions) → **Calculate** (engine processes batch) → **Report** (results from `te_*_result` tables)

### Engine Layer (`includes/`)

Three engine classes, all with the same pattern — constructor takes `PDO`, main method is `calculateBatch(string $batch_id): array`:

- `TEEngine` (`te_profit_tax_engine.php`) — Corporate Income Tax
- `TEVatEngine` (`te_vat_engine.php`) — VAT
- `TEPitEngine` (`te_pit_engine.php`) — Personal Income Tax
- `TEAsycudaEngine` (`te_asycuda_engine.php`) — ASYCUDA (Customs/Excise)
- `TELandConcessionEngine` (`te_land_concession_engine.php`) — Non-tax land concessions

Benchmark rates live in `bm_*` database tables — never hardcode rates in engine code. Engines look up rates via prepared statements against these tables.

### Page Layer (`pages/`)

Each page is a single PHP file that handles POST actions at the top, then renders HTML with Bootstrap 5. Pattern:

```php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
// ... process POST ...
require_once __DIR__ . "/../includes/header.php";  // includes auth check + sidebar
// ... render HTML ...
require_once __DIR__ . "/../includes/footer.php";
```

`header.php` loads `config.php`, `db.php`, `auth.php`, and `sidebar_FIXED.php`. Pages that need authentication just include `header.php` (auth is built in).

### Database (`db/`)

- `schema.sql` — base schema (benchmark tables, companies, provisions, results)
- Additional `*_schema.sql` files for VAT, PIT, customs, ASYCUDA, land concessions, repository modules (MOIC, MPI, MOLSW, LSE, SEZO, TaxRIS)
- `seed_*.sql` files for benchmark rate data and initial provisions

### Key Tables

| Pattern | Purpose |
|---------|---------|
| `bm_profit_*`, `bm_vat_*`, `bm_pit_*`, `bm_customs_*` | Benchmark rates by tax type |
| `companies` | Imported company financial data |
| `profit_provisions` + `profit_provision_conditions` | Configurable tax rules with dynamic conditions |
| `te_profit_result`, `te_vat_result`, etc. | Calculation output |
| `repo_moic`, `repo_mpi`, `repo_molsw`, `repo_lse`, `repo_sezo`, `repo_taxris` | External data repositories |

### Auth (`includes/auth.php`)

Session-based. `auth.php` calls `session_start()` and redirects to `login.php` if no session. Provides `getCurrentUserId()`, `getCurrentUserName()`. `header.php` includes auth automatically.

### External Data Repositories

Separate modules for importing data from government systems: MOIC (enterprise registry), MPI (investment), MOLSW (labor), LSE (stock exchange), SEZO (special economic zones), TaxRIS (tax records). Each has its own schema, import page, and repository view in `pages/`.

## Database Access

Always use `getDbConnection()` from `includes/db.php` — returns PDO with `FETCH_ASSOC` default. Use prepared statements with `?` placeholders — never interpolate variables into SQL.

## Security

- Output-encode user data: `<?= htmlspecialchars($var) ?>`
- Validate all `$_POST`/`$_GET` inputs
- `config.php` contains credentials — never commit with real values
