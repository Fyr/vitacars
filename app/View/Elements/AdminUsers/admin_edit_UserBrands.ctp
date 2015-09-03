<?
	echo $this->PHTableGrid->render('Brand', array(
		'actions' => array(
			'table' => array(),
			'row' => array(),
			'checked' => array()
		)
	));
	echo $this->PHForm->hidden('User.brand_rights', array('value' => $this->request->data('User.brand_rights')));
?>
