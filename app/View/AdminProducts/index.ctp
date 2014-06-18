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
    	) : '';
    	if (Hash::check($row, $paramMotor.'.value')) {
    		$row[$paramMotor]['value'] = str_replace(',', ' ', $row[$paramMotor]['value']);
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
		$options = $this->PHformFields->getSelectOptions(Hash::get($motorOptions, 'FormField.options'));
		$options = array('label' => false, 'class' => 'multiselect', 'type' => 'select', 'multiple' => true, 'options' => $options, 'div' => array('class' => 'inline'));
		echo $this->PHForm->input('motor', $options);
	}
?>
		<div id="filterByNumber" class="input-append">
			<input class="span2" type="text" onfocus="this.select()" placeholder="Введите номер запчасти" style="width: 200px;">
			<button class="btn" type="button"><i class="icon icon-search"></i> Найти</button>
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
	
	var filterNum = $('#grid-filter-<?=$paramDetail?>-value').val();
	if (filterNum) {
		$('#filterByNumber input').val(filterNum.replace(/\*/g, ''));
	}
	
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
			submitFilter();
		}
	});
	
	$('#filterByNumber input').change(function(){
		submitFilter();
	});
	$('#filterByNumber button').click(function(){
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
	var filterNum = $('#filterByNumber input').val();
	$('#grid-filter-<?=$paramDetail?>-value').val((filterNum) ? '*' + filterNum + '*' : '');
	
	var filterMotor = $('#motor').val();
	$('#grid-filter-<?=$paramMotor?>-value').val((filterMotor) ? '*' + filterMotor.join('*') + '*' : '');
	
	grid_Product.submitFilter();
}
</script>
<form id="printXls" method="post" action="<?= $this->Html->url(array('controller' => 'AdminProducts', 'action' => 'printXls')) ?>"><input type="hidden" name="aID" /></form>