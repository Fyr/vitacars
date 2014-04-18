<?
	$this->Html->css('jquery.fancybox.css', array('inline' => false));
	$this->Html->script('vendor/jquery/jquery.fancybox', array('inline' => false));

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
    }
?>
<?=$this->element('admin_title', compact('title'))?>
<div class="text-center">
	<div id="filterByNumber" class="input-append pull-left">
		<input class="span2" type="text" onfocus="this.select()" placeholder="Введите номер запчасти" style="width: 200px;">
		<button class="btn" type="button">Найти</button>
	</div>
	<a class="btn btn-primary" href="<?=$createURL?>">
		<i class="icon-white icon-plus"></i> <?=$createTitle?>
	</a>
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
	if ($('#grid-filter-Param2-value').val()) {
		$('#filterByNumber input').val($('#grid-filter-Param2-value').val().replace(/\*/g, ''));
	}
	$('#filterByNumber input').keypress(function(event){
		if (event.which == 13) {
			event.preventDefault();
			submitFilterByNumber();
		}
	});
	
	$('#filterByNumber input').change(function(){
		submitFilterByNumber();
	});
	$('#filterByNumber button').click(function(){
		submitFilterByNumber();
	});
	
	$('.fancybox').fancybox({
		padding: 5
	});
});

function submitFilterByNumber() {
	$('#grid-filter-Param2-value').val('*' + $('#filterByNumber input').val() + '*');
	grid_Product.submitFilter();
}
</script>