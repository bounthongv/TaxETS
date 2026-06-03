<?php
$asycudaTeConfig = [
    "title" => "Customs Duty TE (ASYCUDA)",
    "detail_title" => "Customs Duty TE: Batch Results",
    "description" => "Calculate and analyze Tax Expenditure for Customs Duty from ASYCUDA imports.",
    "icon" => "fas fa-gavel",
    "theme" => "primary",
    "table_class" => "table-primary",
    "te_text_class" => "text-danger",
    "paid_column" => "paid_customs",
    "paid_label" => "Paid Customs",
    "benchmark_column" => "exemp_customs",
    "benchmark_label" => "BM Customs",
    "te_column" => "customs_te",
    "te_label" => "Customs TE",
    "expert_column" => "te_customs_excel",
    "calc_total_key" => "total_customs",
];

require_once __DIR__ . "/te_asycuda_common.php";
