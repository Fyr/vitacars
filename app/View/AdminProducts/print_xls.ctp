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
                    <th><?=mb_convert_encoding('Код', "CP1251", "UTF-8")?></th>
<?php
    for ($i = 1; $i <= count($aLabels); $i++) {
        echo '<th>&nbsp;'.mb_convert_encoding($aLabels['Param'.$i.'.value'], "CP1251", "UTF-8").'</th>';
    }
?>
                </tr>
            </thead>
            <tbody>
<?php 
	foreach ($aRowset as $Product) { 
?>
            <tr class="row">
                <td>&nbsp;<?= mb_convert_encoding($Product['Product']['title'], "CP1251", "UTF-8")?></td>
                <td>&nbsp;<?= mb_convert_encoding($Product['Product']['code'], "CP1251", "UTF-8") ?></td>
<?php
		for ($i = 1; $i <= count($aLabels); $i++) {
            if ($i == 3) {
                echo '<td>&nbsp;'.mb_convert_encoding(str_replace(' ', ', ', $Product['Param'.$i]['value']), "CP1251", "UTF-8").'</td>';
            } else {
                echo '<td>&nbsp;'.mb_convert_encoding($Product['Param'.$i]['value'], "CP1251", "UTF-8").'</td>';
            }
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