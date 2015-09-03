<?
	echo $this->PHTableGrid->render('FormField', array(
		'actions' => array(
			'table' => array(),
			'row' => array(),
			'checked' => array()
		)
	));
	echo $this->PHForm->hidden('User.field_rights', array('value' => $this->request->data('User.field_rights')));
?>

