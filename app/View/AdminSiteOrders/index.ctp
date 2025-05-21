<?

    $title = $this->ObjectType->getTitle('index', $objectType);
    $createURL = $this->Html->url(array('action' => 'edit', 0));
    $createTitle = $this->ObjectType->getTitle('create', $objectType);
    $actions = $this->PHTableGrid->getDefaultActions($objectType);
    $actions['table']['add']['href'] = $createURL;
    $actions['table']['add']['label'] = $createTitle;

    $backURL = $this->Html->url(array('action' => 'index'));
    $deleteURL = $this->Html->url(array('action' => 'delete')).'/{$id}?model=SiteOrder&backURL='.urlencode($backURL);
    $actions['row']['delete'] = $this->Html->link('', $deleteURL, array('class' => 'icon-color icon-delete', 'title' => __('Delete record')), __('Are you sure to delete this record?'));

    foreach($aRows as &$row) {
        $row['SiteOrder']['zone'] = strtoupper($row['SiteOrder']['zone']);

        $group_id = ($row['Client']['group_id']) ? $row['Client']['group_id'] : 0;
        if ($group_id == Client::GROUP_USER) {
            // substitute actual client's data
            $row['SiteOrder']['username'] = $row['Client']['fio'];
            $row['SiteOrder']['phone'] = $row['Client']['phone'];
        } else if ($group_id == Client::GROUP_COMPANY) {
            // substitute actual company's data
            $row['SiteOrder']['username'] = $row['SiteOrderCompany']['contact_person'];
            $row['SiteOrder']['phone'] = $row['SiteOrderCompany']['contact_phone'];

            $uuid = "ИНН: ".$row['SiteOrderCompany']['company_uuid'];
            $address = "Юр.адрес: ".$row['SiteOrderCompany']['address'];
            $infoIcon = $this->Html->link('', 'javascript:void(0)', array('class' => 'icon-color icon-info', 'title' => $uuid."\n".$address));
            $row['SiteOrderCompany']['company_name'] = $infoIcon.' '.$row['SiteOrderCompany']['company_name'];
        }

        $row['Client']['group_id'] = Client::getOptions($group_id);

        $comment = trim($row['SiteOrder']['comment']);
        $infoIcon = ($comment) ? $this->Html->link('', 'javascript:void(0)', array('class' => 'icon-color icon-info', 'title' => $comment)) : '';
        $row['SiteOrder']['address'] = $infoIcon.' '.nl2br($row['SiteOrder']['address']);

        $class = 'icon-color icon-check'.(($row['SiteOrder']['completed']) ? '' : ' inactive');
        $row['SiteOrder']['completed'] = $this->Html->link('', 'javascript:void(0)', array('class' => $class, /* 'title' => __('Activate') */));
    }

    $columns = $this->PHTableGrid->getDefaultColumns('SiteOrder');
    unset($columns['SiteOrder.comment']);
    $columns['SiteOrder.zone']['align'] = 'center';
    $columns['SiteOrder.username']['label'] = __('Fio');
    $columns['SiteOrder.address']['label'] = __('Delivery');
    $columns['SiteOrder.completed']['align'] = 'center';
    $columns['SiteOrder.completed']['format'] = 'string';
    $columns['Client.*'] = array(
        'key' => 'Client.group_id',
        'label' => __('Client Type'),
        'format' => 'string'
    );
    $columns['SiteOrderCompany.*'] = array(
        'key' => 'SiteOrderCompany.company_name',
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
