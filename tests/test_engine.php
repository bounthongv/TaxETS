<?php
require_once __DIR__ . '/../includes/te_profit_tax_engine.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Mock PDO for testing matchProvisions
class MockPDO extends PDO
{
    public function __construct()
    {
    }
}

echo "Starting TEEngine Operator Tests...\n";

// We'll test matchProvisions by making it public or using reflection, 
// but for simplicity, let's just test the logic via a subclass or reflection if needed.
// Given matchProvisions is private, let's use Reflection.

$engine = new TEEngine(new MockPDO());
$reflection = new ReflectionClass($engine);
$method = $reflection->getMethod('matchProvisions');
$method->setAccessible(true);

$test_cases = [
    [
        "name" => "TAX_HOLIDAY_ENDED - Pass",
        "company" => ["id" => 1, "tax_year" => 2024, "investment_license_date" => "2018-01-01", "tax_holiday_years" => 5],
        "provision" => ["id" => 1, "provision_number" => "P1", "conditions" => "investment_license_date|TAX_HOLIDAY_ENDED|5|"],
        "expect_match" => true
    ],
    [
        "name" => "TAX_HOLIDAY_ENDED - Fail (Still in holiday)",
        "company" => ["id" => 2, "tax_year" => 2024, "investment_license_date" => "2022-01-01", "tax_holiday_years" => 5],
        "provision" => ["id" => 2, "provision_number" => "P2", "conditions" => "investment_license_date|TAX_HOLIDAY_ENDED|5|"],
        "expect_match" => false
    ],
    [
        "name" => "EDATE_MONTHS_NOT_EXCEEDED - Pass",
        "company" => ["id" => 3, "tax_year" => 2024, "registration_date" => "2024-01-01"],
        "provision" => ["id" => 3, "provision_number" => "P3", "conditions" => "registration_date|EDATE_MONTHS_NOT_EXCEEDED|12|"],
        "expect_match" => true
    ],
    [
        "name" => "EDATE_MONTHS_BETWEEN - Pass",
        "company" => ["id" => 4, "tax_year" => 2024, "start_date" => "2023-01-01"],
        "provision" => ["id" => 4, "provision_number" => "P4", "conditions" => "start_date|EDATE_MONTHS_BETWEEN|12|24|"],
        "expect_match" => true // 2023-01-01 + 12m = 2024-01-01. Eval date 2024-12-31 is between 2024-01-01 and 2025-01-01.
    ]
];

// Mock the DB query inside matchProvisions
// This is tricky because it calls $this->pdo->query.
// For a quick check, I'll modify TEEngine to allow passing provisions or use a more sophisticated mock.
// Actually, I'll just run a simple script that tests the DATE Logic directly if I can't mock PDO easily.

echo "Ready to verify logic.\n";
?>
