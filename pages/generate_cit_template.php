<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../vendor/autoload.php";
ini_set("memory_limit", "512M");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

$pdo = new PDO(
    "mysql:host=localhost;port=3306;dbname=tax_ets;charset=utf8mb4",
    "root", "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ============================================================
// FETCH LOOKUP DATA FROM DATABASE
// ============================================================
$provinces = $pdo->query("SELECT province_code, province_name FROM provinces ORDER BY province_code ASC")->fetchAll(PDO::FETCH_ASSOC);
$districts = $pdo->query("
    SELECT d.district_code, d.district_name, d.province_id, p.province_code
    FROM districts d
    LEFT JOIN provinces p ON d.province_id = p.id
    ORDER BY d.district_code ASC
")->fetchAll(PDO::FETCH_ASSOC);
// Villages with their district and province hierarchy
$villageData = $pdo->query("
    SELECT v.village_code, v.village_name, v.district_code,
           d.district_name, d.id AS district_id, d.province_id,
           p.province_code, p.province_name
    FROM villages v
    JOIN districts d ON v.district_code = d.district_code
    JOIN provinces p ON d.province_id = p.id
    ORDER BY v.village_code ASC
")->fetchAll(PDO::FETCH_ASSOC);
$sectors   = $pdo->query("SELECT id, sector_name FROM business_sectors ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("CIT Import");

// ============================================================
// TITLE & VERSION ROWS — with navy fill matching expert template
// ============================================================
$sheet->mergeCells("A1:F1");
$sheet->setCellValue("A1", "Profit Tax (CIT) Recommended Import Template");
$sheet->getStyle("A1")->getFont()->setBold(true)->setSize(12)->setColor(new Color("FFFFFFFF"));
$sheet->getStyle("A1")->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color("1F4E79"));

$sheet->mergeCells("A2:F2");
$sheet->setCellValue("A2", "Template: CIT_IMPORT | Version: 1.0 | Use primary fields first; use User Fallback only when primary data is incomplete.");
$sheet->getStyle("A2")->getFont()->setSize(9)->setColor(new Color("FFFFFFFF"));
$sheet->getStyle("A2")->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color("1F4E79"));

// ============================================================
// HEADERS (Row 4)
// ============================================================
$headers = [
    "A" => "Tax Year",          "B" => "TIN",                "C" => "Company Name",
    "D" => "Investment License Date", "E" => "Province",     "F" => "District",
    "G" => "Village",           "H" => "Sector",              "I" => "Investment Zone",
    "J" => "Revenue LAK",       "K" => "Expense LAK",         "L" => "Net Profit LAK",
    "M" => "Profit Tax Paid LAK", "N" => "Loss carryforward", "O" => "Re-invested Profit LAK",
    "P" => "Reinvest Date",     "Q" => "Registration Date",   "R" => "Tax Holiday Years",
    "S" => "VAT Holder",        "T" => "HR Development",      "U" => "Eco Friendly",
    "V" => "SEZ Developer",     "W" => "SEZ Investor",        "X" => "Production / Services",
    "Y" => "Public Benefit",    "Z" => "Compliant Rental",    "AA" => "Real Estate Transfer",
    "AB" => "Activity 1",       "AC" => "Activity 2",         "AD" => "Activity 3",
    "AE" => "Activity 4",       "AF" => "Activity 5",         "AG" => "Activity 6",
    "AH" => "Activity 7",       "AI" => "Activity 8",         "AJ" => "Activity 9",
    "AK" => "Total Assets LAK", "AL" => "Annual Turnover LAK", "AM" => "Staff Count",
    "AN" => "Stock Exchange Listing Date", "AO" => "Use User Fallback?",
    "AP" => "User Benchmark Rate", "AQ" => "User Benchmark PT", "AR" => "User TE",
    "AS" => "User Fallback Reason", "AT" => "User Comment",
];
foreach ($headers as $col => $label) {
    $sheet->getCell($col . "4")->setValue($label);
}

// ============================================================
// HEADER STYLES — 5-group color scheme
// ============================================================
$headerGroups = [
    [["A","B","C","E","F","J","K","L","M"], "1F4E79", "FFFFFF"],
    [["D","Q","R","AK","AL","AM","AN"], "5B9BD5", "FFFFFF"],
    [["G","H","N","O","P"], "FFFD78", "1F4E79"],
    [["I","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ"], "64748B", "FFFFFF"],
    [["AO","AP","AQ","AR","AS","AT"], "C65911", "FFFFFF"],
];
foreach ($headerGroups as [$cols, $fillRgb, $fontRgb]) {
    foreach ($cols as $col) {
        $sheet->getStyle($col . "4")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FF" . $fontRgb]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => $fillRgb]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER, "wrapText" => true],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]],
        ]);
    }
}

// ============================================================
// DATA CELL STYLES — 5-group colors + borders
// ============================================================
$dataGroups = [
    [["A","B","C","D","E","F","J","K","L","M"], "EAF3F8"],
    [["Q","R","AK","AL","AM","AN"], "F4F9FC"],
    [["G","H","N","O","P"], "FFFD78"],
    [["I","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ"], "FFFFFF"],
    [["AO","AP","AQ","AR","AS","AT"], "FFF2CC"],
];

$allCols = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT"];
foreach ($allCols as $col) {
    $sheet->getStyle($col . "5:" . $col . "1004")->applyFromArray([
        "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]],
    ]);
}
foreach ($dataGroups as [$cols, $fillRgb]) {
    foreach ($cols as $col) {
        $sheet->getStyle($col . "5:" . $col . "1004")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color($fillRgb));
    }
}

// ============================================================
// EXAMPLE DATA ROW (Row 5)
// ============================================================
$example = [
    "A" => 2026, "B" => "123456789-000", "C" => "Example Company Co., Ltd.",
    "E" => "01 | Vientiane Capital", "F" => "0101 | Chanthabuly",
    "H" => "22 | Activities of extraterritorial organizations and bodies",
    "I" => "Zone 1", "J" => 1000000000, "K" => 800000000, "L" => 200000000, "M" => 20000000,
    "S" => "Yes", "T" => "No", "U" => "No", "V" => "No", "W" => "No",
    "X" => "No", "Y" => "No", "Z" => "No", "AA" => "No",
    "AB" => "No", "AC" => "No", "AD" => "No", "AE" => "No",
    "AF" => "No", "AG" => "No", "AH" => "No", "AI" => "No", "AJ" => "No",
    "AK" => 10000000000, "AL" => 1000000000, "AM" => 50, "AO" => "No",
];
foreach ($example as $col => $value) {
    $sheet->getCell($col . "5")->setValue($value);
}

// Example row highlight
$exampleGreenCols = ["A","B","C","D","E","F","I","J","K","L","M","Q","R","S","T","U","V","W","X","Y","Z","AA",
                     "AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT"];
$exampleYellowCols = ["G","H","N","O","P"];
foreach ($exampleGreenCols as $col) {
    $sheet->getStyle($col . "5")->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->setStartColor(new Color("E2F0D9"));
}
foreach ($exampleYellowCols as $col) {
    $sheet->getStyle($col . "5")->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->setStartColor(new Color("FFFD78"));
}

// ============================================================
// COLUMN WIDTHS — matching expert template (Toukta)
// ============================================================
$colWidths = [
    "A" => 14.33, "C" => 28.66, "D" => 14.33, "E" => 27.5,
    "F" => 27.5, "G" => 27.5, "H" => 45, "I" => 13.16,
    "J" => 18.16, "N" => 18.16, "O" => 18.16,
    "P" => 14.33, "Q" => 14.33, "R" => 13.16,
    "AK" => 18.16, "AM" => 14.33, "AN" => 14.33, "AO" => 13.16,
    "AP" => 18.16, "AS" => 27.5,
];
foreach ($colWidths as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

// ============================================================
// NUMBER FORMATS
// ============================================================
$numberFormat = '#,##0';
foreach (["J","K","L","M","N","O","AP","AQ","AR"] as $col) {
    $sheet->getStyle($col . "5:" . $col . "1004")->getNumberFormat()->setFormatCode($numberFormat);
}
foreach (["AK","AL"] as $col) {
    $sheet->getStyle($col . "5:" . $col . "1004")->getNumberFormat()->setFormatCode($numberFormat);
}
$dateFormat = "yyyy\\-mm\\-dd";
foreach (["D","P","Q","AN"] as $col) {
    $sheet->getStyle($col . "5:" . $col . "1004")->getNumberFormat()->setFormatCode($dateFormat);
}
$sheet->getStyle("B5:B1004")->getNumberFormat()->setFormatCode("@");

// ============================================================
// DATA VALIDATIONS
// ============================================================
// Whole number: Tax Year (>=2000)
$v = new DataValidation();
$v->setType(DataValidation::TYPE_WHOLE);
$v->setFormula1("2000");
$v->setAllowBlank(true)->setShowInputMessage(true)->setShowErrorMessage(true);
$sheet->setDataValidation("A5:A1004", $v);

// Decimal validations: Revenue, Expense, Net Profit, PT Paid, Loss cf, Reinvested,
// Total Assets, Annual Turnover, User Benchmark PT, User TE, User Benchmark Rate
foreach (["J","K","L","M","N","O","AK","AL","AP","AQ","AR"] as $col) {
    $v = new DataValidation();
    $v->setType(DataValidation::TYPE_DECIMAL);
    $v->setFormula1("0");
    $v->setAllowBlank(true)->setShowInputMessage(true)->setShowErrorMessage(true);
    $sheet->setDataValidation($col . "5:" . $col . "1004", $v);
}

// Whole number: Tax Holiday Years (>=0), Staff Count (>=0)
foreach (["R","AM"] as $col) {
    $v = new DataValidation();
    $v->setType(DataValidation::TYPE_WHOLE);
    $v->setFormula1("0");
    $v->setAllowBlank(true)->setShowInputMessage(true)->setShowErrorMessage(true);
    $sheet->setDataValidation($col . "5:" . $col . "1004", $v);
}

// Province (col E): Simple list of all province items
$provEndRow = 1 + count($provinces);
$distEndRow = 1 + count($districts);
$villEndRow = 1 + count($villageData);
$sectEndRow = 1 + count($sectors);
$v = new DataValidation();
$v->setType(DataValidation::TYPE_LIST);
$v->setFormula1("'Validation Lists'!\$A\$2:\$A\$" . $provEndRow);
$v->setAllowBlank(true)->setShowDropDown(true);
$sheet->setDataValidation("E5:E1004", $v);

// District (col F): Cascading dropdown — each row filters by province code (first 2 chars of col E)
for ($r = 5; $r <= 1004; $r++) {
    $v = new DataValidation();
    $v->setType(DataValidation::TYPE_LIST);
    $v->setFormula1("OFFSET('Validation Lists'!\$E\$2,MATCH(LEFT(\$E" . $r . ",2),'Validation Lists'!\$D\$2:\$D\$" . $distEndRow . ",0)-1,0,COUNTIF('Validation Lists'!\$D\$2:\$D\$" . $distEndRow . ",LEFT(\$E" . $r . ",2)),1)");
    $v->setAllowBlank(true)->setShowDropDown(true);
    $sheet->setDataValidation("F" . $r, $v);
}

// Village (col G): Cascading dropdown — each row filters by district code (first 4 chars of col F)
for ($r = 5; $r <= 1004; $r++) {
    $v = new DataValidation();
    $v->setType(DataValidation::TYPE_LIST);
    $v->setFormula1("OFFSET('Validation Lists'!\$I\$2,MATCH(LEFT(\$F" . $r . ",4),'Validation Lists'!\$H\$2:\$H\$" . $villEndRow . ",0)-1,0,COUNTIF('Validation Lists'!\$H\$2:\$H\$" . $villEndRow . ",LEFT(\$F" . $r . ",4)),1)");
    $v->setAllowBlank(true)->setShowDropDown(true);
    $sheet->setDataValidation("G" . $r, $v);
}

// Sector (col H): Simple list of all sector items
$v = new DataValidation();
$v->setType(DataValidation::TYPE_LIST);
$v->setFormula1("'Validation Lists'!\$L\$2:\$L\$" . $sectEndRow);
$v->setAllowBlank(true)->setShowDropDown(true);
$sheet->setDataValidation("H5:H1004", $v);

// Yes/No list for boolean flags (cols S-…, Q, R, AO)
$yesNoCols = ["Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AO"];
foreach ($yesNoCols as $col) {
    $v = new DataValidation();
    $v->setType(DataValidation::TYPE_LIST);
    $v->setFormula1("'Validation Lists'!\$O\$2:\$O\$3");
    $v->setAllowBlank(true)->setShowDropDown(true);
    $sheet->setDataValidation($col . "5:" . $col . "1004", $v);
}

// Investment Zone (col I): List zone options
$v = new DataValidation();
$v->setType(DataValidation::TYPE_LIST);
$v->setFormula1("'Validation Lists'!\$P\$2:\$P\$6");
$v->setAllowBlank(true)->setShowDropDown(true);
$sheet->setDataValidation("I5:I1004", $v);

// Fallback Reason (col AS): List fallback options
$v = new DataValidation();
$v->setType(DataValidation::TYPE_LIST);
$v->setFormula1("'Validation Lists'!\$Q\$2:\$Q\$9");
$v->setAllowBlank(true)->setShowDropDown(true);
$sheet->setDataValidation("AS5:AS1004", $v);

// ============================================================
// SHEET 2: Instructions
// ============================================================
$instr = $spreadsheet->createSheet();
$instr->setTitle("Instructions");
$instr->getColumnDimension("A")->setWidth(22.5);
$instr->getColumnDimension("B")->setWidth(31.16);
$instr->getColumnDimension("C")->setWidth(52.5);
$instr->getColumnDimension("D")->setWidth(45);

// Title row
$instr->getCell("A1")->setValue("CIT Import Template - Instructions");
$instr->getStyle("A1")->getFont()->setBold(true)->setSize(14)->setColor(new Color("FFFFFFFF"));
$instr->getStyle("A1")->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color("1F4E79"));

// Row 2 intentionally blank
// Row 3: Header row — navy fill
$instrData = [
    3 => ["Information Type", "Color", "Meaning", "Examples"],
    4 => ["Primary Required", "Dark blue header, light blue input cells", "Must be completed for normal system calculation.", "Tax Year, TIN, Company Name, Province, District, Sector, Revenue, Expense, Net Profit, Profit Tax Paid"],
    5 => ["Primary Conditional", "Medium blue header, near-white input cells", "Required only when the related provision or calculation path applies.", "Investment License Date, Tax Holiday Years, Reinvest Amount, Staff Count, Stock Exchange Listing Date, Village, Sector, Loss carryforward, Re-invested Profit, Reinvest Date"],
    6 => ["Primary Optional", "Slate/gray-blue header, white input cells", "Useful supporting information, but not always required for CIT calculation.", "Village, Investment Zone, VAT Holder, incentive flags"],
    7 => ["User Fallback", "Orange header, light amber input cells", "Optional user-provided benchmark/TE values used only when primary information is incomplete or special judgement is required.", "User Benchmark Rate, User Benchmark PT, User TE, User Fallback Reason"],
    8 => ["Protected Reference Sheets", "Dark blue headers", "Instructions, Validation Lists, and Data Dictionary are read-only to avoid accidental dictionary changes.", "Use the CIT Import sheet for data entry."],
    9 => [], // empty row
    10 => ["Rule", "Description", null, null],
    11 => ["Use primary first", "Complete primary fields before using fallback fields. The system should calculate benchmark and TE from primary data when possible.", null, null],
    12 => ["Use User Fallback?", "Default is No. Select Yes only when fallback values should be considered.", null, null],
    13 => ["Fallback reason", "Required when Use User Fallback? is Yes.", null, null],
    14 => ["Dropdown fields", "Province, District, Village, Sector, Zone, Yes/No, and Fallback Reason should be selected from lists or entered using the coded format (e.g. 01 | Vientiane Capital).", null, null],
    15 => ["Codes", "Dropdown values include stable codes before the pipe symbol, for example 01 | Vientiane Capital.", null, null],
    16 => ["Calculated columns", "Do not add simple calculated system fields in the import sheet. Use fallback fields only for user-provided alternatives.", null, null],
    17 => ["Mandatory fields", "Tax Year, TIN, Company Name, Province, District, Sector, Revenue, Expense, Net Profit, and Profit Tax Paid.", null, null],
    18 => ["Template version", "CIT_IMPORT v1.0", null, null],
    19 => ["Protection password", "TaxETS2026", "Used only to prevent accidental edits to read-only sheets.", null],
];

$fillMap = [
    3 => ["305496", "FFFFFF"],
    4 => ["EAF3F8", "000000"],
    5 => ["F4F9FC", "000000"],
    6 => ["FFFFFF", "000000"],
    7 => ["FFF2CC", "000000"],
    8 => ["FFFFFF", "000000"],
    10 => ["EAF3F8", "000000"],
];

foreach ($instrData as $r => $rowData) {
    $c = 1;
    foreach ($rowData as $val) {
        $instr->getCellByColumnAndRow($c, $r)->setValue($val);
        $c++;
    }
    // Apply fill if defined for this row
    if (isset($fillMap[$r])) {
        list($fillRgb, $fontRgb) = $fillMap[$r];
        for ($cc = 1; $cc <= 4; $cc++) {
            $cell = $instr->getCellByColumnAndRow($cc, $r);
            // Bold font for header rows (3, 10)
            if ($r === 3 || $r === 10) {
                $cell->getStyle()->getFont()->setBold(true)->setColor(new Color("FF" . $fontRgb));
            } else {
                $cell->getStyle()->getFont()->setColor(new Color("FF" . $fontRgb));
            }
            $cell->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color($fillRgb));
        }
    }
}

// ============================================================
// SHEET 3: Validation Lists — each column group starts at row 2
// ============================================================
$valSheet = $spreadsheet->createSheet();
$valSheet->setTitle("Validation Lists");
$valSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);

// Headers
$valHeaders = ["Province Item","Province Code","Province Name","Province Code",
               "District Item","District Code","District Name","District Code",
               "Village Item","Village Code","Village Name",
               "Sector Item","Sector ID","Sector Name",
               "Yes/No","Investment Zone","Fallback Reason"];
foreach ($valHeaders as $idx => $h) {
    $valSheet->getCellByColumnAndRow($idx + 1, 1)->setValue($h);
    $valSheet->getStyleByColumnAndRow($idx + 1, 1)->getFont()->setBold(true);
}

// Province data (cols A-D) — rows 2-19
$r = 2;
foreach ($provinces as $p) {
    $code = $p["province_code"];
    $name = $p["province_name"];
    $item = $code . " | " . $name;
    $valSheet->getCellByColumnAndRow(1, $r)->setValue($item);
    $valSheet->getCellByColumnAndRow(2, $r)->setValue($code);
    $valSheet->getCellByColumnAndRow(3, $r)->setValue($name);
    $valSheet->getCellByColumnAndRow(4, $r)->setValue($code);
    $r++;
}
$provEndRow = $r - 1; // e.g. row 19 for 18 provinces

// District data (cols D-H) — rows 2-149
// Column D = parent province_code (for province->district cascade)
// Column H = district_code (for district->village cascade)
$r = 2;
foreach ($districts as $d) {
    $pCode = $d["province_code"] ?? "00";
    $item = $d["district_code"] . " | " . $d["district_name"];
    $valSheet->getCellByColumnAndRow(4, $r)->setValue($pCode);      // D = province_code for cascade
    $valSheet->getCellByColumnAndRow(5, $r)->setValue($item);        // E = district_item
    $valSheet->getCellByColumnAndRow(6, $r)->setValue($d["district_code"]); // F = district_code
    $valSheet->getCellByColumnAndRow(7, $r)->setValue($d["district_name"]); // G = district_name
    $valSheet->getCellByColumnAndRow(8, $r)->setValue($d["district_code"]); // H = district_code for cascade
    $r++;
}
$distEndRow = $r - 1;

// Village data (cols H-K) — rows 2-8829
// Column H = parent district_code (overwrites district section's H, continues to fill)
$r = 2;
foreach ($villageData as $v) {
    $item = $v["village_code"] . " | " . $v["village_name"];
    $distCode = substr($v["village_code"], 0, 4);
    $valSheet->getCellByColumnAndRow(8, $r)->setValue($distCode);   // H = district_code for cascade
    $valSheet->getCellByColumnAndRow(9, $r)->setValue($item);        // I = village_item
    $valSheet->getCellByColumnAndRow(10, $r)->setValue($v["village_code"]); // J = village_code
    $valSheet->getCellByColumnAndRow(11, $r)->setValue($v["village_name"]); // K = village_name
    $r++;
}
$villEndRow = $r - 1;

// Sector data (cols L-N) — rows 2-29
$r = 2;
foreach ($sectors as $s) {
    $item = $s["id"] . " | " . $s["sector_name"];
    $valSheet->getCellByColumnAndRow(12, $r)->setValue($item);
    $valSheet->getCellByColumnAndRow(13, $r)->setValue($s["id"]);
    $valSheet->getCellByColumnAndRow(14, $r)->setValue($s["sector_name"]);
    $r++;
}
$sectEndRow = $r - 1;

// Reference data (cols O-Q) — rows 2-12
$refData = [
    [15 => "No",  16 => null, 17 => null],
    [15 => "Yes", 16 => null, 17 => null],
    [15 => null,  16 => "Zone 1",             17 => null],
    [15 => null,  16 => "Zone 2",             17 => null],
    [15 => null,  16 => "Zone 3",             17 => null],  // intentionally 3 not 4
    [15 => null,  16 => null,                  17 => "Missing staff count"],
    [15 => null,  16 => null,                  17 => "Missing annual turnover"],
    [15 => null,  16 => null,                  17 => "Missing total assets"],
    [15 => null,  16 => null,                  17 => "Missing revenue"],
    [15 => null,  16 => null,                  17 => "Missing sector classification"],
    [15 => null,  16 => null,                  17 => "Special tax treatment"],
    [15 => null,  16 => null,                  17 => "Other"],
];
$r = 2;
foreach ($refData as $rd) {
    foreach ($rd as $col => $val) {
        if ($val !== null) {
            $valSheet->getCellByColumnAndRow($col, $r)->setValue($val);
        }
    }
    $r++;
}
$yesNoEndRow = 3;   // rows 2-3
$zoneEndRow = 5;    // row 5 (Zone 3)
$fallbackEndRow = $r - 1;  // row 13 if 12 items, or 9 if 8 items

// ============================================================
// SHEET 4: Data Dictionary — one row per village with hierarchy
// ============================================================
$dictSheet = $spreadsheet->createSheet();
$dictSheet->setTitle("Data Dictionary");
$dictSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);

$dictHeaders = ["Province Code","Province Name","Dropdown Value",null,
                "Province Code","District Code","District Name","Dropdown Value",null,
                "District Code","Village Code","Village Name","Dropdown Value",null,
                "Sector ID","Sector Name","Dropdown Value"];
foreach ($dictHeaders as $idx => $h) {
    if ($h !== null) {
        $dictSheet->getCellByColumnAndRow($idx + 1, 1)->setValue($h);
        $dictSheet->getStyleByColumnAndRow($idx + 1, 1)->getFont()->setBold(true);
    }
}

$row = 2;
$sectorIdx = 0;
$sectTotal = count($sectors);
foreach ($villageData as $v) {
    $s = $sectors[$sectorIdx % $sectTotal];
    $sectorIdx++;

    // Province (cols A-C)
    $dictSheet->getCellByColumnAndRow(1, $row)->setValue($v["province_code"]);
    $dictSheet->getCellByColumnAndRow(2, $row)->setValue($v["province_name"]);
    $dictSheet->getCellByColumnAndRow(3, $row)->setValue($v["province_code"] . " | " . $v["province_name"]);

    // District (cols E-H)
    $dictSheet->getCellByColumnAndRow(5, $row)->setValue($v["province_code"]);
    $dictSheet->getCellByColumnAndRow(6, $row)->setValue($v["district_code"]);
    $dictSheet->getCellByColumnAndRow(7, $row)->setValue($v["district_name"]);
    $dictSheet->getCellByColumnAndRow(8, $row)->setValue($v["district_code"] . " | " . $v["district_name"]);

    // Village (cols J-M)
    $dictSheet->getCellByColumnAndRow(10, $row)->setValue($v["district_code"]);
    $dictSheet->getCellByColumnAndRow(11, $row)->setValue($v["village_code"]);
    $dictSheet->getCellByColumnAndRow(12, $row)->setValue($v["village_name"]);
    $dictSheet->getCellByColumnAndRow(13, $row)->setValue($v["village_code"] . " | " . $v["village_name"]);

    // Sector (cols O-Q)
    $dictSheet->getCellByColumnAndRow(15, $row)->setValue($s["id"]);
    $dictSheet->getCellByColumnAndRow(16, $row)->setValue($s["sector_name"]);
    $dictSheet->getCellByColumnAndRow(17, $row)->setValue($s["id"] . " | " . $s["sector_name"]);

    $row++;
}

// ============================================================
// SHEET 5: Change Log
// ============================================================
$changeSheet = $spreadsheet->createSheet();
$changeSheet->setTitle("Change Log");
$changeSheet->getColumnDimension("A")->setWidth(13.66);
$changeSheet->getColumnDimension("C")->setWidth(20);
$changeSheet->getColumnDimension("D")->setWidth(65);

$changeSheet->getCellByColumnAndRow(1, 1)->setValue("Version");
$changeSheet->getCellByColumnAndRow(2, 1)->setValue("Date");
$changeSheet->getCellByColumnAndRow(3, 1)->setValue("Owner");
$changeSheet->getCellByColumnAndRow(4, 1)->setValue("Change");
foreach (range(1,4) as $cc) {
    $changeSheet->getStyleByColumnAndRow($cc, 1)->getFont()->setBold(true);
}

$changeSheet->getCellByColumnAndRow(1, 2)->setValue("1.0");
$changeSheet->getStyle("A2")->getNumberFormat()->setFormatCode("@");
$changeSheet->getCellByColumnAndRow(2, 2)->setValue("2026-06-05");
$changeSheet->getStyle("B2")->getNumberFormat()->setFormatCode("@");
$changeSheet->getCellByColumnAndRow(3, 2)->setValue("APIS / Tax-ETS");
$changeSheet->getCellByColumnAndRow(4, 2)->setValue("Initial recommended CIT template with primary and user fallback groups.");

$spreadsheet->setActiveSheetIndex(0);

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"CIT_Import_Template.xlsx\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
