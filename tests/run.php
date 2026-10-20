<?php
// Минимальные тесты без фреймворка. Запуск (нужна база из docker compose):
//   docker compose exec app php tests/run.php
// Тестовые сделки пишутся в рынок 9999 внутри транзакции и откатываются, данные в базе не меняются.
// «Сегодня» заморожено на 2026-10-19 (APP_NOW): август и сентябрь — прошлые месяцы, октябрь — текущий.

putenv('APP_NOW=2026-10-19 12:00:00');
require __DIR__ . '/../connect_db.php';
require __DIR__ . '/../common_function.php';

$mysqli->query("SET SESSION sql_mode = ''"); // нулевые даты, как в схеме
$fnc = new Fnc($mysqli);

const CAT = 9999;
const Z = '0000-00-00 00:00:00';

$passed = 0;
$failed = 0;

function check($name, $expected, $actual)
{
	global $passed, $failed;
	$same = (is_float($expected) || is_float($actual))
		? abs((float)$expected - (float)$actual) < 0.00001
		: $expected === $actual;
	if ($same) {
		$passed++;
		echo "  ok   $name\n";
	} else {
		$failed++;
		echo "  FAIL $name\n       ожидалось: " . var_export($expected, true) . "\n       получено:  " . var_export($actual, true) . "\n";
	}
}

// Вставляет сделку; незаданные поля — «пустые». Возвращает id.
function add($mysqli, array $f)
{
	$d = [
		'category_id_signal' => CAT, 'tool_id' => 1, 'diraction' => 'buy', 'type' => 'market',
		'price_open' => 100, 'price_close' => 0, 'stop_loss' => 90, 'stop_loss_rash' => 90, 'risk' => 1,
		'take_profit' => 0, 'take_profit_2' => 0, 'take_profit_3' => 0,
		'date_take_profit' => Z, 'date_take_profit_2' => Z, 'date_take_profit_3' => Z,
		'r' => '', 'comment' => '', 'graph_link' => '', 'is_published' => 1,
		'date_created' => '2026-08-01 10:00:00', 'date_published' => '2026-08-01 10:00:00',
		'date_closed' => Z, 'date_deleted' => Z, 'date_canceled' => Z,
		'is_deleted' => 0, 'is_closed' => 0, 'is_canceled' => 0, 'is_in_work' => 1, 'is_in_work_datetime' => Z,
	];
	$d = array_merge($d, $f);
	$cols = '`' . implode('`,`', array_keys($d)) . '`';
	$vals = "'" . implode("','", array_map([$mysqli, 'real_escape_string'], array_map('strval', $d))) . "'";
	$mysqli->query("INSERT INTO tg_signal ($cols) VALUES ($vals)");
	return $mysqli->insert_id;
}

// Сделка, закрытая по стопу (цена закрытия = стоп 90), с заданными тейками
function stopped($mysqli, $closed, array $extra = [])
{
	return add($mysqli, array_merge(['is_closed' => 1, 'date_closed' => $closed, 'price_close' => 90, 'take_profit' => 110], $extra));
}

// Отчёт рынка CAT за месяц, ключи — id сделок
function report($fnc, $month, $year = '2026')
{
	$out = [];
	foreach ($fnc->getTgSignalList(CAT, $month, $year) as $s) $out[$s['tg_signal_id']] = $s;
	return $out;
}

function clear_market($mysqli)
{
	$mysqli->query("DELETE FROM tg_signal WHERE category_id_signal = " . CAT);
}

$mysqli->begin_transaction();
try {

	// ---------- R: формулы ----------
	echo "R и цена закрытия\n";

	clear_market($mysqli);
	$buy_tp = add($mysqli, ['is_closed' => 1, 'date_closed' => '2026-08-10 12:00:00', 'price_close' => 120,
		'take_profit' => 120, 'date_take_profit' => '2026-08-10 12:00:00']);
	$sell_stop = add($mysqli, ['diraction' => 'sell', 'stop_loss' => 110, 'stop_loss_rash' => 110,
		'is_closed' => 1, 'date_closed' => '2026-08-11 12:00:00', 'price_close' => 110, 'take_profit' => 80]);
	$r = report($fnc, '08');
	check('buy, один тейк: R = (120-100)/(100-90) = 2', 2.0, (float)$r[$buy_tp]['r']);
	check('buy, один тейк: цена закрытия = тейк', 120.0, (float)$r[$buy_tp]['price_close']);
	check('sell, закрыта по стопу: R = -1', -1.0, (float)$r[$sell_stop]['r']);
	check('sell, закрыта по стопу: цена закрытия = стоп', 110.0, (float)$r[$sell_stop]['price_close']);

	clear_market($mysqli);
	// 2 тейка: первый сработал на 110 (+1R × 1/2 = +0.5), вторая половина закрыта по 95 (-0.5R × 1/2 = -0.25)
	$two = add($mysqli, ['is_closed' => 1, 'date_closed' => '2026-08-12 12:00:00', 'price_close' => 95,
		'take_profit' => 110, 'take_profit_2' => 130, 'date_take_profit' => '2026-08-12 10:00:00']);
	$r = report($fnc, '08');
	check('два тейка, сработал один: R = 0.5 - 0.25 = 0.25', 0.25, (float)$r[$two]['r']);
	check('два тейка: цена закрытия — среднее (110+95)/2', 102.5, (float)$r[$two]['price_close']);

	clear_market($mysqli);
	$manual = add($mysqli, ['is_closed' => 1, 'date_closed' => '2026-08-12 12:00:00', 'price_close' => 120,
		'take_profit' => 120, 'date_take_profit' => '2026-08-12 10:00:00', 'r' => '7.5']);
	check('ручной R не пересчитывается', 7.5, (float)report($fnc, '08')[$manual]['r']);

	// ---------- регресс бага 1: счётчик тейков не течёт между сделками ----------
	echo "Счётчик тейков не копится между сделками (баг 1)\n";

	clear_market($mysqli);
	$three = stopped($mysqli, '2026-08-05 12:00:00', ['take_profit_2' => 120, 'take_profit_3' => 130]); // 3 тейка, по стопу
	$one_a = stopped($mysqli, '2026-08-06 12:00:00');
	$one_b = stopped($mysqli, '2026-08-07 12:00:00');
	$r = report($fnc, '08');
	check('3 тейка по стопу: R = -0.99 (3 × round(-1/3, 2))', -0.99, (float)$r[$three]['r']);
	check('1 тейк по стопу после сделки с 3 тейками: R = -1', -1.0, (float)$r[$one_a]['r']);
	check('1 тейк по стопу, третья в списке: R = -1', -1.0, (float)$r[$one_b]['r']);
	check('цена закрытия не делится на накопленное N', 90.0, (float)$r[$one_b]['price_close']);

	// ---------- регресс бага 2: границы месяца ----------
	echo "Границы месяца у прошлых периодов (баг 2)\n";

	clear_market($mysqli);
	$last_day = stopped($mysqli, '2026-08-31 18:40:00');
	$last_sec = stopped($mysqli, '2026-08-31 23:59:59');
	$first_s  = stopped($mysqli, '2026-08-01 00:00:00');
	$next_m   = stopped($mysqli, '2026-09-01 00:00:00');
	$prev_m   = stopped($mysqli, '2026-07-31 23:59:59');
	$aug = report($fnc, '08');
	$sep = report($fnc, '09');
	check('закрыта 31.08 18:40 — есть в августе', true, isset($aug[$last_day]));
	check('закрыта 31.08 23:59:59 — есть в августе', true, isset($aug[$last_sec]));
	check('закрыта 01.08 00:00:00 — есть в августе', true, isset($aug[$first_s]));
	check('закрыта 01.09 00:00:00 — нет в августе', false, isset($aug[$next_m]));
	check('закрыта 31.07 23:59:59 — нет в августе', false, isset($aug[$prev_m]));
	check('закрыта 31.08 — нет в сентябре', false, isset($sep[$last_day]));
	check('закрыта 01.09 00:00:00 — есть в сентябре', true, isset($sep[$next_m]));

	clear_market($mysqli);
	$sep30 = stopped($mysqli, '2026-09-30 20:00:00'); // месяц из 30 дней
	check('закрыта 30.09 20:00 — есть в сентябре', true, isset(report($fnc, '09')[$sep30]));

	// ---------- что попадает в отчёт ----------
	echo "Состав отчёта\n";

	clear_market($mysqli);
	$ok      = stopped($mysqli, '2026-08-10 12:00:00');
	$unpub   = stopped($mysqli, '2026-08-10 12:00:00', ['is_published' => 0]);
	$deleted = stopped($mysqli, '2026-08-10 12:00:00', ['is_deleted' => 1, 'date_deleted' => '2026-08-11 12:00:00']);
	$cancel  = stopped($mysqli, '2026-08-10 12:00:00', ['is_canceled' => 1, 'date_canceled' => '2026-08-11 12:00:00']);
	$aug = report($fnc, '08');
	check('прошлый месяц: опубликованная закрытая есть', true, isset($aug[$ok]));
	check('прошлый месяц: неопубликованной нет', false, isset($aug[$unpub]));
	check('прошлый месяц: удалённой нет', false, isset($aug[$deleted]));
	check('прошлый месяц: отменённой нет', false, isset($aug[$cancel]));

	clear_market($mysqli);
	$open_old   = add($mysqli, ['date_created' => '2026-06-01 10:00:00']); // висит с июня
	$open_new   = add($mysqli, ['date_created' => '2026-10-02 10:00:00']);
	$closed_now = stopped($mysqli, '2026-10-05 10:00:00', ['date_created' => '2026-09-01 10:00:00']);
	$closed_old = stopped($mysqli, '2026-09-05 10:00:00', ['date_created' => '2026-08-01 10:00:00']);
	$del_now    = add($mysqli, ['date_created' => '2026-10-01 10:00:00', 'is_deleted' => 1, 'date_deleted' => '2026-10-03 10:00:00']);
	$del_old    = add($mysqli, ['date_created' => '2026-09-01 10:00:00', 'is_deleted' => 1, 'date_deleted' => '2026-09-03 10:00:00']);
	$unpub_now  = add($mysqli, ['date_created' => '2026-10-02 10:00:00', 'is_published' => 0]);
	$oct = report($fnc, '10');
	check('текущий месяц: давно открытая есть', true, isset($oct[$open_old]));
	check('текущий месяц: открытая в этом месяце есть', true, isset($oct[$open_new]));
	check('текущий месяц: закрытая в этом месяце есть', true, isset($oct[$closed_now]));
	check('текущий месяц: закрытая в прошлом месяце нет', false, isset($oct[$closed_old]));
	check('текущий месяц: удалённая в этом месяце показана', true, isset($oct[$del_now]));
	check('текущий месяц: удалённая — R "-"', '-', isset($oct[$del_now]) ? $oct[$del_now]['r'] : null);
	check('текущий месяц: удалённой в прошлом месяце нет', false, isset($oct[$del_old]));
	check('текущий месяц: неопубликованной нет', false, isset($oct[$unpub_now]));

	// ---------- итоги ----------
	echo "Итоги\n";

	$itog = $fnc->getItogDataSignalTable($fnc->getTgSignalList(CAT, '10', '2026'));
	check('«Всего» без удалённых: 3 из 4 показанных', 3, $itog['itog_col_deal_total']);
	check('закрытых: 1', 1, $itog['itog_col_deal_closed']);
	check('прибыльные + убыточные = закрытые', $itog['itog_col_deal_closed'], $itog['itog_col_deal_plus'] + $itog['itog_col_deal_minus']);

	clear_market($mysqli);
	add($mysqli, ['is_closed' => 1, 'date_closed' => '2026-08-10 12:00:00', 'price_close' => 100, 'take_profit' => 110]); // R = 0, прибыльная
	stopped($mysqli, '2026-08-11 12:00:00');                                                                              // R = -1
	add($mysqli, ['is_closed' => 1, 'date_closed' => '2026-08-12 12:00:00', 'price_close' => 120, 'take_profit' => 120, 'date_take_profit' => '2026-08-12 12:00:00']); // R = 2
	$itog = $fnc->getItogDataSignalTable($fnc->getTgSignalList(CAT, '08', '2026'));
	check('R = 0 считается прибыльной: плюс = 2', 2, $itog['itog_col_deal_plus']);
	check('R < 0 — убыточная: минус = 1', 1, $itog['itog_col_deal_minus']);
	check('ИТОГ R = 0 + (-1) + 2 = 1', 1.0, (float)$itog['itog_r']);

} finally {
	$mysqli->rollback(); // тестовые данные в базе не остаются
}

echo "\nПройдено: $passed, упало: $failed\n";
exit($failed > 0 ? 1 : 0);
