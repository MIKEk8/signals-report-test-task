<?php

include(__DIR__ . '/../connect_db.php');
include(__DIR__ . '/../common_function.php');
include(__DIR__ . '/../vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\IOFactory;

$fnc = new Fnc($mysqli);

if (isset($_GET['month'])) $month = $_GET['month'];
else $month = $fnc->date('m');

if (isset($_GET['year'])) $year = $_GET['year'];
else $year = $fnc->date('Y');

$spreadsheet = IOFactory::load(__DIR__ . '/template_report.xlsx');
$spreadsheet->setActiveSheetIndex(0);

$array_category_signal_lsit = $fnc->getTgCategoryList(2);

$total_deal = 0;
$total_deal_plus = 0;
$itog_r = 0;

$row = 2;

foreach ($array_category_signal_lsit as $category) {
	$category_id = $category['category_id'];
	$array_signal_list = $fnc->getTgSignalList($category_id,$month,$year);
	$array_result = $fnc->getItogDataSignalTable($array_signal_list);
	$total_deal = $total_deal + $array_result['itog_col_deal_closed'];
	$total_deal_plus = $total_deal_plus + $array_result['itog_col_deal_plus'];
	$itog_r = $itog_r + $array_result['itog_r'];
	if ($array_result['itog_col_deal_closed'] != 0) $itog_col_deal_closed_pr = round(100 * $array_result['itog_col_deal_plus'] / $array_result['itog_col_deal_closed']);
	else $itog_col_deal_closed_pr = "0";
	$spreadsheet->setActiveSheetIndex(0)->setCellValue('A'.$row, $category['name']);
	$spreadsheet->setActiveSheetIndex(0)->setCellValue('B'.$row, $array_result['itog_col_deal_closed']);
	$spreadsheet->setActiveSheetIndex(0)->setCellValue('C'.$row, $itog_col_deal_closed_pr."%");
	$spreadsheet->setActiveSheetIndex(0)->setCellValue('D'.$row, $array_result['itog_r']);
	$spreadsheet->setActiveSheetIndex(0)->setCellValue('E'.$row, $array_result['itog_r']."%");
	$spreadsheet->setActiveSheetIndex(0)->setCellValue('F'.$row, ($array_result['itog_r']*2)."%");
	
	$row++;
}

$row++;

if ($total_deal != 0) $total_plus_pr = round(100 * $total_deal_plus / $total_deal);
else $total_plus_pr = 0;

$spreadsheet->setActiveSheetIndex(0)->setCellValue('B'.$row, $total_deal);
$spreadsheet->setActiveSheetIndex(0)->setCellValue('C'.$row, $total_plus_pr."%");
$spreadsheet->setActiveSheetIndex(0)->setCellValue('D'.$row, $itog_r);
$spreadsheet->setActiveSheetIndex(0)->setCellValue('E'.$row, $itog_r."%");
$spreadsheet->setActiveSheetIndex(0)->setCellValue('F'.$row, ($itog_r*2)."%");

foreach ($array_category_signal_lsit as $category) {

	$category_id = $category['category_id'];
	if ($category_id == 8) $ind = 1;
	else if ($category_id == 9) $ind = 2;
	$array_signal_list = $fnc->getTgSignalList($category_id,$month,$year);

	$array_result = $fnc->getItogDataSignalTable($array_signal_list);	

	$i = 1;

	$row = 3;

	foreach ($array_signal_list as $signal) {
		$spreadsheet->setActiveSheetIndex($ind)->insertNewRowBefore($row);
		$spreadsheet->setActiveSheetIndex($ind)->setCellValue('A'.$row, $i);
		$spreadsheet->setActiveSheetIndex($ind)->setCellValue('B'.$row, $signal['tool']['tiker']);
		$spreadsheet->setActiveSheetIndex($ind)->setCellValue('C'.$row, $signal['diraction'] . ' ' . $signal['type']);
		$spreadsheet->setActiveSheetIndex($ind)->setCellValue('D'.$row, $signal['date_open']);
		$spreadsheet->setActiveSheetIndex($ind)->setCellValue('E'.$row, $signal['price_open']);
		$spreadsheet->setActiveSheetIndex($ind)->setCellValue('F'.$row, $signal['stop_loss_rash']);
		$spreadsheet->setActiveSheetIndex($ind)->setCellValue('G'.$row, $signal['take_profit']);
		$spreadsheet->setActiveSheetIndex($ind)->setCellValue('H'.$row, $signal['take_profit_2']);
		$spreadsheet->setActiveSheetIndex($ind)->setCellValue('I'.$row, $signal['take_profit_3']);
		$spreadsheet->setActiveSheetIndex($ind)->setCellValue('J'.$row, $signal['date_close']);
		$spreadsheet->setActiveSheetIndex($ind)->setCellValue('K'.$row, $signal['price_close']);
		$spreadsheet->setActiveSheetIndex($ind)->setCellValue('L'.$row, $signal['r']);
		$spreadsheet->setActiveSheetIndex($ind)->setCellValue('M'.$row, $signal['d1']);
		$spreadsheet->setActiveSheetIndex($ind)->setCellValue('N'.$row, $signal['d2']);
		
		$row++;

		$i++;

	}

	$spreadsheet->setActiveSheetIndex($ind)->setCellValue('C'.($row+1), $array_result['itog_col_deal_total']);
	$spreadsheet->setActiveSheetIndex($ind)->setCellValue('C'.($row+2), $array_result['itog_col_deal_closed']);
	$spreadsheet->setActiveSheetIndex($ind)->setCellValue('C'.($row+3), $array_result['itog_col_deal_plus']);
	$spreadsheet->setActiveSheetIndex($ind)->setCellValue('C'.($row+4), $array_result['itog_col_deal_minus']);

	if ($array_result['itog_col_deal_closed'] != 0) $pr_plus = round(100 * $array_result['itog_col_deal_plus'] / $array_result['itog_col_deal_closed']);
	else $pr_plus = 0;

	if ($array_result['itog_col_deal_closed'] != 0) $pr_minus = round(100 * $array_result['itog_col_deal_minus'] / $array_result['itog_col_deal_closed']);
	else $pr_minus = 0;

	$spreadsheet->setActiveSheetIndex($ind)->setCellValue('D'.($row+3), $pr_plus . "%");
	$spreadsheet->setActiveSheetIndex($ind)->setCellValue('D'.($row+4), $pr_minus . "%");
	$spreadsheet->setActiveSheetIndex($ind)->setCellValue('L'.($row+1), $array_result['itog_r']);
	$spreadsheet->setActiveSheetIndex($ind)->setCellValue('M'.($row+1), $array_result['itog_r']."%");
	$spreadsheet->setActiveSheetIndex($ind)->setCellValue('N'.($row+1), ($array_result['itog_r']*2)."%");
	
}

$title = $fnc->array_month[$month] . " " . $year . " г";

$spreadsheet->setActiveSheetIndex(0);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$title.'.xlsx"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header('Pragma: public'); // HTTP/1.0

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;
