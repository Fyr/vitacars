<?
    $columns = $this->PHTableGrid->getDefaultColumns('Brand');

    // add one more column for discounts
    $columns['Brand.discount']['key'] = 'discount';
    $columns['Brand.discount']['label'] = __('Discount');
    $columns['Brand.discount']['format'] = 'string';
    $columns['Brand.discount']['align'] = 'right';

    foreach($aRows as &$row) {
        $id = $row['Brand']['id'];
        $row['Brand']['discount'] = $this->Form->input('Brand.discount', array(
            'class' => 'hidden',
            'name' => 'data[ClientBrandDiscount][brand_'.$id.']',
            'value' => (isset($aDiscounts[$id])) ? $aDiscounts[$id] : '',
            'div' => false,
            'label' => false,
            'id' => false,
            // 'onchange' => set change flag to minimize DB operations
            'onblur' => 'updateBrandDiscount('.$id.', false)'
            )).'<span>'.((isset($aDiscounts[$id])) ? $aDiscounts[$id].'%' : '').'</span>';
    }

    echo $this->PHTableGrid->render('Brand', array(
        'columns' => $columns,
        'data' => $aRows,
        'actions' => array(
            'table' => array(),
            'row' => array(),
            'checked' => array()
        )
    ));

?>
<style>
.grid .grid-row td:nth-child(4) input { width: 30px !important; }
.grid .grid-row td:nth-child(4) input.hidden { width: 0 !important; }
.grid .grid-row td:nth-child(4) input.error { border: 1px solid #f00; }
</style>

<script>
function updateBrandDiscount(brand_id, lShow) {
    var $td = $('.grid #row_' + brand_id + ' td:nth-child(4)');
    if (lShow) {
        $('span', $td).html('%');
        $('input', $td).removeClass('hidden').focus();
    } else {
        var val = $('input', $td).val().trim().replace(',', '.');
        if (val && isNaN(val)) {
            $('input', $td).addClass('error').focus();
            return;
        }
        $('input', $td).val(val).addClass('hidden').removeClass('error');
        $('span', $td).html(val ? val + '%' : '');
    }
}

$(function() {
    $('.grid .grid-row td:nth-child(4)').click(function () {
        var brand_id = $(this).parent().prop('id').replace('row_', '');
        updateBrandDiscount(brand_id, true);
    });
});
</script>
