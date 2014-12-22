<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=windows-1251">
		<style type="text/css">
		td {
		    vertical-align: middle;
		    text-align: center;
		}
		.row {
		    /*height: 100px;*/
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
		$class = ($class == ' class="even"') ? ' class="odd"' : ' class="even"';
?>
            <tr class="row">
                <td<?=$class?>>&nbsp;<?= mb_convert_encoding($Product['Product']['title'], "CP1251", "UTF-8")?></td>
                <td<?=$class?>>&nbsp;<?= mb_convert_encoding($Product['Product']['title_rus'], "CP1251", "UTF-8")?></td>
                <td<?=$class?>>&nbsp;<?= mb_convert_encoding($Product['Product']['code'], "CP1251", "UTF-8") ?></td>
<?php
		foreach ($aLabels as $key => $label) {
			$val = Hash::get($Product, $key);
			if ($key == 'PMFormData.fk_6') {
				$val = implode(' ', explode(',', $val));
			}
			echo '<td'.$class.'>&nbsp;'.mb_convert_encoding($val, "CP1251", "UTF-8").'</td>';
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