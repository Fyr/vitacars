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
	foreach($aFieldOptions as $label) {
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
		$options = array('class' => $class);
?>
		<tr class="row">
<?
		foreach($aFieldOptions as $key => $label) {
			$val = '';
			if ($key == 'Category.title') {
				$val = $aBrands[$Product['Product']['brand_id']];
			} elseif ($key == 'qty') {
				$val = $Product['qty'];
				$options['align'] = 'right';
			} elseif ($key == 'price') {
				$val = $this->Price->format($Product['price'], $currency);
				$options['align'] = 'right';
			} elseif ($key == 'discount') {
				$val = ($Product['discount']) ? $Product['discount'].'%' : '';
				$options['align'] = 'right';
			} elseif ($key == 'row_sum') {
				$val = $this->Price->format($Product['sum'], $currency);
				$options['align'] = 'right';
			} elseif (strpos($key, 'PMFormData.') !== false) {
				$fk_id = str_replace('PMFormData.fk_', '', $key);
				$col = $aParams[$fk_id]['PMFormField'];
				$val = Hash::get($Product, $key);
				if ($col['field_type'] == FieldTypes::INT) {
					$val = (intval($val)) ? $val : '';
					$options['align'] = 'right';
				} elseif ($col['field_type'] == FieldTypes::FLOAT) {
					$val = (floatval($val)) ? number_format($val, 2, ',', ' ') : '';
					$options['align'] = 'right';
				} elseif ($col['field_type'] == FieldTypes::MULTISELECT) {
					$val = str_replace(',', '<br />', $val);
				} elseif ($col['field_type'] == FieldTypes::TEXTAREA) {
					$val = nl2br($val);
				}
			} else {
				$val = '&nbsp;'.Hash::get($Product, $key);
			}
			echo $this->Html->tag('td', $val, $options)."\r\n";
		}
?>
		</tr>
<?php
	}
?>
        </tbody>
    </table>

<?=$this->Tpl->format($sf_footer, $tpl_data)?>