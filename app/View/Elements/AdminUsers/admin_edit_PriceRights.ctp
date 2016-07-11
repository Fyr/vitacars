<?
	echo $this->PHTableGrid->render('FormField', array(
		'actions' => array(
			'table' => array(),
			'row' => array(),
			'checked' => array()
		),
		'container_id' => 'grid_PriceField'
	));
	echo $this->PHForm->hidden('User.price_rights', array('value' => $this->request->data('User.price_rights')));
?>

