<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();

echo "=== DETAILED PER-INDIVIDUAL ANALYSIS ===\n\n";

$records = $pdo->query("SELECT d.*, r.employment_income, r.other_income, r.actual_tax_paid, r.benchmark_calculated_tax, r.te_amount, r.matched_provisions
    FROM import_pit_data d 
    LEFT JOIN te_individual_result r ON r.tin = d.ptin COLLATE utf8mb4_unicode_ci AND r.tax_year = d.tax_year
    ORDER BY d.ptin")->fetchAll(PDO::FETCH_ASSOC);

foreach ($records as $r) {
    $name = $r['employee_name'];
    $year = $r['tax_year'];
    $exp_total = (float)$r['expert_te_total'];
    $sys_te = (float)$r['te_amount'];
    
    echo "=== {$name} (PTIN={$r['ptin']}, Year={$year}) ===\n";
    echo "Expert Total TE: " . number_format($exp_total, 0) . " LAK\n";
    
    // Per-provision breakdown
    $provisions = ['21','22','23_1','23_2','24','25','26','27','28_1','28_2','29'];
    $prov_names = [
        '21'=>'Overtime','22'=>'Uniforms','23_1'=>'Spouse allow.','23_2'=>'Child allow.',
        '24'=>'Govt allow.','25'=>'Student allow.','26'=>'Shares','27'=>'Dividends',
        '28_1'=>'Interest deposits','28_2'=>'Interest bonds','29'=>'Perf. bonus'
    ];
    $emp_provs = ['21','22','24','25','29'];
    $other_provs = ['23_1','23_2','26','27','28_1','28_2'];
    
    $system_emp_inc = 0;
    $system_other_inc = 0;
    
    echo "\nProvision breakdown:\n";
    echo str_pad("Prov", 14) . str_pad("Amount", 16) . str_pad("Exp TE", 16) . str_pad("Exp Rate", 10) . "Type\n";
    echo str_pad("", 60, "-") . "\n";
    
    $emp_amount = 0;
    $other_amount = 0;
    $prov_expert_tes = [];
    
    foreach ($provisions as $p) {
        $amt_col = 'amount_' . $p;
        $te_col = 'expert_te_' . $p;
        $amt = (float)$r[$amt_col];
        $te = (float)$r[$te_col];
        $rate = ($amt > 0) ? ($te / $amt * 100) : 0;
        $type = in_array($p, $emp_provs) ? "Employment" : "Other";
        
        if (in_array($p, $emp_provs)) $emp_amount += $amt;
        if (in_array($p, $other_provs)) $other_amount += $amt;
        if ($te > 0) $prov_expert_tes[$p] = $te;
        
        if ($amt > 0 || $te > 0) {
            echo str_pad($prov_names[$p] . " ($p)", 14) . str_pad(number_format($amt, 0), 16) 
                . str_pad(number_format($te, 0), 16) . str_pad(number_format($rate, 1) . "%", 10) 
                . $type . "\n";
        }
    }
    
    $ss = (int)$r['is_ss_member'];
    echo "\nSS Member: " . ($ss ? "Yes (Prov 30)" : "No") . "\n";
    
    echo "\nSystem calculation:\n";
    echo "  Employment income (21+22+24+25+29): " . number_format($emp_amount, 0) . "\n";
    echo "  Other income (23+26+27+28): " . number_format($other_amount, 0) . "\n";
    echo "  System employment inc: " . number_format((float)$r['employment_income'], 0) . "\n";
    echo "  System other inc: " . number_format((float)$r['other_income'], 0) . "\n";
    echo "  System benchmark tax: " . number_format((float)$r['benchmark_calculated_tax'], 0) . "\n";
    echo "  System actual tax paid: " . number_format((float)$r['actual_tax_paid'], 0) . "\n";
    echo "  System TE: " . number_format($sys_te, 0) . "\n";
    echo "  Expert TE: " . number_format($exp_total, 0) . "\n";
    echo "  Difference: " . number_format($sys_te - $exp_total, 0) . "\n";
    
    // Actual = total_income * 0.10 - expert_te_total
    $total_income = $emp_amount + $other_amount;
    $derived_actual = max(0, $total_income * 0.10 - $exp_total);
    echo "  Derived actual (total_inc*10% - ExpTE): " . number_format($derived_actual, 0) . "\n";
    
    echo "\n";
}
