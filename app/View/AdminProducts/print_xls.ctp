
    <table>
        <thead>
            <tr>
<?
	foreach(array(__('Brand'), __('Category'), __('Subcategory'), __('Title'), __('Title rus'), __('Code'), __('Detail num')) as $label) {
?>
                <th><?=$label?></th>
<?
	}
    foreach ($aLabels as $label) {
        echo '<th>&nbsp;'.$label.'</th>';
    }
?>
            </tr>
        </thead>
        <tbody>
<?php 
	$class = 'even';
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
			<td class="<?=$class?>" nowrap="nowrap">&nbsp;<?=$Product['Product']['detail_num']?></td>
<?php
		foreach ($aLabels as $key => $label) {
			$_class = $class;
			$val = Hash::get($Product, $key);
			if ($key == 'PMFormData.fk_6') {
				$val = implode(' ', explode(',', $val));
			}
			$fk = str_replace('PMFormData.fk_', '', $key);
			$formField = $aParams[$fk]['PMFormField'];
			$fieldType = $formField['field_type'];
			
			if ($fieldType == FieldTypes::FORMULA) {
				// fdebug($aParams[$fk]['PMFormField']);
				
				// приводим формулу к стд.виду для Excel
				$val = str_replace($formField['div_int'], '', $val); // убираем разделители для целой части
				$val = floatval(str_replace($formField['div_float'], '.', $val));
				$val = number_format($val, $formField['decimals'], ',', '');
			} else if ($fieldType == FieldTypes::FLOAT) {
				$val = number_format($val, 2, ',', '');
			}
			
			if (in_array($fieldType, array(FieldTypes::INT, FieldTypes::FLOAT, FieldTypes::FORMULA, FieldTypes.PRICE)) {
				// $_class.= ' align-right';
			} else {
				$val = '&nbsp;'.$val;
			}
?>
			<td class="<?=$_class?>"><?=$val?></td>
<?
        }
?>
		</tr>
<?php 
	}
?>
        </tbody>
    </table>
