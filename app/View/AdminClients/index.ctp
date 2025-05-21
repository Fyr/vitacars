<?
	$title = $this->ObjectType->getTitle('index', $objectType);
    $createURL = $this->Html->url(array('action' => 'edit', 0));
    $createTitle = $this->ObjectType->getTitle('create', $objectType);
    $actions = $this->PHTableGrid->getDefaultActions($objectType);
    $actions['table']['add']['href'] = $createURL;
    $actions['table']['add']['label'] = $createTitle;

    $backURL = $this->Html->url(array('action' => 'index'));
    $deleteURL = $this->Html->url(array('action' => 'delete')).'/{$id}?model=Client&backURL='.urlencode($backURL);
    $actions['row']['delete'] = $this->Html->link('', $deleteURL, array('class' => 'icon-color icon-delete', 'title' => __('Delete record')), __('Are you sure to delete this record?'));

    foreach($aRows as &$row) {
        $row['Client']['zone'] = strtoupper($row['Client']['zone']);

        $class = 'icon-color icon-check'.(($row['Client']['active']) ? '' : ' inactive');
        $row['Client']['active'] = $this->Html->link('', 'javascript:void(0)', array('class' => $class, /* 'title' => __('Activate') */));

        if ($row['Client']['group_id'] == Client::GROUP_COMPANY) {
            $row['Client']['fio'] = Hash::get($row, 'ClientCompany.contact_person');
            $row['Client']['phone'] = Hash::get($row, 'ClientCompany.contact_phone');

            $uuid = "ИНН: ".Hash::get($row, 'ClientCompany.company_uuid');
            $address = "Юр.адрес: ".Hash::get($row, 'ClientCompany.address');
            $infoIcon = $this->Html->link('', 'javascript:void(0)', array('class' => 'icon-color icon-info', 'title' => $uuid."\n".$address));
            $row['ClientCompany']['company_name'] = $infoIcon.' '.Hash::get($row, 'ClientCompany.company_name');
        }

        $row['Client']['group_id'] = Client::getOptions($row['Client']['group_id']);
    }

    $columns = $this->PHTableGrid->getDefaultColumns('Client');
    $columns['Client.zone']['align'] = 'center';
    $columns['Client.active']['align'] = 'center';
    $columns['Client.active']['format'] = 'string';
    $columns['Client.group_id']['label'] = __('Client Type');
    $columns['Client.group_id']['format'] = 'string';
    $columns['Client.group_id']['align'] = 'center';
    $columns['ClientCompany.*'] = array(
        'key' => 'ClientCompany.company_name',
        'label' => __('Company'),
        'format' => 'string'
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
    echo $this->PHTableGrid->render($objectType, array('actions' => $actions, 'data' => $aRows, 'columns' => $columns));
?>
