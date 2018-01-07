<?
	$this->Html->css(array('jquery.fancybox', 'bootstrap-multiselect'), array('inline' => false));
	$this->Html->script(array('vendor/jquery/jquery.fancybox', 'vendor/bootstrap-multiselect' ), array('inline' => false));

	$title = $this->ObjectType->getTitle('index', $objectType);
    $createURL = $this->Html->url(array('action' => 'edit', 0));
    $createTitle = $this->ObjectType->getTitle('create', $objectType);

    $actions = $this->PHTableGrid->getDefaultActions($objectType);
	// unset($actions['checked'][0]);
/*
	$actions['checked']['del']['href'] = 'javascript:;';
	$actions['checked']['del']['label'] = __('Delete');
	$actions['checked']['del']['icon'] = 'icon-color icon-delete';
	$actions['checked']['del']['onclick'] = 'deleteChecked();return false;';
*/
    $actions['checked']['print']['href'] = $this->Html->url(array('action' => 'printXls'));
    $actions['checked']['print']['label'] = __('Print');
    $actions['checked']['print']['icon'] = 'icon-color icon-print';
    $actions['checked']['print']['onclick'] = 'sendToPrint();return false;';

	$actions['checked']['print_brand']['href'] = $this->Html->url(array('action' => 'printXls'));
	$actions['checked']['print_brand']['label'] = 'Печать брендов (фильтр)';
	$actions['checked']['print_brand']['icon'] = 'icon-color icon-print';
	$actions['checked']['print_brand']['onclick'] = 'sendToPrintBrands(1);return false;';

	$actions['checked']['print_brand_all']['href'] = $this->Html->url(array('action' => 'printXls'));
	$actions['checked']['print_brand_all']['label'] = 'Печать брендов (нул.остатки)';
	$actions['checked']['print_brand_all']['icon'] = 'icon-color icon-print';
	$actions['checked']['print_brand_all']['onclick'] = 'sendToPrintBrands();return false;';

    if ($isAdmin) {
    	$actions['table']['add']['href'] = $createURL;
    	$actions['table']['add']['label'] = $createTitle;
    } else {
    	// unset($actions['table']);
		unset($actions['checked']['delete']);
    	$actions['table'] = array();
    	$actions['row'] = array();
    }

    $columns = array_merge(
    	array(
    		'Product.image' => array('key' => 'Product.image', 'label' => 'Фото', 'align' => 'center', 'showFilter' => false, 'showSorting' => false),
    		'Category.title' => array('key' => 'Category.title', 'label' => 'Категория', 'showFilter' => false, 'showSorting' => true)
    	),
    	$this->PHTableGrid->getDefaultColumns($objectType)
    );
    $columns['Category.title']['label'] = 'Брeнд';
	$columns['Product.detail_num']['format'] = 'string';

	if (isset($aCols)) {
		$columns = array_merge($columns, $aCols);
	}

	$field = 'PMFormData.fk_'.Configure::read('Params.crossNumber');
	if (isset($columns[$field])) {
		$columns[$field]['format'] = 'string';
	}
	$field = 'PMFormData.fk_'.Configure::read('Params.motor');
	if (isset($columns[$field])) {
		$columns[$field]['format'] = 'string';
	}
	$field = 'PMFormData.fk_'.Configure::read('Params.x_info');
	if (isset($columns[$field])) {
		$columns[$field]['format'] = 'string';
	}
    /*
    unset($columns['Media.id']);
    unset($columns['Media.object_type']);
    unset($columns['Media.file']);
    unset($columns['Media.ext']);
    */
	unset($columns['Product.cat_id']);
    unset($columns['Product.brand_id']);
    unset($columns['PMFormData.fk_23']);
	unset($columns['PMFormData.fk_23']);
	unset($columns['PMFormData.fk_'.Configure::read('Params.discountComment')]);

    foreach($columns as $key => &$column) {
    	if (isset($aLabels[$key])) {
    		$column['label'] = $aLabels[$key];
    	}
    }
    $aColors = array();

foreach($aRowset as &$row) {
		$row['Category']['title'] = $aCategories[$row['Product']['cat_id']]['title'];
    	$img = (isset($aProductMedia[$row['Product']['id']])) ? $this->Media->imageUrl($aProductMedia[$row['Product']['id']], '100x') : array();
    	if ($img) {
	    	$row['Product']['image'] = $this->Html->link(
	    		$this->Html->image($img),
	    		$this->Media->imageUrl($aProductMedia[$row['Product']['id']], 'noresize'),
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
    	/*
    	if (isset($paramMotor) && Hash::check($row, 'PMFormData.'.$paramMotor)) {
    		$row['PMFormData'][$paramMotor] = str_replace(',', '<br />', $row['PMFormData'][$paramMotor]);
    	}
    	*/
    	foreach($row['PMFormData'] as $_field => $_val) {
			$field_id = str_replace('fk_', '', $_field);
			if (isset($aParams[$field_id])) {
				if ($aParams[$field_id]['PMFormField']['field_type'] == FieldTypes::INT) {
					$row['PMFormData'][$_field] = (intval($_val)) ? $_val : '';
				} elseif ($aParams[$field_id]['PMFormField']['field_type'] == FieldTypes::FLOAT) {
					$row['PMFormData'][$_field] = (floatval($_val)) ? number_format($_val, 2, ',', ' ') : '';
				} elseif ($aParams[$field_id]['PMFormField']['field_type'] == FieldTypes::MULTISELECT) {
					$row['PMFormData'][$_field] = str_replace(',', '<br />', $row['PMFormData'][$_field]);
				} elseif ($aParams[$field_id]['PMFormField']['field_type'] == FieldTypes::TEXTAREA) {
					$row['PMFormData'][$_field] = nl2br($row['PMFormData'][$_field]);
				} elseif ($aParams[$field_id]['PMFormField']['field_type'] == FieldTypes::FORMULA) {
					// форумла уже хранится отформатированной
				} elseif ($aParams[$field_id]['PMFormField']['field_type'] == FieldTypes::PRICE) {
					// форматируем по ходу
					$row['PMFormData'][$_field] = $this->Price->formatPrice($row['PMFormData'][$_field], $aParams[$field_id]['PMFormField']);
				}
			}
			if ($field_id == Configure::read('Params.color')) { // выделение строки цветом
				$aColors[$_val][] = $row['Product']['id'];
			} elseif ($field_id == Configure::read('Params.discountPrice') && Hash::get($row['PMFormData'], 'fk_'.Configure::read('Params.discount'))) { // цена со скидкой
				// $row['PMFormData'][$_field] = $this->Html->tag('span', $row['PMFormData'][$_field], array('class' => 'discount'));

				if ($comment = Hash::get($row['PMFormData'], 'fk_'.Configure::read('Params.discountComment'))) {
					$row['PMFormData'][$_field] = $this->Html->tag('span', $row['PMFormData'][$_field], array('class' => 'discount')).' '.
						$this->Html->link('', 'javascript::void(0)', array('class' => 'icon-color icon-info', 'title' => $comment, 'rel' => "tooltip"));
				} else {
					$comment = 'Окончательная цена со скидкой';
					$row['PMFormData'][$_field] = $this->Html->link($row['PMFormData'][$_field], 'javascript::', array('class' => 'discount', 'title' => $comment));
				}
			}

			// выделение цветом отдельных ячеек
			/*
			foreach(Configure::read('Params.fkColor') as $class => $fk_keys) {
				if (in_array($field_id, $fk_keys)) {
					// $row['PMFormData'][$_field]
				}
			}
			*/

			foreach(array('crossNumber', 'motor', 'motorTS', 'x_info') as $key) {
				if ($field_id == Configure::read('Params.'.$key)) { //
					if ($aParams[$field_id]['PMFormField']['field_type'] == FieldTypes::STRING) {
						$detail_nums = explode("<br>", str_replace(array(', ', ','), '<br>', $row['PMFormData'][$_field]));
					} else {
						$detail_nums = explode("<br>", str_replace(array('<br />', '<br/>'), '<br>', $row['PMFormData'][$_field]));
					}
					if (count($detail_nums) > 1) {
						$items = 'строк(а)';
						$row['PMFormData'][$_field] = $this->element('AdminProduct/detail_nums', compact('detail_nums', 'items'));
					} else {
						$row['PMFormData'][$_field] = implode('<br />', $detail_nums);
					}
				}
			}
		}
    }
?>
<?//$this->element('admin_title', compact('title'))?>
<h3 class="text-left"><?=$title?></h3>
<div class="">
<?
	if ($isAdmin) {
?>
	<a class="btn btn-primary" href="<?=$createURL?>">
		<i class="icon-white icon-plus"></i> <?=$createTitle?>
	</a>
<?
	}
?>
	<div style="margin-top: 10px;">
		Фильтр:
<?
	if ($isAdmin) {
		$motorOptions = $this->PHFormFields->getSelectOptions(Hash::get($motorOptions, 'PMFormField.options'));
		$options = array(
			'label' => false, 'class' => 'multiselect', 'type' => 'select', 'multiple' => true, 'div' => array('class' => 'inline multiMotors'), 
			'options' => $motorOptions, 'value' => (isset($motorFilterValue)) ? $motorFilterValue : null
		);
		echo $this->PHForm->input('motor', $options);
	}
	$options = array(
		'label' => false, 'class' => 'multiselect', 'type' => 'select', 'multiple' => true, 'div' => array('class' => 'inline multiMotors'),
		'options' => $aBrandOptions,
		'value' => (isset($brandsFilterValue)) ? $brandsFilterValue :  null
	);
	echo $this->PHForm->input('brand', $options);
?>
		<div id="filterByNumber" class="input-append">
			<input class="span2" type="text" name="" value="<?=(isset($detail_num)) ? $detail_num : ''?>" onfocus="this.select()" placeholder="Поиск по номерам" style="width: 200px;">
			<!--button id="byNumber" class="btn" type="button"><i class="icon icon-search"></i> Найти</button-->
			<button id="bySame" class="btn" type="button"><i class="icon icon-search"></i> Найти</button>
		</div>
		<button id="clearFilter" class="btn" type="button"><i class="icon icon-remove"></i> Очистить</button>
	</div>
	
</div>
<br/>

<?
    echo $this->PHTableGrid->render('Product', array(
        'baseURL' => $this->ObjectType->getBaseURL($objectType),
        'columns' => $columns,
        'actions' => $actions,
        'data' => $aRowset
    ));

?>
<br />

<style>
	#grid_Product .grid .fixed { position: fixed; top: 43px; left: 40px; margin-left: 1px;}
	#grid_Product .grid .duplicateHead .grid-filter { display: none !important;}
	#grid_Product .grid .originalHead .grid-filter { background: #fff;}
	#grid_Product .grid .discount { font-weight: bold; color: #f00; }
	#grid_Product .grid .discount:hover { text-decoration: none; }
	#grid_Product .grid .fk-green {font-weight: bold; color: #0a0;}
</style>

<script type="text/javascript">
$(document).ready(function(){
	var getFilterURL = grid_Product.getFilterURL;
	grid_Product.getFilterURL = function(params) {
		if ($('#brand').val()) {
			params['Product.brand_id'] = $('#brand').val().join(' ');
		}
		return getFilterURL(params);
	}

	var deleteChecked = grid_Product.deleteChecked;
	grid_Product.deleteChecked = function(deleteURL) {
		if (confirm('Вы уверены, что хотите удалить помеченные записи?')) {
			var checkedAll = $('.grid-chbx-checkAll:checked').length && $('.grid-chbx-row:checked').length == $('.grid-chbx-row').length;
			console.log($('#brand').val(), $('#motor').val());
			var brandChecked = ($('#brand').val() && $('#brand').val().length) ? $('#brand').val().join(',') : '';
			var motorChecked = ($('#motor').val() && $('#motor').val().length) ? $('#motor').val().join(',') : '';
			if (checkedAll && !$('#filterByNumber input').val() && (brandChecked || motorChecked)) {
				deleteURL = deleteURL.replace(/\{\$id\}/, '') + '&brand_id=' + brandChecked + '&motor=' + motorChecked;
				window.location.href = deleteURL + '&backURL=' + this.getURL();
			} else {
				deleteChecked(deleteURL);
			}
		}
	}

	colorifyCells(<?=json_encode(Configure::read('Params.fkColor'))?>);
	hideEmptyColumns();

	var tableHeadWidth = $('#grid_Product .grid thead').width();
	
	$('#grid_Product .grid thead').clone().insertAfter('#grid_Product .grid thead').addClass('duplicateHead');
	$('#grid_Product .grid .duplicateHead').hide();
	$('#grid_Product .grid .duplicateHead input').prop('id', '').prop('name', '').prop('class', '');
	
	$('#grid_Product .grid thead:first-child').addClass('originalHead');
	$('#grid_Product .grid .originalHead').width(tableHeadWidth);
	
	$('#grid_Product .grid .originalHead .first th').each( function(index,element) {
		$(this).css({"max-width":$(this).width()+"px", "min-width":$(this).width()+"px"});
	});
	
	$(window).scroll(function() {
		var topOfWindow = $(window).scrollTop();
		var leftOfWindow = $(window).scrollLeft();
		
		$('#grid_Product .grid .originalHead').css("left", -leftOfWindow+40+"px");
		
		if ( topOfWindow > 154 ) {
			$('#grid_Product .grid .duplicateHead').show();
			$('#grid_Product .grid .originalHead').addClass('fixed');
			$('#grid_Product .grid .originalHead .grid-filter th').css('border-bottom',"1px solid #dddddd");
		}
		else {
			$('#grid_Product .grid .duplicateHead').hide();
			$('#grid_Product .grid .originalHead').removeClass('fixed');
			$('#grid_Product .grid .originalHead .grid-filter th').css('border-bottom',"none");
		}
		
	});

	$('#motor').multiselect({
		nonSelectedText: 'Выберите мотор',
		nSelectedText: 'выбрано'
	});
	$('#brand').multiselect({
		nonSelectedText: 'Выберите бренд',
		nSelectedText: 'выбрано'
	});
	
	$('#filterByNumber input').keypress(function(event){
		if (event.which == 13) {
			event.preventDefault();
			
			// $('#grid-filter-Product-detail_num').val('*' + $('#filterByNumber input').val());
			// submitFilter();
			$('#bySame').click();
		}
	});
	
	$('#byNumber').click(function(){
		$('#grid-filter-Product-detail_num').val('*' + $('#filterByNumber input').val());
		submitFilter();
	});
	
	$('#bySame').click(function(){
		$('#grid-filter-Product-detail_num').val('~' + $('#filterByNumber input').val());
		submitFilter();
	});
	
	$('#clearFilter').click(function(){
		$('#filterByNumber input').val('');
		$('#motor').val([]);
		submitFilter();
	});
	
	$('.fancybox').fancybox({
		padding: 5
	});

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

	aColors = <?=json_encode($aColors)?>;
	if (aColors[2]) {
		for(var i = 0; i < aColors[2].length; i++) {
			var id = aColors[2][i];
			$('#row_' + id).addClass('legend-red');
		}
	}
	if (aColors[1]) {
		for(var i = 0; i < aColors[1].length; i++) {
			var id = aColors[1][i];
			$('#row_' + id).addClass('legend-yellow');
		}
	}
});

function submitFilter() {
	var filterMotor = $('#motor').val();
	$('#grid-filter-PMFormData-fk_6').val((filterMotor) ? '*' + filterMotor.join(' ') : '');
	
	grid_Product.submitFilter();
}

function sendToPrint() {
    selectedId = new Array();
    $('input[name="gridChecked[]"]').each(function(id) {
        if ($(this).is(':checked')) {
            selectedId.push($(this).val());
        }
    })
    $('input[name="aID"]').val(selectedId.join(','));
    $('#printXls').submit();
}

function sendToPrintBrands(nonZeroAmount) {
	$('input[name="brandID"]').val($('#brand').val().join(','));
	$('input[name="aID"]').val('');
	$('input[name="nonZeroAmount"]').val(nonZeroAmount);
	$('#printXls').submit();
}

function colorifyCells(fkColor) {
	var fkCells = {};
	for(fkClass in fkColor) {
		fkCells[fkClass] = [];
		$('#grid_Product > table.grid > thead > tr.first > th').each(function(i, e){
			var fk = $(e).data('grid_col');
			if (fk && in_array(parseInt(fk.replace(/PMFormData\.fk_/, '')), fkColor[fkClass])) {
				fkCells[fkClass].push(i);
			}
		});
	}
	for(fkClass in fkCells) {
		for(var i = 0; i < fkCells[fkClass].length; i++) {
			var fkPos = fkCells[fkClass][i];
			$('#grid_Product > table.grid > tbody > tr > td:nth-child(' + (fkPos+1) + ')').addClass(fkClass);
		}
	}
}

function isColumnEmpty(col) {
	var $cells = $('#grid_Product > table.grid > tbody > tr > td:nth-child(' + (col+1) + ')');
	for (var i = 0; i < $cells.length; i++) {
		if ($cells.get(i).innerHTML) {
			return false;
		}
	}
	return true;
}

function hideColumn(i) {
	$('#grid_Product > table.grid > tbody > tr > td:nth-child(' + (i+1) + ')').addClass('hidden');
	$('#grid_Product > table.grid > thead > tr > th:nth-child(' + (i+1) + ')').addClass('hidden');
	var $lastRow = $('#grid_Product > table.grid > tbody > tr#last-tr');
	$lastRow.attr('colspan', $lastRow.attr('colspan') - 1);
}

function hideEmptyColumns() {
	$('#grid_Product > table.grid > thead > tr.first > th').each(function(i, e){
		if (isColumnEmpty(i)) {
			hideColumn(i);
		}
	});
}

</script>
<form id="printXls" method="post" action="<?= $this->Html->url(array('controller' => 'AdminProducts', 'action' => 'printXls')) ?>">
	<input type="hidden" name="aID" />
	<input type="hidden" name="brandID" />
	<input type="hidden" name="nonZeroAmount" />
</form>