	<table>
		<thead>
		<tr>
			<th>
				<?= __('Brand') ?>
			</th>
			<th>
				<?=__('Code')?>
			</th>
			<th>
				<?=__('Title rus')?>
			</th>
			<th>
				<?= __('Income') ?>
			</th>
			<th>
				<?= __('Outcome') ?>
			</th>
		</tr>
		</thead>
		<tbody>
<?
$class = 'even';
$total_income = 0;
$total_outcome = 0;
foreach ($rows as $row) {
	$class = ($class == 'even') ? 'odd' : 'even';
	$qty_income = abs($row[0]['sum_income']);
	$qty_outcome = abs($row[0]['sum_outcome']);
	$total_income += $qty_income;
	$total_outcome += $qty_outcome;
	$article = $aProducts[$row['ProductRemain']['product_id']];
?>
	<tr>
		<td class="<?= $class ?>"><?= $article['Brand']['title'] ?></td>
		<td class="<?= $class ?>"><?= $article['Product']['code'] ?></td>
		<td class="<?= $class ?>"><?= $article['Product']['title_rus'] ?></td>
		<td class="<?= $class ?>" align="right"><?= $qty_income ?></td>
		<td class="<?= $class ?>" align="right"><?= $qty_outcome ?></td>
	</tr>
<?
}
?>

		<tr>
			<td colspan="3" align="right">
				<b><?=__('Total')?>:</b>
			</td>
			<td style="padding: 5px; text-align: right"><b><?= $total_income ?></b></td>
			<td style="padding: 5px; text-align: right"><b><?= $total_outcome ?></b></td>
		</tr>
		</tbody>
	</table>