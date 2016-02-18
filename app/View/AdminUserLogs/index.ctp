<?
	$title = __('System events');
    $actions = $this->PHTableGrid->getDefaultActions('UserLog');
    unset($actions['table']['add']);
    $actions['row'] = array();
    $cols = $this->PHTableGrid->getDefaultColumns('UserLog');

    $cols['UserLog.user_id']['label'] = __('Username');
    $cols['UserLog.user_id']['format'] = 'select';
    $cols['UserLog.user_id']['options'] = $aUsers;

    $cols['UserLog.event_type']['format'] = 'select';
    $cols['UserLog.event_type']['options'] = EventType::getTypes();

    $cols['UserLog.ip']['label'] = 'IP адрес';
    $cols['UserLog.host']['label'] = 'Адрес хоста';
    $cols['UserLog.xdata']['label'] = 'Доп.данные';

    echo $this->element('admin_title', compact('title'));
    echo $this->PHTableGrid->render('UserLog', array('actions' => $actions, 'columns' => $cols));
?>