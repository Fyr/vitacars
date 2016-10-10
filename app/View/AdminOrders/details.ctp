<?
    $this->Html->css(array('jquery.fancybox', 'bootstrap-multiselect'), array('inline' => false));
    $this->Html->script(array('vendor/jquery/jquery.fancybox', 'vendor/bootstrap-multiselect', 'vendor/tmpl.min'), array('inline' => false));

    $objectType = 'OrderProduct';
    $title = 'Счет-фактура N '.$order['Order']['id'].' от '.date('d.m.Y', strtotime($order['Order']['created']));
    echo $this->element('admin_title', compact('title'));
    $currency = $order['Order']['currency'];
?>
    <form action="<?=$this->Html->url(array('action' => 'addDetail', $order['Order']['id']))?>" method="post">
        <div class="input-append" id="filterByNumber">
            <input type="text" style="width: 200px;" placeholder="Номер детали..." onfocus="this.select()" value="" name="data[detail_num]" class="span2">
            <button type="submit" class="btn" id="bySame"><i class="icon icon-plus"></i> Добавить</button>
        </div>
    </form>
    <div style="margin: 10px 0;">
        Фильтр:
<?
    $options = array(
        'label' => false, 'class' => 'multiselect grid-filter-input', 'type' => 'select', 'multiple' => true,
        'div' => array('class' => 'inline multiMotors'),
        'options' => $aBrandOptions,
        'value' => $filterBrand
    );
    echo $this->PHForm->input('brand', $options);
?>
        <button id="submitFilter" class="btn" type="button"><i class="icon icon-search"></i> Найти</button>
        <button id="clearFilter" class="btn" type="button"><i class="icon icon-remove"></i> Очистить</button>
    </div>
<?
    $actions = $this->PHTableGrid->getDefaultActions($objectType);

    $actions['table'] = array();
    // unset($actions['row']['edit']);
    $backURL = $this->Html->url(array('action' => 'details', $order['Order']['id']));
    $url = $this->Html->url(array('action' => 'delete')).'/{$id}?model=OrderProduct&backURL='.$backURL;
    $deleteURL = $this->Html->link('', $url,
        array('class' => 'icon-color icon-delete', 'title' => 'Удалить запись'),
        'Удалить запись?'
    );
    $actions['row'] = array(
        'delete' => $deleteURL
    );
    $actions['checked']['print']['href'] = 'javascript:;';
    $actions['checked']['print']['label'] = __('Print');
    $actions['checked']['print']['icon'] = 'icon-color icon-print';
    $actions['checked']['print']['onclick'] = 'sendToPrint();return false;';

    $columns = array_merge(
        array(
            'Product.image' => array('key' => 'Product.image', 'label' => 'Фото', 'align' => 'center', 'showFilter' => false, 'showSorting' => false),
            'Category.title' => array('key' => 'Category.title', 'label' => 'Брэнд', 'showFilter' => false, 'showSorting' => true)
        ),
        $this->PHTableGrid->getDefaultColumns($objectType),
        $aColumns
    );
/*
    // Добавляем доп.поля
    foreach($aLabels as $fk => $param) {

        $columns[$fk] = array('key' => $fk, 'label' => $param['label'], 'format' => 'integer');
        $columns[$fk.'_discount'] = array('key' => $fk.'_discount', 'label' => 'Скидка, %', 'format' => 'integer');
        $columns[$fk.'_sum'] = array('key' => $fk.'_sum', 'label' => 'Сумма', 'format' => 'integer', 'nowrap' => true);
    }
*/
    $columns['Product.detail_num']['format'] = 'string';
    unset($columns['Product.id']);
    unset($columns['Product.cat_id']);
    unset($columns['Product.brand_id']);
    unset($columns['OrderProduct.number']);

    $aNumbers = array();
    $greenRows = array();
    $yellowRows = array();
    foreach($aRowset as &$row) {
        $row['Category']['title'] = $aCategories[$row['Product']['cat_id']]['title'];
        $product_id = $row['Product']['id'];
        $img = (isset($aProductMedia[$product_id])) ? $this->Media->imageUrl($aProductMedia[$product_id], '100x') : array();
        if ($img) {
            $row['Product']['image'] = $this->Html->link(
                $this->Html->image($img),
                $this->Media->imageUrl($aProductMedia[$product_id], 'noresize'),
                array('escape' => false, 'class' => 'fancybox', 'rel' => 'gallery')
            );
        } else {
            $brand_id = $row['Product']['brand_id'];
            if (isset($aBrandMedia[$brand_id])) {
                $img = $this->Media->imageUrl($aBrandMedia[$brand_id], '100x');
            }
            $row['Product']['image'] = ($img) ? $this->Html->image($img) : '<img src="/img/default_product100.png" style="width: 100px; alt="" />';
        }

        $detail_nums = explode("\n", str_replace(', ', ",\n", $row['Product']['detail_num']));
        if (count($detail_nums) > 1) {
            $items = 'номер(ов)';
            $row['Product']['detail_num'] = $this->element('AdminProduct/detail_nums', compact('detail_nums', 'items'));
        } else {
            $row['Product']['detail_num'] = implode('<br />', $detail_nums);
        }

        $aNumbers[] = $row['OrderProduct']['number'];
        $row['Product']['title_rus'] = $row['Product']['title_rus'].$this->Html->tag('span', '', array('class' => 'x-data', 'data-number' => $row['OrderProduct']['number']));

        $qty = $row['OrderProduct']['qty'];
        $row['PMFormData'] = $aFormData[$product_id]['PMFormData'];

        $params = array();
        foreach($aStockKeys as $key) {
            $params[$key] = Hash::get($row, 'PMFormData.fk_'.Configure::read('Params.'.$key));
        }
        if ($params['A1'] || $params['A2']) {
            $greenRows[] = $row['OrderProduct']['id'];
        }
        if ($params['A3'] || $params['A4'] || $params['A5'] || $params['A6']) {
            $yellowRows[] = $row['OrderProduct']['id'];
        }

        foreach($aColumns as $_field => $col) {
            $fk_id = 'fk_'.$col['id'];
            if (strpos($_field, '_discount') !== false) {
                $row['PMFormData'][$_field] = '';
            } elseif (strpos($_field, '_sum') !== false) {
                $row['PMFormData'][$_field] = '';
            }
            if (in_array($col['id'], array(Configure::read('Params.crossNumber'), Configure::read('Params.motor'), Configure::read('Params.motorTS')))) { //
                $_val = $row['PMFormData'][$fk_id];
                if (isset($col['field_type'])) {
                    if ($col['field_type'] == FieldTypes::INT) {
                        $row['PMFormData'][$fk_id] = (intval($_val)) ? $_val : '';
                    } elseif ($col['field_type'] == FieldTypes::FLOAT) {
                        $row['PMFormData'][$fk_id] = (floatval($_val)) ? number_format($_val, 2, ',', ' ') : '';
                    } elseif ($col['field_type'] == FieldTypes::MULTISELECT) {
                        $row['PMFormData'][$fk_id] = str_replace(',', '<br />', $row['PMFormData'][$fk_id]);
                    } elseif ($col['field_type'] == FieldTypes::TEXTAREA) {
                        $row['PMFormData'][$fk_id] = nl2br($row['PMFormData'][$fk_id]);
                    }
                }
                if ($fk_id == Configure::read('Params.color')) { // цвет
                    $aColors[$_val][] = $row['Product']['id'];
                }

                if ($col['field_type'] == FieldTypes::STRING) {
                    $detail_nums = explode("<br>", str_replace(array(', ', ','), '<br>', $row['PMFormData'][$fk_id]));
                } else {
                    $detail_nums = explode("<br>", str_replace(array('<br />', '<br/>'), '<br>', $row['PMFormData'][$fk_id]));
                }
                if (count($detail_nums) > 2) {
                    $num_1st = array_shift($detail_nums);
                    $items = 'строк(а)';
                    $row['PMFormData'][$fk_id] = $num_1st.$this->element('AdminProduct/detail_nums', compact('detail_nums', 'items'));
                } else {
                    $row['PMFormData'][$fk_id] = implode('<br />', $detail_nums);
                }
            }
        }
    }
    $data = $aRowset;
    $baseURL = $this->Html->url(array('action' => 'details', $order['Order']['id']));
    echo $this->PHTableGrid->render($objectType, compact('actions', 'columns', 'data', 'baseURL'));
    $aNumbers = array_values(array_unique($aNumbers));
    $ofs = 7;
?>

<table class="form-3actions" width="100%">
<tr>
    <td width="30%">
        <a class="btn" href="<?=$this->Html->url(array('action' => 'index'))?>">
            <i class="icon-chevron-left"></i>
            К списку
        </a>
    </td>
    <td width="40%" align="center">
        <?=$this->PHForm->button('<i class="icon-white icon-ok"></i> '.__('Save'), array('class' => 'btn btn-primary', 'name' => 'save', 'value' => 'save', 'onclick' => 'saveXData()'))?>
    </td>
    <td width="30%">
        <?//$this->PHForm->submit(__('Apply').' <i class="icon-white icon-chevron-right"></i>', array('class' => 'btn btn-success pull-right', 'name' => 'apply', 'value' => 'apply'))?>
    </td>
</tr>
</table>

<style type="text/css">
    .grid-header {background: #eee !important; font-weight: bold; color: #000;}
    .grid-header span {cursor: pointer; }
    .caret.large {
        border-width: 6px;
        position: relative;
        top: 8px;
        margin-right: 3px;
    }
    .caret.large.right {
        border-left: 6px solid #000;
        border-right:6px solid transparent;
        border-top: 6px solid transparent;
        border-bottom: 6px solid transparent;
        top: 3px;
        left: 3px;
    }
    .small-input { width: 50px !important; text-align: right; }
    .price-select { float: left; }
    .grid-records-count { font-weight: bold; color: #000 !important; }
    .no-wrap { white-space: nowrap; }
</style>
<script type="text/javascript">
<?=$this->Price->jsFunction($currency, true)?>

function round(number, decimals) {
    decimals = decimals || 0;
    return Math.round(number * Math.pow(10, decimals)) / Math.pow(10, decimals);
}

function normalizeSum(sum) {
    if (typeof(sum) == 'undefined') {
        sum = '0';
    }
    sum = sum.replace(/\s/g, '').replace(/\,/g, '.');
    return (sum) ? parseFloat(sum) : 0;
}

function recalcSum(id, price_id) {
    var qty = normalizeSum($('#qty-' + id).val());

    var price = normalizeSum($('#' + price_id).html());
    var discount = normalizeSum($('#discount-' + id).val());
    var sum = qty * price;
    sum = round(sum - sum * discount / 100, 2); // вычисляем с учетом скидки
    $('#sum-' + id).html(sum ? Price.format(sum) : '');
    return sum;
}

function recalcRow(id) {
    price_id = $('#row_' + id + ' .price-select:checked').prop('id');
    return recalcSum(id, price_id.replace(/priceselect/, 'price'));
}

function recalcAllRows() {
    var total = 0;
    $('.grid-row').each(function(){
        var id = $(this).prop('id');
        if (id.indexOf('row') > -1) {
            id = id.replace(/row_/, '');
            var sum = recalcRow(id);
            if ($(this).hasClass('grid-row-selected')) {
                total += sum;
            }
        }
    });
    $('.grid-records-count').html('Итого: ' + Price.format(total));
}

function checkAllPrices(fk_id) {
    $('tbody .grid-row').each(function(){
        if ($('.price-select', this).length && $('.price-select', this).hasClass('price-fk_' + fk_id)) {
            $('.price-select', this).attr({checked: false}).prop({checked: false});
            $('.price-fk_' + fk_id, this).attr({checked: true}).prop({checked: true});
        }
    });
    recalcAllRows();
}

function changeAllDiscount() {
    var val = $('[name="discount-all"]').val();
    $('tbody .grid-row .discount').val(val);
    recalcAllRows();
}

$(function() {
    $('.detail-nums .expand-num').click(function(){
        $(this).hide();
        $(this).parent().find('.collapse-num').show();
        $(this).parent().find('div').slideDown('fast', function(){
            // $(this).parent().find('.collapse-num').show();
        });
    });
    $('.detail-nums .collapse-num').click(function(){
        $(this).parent().find('div').slideUp('fast', function(){
            $(this).parent().find('.collapse-num').hide();
            $(this).parent().find('.expand-num').show();
        });

    });

    $('#grid_OrderProduct table.grid > tbody > tr.grid-row').each(function(){
        var $td = $('td:eq(<?=$ofs?>)', this);
        var id = $td.parent().prop('id').replace(/row_/, '');
        $td.html(Format.tag('input', {
            type: 'text',
            class: 'small-input',
            id: 'qty-' + id,
            value: $td.html(),
            placeholder: '&gt;' + $td.html(),
            onfocus: 'this.select()',
            onchange: 'recalcAllRows()'
        }));
<?
    $i = 1;
    $headerHtml = '<th></th><th></th>';
    foreach($columns as $col) {
        $i++;
        // create header
        if (Hash::get($col, 'is_price'))  {
            $headerHtml.= '<th><input id="price-select-all_'.$col['id'].'" type="radio" class="price-select" name="price-all" onclick="checkAllPrices('.$col['id'].')" /></th>';
        } elseif (Hash::get($col, 'id') == 'discount') {
            $headerHtml.= '<th><input type="text" class="small-input" name="discount-all" onchange="changeAllDiscount()" /></th>';
        } else {
            $headerHtml.= '<th></th>';
        }

        if (Hash::get($col, 'is_price')) {
?>
        $td = $('td:eq(<?=$i?>)', this);
        if ($td.html()) {
            var selectPrice = Format.tag('input', {
                type: 'radio',
                id: 'priceselect' + id + '-fk_<?=$col['id']?>',
                name: 'price-' + id,
                class: 'price-select price-fk_<?=$col['id']?>',
                onchange: 'recalcAllRows()'
            });
            var htmlPrice = Format.tag('span', {id: 'price' + id + '-fk_<?=$col['id']?>', class: 'no-wrap'}, $td.html());
            $td.html(selectPrice + htmlPrice);
        }
<?
        } elseif (Hash::get($col, 'id') == 'discount') {
?>
        $td = $('td:eq(<?=$i?>)', this);
        $td.html(Format.tag('input', {
            type: 'text',
            class: 'small-input discount',
            id: 'discount-' + id,
            value: $td.html(),
            onfocus: 'this.select()',
            onchange: 'recalcAllRows()'
        }));
<?
        } elseif (Hash::get($col, 'id') == 'row_sum') {
?>
        $td = $('td:eq(<?=$i?>)', this);
        $td.html(Format.tag('span', {id: 'sum-' + id, class: 'no-wrap'}));
<?
        }
    }
?>
        $('[type=radio]:first', this).attr('checked', 'checked');
    });

    $('#grid_OrderProduct table.grid > thead').append('<tr><?=$headerHtml?></tr>');
    aNumbers = <?=json_encode($aNumbers)?>;
    cols = $('.grid thead > tr:eq(0) > th').length;
    for(var i = 0; i < aNumbers.length; i++) {
        number = aNumbers[i];
        var $span = $('span.x-data[data-number="' + number + '"]:eq(0)');
        var $row = $span.parent().parent();
        $row.before(tmpl('grid-header', {n: i + 1, number: number, cols: cols, items: $('span.x-data[data-number="' + number + '"]').length}));
        var setHandler = function(number) {
            $('#gh-' + number + ' span').click(function () {
                $('#gh-' + number + ' span').toggle();
                $('span.x-data[data-number="' + number + '"]').parent().parent().toggle();
            });
        };
        setHandler(number);
    }

    $('.grid-chbx-checkAll, .grid-chbx-row').change(function(){
        setTimeout(recalcAllRows, 200);
    });

<?
    if ($order['Order']['xdata']) {
?>
    var xdata = JSON.parse('<?=$order['Order']['xdata']?>');
    applyXData(xdata);
<?
    } else {
?>

    $('.grid-chbx-checkAll').click();
<?
    }
?>


    $('#brand').multiselect({
        nonSelectedText: 'Выберите бренд',
        nSelectedText: 'выбрано'
    });

    $('#submitFilter').click(function(){
        window.location.href = grid_OrderProduct.getURL({'Product.brand_id': $('#brand').val()});
    });

    $('#clearFilter').click(function(){
        window.location.href = grid_OrderProduct.settings.baseURL;
    });

    var colorRows = <?=json_encode(compact('greenRows', 'yellowRows'))?>;
    if (colorRows.greenRows.length) {
        for(var i = 0; i < colorRows.greenRows.length; i++) {
            $('#row_' + colorRows.greenRows[i]).addClass('legend-green');
        }
    }
    if (colorRows.yellowRows.length) {
        for(var i = 0; i < colorRows.yellowRows.length; i++) {
            $('#row_' + colorRows.yellowRows[i]).addClass('legend-yellow');
        }
    }
});

function getXData() {
    var json_data = {};
    $('.grid-chbx-row:checked').each(function(){
        var id = this.value;
        var price_id = $('[name="price-' + id + '"]:checked').prop('id');
        var qty = normalizeSum($('#qty-' + id).val());
        var price = normalizeSum($('#' + price_id.replace(/select/, '')).html());
        var discount = normalizeSum($('#discount-' + id).val());
        json_data[id] = {price: price, qty: qty, discount: discount, price_id: price_id};
    });
    return json_data;
}

function saveXData() {
    $('#xdata').val(JSON.stringify(getXData()));
    $('#brand_ids').val(($('#brand').val() || []).join(','));
    $('#saveForm').submit();
}

function sendToPrint() {
    $('#json_data').val(JSON.stringify(getXData()));
    $('#printForm').submit();
}

function applyXData(xdata) {
    for(var id in xdata) {
        var row = xdata[id];
        $('#qty-' + id).val(row.qty);
        $('#discount-' + id).val(row.discount);
        $('#' + row.price_id).click();
        $('.grid-chbx-row[value="' + id + '"]').click();
    }
}
</script>
<script type="text/x-tmpl" id="grid-header">
<tr id="gh-{%=o.number%}" class="grid-row">
    <td class="grid-header" colspan="{%=o.cols%}">
        <span style="display: none"><b class="caret large right"></b>N {%=o.n%}. {%=o.number%} ({%=o.items%} {%=(o.items == 1 ? 'позиция' : 'позиции')%})</span>
        <span><b class="caret large"></b>N {%=o.n%}. {%=o.number%}</span>
    </td>
</tr>
</script>
<form id="saveForm" action="<?=$this->Html->url(array('action' => 'details', $order['Order']['id']))?>" method="post">
<?
    echo $this->Form->hidden('xdata');
    echo $this->Form->hidden('brand_ids');
?>
</form>
<form id="printForm" action="<?=$this->Html->url(array('action' => 'printXls', $order['Order']['id']))?>" method="post">
<?
    echo $this->Form->hidden('json_data');
?>
</form>