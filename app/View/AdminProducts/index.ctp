<?
	$this->Html->css(array('jquery.fancybox', 'bootstrap-multiselect'), array('inline' => false));
	$this->Html->script(array('vendor/jquery/jquery.fancybox', 'vendor/bootstrap-multiselect' ), array('inline' => false));

	$title = $this->ObjectType->getTitle('index', $objectType);
    $createURL = $this->Html->url(array('action' => 'edit', 0));
    $createTitle = $this->ObjectType->getTitle('create', $objectType);
    $actions = $this->PHTableGrid->getDefaultActions($objectType);
    $actions['checked']['add']['href'] = $this->Html->url(array('action' => 'printXls'));
    $actions['checked']['add']['label'] = __('Print selected records');
    $actions['checked']['add']['icon'] = 'icon-color icon-print';
    $actions['checked']['add']['onclick'] = 'sendToPrint();return false;';
    if ($isAdmin) {
    	$actions['table']['add']['href'] = $createURL;
    	$actions['table']['add']['label'] = $createTitle;
    } else {
    	// unset($actions['table']);
        unset($actions['checked'][0]);
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
    
    unset($columns['Media.id']);
    unset($columns['Media.object_type']);
    unset($columns['Media.file']);
    unset($columns['Media.ext']);
    unset($columns['Product.brand_id']);
    unset($columns['PMFormData.fk_23']);
    foreach($columns as $key => &$column) {
    	if (isset($aLabels[$key])) {
    		$column['label'] = $aLabels[$key];
    	}
    }
    $aColors = array();
    foreach($aRowset as &$row) {
    	$img = $this->Media->imageUrl($row, '100x');
    	if ($img) {
	    	$row['Product']['image'] = $this->Html->link(
	    		$this->Html->image($img),
	    		$this->Media->imageUrl($row, 'noresize'),
	    		array('escape' => false, 'class' => 'fancybox', 'rel' => 'gallery')
	    	);
    	} else {
    		$brand_id = $row['Product']['brand_id'];
    		if (isset($aBrandMedia[$brand_id])) {
	    		$img = $this->Media->imageUrl($aBrandMedia[$brand_id], '100x');
    		}
    		$row['Product']['image'] = ($img) ? $this->Html->image($img) : '<img src="/img/default_product100.png" style="width: 100px; alt="" />';
    	}
	    	
    	$row['Product']['detail_num'] = str_replace(' ', '<br />', $row['Product']['detail_num']);
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
				}
			}
			if ($field_id == Configure::read('Params.color')) { // цвет
				$aColors[$_val][] = $row['Product']['id'];
			}
    	}
    }
?>
<?=$this->element('admin_title', compact('title'))?>
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

<style>
	.grid .fixed { position: fixed; top: 43px; left: 40px; margin-left: 1px;}
	.grid .duplicateHead .grid-filter { display: none !important;}
	.grid .originalHead .grid-filter { background: #fff;}
</style>

<script type="text/javascript">
$(document).ready(function(){
	var tableHeadWidth = $('.grid thead').width();
	
	$('.grid thead').clone().insertAfter('.grid thead').addClass('duplicateHead');
	$('.grid .duplicateHead').hide();
	$('.grid .duplicateHead input').prop('id', '').prop('name', '').prop('class', '');
	
	$('.grid thead:first-child').addClass('originalHead');
	$('.grid .originalHead').width(tableHeadWidth);
	
	$('.grid .originalHead .first th').each( function(index,element) {
		$(this).css({"max-width":$(this).width()+"px", "min-width":$(this).width()+"px"});
	});
	
	$(window).scroll(function() {
		var topOfWindow = $(window).scrollTop();
		var leftOfWindow = $(window).scrollLeft();
		
		$('.grid .originalHead').css("left", -leftOfWindow+40+"px");
		
		if ( topOfWindow > 154 ) {
			$('.grid .duplicateHead').show();
			$('.grid .originalHead').addClass('fixed');
			$('.grid .originalHead .grid-filter th').css('border-bottom',"1px solid #dddddd");
		}
		else {
			$('.grid .duplicateHead').hide();
			$('.grid .originalHead').removeClass('fixed');
			$('.grid .originalHead .grid-filter th').css('border-bottom',"none");
		}
		
	});
	

	$('.multiselect').multiselect({
		nonSelectedText: 'Выберите мотор',
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
</script>
<form id="printXls" method="post" action="<?= $this->Html->url(array('controller' => 'AdminProducts', 'action' => 'printXls')) ?>"><input type="hidden" name="aID" /></form>