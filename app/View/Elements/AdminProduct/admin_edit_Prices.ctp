<style>
    .price-table th, .price-table td {
        padding: 3px 7px;
    }
</style>
<table class="price-table">
    <thead>
    <tr>
        <th></th>
        <th></th>
        <th></th>
        <th>
            <?
            echo $this->PHForm->input('currency', array(
                'class' => 'input-medium', 'options' => $aCurrency, 'escape' => false,
                'div' => false, 'between' => false, 'after' => false, 'label' => false,
                'onchange' => 'updateCurrency()'
            ));
            ?>
        </th>
        <th>Коэфф.</th>
        <th>Цена<br/>по курсу</th>
        <th>Цена<br/>с НДС</th>
        <th>История<br/>цен</th>
    </tr>
    </thead>
    <tbody>
    <?
    foreach ($form as $field) {
        $field = $field['PMFormField'];
        $value = '';
        $calculated = $this->request->data('PMFormData.fk_' . $field['id']);
        $koeff = '1.00';
        $kurs = 1;
        $currency_from = '';
        if (isset($xPrices[$field['id']])) {
            $value = $xPrices[$field['id']]['price'];
            $kurs = $xPrices[$field['id']]['kurs'];
            $currency_from = $xPrices[$field['id']]['currency_from'];
            // $formatted = $xPrices[$field['id']]['calculated'];
            $koeff = $xPrices[$field['id']]['koeff'];
        }

        $curr = $field['price_currency'];
        /*
         * HOT fix - переделать курсы валют на 1 код
         */

        if ($curr == 'RUR') {
            $curr = 'RUB';
        }
        $kursOptions = array('' => $curr . ' =');
        foreach ($aKurs as $i => $const) {
            if ($const['PMFormConst']['price_kurs_to'] == 'RUR') {
                $aKurs[$i]['PMFormConst']['price_kurs_to'] = 'RUB';
            }
            $const = $const['PMFormConst'];
            if ($const['price_kurs_to'] == $curr) {
                $kursOptions[$const['price_kurs_from']] = $const['price_kurs_from'] . ' *' . $const['value'];
            }
        }
        $historyURL = $this->Html->url(array('controller' => 'AdminPriceHistory', 'action' => 'index',
            'PriceHistory.product_id' => $this->request->params['pass'][0], 'PriceHistory.fk_id' => $field['id']
        ));
        ?>
        <tr id="<?= $field['id'] ?>">
            <td>
                <?= $field['label'] ?>
                <?= $this->PHForm->hidden('currency_to', array('value' => $curr, 'class' => 'price-curr-to')) ?>
                <?= $this->PHForm->hidden('price_old', array('value' => $calculated, 'class' => 'price-old')) ?>
            </td>
            <td align="right">
                <?= ($field['price_formula']) ? '' : '<i class="icon-chevron-right"></i>' ?>
            </td>
            <td>
                <?
                echo $this->PHForm->hidden('FormPrice.' . $field['id'] . '.fk_id', array('value' => $field['id']));
                echo $this->PHForm->hidden('FormPrice.' . $field['id'] . '.kurs', array('value' => $kurs, 'class' => 'price-kurs'));
                echo $this->PHForm->input('FormPrice.' . $field['id'] . '.price', array(
                    'type' => 'text',
                    'class' => 'input-small price-orig',
                    'value' => $value,
                    'label' => false,
                    'div' => false, 'between' => false,
                    'onchange' => 'updatePrices(' . $field['id'] . ')'
                ));
                ?>
            </td>
            <td>
                <?
                echo $this->PHForm->input('FormPrice.' . $field['id'] . '.currency_from', array(
                    'class' => 'input-medium currency-from', 'options' => $kursOptions, 'value' => $currency_from,
                    'div' => false, 'between' => false, 'after' => false, 'label' => false,
                    'onchange' => 'updatePrices(' . $field['id'] . ')'
                ));
                ?>
            </td>
            <td>
                <?
                echo $this->PHForm->input('FormPrice.' . $field['id'] . '.koeff', array(
                    'type' => 'text',
                    'class' => 'input-mini price-koeff',
                    'value' => $koeff,
                    'div' => false, 'between' => false, 'after' => false, 'label' => false,
                    'onchange' => 'updatePrices(' . $field['id'] . ')'
                ));
                ?>
            </td>
            <td class="price" align="right"></td>
            <td class="price-nds" align="right"></td>
            <td align="center">
                <a class="icon-color icon-open-folder" href="<?= $historyURL ?>" target="_blank"></a>
            </td>
        </tr>
        <?
    }
    ?>
    </tbody>
</table>
<script>
    <?=$this->Price->jsFunction()?>

    function formatPrice(price, curr) {
        return (price) ? number_format(price, 2, '.', ', ') + ' ' + curr : '';
    }

    function updatePrices(id) {
        var tr = $('#tab-content-Prices tr#' + id);
        var price = parseFloat($('.price-orig', tr).val());
        var kurs = 1;
        var koeff = parseFloat($('.price-koeff', tr).val());
        if (!koeff) {
            koeff = 1;
        }
        var currencyTo = $('.price-curr-to', tr).val();
        var nds = (currencyTo == 'RUR') ? 1.18 : 1.20;
        if (price) {
            var curr = $('.currency-from', tr).val();
            for (var i = 0; i < aKurs.length; i++) {
                var row = aKurs[i].PMFormConst;
                if (row.price_kurs_from == curr && row.price_kurs_to == currencyTo) {
                    kurs = parseFloat(row.value);
                    break;
                }
            }
        } else {
            price = $('.price-old', tr).val();
        }

        $('.price-kurs', tr).val(kurs);
        $('.price', tr).html(formatPrice(price * kurs * koeff, currencyTo));
        $('.price-nds', tr).html(formatPrice(price * kurs * nds * koeff, currencyTo));
    }

    function updateCurrency() {
        var curr = $('#ProductCurrency').val();
        $('.price-table > tbody >tr .currency-from').each(function () {
            if ($('option[value="' + curr + '"]', this).length) {
                $(this).val(curr);
            } else {
                $(this).val('');
            }

        });
        updateAllPrices();
    }

    function updateAllPrices() {
        $('.price-table > tbody >tr').each(function () {
            updatePrices(this.id);
        });
    }
    var aKurs;
    $(function () {
        aKurs = <?=json_encode($aKurs)?>;
        updateAllPrices();
    });
</script>