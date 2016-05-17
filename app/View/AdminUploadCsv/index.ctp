<div class="span8 offset2">
<?
	$title = __('Upload counters');
	echo $this->element('admin_title', compact('title'));
	if ($task) {
		echo $this->element('admin_content');
		echo $this->element('progress', $task);
		echo $this->element('admin_content_end');
	} else {
		echo $this->PHForm->create('UploadCsv', array(
			'url' => array(
				'controller' => 'AdminUploadCsv',
				'action' => 'index'
			),
			'method' => 'POST',
			'enctype' => 'multipart/form-data'
		));
		echo $this->element('admin_content');
		if (isset($avgTime) && $avgTime) {
			date_default_timezone_set('UTC');
			echo '<p class="text-center">Среднее время выполнения процесса: '.date('H:i:s', $avgTime).'</p>';
		}
		echo $this->PHForm->input(__('Select file'), array('class' => 'input-medium', 'type' => 'file', 'name' => 'csv_file', 'id' => 'csv_file'));
		echo $this->element('admin_content_end');
		echo $this->PHForm->submit(__('Upload').' <i class="icon-white icon-chevron-right"></i>', array('class' => 'btn btn-success pull-right', 'name' => 'apply', 'value' => 'apply'));
		echo '<br/>';
		echo $this->PHForm->end();
	}

?>
</div>