<?
	echo $this->PHTableGrid->render('User', array(
		'actions' => array(
			'table' => array(),
			'row' => array(),
			'checked' => array()
		),
		'baseURL' => $this->Html->url(array('action' => 'edit', $this->request->data('User.id')))
	));
	echo $this->PHForm->hidden('Notify.users', array('value' => $this->request->data('Notify.users')));
?>

