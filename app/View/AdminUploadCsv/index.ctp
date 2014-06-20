<div class="span8 offset2">
<?
	$title = 'Загрузка CSV';
	echo $this->element('admin_title', compact('title'));
	echo $this->PHForm->create('UploadCsv', array(
		'url' => array(
			'controller' => 'AdminUploadCsv', 
			'action' => 'upload'
			), 
        'method' => 'POST',
        'enctype' => 'multipart/form-data'
	));
	echo $this->element('admin_content');
	echo $this->element('/AdminUploadCsv/admin_upload_csv_form');
	echo $this->element('admin_content_end');
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
    echo $this->PHForm->end();
?>
</div>