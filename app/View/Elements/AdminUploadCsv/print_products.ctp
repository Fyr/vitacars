<?
		$class = 'even';
		foreach($aCodes as $code) {
			$products = Hash::get($aProducts, $code);
			$class = ($class == 'even') ? 'odd' : 'even';
			if ($products) {
				if (isset($products['Product'])) {
					$products = array($products);
				}
				$product = array('code' => '', 'detail_num' => '', 'title_rus' => '');
				foreach($products as $_product) {
					$product['code'].= '&nbsp;'.$_product['Product']['code'].'<br />';
					$product['detail_num'].= '&nbsp;'.$_product['Product']['detail_num'].'<br />';
					$product['title_rus'].= $_product['Product']['title_rus'].'<br />';
				}
?>
		<tr class="grid-row">
			<td class="<?=$class?>" align="center">&nbsp;<?=$code?></td>
			<td class="<?=$class?>"><?=$product['code']?></td>
			<td class="<?=$class?>"><?=$product['detail_num']?></td>
			<td class="<?=$class?>"><?=$product['title_rus']?></td>
		</tr>
<?
			} else {
?>
		<tr class="grid-row legend-red">
			<td class="<?=$class?>" align="center" style="color: #f00">&nbsp;<?=$code?></td>
			<td class="<?=$class?>" align="center" colspan="3">- <?=__('Product %s not found', $code)?> -</td>
		</tr>
<?
			}
		}