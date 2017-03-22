<?
	echo $this->PHForm->create('UploadCsv', array(
		'method' => 'POST',
		'enctype' => 'multipart/form-data'
	));
	echo $this->element('admin_content');
	if ($avgTime) {
		date_default_timezone_set('UTC');
		echo '<p class="text-center">Среднее время выполнения процесса: '.date('H:i:s', $avgTime).'</p>';
	}
	echo $this->PHForm->input(__('Select file'), array('class' => 'input-medium', 'type' => 'file', 'name' => 'csv_file', 'id' => 'csv_file'));
	$this->request->data('UploadCsv.status', array('recalc_formula')); // изначально всегда помечен
	echo $this->PHForm->input('status', array('label' => false, 'multiple' => 'checkbox', 'class' => 'checkbox inline',
		'options' => array('recalc_formula' => 'Пересчитывать формулы')
	));

	echo $this->element('admin_content_end');
	echo $this->PHForm->submit(__('Upload').' <i class="icon-white icon-chevron-right"></i>', array('class' => 'btn btn-success pull-right', 'name' => 'apply', 'value' => 'apply'));
	echo '<br/>';
	echo $this->PHForm->end();
?>
