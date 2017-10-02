<?
	$title = 'Сообщения';
    $actions = $this->PHTableGrid->getDefaultActions('Message');
    $columns = $this->PHTableGrid->getDefaultColumns('Message');
    $columns['Message.active']['label'] = 'Прочитано';
    $actions['table'] = array();

    $actions['row']['edit'] = $this->Html->link('',
        $this->Html->url(array('action' => 'view')).'/{$id}',
        array('class' => 'icon-color icon-preview', 'title' => 'Посмотреть сообщение')
    );
    $backURL = $this->Html->url(array('action' => 'messageList'));
    $actions['row']['delete'] = $this->Html->link('',
        $this->Html->url(array('action' => 'delete')).'/{$id}?model=Message&backURL='.urlencode($backURL),
        array('class' => 'icon-color icon-delete', 'title' => __('Delete record')),
        __('Are you sure to delete this record?')
    );

    foreach($data as &$row) {
        $row['Message']['created'] = $this->PHTime->niceShort($row['Message']['created']);
        $row['Message']['title'] = $this->Html->link($row['Message']['title'],
            array('action' => 'view', $row['Message']['id']),
            array('class' => ($row['Message']['active']) ? 'unread' : '', 'title' => 'Посмотреть сообщение')
        );

        $row['Message']['active'] = !$row['Message']['active'];
    }

    echo $this->element('admin_title', compact('title'));
    echo $this->PHTableGrid->render('Message', compact('actions', 'data', 'columns'));
?>
