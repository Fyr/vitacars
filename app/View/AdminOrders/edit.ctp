<?
    $this->Html->script('vendor/tmpl.min', array('inline' => false));
    $objectType = 'OrderProduct';
    $title = 'Счет-фактура N '.$order['Order']['id'].' от '.date('d.m.Y', strtotime($order['Order']['created']));
    echo $this->element('admin_title', compact('title'));

    $actions = $this->PHTableGrid->getDefaultActions($objectType);
    $actions['table'] = array();
    $actions['row'] = array();

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
        if (count($detail_nums) > 2) {
            $num_1st = array_shift($detail_nums);
            $items = 'номер(ов)';
            $row['Product']['detail_num'] = $num_1st.'<br />'.$this->element('AdminProduct/detail_nums', compact('detail_nums', 'items'));
        } else {
            $row['Product']['detail_num'] = implode('<br />', $detail_nums);
        }

        $aNumbers[] = $row['OrderProduct']['number'];
        $row['Product']['title_rus'] = $row['Product']['title_rus'].$this->Html->tag('span', '', array('class' => 'x-data', 'data-number' => $row['OrderProduct']['number']));

        $qty = $row['OrderProduct']['qty'];
        $row['PMFormData'] = $aFormData[$product_id]['PMFormData'];

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
    echo $this->PHTableGrid->render($objectType, compact('actions', 'columns', 'data'));
    $aNumbers = array_values(array_unique($aNumbers));
    $ofs = 7;
?>
<div class="pull-left">
    <a class="btn" href="<?=$this->Html->url(array('action' => 'index'))?>">
        <i class="icon-chevron-left"></i>
        К списку
    </a>
</div>
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
</style>
<script type="text/javascript">
<?=$this->Price->jsFunction()?>
function normalizeSum(sum) {
    if (typeof(sum) == 'undefined') {
        sum = '0';
    }
    sum = sum.replace(/\s/g, '').replace(/\,/g, '.');
    return (sum) ? parseFloat(sum) : 0;
}

function recalcSum(id, fk) {
    var qty = normalizeSum($('#qty-' + id).val());

    var price = normalizeSum($('#price-' + id + '-fk_' + fk).val());
    var discount = normalizeSum($('#discount-' + id + '-fk_' + fk).val());
    var sum = Math.round(qty * price - (qty * price) * discount / 100, 2);
    $('#sum-' + id + '-fk_' + fk).val(sum ? sum : '');
    recalcTotalSum(fk);
}

function recalcTotalSum(fk) {
    var total = 0;
    $('.sum-fk_' + fk).each(function(){
        total+= normalizeSum($(this).val());
    });
    $('#total-sum_' + fk).html(total);
}

function recalcRow(id) {
<?
    foreach($aColumns as $fk => $col) {
        if ($col['is_price']) {
?>

    recalcSum(id, <?=$col['id']?>);
<?
        }
    }
?>
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
/*
    var $tdFooter = $('#grid_OrderProduct table.grid > tbody > tr.grid-footer > td');
    $tdFooter.attr('colspan', <?=$ofs - 1?>);
    $('.grid-paging', $tdFooter).remove();
    $('.grid-records-count', $tdFooter).remove();
    $tdFooter.parent().append('<td align="right">Всего:</td>');
    $tdFooter.parent().append('<td id="total-qty" align="right"></td>');
<?
/*
    foreach($aLabels as $fk => $param) {
?>
    $tdFooter.parent().append('<td id="total-price_<?=$param['id']?>" align="right"></td>');
    $tdFooter.parent().append('<td id="total-discount_<?=$param['id']?>" align="right"></td>');
    $tdFooter.parent().append('<td id="total-sum_<?=$param['id']?>" align="right"></td>');
<?
    }
*/
?>
    $tdFooter.parent().append('<td colspan="10"></td>');
    */

    $('#grid_OrderProduct table.grid > tbody > tr.grid-row').each(function(){
        var $td = $('td:eq(<?=$ofs?>)', this);
        var id = $td.parent().prop('id').replace(/row_/, '');
        $td.html(Format.tag('input', {
            type: 'text',
            size: 5,
            id: 'qty-' + id,
            value: $td.html(),
            placeholder: '&gt;' + $td.html(),
            style: 'text-align: right',
            onfocus: 'this.select()',
            onchange: 'recalcRow(' + id + ')'
        }));
<?

    $i = 1;
    $_columns = array_values($columns);
    $price = array();
    foreach($columns as $fk => $param) {
        $i++;
        if (isset($param['is_price']) && $param['is_price']) {
            $param['col_num'] = $i;
            $price = $param;
?>
        $td = $('td:eq(<?=$i?>)', this);
        $td.html(Format.tag('input', {
            type: 'text',
            size: 10,
            id: 'price-' + id + '-fk_<?=$param['id']?>',
            value: $td.html(),
            placeholder: ($td.html()) ? '=' + $td.html() : '- нет цены -',
            style: 'text-align: right',
            onfocus: 'this.select()',
            onchange: 'recalcSum(' + id + ', <?=$param['id']?>)'
        }));
<?
        } elseif (strpos($param['key'], '_discount')) {
?>
        $td = $('td:eq(<?=$i?>)', this);
        $td.html(Format.tag('input', {
            type: 'text',
            size: 3,
            id: 'discount-' + id + '-fk_<?=$param['id']?>',
            value: $td.html(),
            placeholder: '0%',
            style: 'text-align: right',
            onfocus: 'this.select()',
            onchange: 'recalcSum(' + id + ', <?=$param['id']?>)'
        }));
<?
        } elseif (strpos($param['key'], '_sum')) {
?>
        $td = $('td:eq(<?=$i?>)', this);
        $td.html(Format.tag('input', {
            type: 'text',
            size: 10,
            id: 'sum-' + id + '-fk_<?=$param['id']?>',
            class: 'sum-fk_<?=$param['id']?>',
            value: $td.html(),
            placeholder: '<?=$price['label']?>',
            style: 'text-align: right',
            onfocus: 'this.select()',
            onchange: 'recalcTotalSum(<?=$param['id']?>)'
        }));
<?
        }
    }
?>

    });
    aNumbers = <?=json_encode($aNumbers)?>;
    cols = $('.grid thead > tr:eq(0) > th').length;
    for(var i = 0; i < aNumbers.length; i++) {
        number = aNumbers[i];
        var $span = $('span.x-data[data-number="' + number + '"]:eq(0)');
        var $row = $span.parent().parent();
        $row.before(tmpl('grid-header', {number: number, cols: cols, items: $('span.x-data[data-number="' + number + '"]').length}));
        var setHandler = function(number) {
            $('#gh-' + number + ' span').click(function () {
                $('#gh-' + number + ' span').toggle();
                $('span.x-data[data-number="' + number + '"]').parent().parent().toggle();
            });
        };
        setHandler(number);
    }

    $('.grid-row').each(function(){
        var id = $(this).prop('id');
        if (id.indexOf('row') > -1) {
            id = id.replace(/row_/, '');
            recalcRow(id);
        }
    });
});
</script>
<script type="text/x-tmpl" id="grid-header">
<tr id="gh-{%=o.number%}" class="grid-row">
    <td class="grid-header" colspan="{%=o.cols%}">
        <span style="display: none"><b class="caret large right"></b>{%=o.number%} ({%=o.items%} {%=(o.items == 1 ? 'позиция' : 'позиции')%})</span>
        <span><b class="caret large"></b>{%=o.number%}</span>
    </td>
</tr>

</script>