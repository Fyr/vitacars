<?
	echo $this->PHForm->input('username', array('class' => 'input-medium'));
    echo $this->PHForm->input('password', array('class' => 'input-medium', 'required' => false));
    echo $this->PHForm->input('password_confirm', array('class' => 'input-medium', 'type' => 'password', 'required' => false));
    echo $this->PHForm->input('status', array(
		'label' => false, 
		'multiple' => 'checkbox', 
		'options' => array(
			'active' => __('Active'), 
			'load_counters' => __('Load counters (CSV)'), 
		), 
		'class' => 'checkbox inline'
	));
    