<?php
/** Generate Royalty Fee Import Template (.xlsx) - 16 columns A-P, expert-confirmed v1.0 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Font, Alignment, Border};
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$pdo = getDbConnection();
$spreadsheet = new Spreadsheet();

define('CLR_REQUIRED','1F4E79'); define('CLR_OPTIONAL','64748B'); define('CLR_FALLBACK','C65911');
define('CLR_INPUT_REQ','D6E0F0'); define('CLR_INPUT_OPT','FFFFFF'); define('CLR_INPUT_FB','FCE4D6');

function sH(Worksheet $w,string $c,string $color,string $fc='FFFFFF'):void{$w->getStyle($c)->applyFromArray(['fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$color]],'font'=>['bold'=>true,'color'=>['rgb'=>$fc],'size'=>10],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'999999']]]]);}
function sB(Worksheet $w,string $r,string $color):void{$w->getStyle($r)->applyFromArray(['fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$color]],'font'=>['size'=>10],'alignment'=>['vertical'=>Alignment::VERTICAL_CENTER],'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]]]);}

$ws = $spreadsheet->setActiveSheetIndex(0);
$ws->setTitle('Royalty Fee Import');
$ws->getSheetView()->setView(\PhpOffice\PhpSpreadsheet\Worksheet\SheetView::SHEETVIEW_PAGE_LAYOUT);

$columns = [
    ['A','TIN','required',20],['B','Date_Investment_License','required',20],['C','Year','required',10],
    ['D','Reciept Date','required',16],['E','Royalty_fee_rate (Benchmark)','required',24],
    ['F','Royalty_fee_rate (Contracted)','required',24],['G','Electricity_sale_value (USD)','required',25],
    ['H','Royalty_fee_collected','required',22],['I','Paid currency','optional',16],
    ['J','Exchange rate','optional',16],['K','Use User Fallback?','fallback',22],
    ['L','User Benchmark Rate','fallback',22],['M','User Benchmark Fee','fallback',22],
    ['N','User TE','fallback',22],['O','User Fallback Reason','fallback',30],
    ['P','User Comment','fallback',30],
];
$cm=['required'=>['header'=>CLR_REQUIRED,'body'=>CLR_INPUT_REQ],'optional'=>['header'=>CLR_OPTIONAL,'body'=>CLR_INPUT_OPT],'fallback'=>['header'=>CLR_FALLBACK,'body'=>CLR_INPUT_FB]];

foreach($columns as[$c,$h,$g,$w])$ws->getColumnDimension($c)->setWidth($w);
foreach($columns as[$c,$h,$g,$w]){$cell=$c.'1';$ws->setCellValue($cell,$h);sH($ws,$cell,$cm[$g]['header']);}

$ws->setCellValue('A2','123456789-000');$ws->setCellValue('B2','2026-01-01');$ws->setCellValue('C2',2026);
$ws->setCellValue('D2',date('Y-m-d'));$ws->setCellValue('E2',5);$ws->setCellValue('F2',5);
$ws->setCellValue('G2',100000000);$ws->setCellValue('H2',5000000);$ws->setCellValue('I2','USD');
$ws->setCellValue('J2',1);$ws->setCellValue('K2','No');

$lb=1001;
foreach($columns as[$c,$h,$g,$w])sB($ws,$c.'2:'.$c.$lb,$cm[$g]['body']);
foreach(['E','F','G','H','J','L','M','N'] as $c){$ws->getStyle($c.'2:'.$c.$lb)->getNumberFormat()->setFormatCode('#,##0');$ws->getStyle($c.'2:'.$c.$lb)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);}

// Yes/No dropdown
$dv=new DataValidation();$dv->setType(DataValidation::TYPE_LIST);$dv->setFormula1('"Yes,No"');$dv->setAllowBlank(true);$dv->setShowDropDown(true);
$ws->setDataValidation('K2:K'.$lb,$dv);

// Numeric
$dv=new DataValidation();$dv->setType(DataValidation::TYPE_DECIMAL);$dv->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);$dv->setFormula1('0');$dv->setAllowBlank(true);
$ws->setDataValidation('E2:H'.$lb.' J2:J'.$lb.' L2:N'.$lb,$dv);

// Instructions
$w2=$spreadsheet->createSheet();$w2->setTitle('Instructions');$w2->getProtection()->setPassword('TaxETS2026');$w2->getProtection()->setSheet(true);
$w2->fromArray([
    ['Royalty Fee Import Template - Instructions'],[''],
    ['Information Type','Color / Style','Meaning','Examples / Notes'],
    ['Primary Required','Dark blue header, light blue input cells','Required for Royalty Fee TE calculation.','TIN, License Date, Year, Reciept Date, Benchmark/Contracted Rates, Electricity Sale Value, Fee Collected'],
    ['Primary Optional','Slate/gray-blue header, white input cells','Supporting info for audit / reference.','Paid Currency, Exchange Rate'],
    ['User Fallback','Orange header, light amber input cells','User override values.','Use User Fallback?, User Benchmark Rate, User Benchmark Fee, User TE, User Fallback Reason, User Comment'],
    [''],['Column Ref','Short Name','Full Description'],
    ['D','Reciept Date','Date of the receipt/payment'],
    ['E','Royalty_fee_rate (Benchmark)','Benchmark royalty fee rate'],
    ['F','Royalty_fee_rate (Contracted)','Contracted royalty fee rate'],
    ['G','Electricity_sale_value (USD)','Total value of electricity sold (in USD)'],
    ['I','Paid currency','Currency code (e.g. USD, THB, LAK)'],
    ['J','Exchange rate','Exchange rate to convert to USD'],
    [''],['Rule','Description'],
    ['Template version','ROYALTY_FEE_IMPORT v1.0 - Expert-confirmed'],
    ['Mandatory fields','TIN, License Date, Year, Reciept Date, Rates, Electricity Value, Fee Collected'],
    ['Protection password','TaxETS2026'],
],null,'A1',true);
$w2->getColumnDimension('A')->setWidth(22);$w2->getColumnDimension('B')->setWidth(50);$w2->getColumnDimension('C')->setWidth(55);$w2->getColumnDimension('D')->setWidth(50);

// Validation Lists
$w3=$spreadsheet->createSheet();$w3->setTitle('Validation Lists');
$w3->setCellValue('A1','Yes/No');$w3->setCellValue('A2','Yes');$w3->setCellValue('A3','No');
$w3->getColumnDimension('A')->setWidth(10);

// Data Dictionary
$w4=$spreadsheet->createSheet();$w4->setTitle('Data Dictionary');
$w4->setCellValue('A1','Note');$w4->setCellValue('B1','No additional reference data for Royalty Fee.');
$w4->getColumnDimension('A')->setWidth(15);$w4->getColumnDimension('B')->setWidth(50);

// Change Log
$w5=$spreadsheet->createSheet();$w5->setTitle('Change Log');
$w5->setCellValue('A1','Version');$w5->setCellValue('B1','Date');$w5->setCellValue('C1','Owner');$w5->setCellValue('D1','Change');
$w5->setCellValue('A2','1.0');$w5->setCellValue('B2',date('Y-m-d'));$w5->setCellValue('C2','APIS / Tax-ETS');
$w5->setCellValue('D2','Expert-confirmed Royalty Fee import template v1.0 - 16 columns A-P');
$w5->getColumnDimension('A')->setWidth(12);$w5->getColumnDimension('B')->setWidth(15);$w5->getColumnDimension('C')->setWidth(30);$w5->getColumnDimension('D')->setWidth(70);

$ws->freezePane('A3');$w2->freezePane('A3');$w3->freezePane('A2');$w4->freezePane('A2');$w5->freezePane('A2');
$w3->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);$w4->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
$w3->getProtection()->setPassword('TaxETS2026');$w3->getProtection()->setSheet(true);

$spreadsheet->setActiveSheetIndex(0);
if(PHP_SAPI==='cli'){$d=__DIR__.'/../tests';if(!is_dir($d))mkdir($d,0777,true);$f=$d.'/Royalty_Fee_Template.xlsx';$w=new Xlsx($spreadsheet);$w->save($f);echo"Template saved: $f\n";exit(0);}
$f='Royalty_Fee_Template.xlsx';header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$f.'"');header('Cache-Control: max-age=0');(new Xlsx($spreadsheet))->save('php://output');
