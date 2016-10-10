<?
    $objectType = 'Order';
	$title = $this->ObjectType->getTitle('index', $objectType);
    $createURL = $this->Html->url(array('action' => 'edit'));
    $createTitle = $this->ObjectType->getTitle('create', $objectType);
    $actions = $this->PHTableGrid->getDefaultActions($objectType);
    $actions['table']['add']['href'] = $createURL;
    $actions['table']['add']['label'] = $createTitle;

    $detailURL = $this->Html->url(array('action' => 'details')).'/{$id}';
    $actions['row']['info'] = $this->Html->link('', $detailURL, array('class' => 'icon-color icon-open-folder', 'title' => 'Посмотреть позиции'));
    
    $backURL = $this->Html->url(array('action' => 'index'));
    $deleteURL = $this->Html->url(array('action' => 'delete')).'/{$id}?model=Order&backURL='.urlencode($backURL);
    $actions['row']['delete'] = $this->Html->link('', $deleteURL, array('class' => 'icon-color icon-delete', 'title' => __('Delete record')), __('Are you sure to delete this record?'));

    $columns = $this->PHTableGrid->getDefaultColumns($objectType);
    $columns = array_merge(
        array('Order.n_id' => array(
            'key' => 'Order.n_id',
            'label' => 'N счет-фактуры',
            'format' => 'string'
        )),
        $columns
    );
    $columns['Order.agent_id']['label'] = 'Поставщик';
    $columns['Order.agent2_id']['label'] = 'Получатель';
    $columns['Order.agent_id']['format'] = 'string';
    $columns['Order.agent2_id']['format'] = 'string';
    $columns['Order.items']['label'] = 'Позиций';
    $columns['Order.nds']['label'] = 'НДС, %';
    unset($columns['Order.currency']);

    foreach($aRowset as &$row) {
        $row['Order']['n_id'] = 'N '.$row['Order']['id'].' от '.date('d.m.Y', strtotime($row['Order']['created']));

        $agent_id = $row['Order']['agent_id'];
        $row['Order']['agent_id'] = (isset($aAgentOptions[$agent_id])) ? $aAgentOptions[$agent_id] : '';

        $agent_id = $row['Order']['agent2_id'];
        $row['Order']['agent2_id'] = (isset($aAgentOptions[$agent_id])) ? $aAgentOptions[$agent_id] : '';

        $row['Order']['sum'] = (floatval($row['Order']['sum'])) ? $this->Price->format($row['Order']['sum'], $row['Order']['currency']) : '-';
    }
    $data = $aRowset;
?>
<?=$this->element('admin_title', compact('title'))?>
<div class="text-center">
    <a class="btn btn-primary" href="<?=$createURL?>">
        <i class="icon-white icon-plus"></i> <?=$createTitle?>
    </a>
</div>
<br/>
<?
    echo $this->PHTableGrid->render($objectType, compact('actions', 'columns', 'data'));
?>