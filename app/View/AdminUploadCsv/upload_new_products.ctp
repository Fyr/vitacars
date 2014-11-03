<div class="span8 offset2">
<?
	$title = __('Upload new products');
	echo $this->element('admin_title', compact('title'));
	
	echo $this->PHForm->create('UploadCsv', array(
		'url' => array(
			'controller' => 'AdminUploadCsv', 
			'action' => 'uploadNewProducts'
			), 
        'method' => 'POST',
        'enctype' => 'multipart/form-data'
	));
	echo $this->element('/AdminUploadCsv/admin_upload_csv_form');
    echo $this->PHForm->end();
?>
</div>