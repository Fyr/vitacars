	<table class="grid table-bordered shadow">
	<thead>
		<tr class="first table-gradient">
			<th class="nowrap"><?=__('Code')?></th>
			<th class="nowrap"><?=__('Title rus')?></th>
		</tr>
	</thead>
	<tbody>
<?
		$class = 'even';
		foreach($aCodes as $code) {
			$class = ($class == 'even') ? 'odd' : 'even';
			$product = Hash::get($aProducts, $code);
			if ($product) {
?>
		<tr class="grid-row">
			<td class="<?=$class?>">&nbsp;<?=$product['Product']['code']?></td>
			<td class="<?=$class?>"><?=$product['Product']['title_rus']?></td>
		</tr>
<?
			} else {
?>
		<tr class="grid-row legend-red">
			<td class="<?=$class?>" style="color: #f00">&nbsp;<b><?=$code?></b></td>
			<td class="<?=$class?>" align="center">- <?=__('Product %s not found', $code)?> -</td>
		</tr>
<?
			}
		}
?>
	</tbody>
	</table>
