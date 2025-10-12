<?
    $cat_id = Hash::get($category, 'Category.id');
	$title = Hash::get($category, 'Category.title').': '.$this->ObjectType->getTitle('index', $objectType);
    $createURL = $this->Html->url(array('action' => 'edit', Hash::get($category, 'Category.id')));
    $createTitle = $this->ObjectType->getTitle('create', $objectType);

    $actions = $this->PHTableGrid->getDefaultActions($objectType);
    $actions['table']['add']['href'] = $this->Html->url(array('controller' => 'AdminSubcategories', 'action' => 'edit', $cat_id));
    $actions['row']['edit']['href'] = $this->Html->url(array('controller' => 'AdminSubcategories', 'action' => 'edit', $cat_id)).'/{$id}';

    $backURL = $this->Html->url(array('action' => 'index', $cat_id));
    $deleteURL = $this->Html->url(array('action' => 'delete')).'/{$id}?model=Subcategory&backURL='.urlencode($backURL);
    $actions['row']['delete'] = $this->Html->link('', $deleteURL, array('class' => 'icon-color icon-delete', 'title' => __('Delete record')), __('Are you sure to delete this record?'));
?>
<?=$this->element('admin_title', compact('title'))?>
<div class="text-center">
    <a class="btn btn-primary" href="<?=$createURL?>">
        <i class="icon-white icon-plus"></i> <?=$createTitle?>
    </a>
</div>
<br/>
<?
    echo $this->PHTableGrid->render($objectType, compact('actions'));
?>
