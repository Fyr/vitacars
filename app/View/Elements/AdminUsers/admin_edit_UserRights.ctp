<?
echo $this->PHTableGrid->render('PMFormField', array(
		'actions' => array(
			'table' => array(),
			'row' => array(),
			'checked' => array()
		),
		'baseURL' => $this->Html->url(array('action' => 'edit', $this->request->data('User.id')))
	));
	echo $this->PHForm->hidden('User.field_rights', array('value' => $this->request->data('User.field_rights')));
?>

