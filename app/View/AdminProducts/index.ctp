<?
	$this->Html->css(array('jquery.fancybox', 'bootstrap-multiselect'), array('inline' => false));
	$this->Html->script(array('vendor/jquery/jquery.fancybox', 'vendor/bootstrap-multiselect'), array('inline' => false));

	$title = $this->ObjectType->getTitle('index', $objectType);
    $createURL = $this->Html->url(array('action' => 'edit', 0));
    $createTitle = $this->ObjectType->getTitle('create', $objectType);
    $actions = $this->PHTableGrid->getDefaultActions($objectType);
    $actions['table']['add']['href'] = $createURL;
    $actions['table']['add']['label'] = $createTitle;
    
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
    	
    	if (Hash::check($row, 'Param3.value')) {
    		$row['Param3']['value'] = str_replace(',', ' ', $row['Param3']['value']);
    	}
    }
?>
<?=$this->element('admin_title', compact('title'))?>
<div class="">
	<a class="btn btn-primary" href="<?=$createURL?>">
		<i class="icon-white icon-plus"></i> <?=$createTitle?>
	</a>
	<div style="margin-top: 10px;">
		Фильтр:
<?
	$options = $this->PHformFields->getSelectOptions(Hash::get($paramMotor, 'FormField.options'));
	$options = array('label' => false, 'class' => 'multiselect', 'type' => 'select', 'multiple' => true, 'options' => $options, 'div' => array('class' => 'inline'));
	echo $this->PHForm->input('motor', $options);
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
	
	var filterNum = $('#grid-filter-Param2-value').val();
	if (filterNum) {
		$('#filterByNumber input').val(filterNum.replace(/\*/g, ''));
	}
	
	var filterMotor = $('#grid-filter-Param3-value').val();
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
	$('#grid-filter-Param2-value').val((filterNum) ? '*' + filterNum + '*' : '');
	
	var filterMotor = $('#motor').val();
	$('#grid-filter-Param3-value').val((filterMotor) ? '*' + filterMotor.join('*') + '*' : '');
	
	grid_Product.submitFilter();
}
</script>