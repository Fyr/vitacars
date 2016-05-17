<div class="span8 offset2">
<?
	$title = 'Пересчет формул';
	echo $this->element('admin_title', compact('title'));
	if ($task) {
		echo $this->element('admin_content');
		echo $this->element('progress', $task);
		echo $this->element('admin_content_end');
	} else {
		echo $this->PHForm->create('RecalcFormula', array(
			'url' => array(
				'controller' => 'AdminForms',
				'action' => 'recalcFormula'
			),
			'method' => 'POST',
		));
		echo $this->element('admin_content');
		if (isset($avgTime) && $avgTime) {
			date_default_timezone_set('UTC');
			echo '<p class="text-center">Среднее время выполнения процесса: '.date('H:i:s', $avgTime).'</p>';
		}
		echo '<p class="text-center">Пересчет формул выполняется на основе формул и констант описанных в разделе Продукция</p>';
		echo $this->element('admin_content_end');
		echo $this->PHForm->submit('Начать <i class="icon-white icon-chevron-right"></i>', array('class' => 'btn btn-success pull-right', 'name' => 'apply', 'value' => 'apply'));
		echo '<br/>';
		echo $this->PHForm->end();
	}

?>
</div>