# Project Status - Tax-ETS Phase 1 (CIT)

Last Updated: 2026-03-04

## Completed ✅

- [x] Database Setup
  - `db/schema.sql` - Core tables
  - `db/schema_update_v2.sql` - Additional columns for Excel import
- [x] Benchmark Rates
  - `db/seed_benchmark_rates.sql` - Seed data for rates
  - `pages/config_rates.php` - UI for managing rates
- [x] Provisions Configuration
  - `db/seed_profit_provisions.sql` - Seed data for 20 provisions
  - `pages/config_provisions.php` - UI for managing provision rules
- [x] TE Calculation Engine
  - `includes/te_profit_tax_engine.php` - Core calculation logic
- [x] Excel Import
  - `pages/import_cit.php` - Import from Excel template
- [x] Calculator
  - `pages/calculator.php` - Run TE calculations
- [x] Reports
  - `pages/report_summary.php` - Summary report
  - `pages/report_provisions.php` - TE by provision report

## In Progress 🚧

- [x] Import test data from Excel (`docs/CIT Test_Toukta.xlsx`)
- [x] Run calculation and verify results against benchmark
  - ✅ Benchmark rate matches perfectly
  - ✅ Benchmark PT and PT TE match when rate matches
  - ⚠️ Some discrepancies due to Excel prototype using simplified formulas

## Pending 📋

- [ ] Dashboard - Summary statistics and widgets
- [ ] User Management - Login/logout, roles
- [ ] Verify calculation accuracy against Excel reference values

---

## Testing Notes

1. Start XAMPP (Apache + MySQL)
2. Access: `http://localhost/Tax-ETS/pages/import_cit.php`
3. Upload `docs/CIT Test_Toukta.xlsx`
4. Run calculation via batch or `pages/calculator.php`
5. Compare results with expected values

## Known Issues

- Test file `tests/test_engine.php` has setup but no assertions yet
- `tax_holiday_years` column added via schema_update_v2.sql (verify it's present)
