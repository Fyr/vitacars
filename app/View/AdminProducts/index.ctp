<?
	$title = $this->ObjectType->getTitle('index', $objectType);
    $createURL = $this->Html->url(array('action' => 'edit', 0));
    $createTitle = $this->ObjectType->getTitle('create', $objectType);
    $actions = $this->PHTableGrid->getDefaultActions($objectType);
    $actions['table']['add']['href'] = $createURL;
    $actions['table']['add']['label'] = $createTitle;
    
    $columns = array_merge(
    	array('Product.image' => array(
    		'key' => 'Product.image', 'label' => 'Фото', 'format' => 'img', 'align' => 'center', 
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
    	$row['Product']['image'] = $this->Media->imageUrl($row, '100x50');
    }
?>
<?=$this->element('admin_title', compact('title'))?>
<div class="text-center">
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