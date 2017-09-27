<?
	echo $this->PHForm->input('title', array(
		'placeholder' => __('New subject...'),
		'label' => array('class' => 'control-label', 'text' => 'Тема')
	));
	echo $this->PHForm->input('body', array(
		'type' => 'textarea',
		'placeholder' => __('Message body...'),
		'label' => array('class' => 'control-label', 'text' => 'Текст')
	));
?>
