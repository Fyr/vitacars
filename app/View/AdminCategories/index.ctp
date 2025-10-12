<?
	$title = $this->ObjectType->getTitle('index', $objectType);
    $createURL = $this->Html->url(array('action' => 'edit'));
    $createTitle = $this->ObjectType->getTitle('create', $objectType);

    $columns = $this->PHTableGrid->getDefaultColumns($objectType);
    $columns['Category.export_by']['label'] = __('Export By');
    $columns['Category.export_ru']['label'] = __('Export Ru');

    $actions = $this->PHTableGrid->getDefaultActions($objectType);
    $actions['row'][] = array(
        'label' => $this->ObjectType->getTitle('index', 'Subcategory'),
        'class' => 'icon-color icon-open-folder',
        'href' => $this->Html->url(array('controller' => 'AdminSubcategories', 'action' => 'index', '')).'/{$id}'
    );
?>
<?=$this->element('admin_title', compact('title'))?>
<div class="text-center">
    <a class="btn btn-primary" href="<?=$createURL?>">
        <i class="icon-white icon-plus"></i> <?=$createTitle?>
    </a>
</div>
<br/>
<?
    echo $this->PHTableGrid->render($objectType, compact('actions', 'columns'));
?>
