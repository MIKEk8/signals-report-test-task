<?php
	$title_seo = "Отчеты по месяцам";

	$array_category_signal_lsit = $fnc->getTgCategoryList(2);

	if (isset($_GET['month'])) $month = $_GET['month'];
	else $month = $fnc->date('m');

	if (isset($_GET['year'])) $year = $_GET['year'];
	else $year = $fnc->date('Y');
?>
