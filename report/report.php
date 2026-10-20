<div class="top_header">
	<div class="title_h">
		<div class="row">
			<div class="col-9 my-auto">
				Отчеты по сделкам
			</div>
		</div>
	</div>
</div>

<div class="pt-4 pb-4">
	<form action="" method="GET" class="row">
		<div class="d-inline pr-1">
			<select name="month" class="form-control">
				<?php foreach ($fnc->array_month as $key=>$val) { ?>
					<option value="<?=$key;?>" <?php if ($month == $key) echo "selected";?>><?=$val;?></option>
				<?php } ?>
			</select>
		</div>
		<div class="d-inline pr-1">
			<select name="year" class="form-control">
				<?php for ($i = $fnc->date('Y'); $i >= 2021; $i--) { ?>
					<option value="<?=$i;?>" <?php if ($year == $i) echo "selected";?>><?=$i;?> г.</option>
				<?php } ?>
			</select>
		</div>
		<div class="d-inline">
			<input type="submit" value="Фильтровать" class="btn btn-success"/>
		</div>
	</form>
</div>

<ul class="nav nav-tabs" id="myTab" role="tablist">
	<li class="nav-item">
		<a class="nav-link active" id="common_table-tab" data-toggle="tab" href="#common_table" role="tab" aria-controls="common_table" aria-selected="true">Сводные показатели</a>
	</li>
	<?php foreach ($array_category_signal_lsit as $category) { ?>
		<li class="nav-item">
			<a class="nav-link" id="category_<?=$category['category_id'];?>-tab" data-toggle="tab" href="#category_<?=$category['category_id'];?>" role="tab" aria-controls="category_<?=$category['category_id'];?>" aria-selected="false"><?=$category['name'];?></a>
		</li>
	<?php } ?>
</ul>

<div class="tab-content" id="myTabContent">
	<div class="tab-pane fade show active" id="common_table" role="tabpanel" aria-labelledby="common_table-tab">
		<table class="table_signal_common">
			<thead>
				<tr>
					<td>Рынок</td>
					<td class="text-center">Кол-во закрытых сделок</td>
					<td class="text-center">Прибыльных сделок</td>
					<td class="text-center">Доходность, R</td>
					<td class="text-center">Доходность, 1% риска</td>
					<td class="text-center">Доходность, 2% риска</td>
				</tr>
			</thead>
			<tbody>
				<?php
					$total_deal = 0;
					$total_deal_plus = 0;
					$itog_r = 0;
				?>

				<?php foreach ($array_category_signal_lsit as $category) { ?>
					<?php
						$category_id = $category['category_id'];
						$array_signal_list = $fnc->getTgSignalList($category_id,$month,$year);
						$array_result = $fnc->getItogDataSignalTable($array_signal_list);
						$total_deal = $total_deal + $array_result['itog_col_deal_closed'];
						$total_deal_plus = $total_deal_plus + $array_result['itog_col_deal_plus'];
						$itog_r = $itog_r + $array_result['itog_r'];
					?>

					<tr>
						<td><?=$category['name'];?></td>
						<td class="text-center"><?=$array_result['itog_col_deal_closed'];?></td>
						<td class="text-center"><?php if ($array_result['itog_col_deal_closed'] != 0) echo round(100 * $array_result['itog_col_deal_plus'] / $array_result['itog_col_deal_closed']);?>%</td>
						<td class="text-center"><?=$array_result['itog_r'];?></td>
						<td class="text-center"><?=$array_result['itog_r'];?>%</td>
						<td class="text-center"><?=($array_result['itog_r']*2);?>%</td>
					</tr>

				<?php } ?>

				<tr class="tr_signal_result">
					<td>Итого</td>
					<td class="text-center"><?=$total_deal;?></td>
					<td class="text-center"><?php if ($total_deal != 0) echo round(100 * $total_deal_plus / $total_deal);?>%</td>
					<td class="text-center"><?=$itog_r;?></td>
					<td class="text-center"><?=$itog_r;?>%</td>
					<td class="text-center"><?=($itog_r*2);?>%</td>
				</tr>

			</tbody>

		</table>

	</div>

<?php

	foreach ($array_category_signal_lsit as $category) {

		$category_id = $category['category_id'];
		$array_signal_list = $fnc->getTgSignalList($category_id,$month,$year);

?>

		<div class="tab-pane fade" id="category_<?=$category['category_id'];?>" role="tabpanel" aria-labelledby="category_<?=$category['category_id'];?>-tab">
			<div class="overflow_auto">

				<?php
					$array_result = $fnc->getItogDataSignalTable($array_signal_list);
				?>

				<table class="table_signal_common">

					<thead>
						<tr>
							<th class="tg_signal_i">№</th>
							<th class="tg_signal_tiker">Инструмент</th>
							<th class="tg_signal_status">Статус</th>
							<th class="tg_signal_diraction">Направление</th>
							<th class="tg_signal_date_open">Дата входа</th>
							<th class="tg_signal_price_open">Цена входа</th>
							<th class="tg_signal_stop_loss">Стоп базовый</th>
							<th class="tg_signal_stop_loss">Стоп текущий</th>
							<th class="tg_signal_take_profit">Тейк 1</th>
							<th class="tg_signal_take_profit">Тейк 2</th>
							<th class="tg_signal_take_profit">Тейк 3</th>
							<th class="tg_signal_date_close">Дата закрытия</th>
							<th class="tg_signal_price_close">Цена закрытия</th>
							<th class="tg_signal_r">R</th>
							<th class="tg_signal_d1">Доходность, 1% риска</th>
							<th class="tg_signal_d2">Доходность, 2% риска</th>
						</tr>
					</thead>

					<tbody>

						<?php $i = 1; ?>

						<?php foreach ($array_signal_list as $signal) { ?>

						<tr>

							<td class="tg_signal_i"><?=$i;?></td>

							<td class="tg_signal_tiker"><?=$signal['tool']['tiker'];?></td>

							<td class="tg_signal_status">

								<?php if ($signal['is_canceled'] == 1) { ?>

									<div class="status_signal_is_canceled">Отменена</div>

								<?php } else if ($signal['is_deleted'] == 1) { ?>

									<div class="status_signal_is_deleted">Удалена</div>

								<?php } else if ($signal['is_closed'] == 1) { ?>

									<div class="status_signal_is_closed">Закрыта</div>

								<?php } else if ($signal['is_in_work'] == 1) { ?>

									<div class="status_signal_is_in_work">В работе</div>

								<?php } else if ($signal['is_in_work'] == 0) { ?>

									<div class="status_signal_not_is_in_work">Отложенный ордер</div>

								<?php } ?>

							</td>

							<td class="tg_signal_diraction"><?=$signal['diraction'];?> <?=$signal['type'];?></td>

							<td class="tg_signal_date_open"><?=$signal['date_open'];?></td>

							<td class="tg_signal_price_open"><?=$signal['price_open'];?></td>

							<td class="tg_signal_stop_loss"><?=$signal['stop_loss_rash'];?></td>

							<td class="tg_signal_stop_loss"><?=$signal['stop_loss'];?></td>

							<td class="tg_signal_take_profit"><?=$signal['take_profit'];?></td>

							<td class="tg_signal_take_profit"><?=$signal['take_profit_2'];?></td>

							<td class="tg_signal_take_profit"><?=$signal['take_profit_3'];?></td>

							<td class="tg_signal_date_close"><?=$signal['date_close'];?></td>

							<td class="tg_signal_price_close"><?=$signal['price_close'];?></td>

							<td class="tg_signal_r"><?=$signal['r'];?></td>

							<td class="tg_signal_d1"><?=$signal['d1'];?></td>

							<td class="tg_signal_d2"><?=$signal['d2'];?></td>

						</tr>

						<?php $i++; ?>

						<?php } ?>

						<tr class="itog_sitnal_bg">

							<td colspan="2" style="text-align:right;">Всего сделок</td>

							<td class="itog_signal_deal"><?=$array_result['itog_col_deal_total'];?></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td style="text-align: center;"><strong>ИТОГ R</strong></td>

							<td style="text-align: center;"><strong><?=$array_result['itog_r'];?></strong></td>

							<td style="text-align: center;"><?=$array_result['itog_r'];?>%</td>

							<td style="text-align: center;"><?=($array_result['itog_r']*2);?>%</td>

						</tr>

						<tr class="itog_sitnal_bg">

							<td colspan="2" style="text-align:right;">Кол-во закрытых</td>

							<td class="itog_signal_deal"><?=$array_result['itog_col_deal_closed'];?></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

						</tr>

						<tr class="itog_sitnal_bg">

							<td colspan="2" style="text-align:right;">Прибыльных</td>

							<td class="itog_signal_deal"><?=$array_result['itog_col_deal_plus'];?></td>

							<td style="text-align: center;"><?php if ($array_result['itog_col_deal_closed'] != 0) echo round(100 * $array_result['itog_col_deal_plus'] / $array_result['itog_col_deal_closed']);?>%</td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

						</tr>

						<tr class="itog_sitnal_bg">

							<td colspan="2" style="text-align:right;">Убыточных</td>

							<td class="itog_signal_deal"><?=$array_result['itog_col_deal_minus'];?></td>

							<td style="text-align: center;"><?php if ($array_result['itog_col_deal_closed'] != 0) echo round(100 * $array_result['itog_col_deal_minus'] / $array_result['itog_col_deal_closed']);?>%</td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

							<td></td>

						</tr>

					</tbody>

				</table>

				<br/><br/>

			</div>

		</div>

<?php

	}

?>

</div>

<br/>

<div class="d-inline">

	<a href="/report/create_xlsx.php?month=<?=$month;?>&year=<?=$year;?>"><div class="btn btn-warning">Скачать отчет в XLSX</div></a>

</div>