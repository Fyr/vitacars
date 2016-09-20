<?
	$tpl_data['Itogo']['k_oplate_propis'] = $this->Price->num2str($tpl_data['Itogo']['k_oplate']);
	foreach(array('sum', 'nds', 'k_oplate') as $key) {
		$tpl_data['Itogo'][$key] = $this->Price->format($tpl_data['Itogo'][$key]);
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
			<td class="<?=$class?>">&nbsp;<?=$Product['qty']?></td>
			<td class="<?=$class?>">&nbsp;<?=$this->Price->format($Product['price'])?></td>
			<td class="<?=$class?>">&nbsp;<?=($Product['discount']) ? $Product['discount'].'%' : ''?></td>
			<td class="<?=$class?>">&nbsp;<?=$this->Price->format($Product['sum'])?></td>
		</tr>
<?php 
	}
?>
        </tbody>
    </table>

<?=$this->Tpl->format($sf_footer, $tpl_data)?>