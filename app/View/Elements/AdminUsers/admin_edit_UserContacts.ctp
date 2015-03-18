<?
	echo $this->PHForm->input('legal_title');
	echo $this->PHForm->input('unp', array('class' => 'input-medium'));
    echo $this->PHForm->input('phone', array('class' => 'input-medium', 'required' => false));
    echo $this->PHForm->input('address');
    echo $this->PHForm->input('discount', array(
    	'class' => 'input-medium', 'required' => false
    ));
