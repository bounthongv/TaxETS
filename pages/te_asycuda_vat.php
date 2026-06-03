<?php
$asycudaTeConfig = [
    "title" => "Import VAT TE (ASYCUDA)",
    "detail_title" => "Import VAT TE: Batch Results",
    "description" => "Calculate and analyze Tax Expenditure for Import VAT from ASYCUDA imports.",
    "icon" => "fas fa-file-invoice-dollar",
    "theme" => "info",
    "table_class" => "table-info",
    "te_text_class" => "text-danger",
    "paid_column" => "paid_vat",
    "paid_label" => "Paid VAT",
    "benchmark_column" => "exempt_vat",
    "benchmark_label" => "BM VAT",
    "te_column" => "vat_te",
    "te_label" => "Import VAT TE",
    "expert_column" => "te_vat_excel",
    "calc_total_key" => "total_vat",
];

require_once __DIR__ . "/te_asycuda_common.php";
