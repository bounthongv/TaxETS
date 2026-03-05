# Tax-ETS Developer Guide

## Project Overview

Tax-ETS (Tax Expenditure Estimation System) is a PHP/MySQL web application for calculating and tracking tax expenditure provisions. The system processes company financial data against tax rules to estimate tax benefits.

## Tech Stack

- **Language**: PHP 8.x
- **Database**: MySQL
- **Dependencies**: PhpSpreadsheet (phpoffice/phpspreadsheet)
- **UI**: Bootstrap 5, DataTables, Font Awesome

---

## Build, Lint & Test Commands

### Dependencies
```bash
composer install
```

### Running Tests
```bash
# Run the tax engine test file
php tests/test_engine.php
```

### PHP Server
```bash
# Start local development server
php -S localhost:8000
```

---

## Code Style Guidelines

### General Conventions

- Use `<?php` opening tags (no short tags `<?`)
- Always use strict type declarations where possible
- Use meaningful, descriptive names for variables, functions, and classes
- Keep functions focused and small (under 50 lines where possible)

### Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Classes | PascalCase | `TEEngine`, `ProfitCalculator` |
| Methods | camelCase | `calculateBatch()`, `matchProvisions()` |
| Variables | camelCase | `$benchmark_pt`, `$total_te` |
| Constants | UPPER_SNAKE_CASE | `DB_HOST`, `BASE_URL` |
| Database Tables | snake_case | `companies`, `bm_profit_standard` |

### File Organization

- **includes/**: Core classes and helpers (db.php, te_profit_tax_engine.php)
- **pages/**: Page controllers/views
- **db/**: SQL schemas and seed data
- **tests/**: Test files
- **assets/**: CSS, JS, images

### PHP Type Hints

Use type hints for function parameters and return types:

```php
// Good
public function calculateBatch(string $batch_id): array {
    // ...
}

private function lookupStandardRate(int $year, string $sector): ?float {
    // ...
}

// Avoid
function calculateBatch($batch_id) {
    // ...
}
```

### SQL Queries

- Always use prepared statements with parameter binding to prevent SQL injection
- Use `FETCH_ASSOC` mode for consistent array access

```php
// Good
$stmt = $this->pdo->prepare("SELECT rate_percentage FROM bm_profit_standard WHERE start_year <= ? AND end_year >= ?");
$stmt->execute([$year, $year]);
$row = $stmt->fetch();

// Avoid - SQL injection risk
$this->pdo->query("SELECT * FROM companies WHERE id = $id");
```

### Error Handling

- Wrap database operations in try-catch blocks
- Log errors appropriately and return user-friendly messages
- Never expose sensitive information (database credentials, stack traces) to users

```php
try {
    $result = $this->calculateCompany($company);
    // ...
} catch (Exception $e) {
    $errors[] = "Company ID {$company['id']}: " . $e->getMessage();
    // Log for debugging: error_log($e->getMessage());
}
```

### Code Formatting

- Indent with 4 spaces (not tabs)
- One space after commas, around operators
- Opening brace on same line for classes/functions
- Use blank lines to separate logical code blocks

```php
class TEEngine {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function calculateBatch(string $batch_id): array {
        // ...
    }
}
```

### Database Conventions

- Foreign keys use `_id` suffix: `company_id`, `provision_id`
- Boolean fields: `is_vat_holder`, `is_active`
- Dates: `YYYY-MM-DD` format in database
- Use `created_at`, `updated_at` timestamps where applicable

### Import/Require Statements

- Use absolute paths with `__DIR__` for includes
- Group requires at the top of files

```php
require_once __DIR__ . '/../includes/te_profit_tax_engine.php';
require_once __DIR__ . '/../vendor/autoload.php';
```

### Working with Dates

- Use PHP `DateTime` class for date operations
- Parse Excel dates using `PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject()`

```php
private function parseDate($val): DateTime {
    if (is_numeric($val)) {
        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val);
    }
    return new DateTime($val);
}
```

### UI/View Guidelines

- Use Bootstrap 5 classes for styling
- Keep PHP logic out of views where possible
- Use short echo tags `<?= $var ?>` for outputting escaped data

---

## Common Tasks

### Adding a New Tax Rule/Benchmark Rate

1. Add data to appropriate table in `db/schema.sql` or create migration
2. Seed data in `db/seed_*.sql` files if needed
3. Add lookup method in `te_profit_tax_engine.php` if custom logic required

### Adding a New Page

1. Create file in `pages/` directory
2. Include header/sidebar/footer:
   ```php
   <?php require_once __DIR__ . "/../includes/header.php"; ?>
   <!-- Page content -->
   <?php require_once __DIR__ . "/../includes/footer.php"; ?>
   ```

### Running Calculations

1. Import company data via import_cit.php page
2. Configure provisions in config_provisions.php
3. Run calculation via API or page
4. View results in report_summary.php

---

## Testing Guidelines

- Test with both valid and edge case data
- Use reflection to test private methods when needed
- Mock PDO for unit tests (see test_engine.php example)
- Verify database state after operations

---

## Security Considerations

- Never commit config.php with real credentials
- Use environment variables for sensitive data in production
- Validate and sanitize all user inputs
- Use CSRF tokens for forms
- Output encode when displaying user data in views
