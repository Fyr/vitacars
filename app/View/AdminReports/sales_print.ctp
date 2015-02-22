	<table>
	<thead>
		<tr>
			<th>
				<?=__('Code')?>
			</th>
			<th>
				<?=__('Title rus')?>
			</th>
			<th>
				<?=__('Sold')?>
			</th>
		</tr>
	</thead>
	<tbody>
<?
		$class = 'even';
		$total = 0;
		foreach($rows as $row) {
			$class = ($class == 'even') ? 'odd' : 'even';
			$qty = abs($row[0]['sum_remain']);
			$total+= $qty;
?>
		<tr>
			<td class="<?=$class?>"><?=$row['Product']['code']?></td>
			<td class="<?=$class?>"><?=$row['Product']['title_rus']?></td>
			<td class="<?=$class?>"><?=abs($row[0]['sum_remain'])?></td>
		</tr>
<?
		}
?>
		<tr>
			<td></td>
			<td align="right">
				<b><?=__('Total')?>:</b>
			</td>
			<td style="padding: 5px; text-align: right"><b><?=$total?></b></td>
		</tr>
	</tbody>
	</table>
