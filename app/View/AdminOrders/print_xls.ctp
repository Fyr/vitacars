<?
	$currency = $tpl_data['Order']['currency'];
	$tpl_data['Itogo']['k_oplate_propis'] = $this->Price->num2str($tpl_data['Itogo']['k_oplate'], $currency);
	foreach(array('sum', 'nds', 'k_oplate') as $key) {
		$tpl_data['Itogo'][$key] = $this->Price->format($tpl_data['Itogo'][$key], $currency);
	}
?>
<?=$this->Tpl->format($sf_header, $tpl_data)?>
    <table>
        <thead>
            <tr>
<?
	foreach(array(__('Brand'), __('Category'), __('Subcategory'), __('Title'), __('Title rus'), __('Code'), __('Qty'), __('Price'), __('Discount'), __('Sum')) as $label) {
?>
                <th><?=$label?></th>
<?
	}
?>
            </tr>
        </thead>
        <tbody>
<?php 
	$class = 'even';
	$total = 0;
	foreach ($aRowset as $Product) {
		$class = ($class == 'even') ? 'odd' : 'even';
		$subcat_id = $Product['Product']['subcat_id'];
?>
		<tr class="row">
			<td class="<?=$class?>"><?=$aBrands[$Product['Product']['brand_id']]?></td>
			<td class="<?=$class?>"><?=$aCategories[$Product['Product']['cat_id']]?></td>
			<td class="<?=$class?>"><?=(isset($aSubcategories[$subcat_id])) ? $aSubcategories[$subcat_id] : ''?></td>
			<td class="<?=$class?>"><?=$Product['Product']['title']?></td>
			<td class="<?=$class?>"><?=$Product['Product']['title_rus']?></td>
			<td class="<?=$class?>">&nbsp;<?=$Product['Product']['code']?></td>
			<td class="<?=$class?>" align="right">&nbsp;<?=$Product['qty']?></td>
			<td class="<?=$class?>" align="right">&nbsp;<?=$this->Price->format($Product['price'], $currency)?></td>
			<td class="<?=$class?>" align="right">&nbsp;<?=($Product['discount']) ? $Product['discount'].'%' : ''?></td>
			<td class="<?=$class?>" align="right">&nbsp;<?=$this->Price->format($Product['sum'], $currency)?></td>
		</tr>
<?php 
	}
?>
        </tbody>
    </table>

<?=$this->Tpl->format($sf_footer, $tpl_data)?>