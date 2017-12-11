<?
	$title = $this->ObjectType->getTitle('index', $objectType);

    $createURL = $this->Html->url(array('action' => 'edit', 0));
    $createTitle = $this->ObjectType->getTitle('create', $objectType);
$actions = $this->PHTableGrid->getDefaultActions('PMFormConst');
    $actions['table']['add']['href'] = $createURL;
    $actions['table']['add']['label'] = $createTitle;
    
    $backURL = $this->Html->url(array('action' => 'index'));
    $deleteURL = $this->Html->url(array('action' => 'delete')).'/{$id}?model=Form.PMFormField&backURL='.urlencode($backURL);
    $actions['row']['delete'] = $this->Html->link('', $deleteURL, array('class' => 'icon-color icon-delete', 'title' => __('Delete record')), __('Are you sure to delete this record?'));

foreach ($data as &$row) {
        $fieldType = $row['PMFormConst']['field_type'];
        $row['PMFormConst']['field_type'] = $aFieldTypes[$fieldType];

    $row['PMFormConst']['is_price_kurs'] = ($row['PMFormConst']['is_price_kurs'])
        ? $row['PMFormConst']['price_kurs_from'] . '&nbsp;<i class="icon-chevron-right"></i>&nbsp;' . $row['PMFormConst']['price_kurs_to']
        : '';
    }

    $columns = $this->PHTableGrid->getDefaultColumns('PMFormConst');
    $columns['PMFormConst.field_type']['format'] = 'string';
unset($columns['PMFormConst.price_kurs_from']);
unset($columns['PMFormConst.price_kurs_to']);
$columns['PMFormConst.is_price_kurs']['label'] = 'Пересчет цен';
$columns['PMFormConst.is_price_kurs']['format'] = 'string';

?>
<?=$this->element('admin_title', compact('title'))?>
<div class="text-center">
    <a class="btn btn-primary" href="<?=$createURL?>">
        <i class="icon-white icon-plus"></i> <?=$createTitle?>
    </a>
    <a class="btn btn-success" href="<?=$this->Html->url(array('controller' => 'AdminForms', 'action' => 'recalcFormula'))?>">
        <i class="icon-white icon-refresh"></i> Пересчитать формулы
    </a>
</div>
<br/>
<?
echo $this->PHTableGrid->render('PMFormConst', compact('actions', 'data', 'columns'));
?>