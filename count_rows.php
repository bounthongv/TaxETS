<?php
require_once 'config.php';
require_once 'includes/db.php';
$pdo = getDbConnection();

$tables = [
    'te_profit_result' => 'Profit Tax (CIT)',
    'te_individual_result' => 'Individual Tax (PIT)',
    'import_vat_data' => 'Domestic VAT',
    'asycuda_imports' => 'ASYCUDA Data'
];

foreach ($tables as $t => $label) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        echo "$label ($t): $count rows\n";
        
        if ($t === 'import_vat_data') {
            $sum = $pdo->query("SELECT SUM(expert_te) FROM $t")->fetchColumn();
            echo "  Total TE: " . number_format($sum, 2) . "\n";
            $y0 = $pdo->query("SELECT COUNT(*) FROM $t WHERE YEAR(filing_period) = 0 OR filing_period IS NULL")->fetchColumn();
            echo "  Year 0/NULL: $y0 rows\n";
        }
        if ($t === 'asycuda_imports') {
            $sum = $pdo->query("SELECT SUM(exemp_customs) FROM $t")->fetchColumn();
            echo "  Total Customs TE: " . number_format($sum, 2) . "\n";
            $y0 = $pdo->query("SELECT COUNT(*) FROM $t WHERE YEAR(doc_date) = 0 OR doc_date IS NULL")->fetchColumn();
            echo "  Year 0/NULL: $y0 rows\n";
        }
        if ($t === 'te_profit_result') {
            $sum = $pdo->query("SELECT SUM(profit_tax_te) FROM $t")->fetchColumn();
            echo "  Total TE: " . number_format($sum, 2) . "\n";
        }
    } catch (Exception $e) {
        echo "$label ($t): Error - " . $e->getMessage() . "\n";
    }
}
