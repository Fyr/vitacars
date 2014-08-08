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
                    <th>Название</th>
                    <th>Код</th>
                    <?php
                    for ($i=1;$i<=count($aLabels);$i++) {
                        echo '<th>'.iconv("UTF-8",  "CP1251", $aLabels['Param'.$i.'.value']).'</th>';
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($aRowset as $Product) : ?>
            <tr class="row">
                <td><?= iconv("UTF-8",  "CP1251", $Product['Product']['title'] )?></td>
                <td><?= iconv("UTF-8",  "CP1251", $Product['Product']['code']) ?></td>
                <?php
                    for ($i=1;$i<=count($aLabels);$i++) {
                        if ($i == 4) {
                            echo '<td>'.iconv("UTF-8",  "CP1251", str_replace(' ', ', ', $Product['Param'.$i]['value'])).'</td>';
                        } else {
                            echo '<td>'.iconv("UTF-8",  "CP1251", $Product['Param'.$i]['value']).'</td>';
                        }
                    }
                ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </body>
</html>