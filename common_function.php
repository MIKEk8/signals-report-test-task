<?php

class Fnc
{

	public $mysqli;

	public $array_month = [
		'01' => 'Январь',
		'02' => 'Февраль',
		'03' => 'Март',
		'04' => 'Апрель',
		'05' => 'Май',
		'06' => 'Июнь',
		'07' => 'Июль',
		'08' => 'Август',
		'09' => 'Сентябрь',
		'10' => 'Октябрь',
		'11' => 'Ноябрь',
		'12' => 'Декабрь',
	];

	private $now;

	public function __construct($mysqli)
	{
		$this->mysqli = $mysqli;

		// Текущая дата отчёта. Для воспроизводимости в docker-compose.yml задана через APP_NOW
		$this->now = APP_NOW !== '' ? strtotime(APP_NOW) : time();
	}



	public function date($format)
	{
		return date($format, $this->now);
	}



	public function getTgCategoryList($tg_block_id = 0)
	{

		$array = array();

		$dop_sql = "";

		if ($tg_block_id > 0) {
			$dop_sql = $dop_sql . " and `tg_block_id`=$tg_block_id";
		}

		$sql = $this->mysqli->query("
				SELECT * FROM `tg_category` WHERE 1=1 $dop_sql ORDER BY `sort`
			");

		while ($res = $sql->fetch_assoc()) {

			array_push($array, $res);
		}

		return $array;
	}



	public function getTool($tool_id)
	{

		$sql = $this->mysqli->query("

				SELECT * FROM `tg_tool` WHERE `tool_id`=$tool_id

			");

		$col = $sql->num_rows;

		if ($col == 0) return false;

		$res = $sql->fetch_array();

		return $res;
	}



	public function getTgSignalList($category_id, $month, $year)
	{

		// Если запрашивается текущий месяц, присылаем все открытые в этом месяце сделки + все ещё не закрытые.

		$date_month = $year . "-" . $month . "-";

		if ($year == $this->date('Y') and $month == $this->date('m')) {

			$sql = $this->mysqli->query("
					SELECT * FROM `tg_signal`
					WHERE
					`category_id_signal`=$category_id and
					`is_published`=1 and
					(
						`is_deleted`=0
							or
						`date_deleted` LIKE '$date_month%'
					)
						and
					(
						`is_closed`=0
							or
						`date_closed` LIKE '$date_month%'
							or
						`date_created` LIKE '$date_month%'
					)
						and
					(
						`is_canceled`=0
							or
						`date_canceled` LIKE '$date_month%'
					)
				");

		} else {

			// LIKE по datetime не использует индекс, поэтому диапазон [1-е число; 1-е следующего месяца).
				// BETWEEN ... LAST_DAY() не годится: LAST_DAY — это полночь последнего дня, сделки с 00:01 до 23:59 31-го выпадают.
			$sql = $this->mysqli->query("
					SELECT * FROM `tg_signal`
					WHERE
					`category_id_signal`=$category_id and
					`is_published`=1 and
					`is_deleted`=0 and
					`date_closed` >= '{$date_month}01' and
						`date_closed` < DATE_ADD('{$date_month}01', INTERVAL 1 MONTH) and
					`is_canceled`=0
				");
		}



		$array = array();
		while ($res = $sql->fetch_assoc()) {

			$col_take_profit = 0; // кол-во take profit у этой сделки (сбрасывается на каждой)

			$tool_id = $res['tool_id'];
			$tool = $this->getTool($tool_id);
			$res['tool'] = $tool;
			$res['d1'] = ""; // доходность 1%
			$res['d2'] = ""; // доходность 2%

			$take_profit = $res['take_profit'];
			$take_profit_2 = $res['take_profit_2'];
			$take_profit_3 = $res['take_profit_3'];

			$date_take_profit = $res['date_take_profit'];
			$date_take_profit_2 = $res['date_take_profit_2'];
			$date_take_profit_3 = $res['date_take_profit_3'];

			if ($res['date_closed'] != "0000-00-00 00:00:00" and $date_take_profit == "0000-00-00 00:00:00" and $res['take_profit'] == $res['price_close']) {
				$date_take_profit = $res['date_closed'];
			}

			if ($take_profit != 0) $col_take_profit++;
			if ($take_profit_2 != 0) $col_take_profit++;
			if ($take_profit_3 != 0) $col_take_profit++;

			if ($res['type'] == 'market') {

				$res['is_in_work'] = 1;

				$res['date_open'] = date("d.m.Y H:i", strtotime($res['date_created']));
			} else {

				if ($res['is_in_work_datetime'] != "0000-00-00 00:00:00") {

					$res['date_open'] = date("d.m.Y H:i", strtotime($res['is_in_work_datetime']));
				} else {

					$res['date_open'] = "-";
				}
			}



			if ($res['is_canceled'] == 1) {

				$res['r'] = "-";

				$res['date_close'] = "";

				$res['price_close'] = "";

			} else if ($res['is_deleted'] == 1) {

				$res['r'] = "-";

				$res['date_close'] = "";

				$res['price_close'] = "";

			} else {

				if ($res['is_closed'] == 1) {

					if (is_numeric($res['r'])) {

						// R вписан вручную, не пересчитываем

					} else {

						if ($res['stop_loss_rash'] == 0) $res['stop_loss_rash'] = $res['stop_loss'];

						$res['r'] = 0; // считаем итоговый R

						$price_close_rasch = 0;

						// Позиция делится на равные части по числу тейков.
						// Часть, тейк которой сработал, закрыта по тейку; остальные — по цене закрытия сделки.

						if ($take_profit != 0) {

							if ($date_take_profit != "0000-00-00 00:00:00") {

								if ($res['diraction'] == "buy") {

									$res['r'] = $res['r'] + round((($take_profit - $res['price_open']) / ($res['price_open'] - $res['stop_loss_rash'])) / $col_take_profit, 2);
								} else {

									$res['r'] = $res['r'] + round((($res['price_open'] - $take_profit) / ($res['stop_loss_rash'] - $res['price_open'])) / $col_take_profit, 2);
								}

								$price_close_rasch = $price_close_rasch + $take_profit / $col_take_profit;
							} else {

								if ($res['price_close'] != $res['stop_loss_rash']) {

									if ($res['diraction'] == "buy") {

										$res['r'] = $res['r'] + round((($res['price_close'] - $res['price_open']) / ($res['price_open'] - $res['stop_loss_rash'])) / $col_take_profit, 2);
									} else {

										$res['r'] = $res['r'] + round((($res['price_open'] - $res['price_close']) / ($res['stop_loss_rash'] - $res['price_open'])) / $col_take_profit, 2);
									}

									$price_close_rasch = $price_close_rasch + $res['price_close'] / $col_take_profit;
								} else {

									$res['r'] = $res['r'] - round(1 / $col_take_profit, 2);

									$price_close_rasch = $price_close_rasch + $res['stop_loss_rash'] / $col_take_profit;
								}
							}
						}



						if ($take_profit_2 != 0) {

							if ($date_take_profit_2 != "0000-00-00 00:00:00") {

								if ($res['diraction'] == "buy") {

									$res['r'] = $res['r'] + round((($take_profit_2 - $res['price_open']) / ($res['price_open'] - $res['stop_loss_rash'])) / $col_take_profit, 2);
								} else {

									$res['r'] = $res['r'] + round((($res['price_open'] - $take_profit_2) / ($res['stop_loss_rash'] - $res['price_open'])) / $col_take_profit, 2);
								}

								$price_close_rasch = $price_close_rasch + $take_profit_2 / $col_take_profit;
							} else {

								if ($res['price_close'] != $res['stop_loss_rash']) {

									if ($res['diraction'] == "buy") {

										$res['r'] = $res['r'] + round((($res['price_close'] - $res['price_open']) / ($res['price_open'] - $res['stop_loss_rash'])) / $col_take_profit, 2);
									} else {

										$res['r'] = $res['r'] + round((($res['price_open'] - $res['price_close']) / ($res['stop_loss_rash'] - $res['price_open'])) / $col_take_profit, 2);
									}

									$price_close_rasch = $price_close_rasch + $res['price_close'] / $col_take_profit;
								} else {

									$res['r'] = $res['r'] - round(1 / $col_take_profit, 2);

									$price_close_rasch = $price_close_rasch + $res['stop_loss_rash'] / $col_take_profit;
								}
							}
						}



						if ($take_profit_3 != 0) {

							if ($date_take_profit_3 != "0000-00-00 00:00:00") {

								if ($res['diraction'] == "buy") {

									$res['r'] = $res['r'] + round((($take_profit_3 - $res['price_open']) / ($res['price_open'] - $res['stop_loss_rash'])) / $col_take_profit, 2);
								} else {

									$res['r'] = $res['r'] + round((($res['price_open'] - $take_profit_3) / ($res['stop_loss_rash'] - $res['price_open'])) / $col_take_profit, 2);
								}

								$price_close_rasch = $price_close_rasch + $take_profit_3 / $col_take_profit;
							} else {

								if ($res['price_close'] != $res['stop_loss_rash']) {

									if ($res['diraction'] == "buy") {

										$res['r'] = $res['r'] + round((($res['price_close'] - $res['price_open']) / ($res['price_open'] - $res['stop_loss_rash'])) / $col_take_profit, 2);
									} else {

										$res['r'] = $res['r'] + round((($res['price_open'] - $res['price_close']) / ($res['stop_loss_rash'] - $res['price_open'])) / $col_take_profit, 2);
									}

									$price_close_rasch = $price_close_rasch + $res['price_close'] / $col_take_profit;
								} else {

									$res['r'] = $res['r'] - round(1 / $col_take_profit, 2);

									$price_close_rasch = $price_close_rasch + $res['stop_loss_rash'] / $col_take_profit;
								}
							}
						}

						$res['price_close'] = round($price_close_rasch, 5);
					}

					$res['d1'] = $res['r'] . "%";

					$res['d2'] = ($res['r'] * 2) . "%";

					$res['date_close'] = date('d.m.Y H:i', strtotime($res['date_closed']));

				} else {

					// Сделка ещё в работе: R считаем только по уже сработавшим тейкам

					if (
						$date_take_profit != "0000-00-00 00:00:00"
						or
						$date_take_profit_2 != "0000-00-00 00:00:00"
						or
						$date_take_profit_3 != "0000-00-00 00:00:00"
					) {

						if ($res['stop_loss_rash'] == 0) $res['stop_loss_rash'] = $res['stop_loss'];

						$res['r'] = 0; // считаем итоговый R

						if ($date_take_profit != "0000-00-00 00:00:00") {

							if ($res['diraction'] == "buy") {

								$res['r'] = $res['r'] + round((($take_profit - $res['price_open']) / ($res['price_open'] - $res['stop_loss_rash'])) / $col_take_profit, 2);
							} else {

								$res['r'] = $res['r'] + round((($res['price_open'] - $take_profit) / ($res['stop_loss_rash'] - $res['price_open'])) / $col_take_profit, 2);
							}
						}

						if ($date_take_profit_2 != "0000-00-00 00:00:00") {

							if ($res['diraction'] == "buy") {

								$res['r'] = $res['r'] + round((($take_profit_2 - $res['price_open']) / ($res['price_open'] - $res['stop_loss_rash'])) / $col_take_profit, 2);
							} else {

								$res['r'] = $res['r'] + round((($res['price_open'] - $take_profit_2) / ($res['stop_loss_rash'] - $res['price_open'])) / $col_take_profit, 2);
							}
						}

						if ($date_take_profit_3 != "0000-00-00 00:00:00") {

							if ($res['diraction'] == "buy") {

								$res['r'] = $res['r'] + round((($take_profit_3 - $res['price_open']) / ($res['price_open'] - $res['stop_loss_rash'])) / $col_take_profit, 2);
							} else {

								$res['r'] = $res['r'] + round((($res['price_open'] - $take_profit_3) / ($res['stop_loss_rash'] - $res['price_open'])) / $col_take_profit, 2);
							}
						}

					} else {

						$res['r'] = "";
					}

					$res['date_close'] = "";

					$res['price_close'] = "";
				}
			}

			if ($take_profit == 0) $res['take_profit'] = '-';

			if ($take_profit_2 == 0) $res['take_profit_2'] = '-';

			if ($take_profit_3 == 0) $res['take_profit_3'] = '-';

			if ($res['stop_loss'] == 0) $res['stop_loss'] = "-";

			array_push($array, $res);
		}

		return $array;
	}



	public function getSignalStatusName($signal)
	{
		if ($signal['is_canceled'] == 1) return 'Отменена';
		if ($signal['is_deleted'] == 1) return 'Удалена';
		if ($signal['is_closed'] == 1) return 'Закрыта';
		if ($signal['is_in_work'] == 1) return 'В работе';
		return 'Отложенный ордер';
	}



	public function getItogDataSignalTable($array_signal_list)
	{

		$itog_col_deal_total = 0;
		$itog_col_deal_open = 0;
		$itog_col_deal_closed = 0;
		$itog_col_deal_plus = 0;
		$itog_col_deal_minus = 0;
		$itog_r = 0;
		$itog_r_closed = 0;

		foreach ($array_signal_list as $signal) {

			if ($signal['r'] != "-") $itog_col_deal_total++;
			if (is_numeric($signal['r']) and $signal['is_closed'] == 1) $itog_col_deal_closed++;
			if (is_numeric($signal['r']) and $signal['r'] >= 0 and $signal['is_closed'] == 1) $itog_col_deal_plus++;
			if (is_numeric($signal['r']) and $signal['r'] < 0 and $signal['is_closed'] == 1) $itog_col_deal_minus++;
			if (is_numeric($signal['r'])) $itog_r = $itog_r + $signal['r'];

			if (
				$signal['is_closed'] == 0 &&
				$signal['is_deleted'] == 0 &&
				$signal['is_canceled'] == 0 &&
				$signal['is_in_work'] == 1
			) {
				$itog_col_deal_open++;
			}

			if ($signal['is_closed'] == 1 and is_numeric($signal['r'])) {
				$itog_r_closed += $signal['r'];
			}
		}

		return [
			'itog_col_deal_total' => $itog_col_deal_total,
			'itog_col_deal_closed' => $itog_col_deal_closed,
			'itog_col_deal_open' => $itog_col_deal_open,
			'itog_col_deal_plus' => $itog_col_deal_plus,
			'itog_col_deal_minus' => $itog_col_deal_minus,
			'itog_r' => round($itog_r, 2),
			'itog_r_closed' => round($itog_r_closed, 2),
		];
	}
}
