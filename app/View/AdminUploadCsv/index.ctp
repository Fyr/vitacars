<div class="span8 offset2">
<?php
echo $this->PHForm->create('UploadCsv', array('url' => array(
                                                        'controller' => 'AdminUploadCsv', 
                                                        'action' => 'upload'
                                                        ), 
                                                'method' => 'POST',
                                                'enctype' => 'multipart/form-data'
));
	$aTabs = array(
		'Import CSV file' => $this->element('/AdminUploadCsv/admin_upload_csv_form')
	);
	echo $this->element('admin_tabs', compact('aTabs'));
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
    echo $this->PHForm->end();
?>
</div>