<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf8">
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
                        echo '<th>'.$aLabels['Param'.$i.'.value'].'</th>';
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($aRowset as $Product) : ?>
            <tr class="row">
                <td><?= $Product['Product']['title'] ?></td>
                <td><?= $Product['Product']['code'] ?></td>
                <?php
                    for ($i=1;$i<=count($aLabels);$i++) {
                        if ($i == 4) {
                            echo '<td>'.str_replace(' ', ', ', $Product['Param'.$i]['value']).'</td>';
                        } else {
                            echo '<td>'.$Product['Param'.$i]['value'].'</td>';
                        }
                    }
                ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </body>
</html>