<div class="span8 offset2">
<?
	$title = $this->ObjectType->getTitle('create', 'Order');
	echo $this->element('admin_title', compact('title'));
	echo $this->PHForm->create('UploadCsv', array(
		'url' => array(
			'controller' => 'AdminOrders',
			'action' => 'upload'
		),
		'method' => 'post',
		'enctype' => 'multipart/form-data'
	));
	echo $this->element('admin_content');
	echo $this->element('AdminOrders/admin_edit_Order');
	echo $this->PHForm->input(__('Select file'), array(
		'class' => 'input-medium',
		'type' => 'file',
		'name' => 'csv_file',
		'id' => 'csv_file',
		'after' => '<div class="small-text">Формат файла: {код};{кол-во} (CSV, без заголовков)</div></div>'
	));
?>
<?
	echo $this->element('admin_content_end');
	echo $this->PHForm->submit(__('Upload').' <i class="icon-white icon-chevron-right"></i>', array('class' => 'btn btn-success pull-right', 'name' => 'apply', 'value' => 'apply'));
	echo '<br/>';
	echo $this->PHForm->end();

?>
</div>