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

    if ($objectType == 'Category') {
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
        'actions' => $actions
    ));
?>