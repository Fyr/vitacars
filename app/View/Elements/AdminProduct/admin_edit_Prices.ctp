<style>
    .price-table th, .price-table td {
        padding: 3px 7px;
    }
</style>
<table class="price-table">
    <thead>
    <tr>
        <th colspan="3"></th>
        <th>
            <?
            echo $this->PHForm->input('currency', array(
                'class' => 'input-medium', 'options' => $aCurrency,
                'div' => false, 'between' => false, 'after' => false, 'label' => false,
                'onchange' => 'updateCurrency()'
            ));
            ?>
        </th>
        <th>Коэфф.</th>
        <th>Цена по курсу</th>
        <th>Цена с НДС</th>
    </tr>
    </thead>
    <tbody>
    <?
    $xPrices = Hash::combine($xPrices, '{n}.FormPrice.fk_id', '{n}.FormPrice');
    foreach ($form as $field) {
        $field = $field['PMFormField'];
        $value = '';
        $formatted = '';
        $koeff = 1.00;
        $kurs = 1;
        $currency_from = '';
        if ($field['field_type'] == FieldTypes::FORMULA) {
            // перевести из символьного в числовой формат
            // если записывать формулу в формате float - придется много где править
            $formatted = $this->request->data('PMFormData.fk_' . $field['id']); // значение уже отформатировано
            if (isset($xPrices[$field['id']])) {
                $value = $xPrices[$field['id']]['price'];
                $kurs = $xPrices[$field['id']]['kurs'];
                $currency_from = $xPrices[$field['id']]['currency_from'];
                $formatted = $xPrices[$field['id']]['calculated'];
                $koeff = $xPrices[$field['id']]['koeff'];
            }
        } else if ($field['field_type'] == FieldTypes::FLOAT || $field['field_type'] == FieldTypes::INT) {
            $value = $this->request->data('PMFormData.fk_' . $field['id']); // значение уже отформатировано
            $formatted = '<i class="icon-chevron-right"></i>'; // форматируем по умолчанию
        }
        $options = array(
            'class' => 'input-small price-orig', 'type' => 'text',
            'value' => $value,
            'label' => false,
            'div' => false, 'between' => false,
            'onchange' => 'updatePrices(' . $field['id'] . ')'
        );

        list($label, $curr) = explode(',', $field['label']);
        $curr = trim($curr);
        $kursOptions = array('' => $curr . ' =');
        foreach ($aKurs as $const) {
            $const = $const['PMFormConst'];
            if ($const['price_kurs_to'] == $curr) {
                $kursOptions[$const['price_kurs_from']] = $const['price_kurs_from'] . ' *' . $const['value'];
            }
        }
        ?>
        <tr id="<?= $field['id'] ?>">
            <td>
                <?= $field['label'] ?>
                <?= $this->PHForm->hidden('price_curr', array('value' => $curr, 'class' => 'price-curr')) ?>
                <?= $this->PHForm->hidden('PMFormData.fk_' . $field['id'] . '_kurs', array('value' => $kurs, 'class' => 'price-kurs')) ?>
            </td>
            <td align="right"><?= $formatted ?></td>
            <td>
                <?= $this->PHForm->input('PMFormData.fk_' . $field['id'], $options) ?>
            </td>
            <td>
                <?
                echo $this->PHForm->input('PMFormData.fk_' . $field['id'] . '_from', array(
                    'class' => 'input-medium price-currency', 'options' => $kursOptions, 'value' => $currency_from,
                    'div' => false, 'between' => false, 'after' => false, 'label' => false,
                    'onchange' => 'updatePrices(' . $field['id'] . ')'
                ));
                ?>
            </td>
            <td>
                <?
                if ($field['field_type'] == FieldTypes::FORMULA) {
                    echo $this->PHForm->input('PMFormData.fk_' . $field['id'] . '_koeff', array(
                        'class' => 'input-small price-koeff', 'value' => $koeff,
                        'div' => false, 'between' => false, 'after' => false, 'label' => false,
                        'onchange' => 'updatePrices(' . $field['id'] . ')'
                    ));
                }
                ?>
            </td>
            <td class="price" align="right"></td>
            <td class="price-nds" align="right"></td>
        </tr>
        <?
    }
    ?>
    </tbody>
</table>
<script>
    <?=$this->Price->jsFunction()?>

    function formatPrice(price, curr) {
        return number_format(price, 2, '.', ', ') + ' ' + curr;
    }

    function updatePrices(id) {
        var tr = $('#tab-content-Prices tr#' + id);
        var price = parseFloat($('.price-orig', tr).val());
        var koeff = parseFloat($('.price-koeff', tr).val());
        if (!koeff) {
            koeff = 1;
        }
        $('.price', tr).html('');
        $('.price-nds', tr).html('');
        $('.price-kurs', tr).val(1);
        if (price) {
            var curr = $('.price-currency', tr).val();
            var priceCurr = $('.price-curr', tr).val();
            var nds = (priceCurr == 'RUR') ? 1.18 : 1.20;
            $('.price', tr).html(formatPrice(price * koeff, priceCurr));
            $('.price-nds', tr).html(formatPrice(price * nds * koeff, priceCurr));
            for (var i = 0; i < aKurs.length; i++) {
                var row = aKurs[i].PMFormConst;
                if (row.price_kurs_from == curr && row.price_kurs_to == priceCurr) {
                    var kurs = parseFloat(row.value);
                    $('.price-kurs', tr).val(kurs);
                    var price1 = price * kurs * koeff;
                    $('.price', tr).html(formatPrice(price1, priceCurr));
                    $('.price-nds', tr).html(formatPrice(price1 * nds, priceCurr));
                }
            }
        }
    }

    function updateCurrency() {
        var curr = $('#ProductCurrency').val();
        $('.price-table > tbody >tr .price-currency').each(function () {
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