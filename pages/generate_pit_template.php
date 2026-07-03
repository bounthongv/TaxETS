<?php
/**
 * Generate PIT (Individual Tax) Import Template
 * Based on expert-confirmed template: PIT-template-apis.xlsx v1.0
 * 
 * Run: php -d memory_limit=512M pages/generate_pit_template.php
 * Output: tests/generated_pit_template.xlsx
 */

require_once __DIR__ . "/../vendor/autoload.php";
ini_set("memory_limit", "512M");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// ============================================================
// TEXT CONSTANTS
// ============================================================
$YES_NO    = ["Yes", "No"];
$FALLBACK  = [
    "Missing tax paid data",
    "Missing taxable base detail",
    "Special legal treatment",
    "Legacy assessment",
    "Manual expert review",
    "User judgment",
    "Other",
];
// PIT Provisions (21-30) with their primary amount field and fallback field
$PROVISIONS = [
    ["id" => "21", "name" => "Overtime",              "primary" => "Amount #21 Overtime LAK",              "fallback" => "User TE #21"],
    ["id" => "22", "name" => "Uniform / Safety",       "primary" => "Amount #22 Uniform / Safety LAK",       "fallback" => "User TE #22"],
    ["id" => "23.1", "name" => "Spouse Allowance",     "primary" => "Amount #23.1 Spouse Allowance LAK",     "fallback" => "User TE #23.1"],
    ["id" => "23.2", "name" => "Child Allowance",      "primary" => "Amount #23.2 Child Allowance LAK",      "fallback" => "User TE #23.2"],
    ["id" => "24", "name" => "Government Allowance",   "primary" => "Amount #24 Government Allowance LAK",   "fallback" => "User TE #24"],
    ["id" => "25", "name" => "Student Allowance",      "primary" => "Amount #25 Student Allowance LAK",      "fallback" => "User TE #25"],
    ["id" => "26", "name" => "Share Sale Profit",      "primary" => "Amount #26 Share Sale Profit LAK",      "fallback" => "User TE #26"],
    ["id" => "27", "name" => "Dividend Income",        "primary" => "Amount #27 Dividend Income LAK",        "fallback" => "User TE #27"],
    ["id" => "28.1", "name" => "Deposit Interest",     "primary" => "Amount #28.1 Deposit Interest LAK",     "fallback" => "User TE #28.1"],
    ["id" => "28.2", "name" => "Bond Interest",        "primary" => "Amount #28.2 Bond Interest LAK",        "fallback" => "User TE #28.2"],
    ["id" => "29", "name" => "Security Bonus",         "primary" => "Amount #29 Security Bonus LAK",         "fallback" => "User TE #29"],
    ["id" => "30", "name" => "Social Security",        "primary" => "Social Security Contribution LAK #30",  "fallback" => "User TE #30"],
];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("PIT Import");

// ============================================================
// TITLE & VERSION ROWS
// ============================================================
$sheet->mergeCells("A1:AI1");
$sheet->setCellValue("A1", "Individual Tax (PIT) Recommended Import Template");
$sheet->getStyle("A1")->getFont()->setBold(true)->setSize(12)->setColor(new Color("FFFFFFFF"));
$sheet->getStyle("A1")->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color("1F4E79"));

$sheet->mergeCells("A2:AI2");
$sheet->setCellValue("A2", "Template: PIT_IMPORT | Version: 1.0 | Use primary fields first; use User Fallback only when primary data is incomplete.");
$sheet->getStyle("A2")->getFont()->setSize(9)->setColor(new Color("FFFFFFFF"));
$sheet->getStyle("A2")->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color("1F4E79"));

// ============================================================
// COLUMN HEADERS (Row 4)
// ============================================================
$headers = [
    "A"  => "Filing Date",
    "B"  => "Tax Year",
    "C"  => "PTIN",
    "D"  => "Individual Name",
    "E"  => "Amount #21 Overtime LAK",
    "F"  => "Amount #22 Uniform / Safety LAK",
    "G"  => "Amount #23.1 Spouse Allowance LAK",
    "H"  => "Amount #23.2 Child Allowance LAK",
    "I"  => "Amount #24 Government Allowance LAK",
    "J"  => "Amount #25 Student Allowance LAK",
    "K"  => "Amount #26 Share Sale Profit LAK",
    "L"  => "Lao Stock Exchange Listed?",
    "M"  => "Amount #27 Dividend Income LAK",
    "N"  => "Amount #28.1 Deposit Interest LAK",
    "O"  => "Deposit in Banking System?",
    "P"  => "Amount #28.2 Bond Interest LAK",
    "Q"  => "Amount #29 Security Bonus LAK",
    "R"  => "Social Security Member?",
    "S"  => "Social Security Contribution LAK #30",
    "T"  => "Use User Fallback?",
    "U"  => "User TE #21",
    "V"  => "User TE #22",
    "W"  => "User TE #23.1",
    "X"  => "User TE #23.2",
    "Y"  => "User TE #24",
    "Z"  => "User TE #25",
    "AA" => "User TE #26",
    "AB" => "User TE #27",
    "AC" => "User TE #28.1",
    "AD" => "User TE #28.2",
    "AE" => "User TE #29",
    "AF" => "User TE #30",
    "AG" => "User TE Total",
    "AH" => "User Fallback Reason",
    "AI" => "User Comment",
];

foreach ($headers as $col => $label) {
    $sheet->getCell($col . "4")->setValue($label);
}

// ============================================================
// HEADER STYLES — 4-group color scheme (like expert template)
// ============================================================
$headerGroups = [
    // Primary Required (Dark blue header, light blue fill)
    [["B", "C"], "1F4E79", "D6E4F0"],
    // Primary Conditional (Medium blue)
    [["E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S"], "5B9BD5", "E9F0F9"],
    // Primary Optional (Slate gray)
    [["A", "D"], "64748B", "FFFFFF"],
    // User Fallback (Orange)
    [["T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI"], "C65911", "FFF2CC"],
];

$allHeaderCols = [];
foreach ($headerGroups as $group) {
    [$cols, $bgColor, $fgColor] = $group;
    foreach ($cols as $col) {
        $allHeaderCols[] = $col;
        $cell = $sheet->getCell($col . "4");
        $cell->getStyle()->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => $bgColor]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER, "wrapText" => true],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]],
        ]);
    }
}

// ============================================================
// DATA ROW 5 — Example row
// ============================================================
$sheet->getCell("B5")->setValue(2026);
$sheet->getCell("C5")->setValue(12345678);
$sheet->getCell("D5")->setValue("Example Individual");
$sheet->getCell("E5")->setValue(20000000);
$sheet->getCell("F5")->setValue(0);
$sheet->getCell("G5")->setValue(0);
$sheet->getCell("H5")->setValue(0);
$sheet->getCell("I5")->setValue(0);
$sheet->getCell("J5")->setValue(0);
$sheet->getCell("K5")->setValue(100000000);
$sheet->getCell("L5")->setValue("Yes");
$sheet->getCell("M5")->setValue(0);
$sheet->getCell("N5")->setValue(0);
$sheet->getCell("O5")->setValue("No");
$sheet->getCell("P5")->setValue(0);
$sheet->getCell("Q5")->setValue(25000000);
$sheet->getCell("R5")->setValue("No");
$sheet->getCell("S5")->setValue(0);
$sheet->getCell("T5")->setValue("No");

// Highlight example row
$sheet->getStyle("A5:AI5")->applyFromArray([
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFF3CD"]],
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]],
]);

// Comment on row 6 (expert template note)
$sheet->getCell("D6")->setValue("keep it as optional (in fact no need)");
$sheet->getStyle("D6")->getFont()->setItalic(true)->setSize(9)->setColor(new Color("808080"));

// ============================================================
// COLUMN WIDTHS
// ============================================================
$colWidths = [
    "A" => 15, "B" => 15, "C" => 15, "D" => 29.83,
    "E" => 19.33, "F" => 13, "G" => 13, "H" => 13,
    "I" => 13, "J" => 13, "K" => 13, "L" => 15,
    "M" => 19.33, "N" => 13, "O" => 15, "P" => 19.33,
    "Q" => 13, "R" => 30.33, "S" => 19.33, "T" => 15,
    "U" => 13, "V" => 13, "W" => 13, "X" => 13,
    "Y" => 13, "Z" => 13, "AA" => 13, "AB" => 13,
    "AC" => 13, "AD" => 13, "AE" => 13, "AF" => 13,
    "AG" => 13, "AH" => 27.5, "AI" => 13,
];
foreach ($colWidths as $col => $w) {
    $sheet->getColumnDimension($col)->setWidth($w);
}

// ============================================================
// NUMBER FORMATS
// ============================================================
// Amount columns: #,##0 format
$amountCols = ["E","F","G","H","I","J","K","M","N","P","Q","S","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG"];
foreach ($amountCols as $col) {
    $sheet->getStyle($col . "5:" . $col . "1004")->getNumberFormat()->setFormatCode("#,##0");
}
// Tax Year column: 0 format
$sheet->getStyle("B5:B1004")->getNumberFormat()->setFormatCode("0");
// Filing Date column: date format
$sheet->getStyle("A5:A1004")->getNumberFormat()->setFormatCode("yyyy\\-mm\\-dd");

// ============================================================
// DATA VALIDATIONS — Yes/No Dropdowns
// ============================================================
// Create Validation Lists sheet first

// ============================================================
// STYLES FOR ALL DATA ROWS (borders)
// ============================================================
$allDataCols = array_keys($headers);
foreach ($allDataCols as $col) {
    $sheet->getStyle($col . "5:" . $col . "1004")->applyFromArray([
        "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]],
    ]);
}

// ============================================================
// FREEZE PANES
// ============================================================
$sheet->freezePane("A5");

// ============================================================
// INSTRUCTIONS SHEET
// ============================================================
$instSheet = $spreadsheet->createSheet();
$instSheet->setTitle("Instructions");
$instSheet->getCell("A1")->setValue("PIT Import Template - Instructions");
$instSheet->getStyle("A1")->getFont()->setBold(true)->setSize(14);

$instSheet->getColumnDimension("A")->setWidth(25);
$instSheet->getColumnDimension("B")->setWidth(20);
$instSheet->getColumnDimension("C")->setWidth(50);
$instSheet->getColumnDimension("D")->setWidth(30);

$instSheet->getCell("A3")->setValue("Information Type");
$instSheet->getCell("B3")->setValue("Color");
$instSheet->getCell("C3")->setValue("Meaning");
$instSheet->getCell("D3")->setValue("Examples");
$instSheet->getStyle("A3:D3")->getFont()->setBold(true);

$colorGroups = [
    ["Primary Required",    "1F4E79", "Must be completed for every PIT record",        "Tax Year, PTIN"],
    ["Primary Conditional", "5B9BD5", "Complete only when the related PIT provision applies", "Amount #21-#30"],
    ["Primary Optional",    "64748B", "Helpful for review but not always required",        "Filing Date, Individual Name"],
    ["User Fallback",       "C65911", "Optional user-provided TE values used only when primary data is incomplete", "Use User Fallback?, User TE #21-30"],
];

$r = 4;
foreach ($colorGroups as $group) {
    $instSheet->getCell("A" . $r)->setValue($group[0]);
    $instSheet->getCell("C" . $r)->setValue($group[2]);
    $instSheet->getCell("D" . $r)->setValue($group[3]);
    // Color square in column B
    $instSheet->getCell("B" . $r)->setValue("███");
    $instSheet->getStyle("B" . $r)->getFont()->setColor(new Color($group[1]));
    $r++;
}

$instSheet->getCell("A" . ($r + 1))->setValue("Protected Reference Sheets");
$instSheet->getStyle("A" . ($r + 1))->getFont()->setBold(true);
$instSheet->getCell("C" . ($r + 1))->setValue("Instructions, Validation Lists, Data Dictionary, Change Log");

// Rules section
$r += 3;
$instSheet->getCell("A" . $r)->setValue("Rule");
$instSheet->getCell("B" . $r)->setValue("Description");
$instSheet->getStyle("A" . $r . ":B" . $r)->getFont()->setBold(true);

$rules = [
    ["Use first sheet only", "Users should enter data only in PIT Import sheet."],
    ["Use primary first", "Enter raw income or allowance amounts in #21-#30 fields. Leave User TE blank."],
    ["No simple Excel calculations", "Do not add calculated TE columns manually. The system calculates from primary amounts."],
    ["Fallback rule", "Use User Fallback only when no automated calculation is possible."],
    ["Fallback reason", "Required when Use User Fallback? is Yes."],
    ["Yes/No fields", "Select Yes or No from dropdown."],
    ["Mandatory fields", "Tax Year and PTIN are required."],
    ["Expert file comparison", "The first expert workbook included many calculated TE columns. This is the simplified recommended version."],
    ["Template version", "PIT_IMPORT v1.0"],
];

foreach ($rules as $i => $rule) {
    $row = $r + 1 + $i;
    $instSheet->getCell("A" . $row)->setValue($rule[0]);
    $instSheet->getCell("B" . $row)->setValue($rule[1]);
}
$instSheet->getStyle("A" . ($r+1) . ":B" . ($r+count($rules)))->applyFromArray([
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]],
]);

// ============================================================
// VALIDATION LISTS SHEET
// ============================================================
$valSheet = $spreadsheet->createSheet();
$valSheet->setTitle("Validation Lists");

$valSheet->getCell("A1")->setValue("Yes/No");
$valSheet->getCell("B1")->setValue("Fallback Reason");

foreach ($YES_NO as $i => $v) {
    $valSheet->getCell("A" . ($i + 2))->setValue($v);
}
foreach ($FALLBACK as $i => $v) {
    $valSheet->getCell("B" . ($i + 2))->setValue($v);
}
$valSheet->getStyle("A1:B1")->getFont()->setBold(true);

// PIT Provision Reference
$valSheet->getCell("D1")->setValue("Provision");
$valSheet->getCell("E1")->setValue("Legal Basis");
$valSheet->getCell("F1")->setValue("Type");
$valSheet->getCell("G1")->setValue("Description");
$valSheet->getCell("H1")->setValue("Purpose");
$valSheet->getCell("I1")->setValue("Primary Field");
$valSheet->getCell("J1")->setValue("Fallback Field");
$valSheet->getStyle("D1:J1")->getFont()->setBold(true);

$provisionRefs = [
    ["21",   "ITL, Art. 35.3",  "Exemption", "Overtime payments",                      "Social",          "Amount #21 Overtime LAK",          "User TE #21"],
    ["22",   "ITL, Art. 35.20", "Exemption", "Uniforms and safety equipment",          "Social / Health", "Amount #22 Uniform / Safety LAK",  "User TE #22"],
    ["23.1", "ITL, Art.35.3",   "Exemption", "Spouse allowance",                       "Social",          "Amount #23.1 Spouse Allowance LAK", "User TE #23.1"],
    ["23.2", "ITL, Art.35.3",   "Exemption", "Child allowance",                        "Social",          "Amount #23.2 Child Allowance LAK",  "User TE #23.2"],
    ["24",   "ITL, Art.35.3",   "Exemption", "Government allowance",                   "Social",          "Amount #24 Government Allowance LAK", "User TE #24"],
    ["25",   "ITL, Art.35.3",   "Exemption", "Student allowances",                     "Social",          "Amount #25 Student Allowance LAK",  "User TE #25"],
    ["26",   "ITL, Art.35.4",   "Exemption", "Share sale profits on LSX",              "Financial Sector","Amount #26 Share Sale Profit LAK", "User TE #26"],
    ["27",   "ITL, Art.35.4",   "Exemption", "Dividends from LSX-listed companies",    "Financial Sector","Amount #27 Dividend Income LAK",   "User TE #27"],
    ["28.1", "ITL, Art.35.11",  "Exemption", "Bank deposit interest",                  "Encourage Savings","Amount #28.1 Deposit Interest LAK", "User TE #28.1"],
    ["28.2", "ITL, Art.35.11",  "Exemption", "Bond interest",                          "Encourage Savings","Amount #28.2 Bond Interest LAK",   "User TE #28.2"],
    ["29",   "ITL, Art.35.13",  "Exemption", "Security/performance bonus",             "National Security","Amount #29 Security Bonus LAK",    "User TE #29"],
    ["30",   "Notice 0824/LSO", "Deduction", "Social security contributions",          "Encourage Savings","Social Security Contribution LAK #30", "User TE #30"],
];

foreach ($provisionRefs as $i => $row) {
    $rowNum = $i + 2;
    $valSheet->getCell("D" . $rowNum)->setValue($row[0]);  // Provision ID
    $valSheet->getCell("E" . $rowNum)->setValue($row[1]);  // Legal Basis
    $valSheet->getCell("F" . $rowNum)->setValue($row[2]);  // Type
    $valSheet->getCell("G" . $rowNum)->setValue($row[3]);  // Description
    $valSheet->getCell("H" . $rowNum)->setValue($row[4]);  // Purpose
    $valSheet->getCell("I" . $rowNum)->setValue($row[5]);  // Primary Field
    $valSheet->getCell("J" . $rowNum)->setValue($row[6]);  // Fallback Field
}

$valSheet->getColumnDimension("A")->setWidth(12);
$valSheet->getColumnDimension("B")->setWidth(35);
$valSheet->getColumnDimension("D")->setWidth(12);
$valSheet->getColumnDimension("E")->setWidth(25);
$valSheet->getColumnDimension("F")->setWidth(15);
$valSheet->getColumnDimension("G")->setWidth(40);
$valSheet->getColumnDimension("H")->setWidth(22);
$valSheet->getColumnDimension("I")->setWidth(35);
$valSheet->getColumnDimension("J")->setWidth(20);

// ============================================================
// DATA DICTIONARY SHEET
// ============================================================
$dictSheet = $spreadsheet->createSheet();
$dictSheet->setTitle("Data Dictionary");

$dictSheet->getCell("A1")->setValue("PIT Provision");
$dictSheet->getCell("B1")->setValue("Legal Basis");
$dictSheet->getCell("C1")->setValue("Type");
$dictSheet->getCell("D1")->setValue("Description");
$dictSheet->getCell("E1")->setValue("Purpose");
$dictSheet->getCell("F1")->setValue("Primary Amount Field");
$dictSheet->getCell("G1")->setValue("User Fallback Field");
$dictSheet->getStyle("A1:G1")->getFont()->setBold(true);

// Same data as validation lists but extended
foreach ($provisionRefs as $i => $row) {
    $rowNum = $i + 2;
    for ($c = 0; $c < 7; $c++) {
        $dictSheet->getCell(chr(65 + $c) . $rowNum)->setValue($row[$c]);
    }
}

$dictSheet->getColumnDimension("A")->setWidth(15);
$dictSheet->getColumnDimension("B")->setWidth(25);
$dictSheet->getColumnDimension("C")->setWidth(15);
$dictSheet->getColumnDimension("D")->setWidth(40);
$dictSheet->getColumnDimension("E")->setWidth(22);
$dictSheet->getColumnDimension("F")->setWidth(35);
$dictSheet->getColumnDimension("G")->setWidth(20);

// ============================================================
// CHANGE LOG SHEET
// ============================================================
$chgSheet = $spreadsheet->createSheet();
$chgSheet->setTitle("Change Log");

$chgSheet->getCell("A1")->setValue("Version");
$chgSheet->getCell("B1")->setValue("Date");
$chgSheet->getCell("C1")->setValue("Owner");
$chgSheet->getCell("D1")->setValue("Change");
$chgSheet->getStyle("A1:D1")->getFont()->setBold(true);

$chgSheet->getCell("A2")->setValue("1.0");
$chgSheet->getCell("B2")->setValue(date("Y-m-d"));
$chgSheet->getCell("C2")->setValue("Tax-ETS Generator");
$chgSheet->getCell("D2")->setValue("Generated PIT template with primary provision amounts and user fallback fields.");

$chgSheet->getColumnDimension("A")->setWidth(12);
$chgSheet->getColumnDimension("B")->setWidth(15);
$chgSheet->getColumnDimension("C")->setWidth(25);
$chgSheet->getColumnDimension("D")->setWidth(60);

// ============================================================
// HIDE REFERENCE SHEETS
// ============================================================
$valSheet->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
$dictSheet->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);

// ============================================================
// DATA VALIDATIONS — Dropdowns on PIT Import
// ============================================================
// Yes/No columns: L, O, R, T
$yesNoCols = ["L", "O", "R", "T"];
$yesNoEndRow = 1 + count($YES_NO); // row 3

foreach ($yesNoCols as $col) {
    $v = new DataValidation();
    $v->setType(DataValidation::TYPE_LIST);
    $v->setFormula1("'Validation Lists'!\$A\$2:\$A\${$yesNoEndRow}");
    $v->setAllowBlank(true);
    $v->setShowDropDown(true); // PhpSpreadsheet inverts: true = show arrow
    $sheet->setDataValidation($col . "5:" . $col . "1004", $v);
}

// User Fallback Reason dropdown
$fbEndRow = 1 + count($FALLBACK);
$v2 = new DataValidation();
$v2->setType(DataValidation::TYPE_LIST);
$v2->setFormula1("'Validation Lists'!\$B\$2:\$B\${$fbEndRow}");
$v2->setAllowBlank(true);
$v2->setShowDropDown(true);
$sheet->setDataValidation("AH5:AH1004", $v2);

// ============================================================
// OUTPUT — browser download vs CLI save
// ============================================================
if (PHP_SAPI === 'cli') {
    $outputDir = __DIR__ . "/../tests";
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    $outputFile = $outputDir . "/generated_pit_template.xlsx";
    $writer = new Xlsx($spreadsheet);
    $writer->save($outputFile);
    echo "✅ PIT template generated: " . realpath($outputFile) . "\n";
    echo "   Sheets: " . implode(", ", $spreadsheet->getSheetNames()) . "\n";
} else {
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=\"PIT_Import_Template.xlsx\"");
    header("Cache-Control: max-age=0");
    $spreadsheet->setActiveSheetIndex(0);
    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");
    exit;
}
