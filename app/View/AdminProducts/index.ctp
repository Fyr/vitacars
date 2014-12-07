<?
	$this->Html->css(array('jquery.fancybox', 'bootstrap-multiselect'), array('inline' => false));
	$this->Html->script(array('vendor/jquery/jquery.fancybox', 'vendor/bootstrap-multiselect'), array('inline' => false));

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
    	array('Product.image' => array(
    		'key' => 'Product.image', 'label' => 'Фото', 'align' => 'center', 
    		'showFilter' => false, 'showSorting' => false
    	)),
    	$this->PHTableGrid->getDefaultColumns($objectType)
    );
    $columns['Product.detail_num']['format'] = 'string';
    
    unset($columns['Media.id']);
    unset($columns['Media.object_type']);
    unset($columns['Media.file']);
    unset($columns['Media.ext']);
    foreach($columns as $key => &$column) {
    	if (isset($aLabels[$key])) {
    		$column['label'] = $aLabels[$key];
    	}
    }
    foreach($aRowset as &$row) {
    	$img = $this->Media->imageUrl($row, '100x50');
    	$row['Product']['image'] = ($img) ? $this->Html->link(
    		$this->Html->image($img),
    		$this->Media->imageUrl($row, 'noresize'),
    		array('escape' => false, 'class' => 'fancybox', 'rel' => 'gallery')
    	) : '<img src="/img/default_product.jpg" style="width: 50px; alt="" />';
    	$row['Product']['detail_num'] = str_replace(' ', '<br />', $row['Product']['detail_num']);
    	if (Hash::check($row, 'PMFormData.'.$paramMotor)) {
    		$row['PMFormData'][$paramMotor] = str_replace(',', '<br />', $row['PMFormData'][$paramMotor]);
    	}
    	foreach($row['PMFormData'] as $_field => $_val) {
			$field_id = str_replace('fk_', '', $_field);
			if (isset($aParams[$field_id])) {
				if ($aParams[$field_id]['PMFormField']['field_type'] == FieldTypes::INT) {
					$row['PMFormData'][$_field] = (intval($_val)) ? $_val : '';
				} elseif ($aParams[$field_id]['PMFormField']['field_type'] == FieldTypes::FLOAT) {
					$row['PMFormData'][$_field] = (floatval($_val)) ? number_format($_val, 2, ',', ' ') : '';
				}
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
		$options = array('label' => false, 'class' => 'multiselect', 'type' => 'select', 'multiple' => true, 'options' => $motorOptions, 'div' => array('class' => 'inline'));
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
<script type="text/javascript">
$(document).ready(function(){
	
	var filterMotor = $('#grid-filter-<?=$paramMotor?>-value').val();
	if (filterMotor) {
		$('#motor').val(filterMotor.slice(1, -1).split('*'));
	}
	$('.multiselect').multiselect({
		nonSelectedText: 'Выберите мотор',
		nSelectedText: 'выбрано'
	});
	
	$('#filterByNumber input').keypress(function(event){
		if (event.which == 13) {
			event.preventDefault();
			
			$('#grid-filter-Product-detail_num').val('*' + $('#filterByNumber input').val());
			submitFilter();
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
});

function submitFilter() {
	var filterMotor = $('#motor').val();
	$('#grid-filter-<?=$paramMotor?>-value').val((filterMotor) ? '*' + filterMotor.join('*') + '*' : '');
	
	grid_Product.submitFilter();
}
</script>
<form id="printXls" method="post" action="<?= $this->Html->url(array('controller' => 'AdminProducts', 'action' => 'printXls')) ?>"><input type="hidden" name="aID" /></form>