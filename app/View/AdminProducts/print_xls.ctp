<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=windows-1251">
	<style type="text/css">
	td {
	    vertical-align: middle;
	}
	.align-right {
		text-align: right;
	}
	.even {
		background-color: #eee;
	}
	.odd {
	}
	img {
	    display: block;
	}
	</style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th><?=mb_convert_encoding('Название', "CP1251", "UTF-8")?></th>
                <th><?=mb_convert_encoding('Название рус.', "CP1251", "UTF-8")?></th>
                <th><?=mb_convert_encoding('Код', "CP1251", "UTF-8")?></th>
<?php
    foreach ($aLabels as $label) {
        echo '<th>&nbsp;'.mb_convert_encoding($label, "CP1251", "UTF-8").'</th>';
    }
?>
            </tr>
        </thead>
        <tbody>
<?php 
	$class = 'even';
	foreach ($aRowset as $Product) { 
		$class = ($class == 'even') ? 'odd' : 'even';
?>
		<tr class="row">
			<td class="<?=$class?>"><?= mb_convert_encoding($Product['Product']['title'], "CP1251", "UTF-8")?></td>
			<td class="<?=$class?>"><?= mb_convert_encoding($Product['Product']['title_rus'], "CP1251", "UTF-8")?></td>
			<td class="<?=$class?>"><?= mb_convert_encoding($Product['Product']['code'], "CP1251", "UTF-8") ?></td>
<?php
		foreach ($aLabels as $key => $label) {
			$_class = $class;
			$val = Hash::get($Product, $key);
			if ($key == 'PMFormData.fk_6') {
				$val = implode(' ', explode(',', $val));
			}
			$fk = str_replace('PMFormData.fk_', '', $key);
			$fieldType = $aParams[$fk]['PMFormField']['field_type'];
			
			if ($fieldType == FieldTypes::FLOAT) {
				$val = number_format($val, 2, ',', '');
			}
			
			if ($fieldType == FieldTypes::INT || $fieldType == FieldTypes::FLOAT) {
				// $_class.= ' align-right';
			} else {
				$val = '&nbsp;'.$val;
			}
			$val = mb_convert_encoding($val, "CP1251", "UTF-8");
			/*
			if ($fieldType == FieldTypes::STRING || $fieldType == FieldTypes::TEXTAREA) {
			}
			*/
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
</body>
</html>