<?
	echo $this->PHForm->input('title');
	echo $this->PHForm->input('full_title');
	echo $this->PHForm->input('inn', array('class' => 'input-small'));
	echo $this->PHForm->input('kpp', array('class' => 'input-small'));
	echo $this->PHForm->input('bik', array('class' => 'input-small'));
	echo $this->PHForm->input('address', array('type' => 'textarea'));
    echo $this->PHForm->input('status', array(
		'label' => false, 
		'multiple' => 'checkbox', 
		'options' => array(
			'active' => __('Active'),
			'agent' => __('Agent'),
			'agent2' => __('Agent2'),
		),
		'class' => 'checkbox inline'
	));
?>
