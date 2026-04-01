# Tax-ETS Developer Guide

## Project Overview

Tax-ETS (Tax Expenditure Estimation System) is a PHP/MySQL web application for calculating and tracking tax expenditure provisions. It processes company financial data against configurable tax rules to estimate tax benefits across Corporate Income Tax (CIT), VAT, and Personal Income Tax (PIT).

## Tech Stack

- **Language**: PHP 8.x (no framework)
- **Database**: MySQL / MariaDB
- **Dependencies**: PhpSpreadsheet (`phpoffice/phpspreadsheet`) via Composer
- **Frontend**: Bootstrap 5, DataTables, Font Awesome, Chart.js

---

## Build, Lint & Test Commands

```bash
composer install                    # Install dependencies
php -S localhost:8000               # Start dev server
php tests/test_engine.php           # Run engine tests (single test file)
```

No linting or static analysis tools (phpstan, phpcs, php-cs-fixer) are currently configured. No PHPUnit — tests are plain PHP scripts using `echo` and `ReflectionClass`.

---

## Project Structure

| Directory | Purpose |
|-----------|---------|
| `includes/` | Core engine classes and helpers (`db.php`, `te_profit_tax_engine.php`, `te_vat_engine.php`, `te_pit_engine.php`) |
| `pages/` | Page controllers/views (import, config, reports) |
| `db/` | SQL schema (`schema.sql`) and seed files (`seed_*.sql`) |
| `tests/` | Test scripts (`test_engine.php`) |
| `assets/` | CSS, JS, images |
| `docs/` | Requirements and templates |
| `implementation/` | Development plans |

---

## Code Style Guidelines

### General Rules

- Use `<?php` tags (no short tags `<?`)
- 4-space indentation (no tabs)
- Opening brace on same line for classes/methods
- Keep functions under 50 lines where possible

### Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Classes | PascalCase | `TEEngine`, `TEVatEngine` |
| Methods | camelCase | `calculateBatch()`, `lookupStandardRate()` |
| Variables | camelCase | `$benchmark_pt`, `$total_te` |
| Constants | UPPER_SNAKE_CASE | `DB_HOST`, `BASE_URL` |
| DB Tables | snake_case | `companies`, `bm_profit_standard` |
| DB Columns | snake_case with `_id` suffix for FKs | `company_id`, `provision_id` |

### Type Hints

Always use parameter and return type hints:

```php
public function calculateBatch(string $batch_id): array { ... }
private function lookupStandardRate(int $year, string $sector): ?float { ... }
```

### Imports & Includes

- Use `__DIR__` for absolute paths
- Group requires at the top of files
- Use `require_once` (not `include`)

```php
require_once __DIR__ . '/../includes/te_profit_tax_engine.php';
require_once __DIR__ . '/../vendor/autoload.php';
```

### Database Access

- Always use `getDbConnection()` from `includes/db.php` — returns PDO instance
- Use prepared statements with parameter binding — never interpolate variables into SQL
- Use `FETCH_ASSOC` mode (set as default in `db.php`)

```php
$stmt = $this->pdo->prepare("SELECT rate_percentage FROM bm_profit_standard WHERE start_year <= ? AND end_year >= ?");
$stmt->execute([$year, $year]);
$row = $stmt->fetch();
```

### Error Handling

- Wrap DB operations in try-catch blocks
- Collect errors into arrays, return alongside results
- Never expose credentials or stack traces to users

```php
try {
    $result = $this->calculateCompany($company);
} catch (Exception $e) {
    $errors[] = "Company ID {$company['id']}: " . $e->getMessage();
}
```

### Date Handling

- Use PHP `DateTime` class
- Parse Excel serial dates: `\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)`

### Views / UI

- Include `header.php`, `sidebar.php`, `footer.php` on every page
- Use Bootstrap 5 classes for layout/styling
- Use short echo tags `<?= htmlspecialchars($var) ?>` for output
- Keep business logic out of view files

---

## Engine Architecture

Three engine classes handle tax calculations, all following the same pattern — constructor takes `PDO`, `calculateBatch(string $batch_id): array` is the main entry point:

- **`TEEngine`** (`includes/te_profit_tax_engine.php`) — Corporate Income Tax
- **`TEVatEngine`** (`includes/te_vat_engine.php`) — VAT
- **`TEPitEngine`** (`includes/te_pit_engine.php`) — Personal Income Tax

Benchmark rates are stored in `bm_*` database tables — never hardcode rates in engine code.

---

## Common Tasks

**Adding a tax rule/benchmark rate**: Add data to the appropriate `bm_*` table via `db/schema.sql` or seed file. Add lookup method in the engine if custom logic is needed.

**Adding a new page**: Create file in `pages/`, include header/sidebar/footer. Follow existing page patterns (see `index.php`).

**Running calculations**: Import data via import page → configure provisions → run calculation → view results in reports.

---

## Security

- Never commit `config.php` with real credentials
- Validate and sanitize all user inputs
- Use CSRF tokens on forms
- Output-encode user data in views with `htmlspecialchars()`
