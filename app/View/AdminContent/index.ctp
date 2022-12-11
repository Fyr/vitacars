<?
	$title = $this->ObjectType->getTitle('index', $objectType);
	if ($objectType == 'Subcategory' && $objectID) {
		$title = $category['Category']['title'].': '.$title;
	}
    $createURL = $this->Html->url(array('action' => 'edit', 0, $objectType, $objectID));
    $createTitle = $this->ObjectType->getTitle('create', $objectType);
    $actions = $this->PHTableGrid->getDefaultActions($objectType);
    $actions['table']['add']['href'] = $createURL;
    $actions['table']['add']['label'] = $createTitle;

    $columns = $this->PHTableGrid->getDefaultColumns($objectType);
    if ($objectType == 'Category') {
        $columns['Category.export_bg']['label'] = 'Экспорт для .BG';
        $columns['Category.export_by']['label'] = 'Экспорт для .BY';
    	$actions['row'][] = array(
    		'label' => $this->ObjectType->getTitle('index', 'Subcategory'), 
    		'class' => 'icon-color icon-open-folder', 
    		'href' => $this->Html->url(array('action' => 'index', 'Subcategory')).'/{$id}'
    	);
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
    echo $this->PHTableGrid->render($objectType, array(
        'baseURL' => $this->ObjectType->getBaseURL($objectType, $objectID),
        'columns' => $columns,
        'actions' => $actions
    ));
?>