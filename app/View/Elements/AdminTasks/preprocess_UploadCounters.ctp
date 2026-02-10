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
	// echo $this->PHForm->input('set_zero', array('type' => 'checkbox', 'label' => array('text' => 'Обнулять значения полей вне списка')));
	$this->request->data('UploadCsv.status', array('set_zero', 'sum_equal')); // изначально всегда помечены
	echo $this->PHForm->input('status', array('label' => false, 'multiple' => 'checkbox', 'class' => 'checkbox inline',
		'options' => array('set_zero' => 'Обнулять вне списка', 'sum_equal' => 'Суммировать по коду\номеру')
	));

	echo $this->element('admin_content_end');
	echo $this->PHForm->submit(__('Upload').' <i class="icon-white icon-chevron-right"></i>', array('class' => 'btn btn-success pull-right', 'name' => 'apply', 'value' => 'apply'));
	echo '<br/>';
	echo $this->PHForm->end();
?>
