<?php
$asycudaTeConfig = [
    "title" => "Excise Tax TE (ASYCUDA)",
    "detail_title" => "Excise Tax TE: Batch Results",
    "description" => "Calculate and analyze Tax Expenditure for Excise Tax from ASYCUDA imports.",
    "icon" => "fas fa-beer",
    "theme" => "warning",
    "table_class" => "table-warning",
    "te_text_class" => "text-danger",
    "paid_column" => "paid_excise",
    "paid_label" => "Paid Excise",
    "benchmark_column" => "exempt_excise",
    "benchmark_label" => "BM Excise",
    "te_column" => "excise_te",
    "te_label" => "Excise TE",
    "expert_column" => "te_excise_excel",
    "calc_total_key" => "total_excise",
];

require_once __DIR__ . "/te_asycuda_common.php";
